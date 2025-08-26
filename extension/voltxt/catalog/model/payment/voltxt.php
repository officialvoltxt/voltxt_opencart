<?php
/**
 * Voltxt Solana Payment Gateway - OpenCart 4.x Catalog Model
 */

namespace Opencart\Catalog\Model\Extension\Voltxt\Payment;

class Voltxt extends \Opencart\System\Engine\Model {
    
    public function getMethods(array $address): array {
        $this->load->language('extension/voltxt/payment/voltxt');
        
        // Check if payment gateway is enabled and configured
        if (!$this->config->get('payment_voltxt_status') || empty($this->config->get('payment_voltxt_api_key'))) {
            return array();
        }
        
        // Check geo zone restrictions
        if ($this->config->get('payment_voltxt_geo_zone_id')) {
            $query = $this->db->query("
                SELECT * FROM `" . DB_PREFIX . "zone_to_geo_zone` 
                WHERE geo_zone_id = '" . (int)$this->config->get('payment_voltxt_geo_zone_id') . "' 
                AND country_id = '" . (int)$address['country_id'] . "' 
                AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')
            ");
            
            if (!$query->num_rows) {
                return array();
            }
        }
        
        // Check supported currencies
        $supported_currencies = array('USD', 'EUR', 'GBP', 'AUD', 'CAD', 'JPY', 'NZD', 'CHF', 'SGD', 'HKD');
        if (!in_array($this->session->data['currency'], $supported_currencies)) {
            return array();
        }
        
        // Build option data (payment sub-methods)
        $option_data = array();
        $option_data['voltxt'] = array(
            'code' => 'voltxt.voltxt',
            'name' => $this->language->get('text_title')
        );
        
        $method_data = array(
            'code'       => 'voltxt',
            'name'       => $this->language->get('text_title'),
            'option'     => $option_data,
            'sort_order' => (int)$this->config->get('payment_voltxt_sort_order')
        );
        
        return $method_data;
    }
    
    /**
     * Legacy getMethod for backwards compatibility (singular)
     */
    public function getMethod(array $address, float $total): array {
        $methods = $this->getMethods($address);
        return $methods;
    }
    
    public function addSession(array $data): int {
        $this->db->query("
            INSERT INTO `" . DB_PREFIX . "voltxt_sessions` SET
                `order_id` = '" . (int)$data['order_id'] . "',
                `session_id` = '" . $this->db->escape($data['session_id']) . "',
                `amount` = '" . (float)$data['amount'] . "',
                `currency` = '" . $this->db->escape($data['currency']) . "',
                `network` = '" . $this->db->escape($data['network']) . "',
                `status` = '" . $this->db->escape($data['status']) . "',
                `payment_url` = '" . $this->db->escape($data['payment_url']) . "',
                `deposit_address` = '" . $this->db->escape($data['deposit_address'] ?? '') . "',
                `amount_sol` = '" . (float)($data['amount_sol'] ?? 0.0) . "',
                `expiry_date` = '" . $this->db->escape($data['expiry_date']) . "',
                `created_at` = NOW()
            ON DUPLICATE KEY UPDATE
                `session_id` = VALUES(`session_id`),
                `amount` = VALUES(`amount`),
                `currency` = VALUES(`currency`),
                `network` = VALUES(`network`),
                `status` = VALUES(`status`),
                `payment_url` = VALUES(`payment_url`),
                `deposit_address` = VALUES(`deposit_address`),
                `amount_sol` = VALUES(`amount_sol`),
                `expiry_date` = VALUES(`expiry_date`),
                `updated_at` = CURRENT_TIMESTAMP
        ");
        
        return $this->db->getLastId();
    }
    
    public function getActiveSession(int $order_id): array {
        $query = $this->db->query("
            SELECT * FROM `" . DB_PREFIX . "voltxt_sessions`
            WHERE `order_id` = '" . (int)$order_id . "'
            AND `status` IN ('pending', 'payment_detected')
            AND `expiry_date` > NOW()
            ORDER BY `created_at` DESC
            LIMIT 1
        ");
        
        return $query->num_rows ? $query->row : array();
    }
    
    public function updateSessionStatus(string $session_id, string $status): bool {
        $this->db->query("
            UPDATE `" . DB_PREFIX . "voltxt_sessions` SET
                `status` = '" . $this->db->escape($status) . "',
                `updated_at` = NOW()
            WHERE `session_id` = '" . $this->db->escape($session_id) . "'
        ");
        
        return $this->db->countAffected() > 0;
    }
    
    public function updateSessionPayment(string $session_id, string $payment_tx_id, float $amount_received): bool {
        $this->db->query("
            UPDATE `" . DB_PREFIX . "voltxt_sessions` SET
                `payment_tx_id` = '" . $this->db->escape($payment_tx_id) . "',
                `amount_received` = '" . (float)$amount_received . "',
                `status` = 'completed',
                `updated_at` = NOW()
            WHERE `session_id` = '" . $this->db->escape($session_id) . "'
        ");
        
        return $this->db->countAffected() > 0;
    }
    
    public function logWebhook(int $order_id, string $session_id, string $event_type, array $payload): int {
        $this->db->query("
            INSERT INTO `" . DB_PREFIX . "voltxt_webhooks` SET
                `order_id` = '" . (int)$order_id . "',
                `session_id` = '" . $this->db->escape($session_id) . "',
                `event_type` = '" . $this->db->escape($event_type) . "',
                `payload` = '" . $this->db->escape(json_encode($payload)) . "',
                `processed` = 1,
                `created_at` = NOW()
        ");
        
        return $this->db->getLastId();
    }
}