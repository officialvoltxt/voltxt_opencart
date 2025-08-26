<?php
/**
 * Voltxt Solana Payment Gateway - OpenCart 4.x Admin Model
 */

namespace Opencart\Admin\Model\Extension\Voltxt\Payment;

class Voltxt extends \Opencart\System\Engine\Model {
    
    public function install(): void {
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "voltxt_sessions` (
                `voltxt_session_id` int(11) NOT NULL AUTO_INCREMENT,
                `order_id` int(11) NOT NULL,
                `session_id` varchar(255) NOT NULL,
                `amount` decimal(15,8) NOT NULL DEFAULT '0.00000000',
                `currency` varchar(3) NOT NULL,
                `network` varchar(10) NOT NULL DEFAULT 'testnet',
                `status` varchar(20) NOT NULL DEFAULT 'pending',
                `payment_url` text,
                `deposit_address` varchar(255) DEFAULT NULL,
                `amount_sol` decimal(15,9) DEFAULT NULL,
                `amount_received` decimal(15,9) DEFAULT NULL,
                `payment_tx_id` varchar(255) DEFAULT NULL,
                `expiry_date` datetime DEFAULT NULL,
                `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`voltxt_session_id`),
                UNIQUE KEY `order_id` (`order_id`),
                UNIQUE KEY `session_id` (`session_id`),
                KEY `status` (`status`),
                KEY `expiry_date` (`expiry_date`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
        ");
        
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "voltxt_webhooks` (
                `webhook_id` int(11) NOT NULL AUTO_INCREMENT,
                `order_id` int(11) NOT NULL,
                `session_id` varchar(255) DEFAULT NULL,
                `event_type` varchar(50) NOT NULL,
                `payload` text NOT NULL,
                `processed` tinyint(1) NOT NULL DEFAULT '0',
                `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`webhook_id`),
                KEY `order_id` (`order_id`),
                KEY `session_id` (`session_id`),
                KEY `event_type` (`event_type`),
                KEY `processed` (`processed`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
        ");
    }
    
    public function uninstall(): void {
        // Optional: Keep data for audit trail
        // $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "voltxt_sessions`");
        // $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "voltxt_webhooks`");
    }
}