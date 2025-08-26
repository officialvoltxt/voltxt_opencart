<?php
/**
 * Voltxt Solana Payment Gateway - OpenCart 4.x Catalog Controller
 * Fixed to follow proper payment flow like Paytm
 */

namespace Opencart\Catalog\Controller\Extension\Voltxt\Payment;

class Voltxt extends \Opencart\System\Engine\Controller {
    
    public function index(): string {
        $this->load->language('extension/voltxt/payment/voltxt');
        
        $data['button_confirm'] = $this->language->get('button_confirm');
        $data['text_loading'] = $this->language->get('text_loading');
        $data['text_payment_info'] = $this->language->get('text_payment_info');
        $data['text_network'] = $this->language->get('text_network');
        $data['text_expires_in'] = $this->language->get('text_expires_in');
        $data['text_hours'] = $this->language->get('text_hours');
        $data['text_solana'] = $this->language->get('text_solana');
        $data['text_order_total'] = $this->language->get('text_order_total');
        $data['text_payment_method'] = $this->language->get('text_payment_method');
        $data['text_payment_instructions'] = $this->language->get('text_payment_instructions');
        $data['text_security_notice'] = $this->language->get('text_security_notice');
        $data['text_powered_by'] = $this->language->get('text_powered_by');
        $data['text_voltxt_gateway'] = $this->language->get('text_voltxt_gateway');
        $data['aria_payment_button'] = $this->language->get('aria_payment_button');
        $data['aria_loading'] = $this->language->get('aria_loading');
        $data['text_secure_payment'] = $this->language->get('text_secure_payment');
        $data['text_title'] = $this->language->get('text_title');
        
        $network = $this->config->get('payment_voltxt_network') ?: 'testnet';
        $expiry_hours = $this->config->get('payment_voltxt_expiry_hours') ?: 24;
        
        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id'] ?? 0);
        
        $data['network'] = ucfirst($network);
        $data['expiry_hours'] = (int)$expiry_hours;
        
        if ($order_info) {
            $data['order_total'] = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value']);
        } else {
            $data['order_total'] = $this->currency->format(0, $this->session->data['currency'] ?? $this->config->get('config_currency'), 1);
        }

        // NEW: Add URL for AJAX payment session creation
        $data['create_session_url'] = $this->url->link('extension/voltxt/payment/voltxt.createSession', '', true);
        
        return $this->load->view('extension/voltxt/payment/voltxt', $data);
    }
    
    /**
     * NEW: AJAX endpoint to create payment session and return payment URL
     */
    public function createSession(): void {
        $this->load->language('extension/voltxt/payment/voltxt');
        
        $json = array();
        
        // Only validate order_id, not payment method (since this is called from the payment method page)
        if (!isset($this->session->data['order_id'])) {
            $json['success'] = false;
            $json['error'] = $this->language->get('error_invalid_order');
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }

        $this->load->model('extension/voltxt/payment/voltxt');
        $this->load->model('checkout/order');
        
        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
        
        if (!$order_info) {
            $json['success'] = false;
            $json['error'] = $this->language->get('error_invalid_order');
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }
        
        // Debug logging
        if ($this->config->get('payment_voltxt_debug')) {
            $this->log->write('Voltxt createSession: Starting payment session creation for order ' . $order_info['order_id']);
            $this->log->write('Voltxt createSession: Order total: ' . $order_info['total'] . ' ' . $order_info['currency_code']);
        }
        
        // Check for existing session
        $existing_session = $this->model_extension_voltxt_payment_voltxt->getActiveSession($this->session->data['order_id']);
        
        if ($existing_session && $this->isSessionValid($existing_session, $order_info)) {
            $json['success'] = true;
            $json['payment_url'] = $existing_session['payment_url'];
            $json['session_id'] = $existing_session['session_id'];
            $json['message'] = 'Using existing payment session';
        } else {
            // Create new session
            $result = $this->createPaymentSession($order_info);
            
            // Debug logging
            if ($this->config->get('payment_voltxt_debug')) {
                $this->log->write('Voltxt createSession: Payment session result: ' . json_encode($result));
            }
            
            if ($result['success']) {
                $json['success'] = true;
                $json['payment_url'] = $result['payment_url'];
                $json['session_id'] = $result['session_id'];
                $json['message'] = 'Payment session created successfully';
            } else {
                $json['success'] = false;
                $json['error'] = $result['error'];
            }
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    public function confirm(): void {
        $this->load->language('extension/voltxt/payment/voltxt');
        
        if (!isset($this->session->data['payment_method']) || $this->session->data['payment_method']['code'] != 'voltxt') {
            $this->session->data['error'] = $this->language->get('error_invalid_payment_method');
            $this->response->redirect($this->url->link('checkout/checkout', '', true));
            return;
        }

        if (!isset($this->session->data['order_id'])) {
            $this->session->data['error'] = $this->language->get('error_invalid_order');
            $this->response->redirect($this->url->link('checkout/checkout', '', true));
            return;
        }

        $this->load->model('extension/voltxt/payment/voltxt');
        $this->load->model('checkout/order');
        
        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
        
        if (!$order_info) {
            $this->session->data['error'] = $this->language->get('error_invalid_order');
            $this->response->redirect($this->url->link('checkout/checkout', '', true));
            return;
        }
        
        // Check for existing session
        $existing_session = $this->model_extension_voltxt_payment_voltxt->getActiveSession($this->session->data['order_id']);
        
        if ($existing_session && $this->isSessionValid($existing_session, $order_info)) {
            $this->response->redirect($existing_session['payment_url']);
            return;
        }
        
        // Create new session
        $result = $this->createPaymentSession($order_info);
        
        if ($result['success']) {
            $this->response->redirect($result['payment_url']);
        } else {
            $this->session->data['error'] = $result['error'];
            $this->response->redirect($this->url->link('checkout/checkout', '', true));
        }
    }
    
    public function webhook(): void {
        $raw_payload = file_get_contents('php://input');
        
        if (empty($raw_payload)) {
            http_response_code(400);
            echo json_encode(array('success' => false, 'error' => 'Empty payload'));
            return;
        }
        
        $payload = json_decode($raw_payload, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            http_response_code(400);
            echo json_encode(array('success' => false, 'error' => 'Invalid JSON'));
            return;
        }
        
        try {
            $this->processWebhook($payload);
            echo json_encode(array('success' => true));
        } catch (\Exception $e) {
            if ($this->config->get('payment_voltxt_debug')) {
                $this->log->write('Webhook error: ' . $e->getMessage());
            }
            http_response_code(500);
            echo json_encode(array('success' => false, 'error' => $e->getMessage()));
        }
    }
    
    private function processWebhook(array $payload): void {
        $event_type = $payload['event_type'] ?? '';
        $session_id = $payload['session_id'] ?? $payload['external_invoice_id'] ?? '';
        $external_payment_id = $payload['external_invoice_id'] ?? $payload['external_payment_id'] ?? '';
        $status = $payload['status'] ?? '';
        $payment_tx_id = $payload['payment_tx_id'] ?? '';
        $auto_process_tx_id = $payload['auto_process_tx_id'] ?? '';
        $amount_received_crypto = $payload['amount_received_crypto'] ?? null;
        $invoice_number = $payload['invoice_number'] ?? '';
        
        // Debug logging
        if ($this->config->get('payment_voltxt_debug')) {
            $this->log->write('Voltxt Webhook: Processing event: ' . $event_type);
            $this->log->write('Voltxt Webhook: Session ID: ' . $session_id);
            $this->log->write('Voltxt Webhook: External Payment ID: ' . $external_payment_id);
            $this->log->write('Voltxt Webhook: Payment TX: ' . $payment_tx_id);
            $this->log->write('Voltxt Webhook: Auto Process TX: ' . $auto_process_tx_id);
        }
        
        $order_id = null;
        
        // Try to extract order ID from external_payment_id (primary method)
        if (!empty($external_payment_id) && preg_match('/opencart_(\d+)_/', $external_payment_id, $matches)) {
            $order_id = (int)$matches[1];
        }
        
        // Fallback: try metadata
        if (!$order_id && isset($payload['metadata']['order_id'])) {
            $order_id = (int)$payload['metadata']['order_id'];
        }
        
        if (!$order_id) {
            throw new \Exception('Could not extract order ID from webhook. External Payment ID: ' . $external_payment_id);
        }
        
        $this->load->model('checkout/order');
        $this->load->model('extension/voltxt/payment/voltxt');
        
        $order_info = $this->model_checkout_order->getOrder($order_id);
        if (!$order_info) {
            throw new \Exception('Order not found: ' . $order_id);
        }
        
        // CRITICAL: Check if order is already processed (following Paytm pattern)
        $approved_status_id = (int)($this->config->get('payment_voltxt_order_status_id') ?: 2);
        $failed_status_id = (int)($this->config->get('payment_voltxt_failed_status_id') ?: 10);
        $cancelled_status_id = (int)($this->config->get('payment_voltxt_cancelled_status_id') ?: 7);
        $current_status = (int)$order_info['order_status_id'];
        
        // Skip processing if order is already in final status
        if (in_array($current_status, [$approved_status_id, $failed_status_id, $cancelled_status_id])) {
            if ($this->config->get('payment_voltxt_debug')) {
                $this->log->write("Voltxt Webhook: Order {$order_id} already processed (status: {$current_status}), skipping");
            }
            
            // Always log the webhook even if skipped
            $this->model_extension_voltxt_payment_voltxt->logWebhook($order_id, $session_id, $event_type, $payload);
            return;
        }
        
        // Debug logging
        if ($this->config->get('payment_voltxt_debug')) {
            $this->log->write('Voltxt Webhook: Found order ' . $order_id . ' with status: ' . $order_info['order_status_id']);
        }

        // Handle different event types
        switch ($event_type) {
            case 'payment_completed':
            case 'payment_received':
                // Use auto_process_tx_id if available, otherwise payment_tx_id
                $final_tx_id = !empty($auto_process_tx_id) ? $auto_process_tx_id : $payment_tx_id;
                
                if (empty($final_tx_id)) {
                    if ($this->config->get('payment_voltxt_debug')) {
                        $this->log->write('Voltxt Webhook: Missing transaction ID for completed payment');
                    }
                    throw new \Exception('Transaction ID required for completed payment');
                }
                
                $this->handlePaymentCompleted($order_id, $final_tx_id, $amount_received_crypto, $session_id, $order_info);
                
                // Update session with both payment and auto-process TX IDs if available
                if (!empty($payment_tx_id)) {
                    $this->model_extension_voltxt_payment_voltxt->updateSessionPayment($session_id, $payment_tx_id, (float)$amount_received_crypto);
                }
                break;
                
            case 'payment_cancelled':
                $this->handlePaymentCancelled($order_id, $session_id, $order_info);
                $this->model_extension_voltxt_payment_voltxt->updateSessionStatus($session_id, 'cancelled');
                break;
                
            case 'invoice_expired':
            case 'payment_expired':
                $this->handlePaymentExpired($order_id, $session_id, $order_info);
                $this->model_extension_voltxt_payment_voltxt->updateSessionStatus($session_id, 'expired');
                break;
                
            case 'payment_detected':
                $this->handlePaymentDetected($order_id, $session_id, $order_info);
                $this->model_extension_voltxt_payment_voltxt->updateSessionStatus($session_id, 'payment_detected');
                break;
                
            case 'underpaid':
                $this->handlePaymentUnderpaid($order_id, $session_id, $order_info, $amount_received_crypto);
                $this->model_extension_voltxt_payment_voltxt->updateSessionStatus($session_id, 'underpaid');
                break;
                
            default:
                if ($this->config->get('payment_voltxt_debug')) {
                    $this->log->write('Voltxt Webhook: Unhandled event type: ' . $event_type);
                }
                break;
        }
        
        // Always log the webhook
        $this->model_extension_voltxt_payment_voltxt->logWebhook($order_id, $session_id, $event_type, $payload);
        
        if ($this->config->get('payment_voltxt_debug')) {
            $this->log->write('Voltxt Webhook: Successfully processed ' . $event_type . ' for order ' . $order_id);
        }
    }
    
    /**
     * FIXED: Only add order history on actual payment events (following Paytm pattern)
     */
    private function handlePaymentCompleted(int $order_id, string $payment_tx_id, ?float $amount_received, string $session_id, array $order_info): void {
        $this->load->model('checkout/order');
        
        $completed_status = (int)($this->config->get('payment_voltxt_order_status_id') ?: 2);
        
        $comment = sprintf(
            $this->language->get('text_payment_completed'),
            $payment_tx_id,
            (float)$amount_received . ' SOL'
        );
        
        // CRITICAL: This is the FIRST time we add order history - only on successful payment
        $this->model_checkout_order->addHistory($order_id, $completed_status, $comment, true);
        
        if ($this->config->get('payment_voltxt_debug')) {
            $this->log->write("Voltxt Webhook: Order {$order_id} status updated to {$completed_status} with TX: {$payment_tx_id}");
        }
    }
    
    private function handlePaymentCancelled(int $order_id, string $session_id, array $order_info): void {
        $this->load->model('checkout/order');
        
        $cancelled_status = (int)($this->config->get('payment_voltxt_cancelled_status_id') ?: 7);
        $comment = sprintf($this->language->get('text_payment_cancelled'), $session_id);
        
        // Add order history only now - when actually cancelled
        $this->model_checkout_order->addHistory($order_id, $cancelled_status, $comment, true);
        
        if ($this->config->get('payment_voltxt_debug')) {
            $this->log->write("Voltxt Webhook: Order {$order_id} status updated to cancelled ({$cancelled_status})");
        }
    }
    
    private function handlePaymentExpired(int $order_id, string $session_id, array $order_info): void {
        $this->load->model('checkout/order');
        
        $failed_status = (int)($this->config->get('payment_voltxt_failed_status_id') ?: 10);
        $comment = sprintf($this->language->get('text_payment_expired'), $session_id);
        
        // Add order history only now - when actually expired
        $this->model_checkout_order->addHistory($order_id, $failed_status, $comment, true);
        
        if ($this->config->get('payment_voltxt_debug')) {
            $this->log->write("Voltxt Webhook: Order {$order_id} status updated to failed ({$failed_status})");
        }
    }
    
    private function handlePaymentDetected(int $order_id, string $session_id, array $order_info): void {
        $this->load->model('checkout/order');
        
        $pending_status = (int)($this->config->get('payment_voltxt_pending_status_id') ?: 1);
        $comment = sprintf($this->language->get('text_payment_detected'), $session_id);
        
        // This is the first order history entry - when payment is actually detected
        $this->model_checkout_order->addHistory($order_id, $pending_status, $comment, false);
        
        if ($this->config->get('payment_voltxt_debug')) {
            $this->log->write("Voltxt Webhook: Order {$order_id} status updated to pending ({$pending_status}) - payment detected");
        }
    }

    private function handlePaymentUnderpaid(int $order_id, string $session_id, array $order_info, ?float $amount_received): void {
        $this->load->model('checkout/order');
        
        $failed_status = (int)($this->config->get('payment_voltxt_failed_status_id') ?: 10);
        $comment = sprintf(
            $this->language->get('text_payment_underpaid'),
            $order_info['total'],
            (float)$amount_received . ' SOL'
        );
        
        $this->model_checkout_order->addHistory($order_id, $failed_status, $comment, true);
    }
    
    public function callback(): void {
        $this->load->language('extension/voltxt/payment/voltxt');
        
        $order_id = (int)($this->request->get['order_id'] ?? 0);
        $payment_status = $this->request->get['voltxt_payment_status'] ?? '';
        $session_id = $this->request->get['voltxt_session_id'] ?? '';
        $payment_tx_id = $this->request->get['voltxt_payment_tx'] ?? '';
        $cancelled = isset($this->request->get['voltxt_cancelled']);
        
        // Debug logging
        if ($this->config->get('payment_voltxt_debug')) {
            $this->log->write("Voltxt Callback: Order ID: {$order_id}, Status: {$payment_status}, Session: {$session_id}, TX: {$payment_tx_id}, Cancelled: " . ($cancelled ? 'yes' : 'no'));
        }
        
        if (!$order_id) {
            if ($this->config->get('payment_voltxt_debug')) {
                $this->log->write('Voltxt Callback: No order ID provided, redirecting to home');
            }
            $this->response->redirect($this->url->link('common/home', '', true));
            return;
        }
        
        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($order_id);
        
        if (!$order_info) {
            if ($this->config->get('payment_voltxt_debug')) {
                $this->log->write("Voltxt Callback: Order {$order_id} not found, redirecting to home");
            }
            $this->response->redirect($this->url->link('common/home', '', true));
            return;
        }
        
        // Handle cancellation from URL parameter
        if ($cancelled) {
            $payment_status = 'cancelled';
        }
        
        // FOLLOWING PAYTM PATTERN: Check if order is already processed
        $approved_status_id = (int)($this->config->get('payment_voltxt_order_status_id') ?: 2);
        $failed_status_id = (int)($this->config->get('payment_voltxt_failed_status_id') ?: 10);
        $cancelled_status_id = (int)($this->config->get('payment_voltxt_cancelled_status_id') ?: 7);
        $current_status = (int)$order_info['order_status_id'];
        
        // If order is already processed, redirect accordingly
        if ($current_status == $approved_status_id) {
            $this->response->redirect($this->url->link('checkout/success', '', true));
            return;
        } else if (in_array($current_status, [$failed_status_id, $cancelled_status_id])) {
            $this->response->redirect($this->url->link('checkout/failure', '', true));
            return;
        }
        
        // Handle different payment statuses
        switch ($payment_status) {
            case 'completed':
                if ($this->config->get('payment_voltxt_debug')) {
                    $this->log->write("Voltxt Callback: Payment completed for order {$order_id}, redirecting to success");
                }
                $this->response->redirect($this->url->link('checkout/success', '', true));
                break;
                
            case 'cancelled':
                if ($this->config->get('payment_voltxt_debug')) {
                    $this->log->write("Voltxt Callback: Payment cancelled for order {$order_id}, redirecting to checkout with error");
                }
                $this->session->data['error'] = $this->language->get('error_payment_cancelled');
                $this->response->redirect($this->url->link('checkout/checkout', '', true));
                break;
                
            case 'expired':
                if ($this->config->get('payment_voltxt_debug')) {
                    $this->log->write("Voltxt Callback: Payment expired for order {$order_id}, redirecting to checkout with error");
                }
                $this->session->data['error'] = 'Payment session has expired. Please try again.';
                $this->response->redirect($this->url->link('checkout/checkout', '', true));
                break;
                
            case 'pending':
            case 'payment_detected':
                if ($this->config->get('payment_voltxt_debug')) {
                    $this->log->write("Voltxt Callback: Payment {$payment_status} for order {$order_id}, redirecting to success to show status");
                }
                $this->response->redirect($this->url->link('checkout/success', '', true));
                break;
                
            default:
                if ($this->config->get('payment_voltxt_debug')) {
                    $this->log->write("Voltxt Callback: Unknown payment status '{$payment_status}' for order {$order_id}, redirecting to checkout");
                }
                $this->session->data['error'] = 'Payment status unknown. Please contact support if payment was completed.';
                $this->response->redirect($this->url->link('checkout/checkout', '', true));
                break;
        }
    }
    
    /**
     * FIXED: Don't add any order history here - just create the session
     */
    private function createPaymentSession(array $order_info): array {
        try {
            require_once(DIR_EXTENSION . 'voltxt/system/library/voltxt_api_client.php');
            
            $api_key = $this->config->get('payment_voltxt_api_key');
            $network = $this->config->get('payment_voltxt_network') ?: 'testnet';
            $expiry_hours = (int)($this->config->get('payment_voltxt_expiry_hours') ?: 24);
            
            if (empty($api_key)) {
                throw new \Exception($this->language->get('error_api_key_missing'));
            }
            
            $api_client = new \VoltxtApiClient($api_key, $network);
            
            // Get base URL - check if HTTPS is enabled
            if (defined('HTTPS_CATALOG') && defined('HTTP_CATALOG')) {
                $base_url = (isset($this->request->server['HTTPS']) && $this->request->server['HTTPS']) ? 
                    HTTPS_CATALOG : HTTP_CATALOG;
            } else {
                // Fallback method to construct base URL
                $protocol = (isset($this->request->server['HTTPS']) && $this->request->server['HTTPS']) ? 'https://' : 'http://';
                $host = $this->request->server['HTTP_HOST'] ?? 'localhost';
                $base_url = $protocol . $host . '/';
            }
            
            // Debug logging
            if ($this->config->get('payment_voltxt_debug')) {
                $this->log->write("Voltxt: Using base URL: {$base_url}");
            }
            
            $payment_data = array(
                'external_payment_id' => 'opencart_' . $order_info['order_id'] . '_' . time(),
                'amount_type' => 'fiat',
                'amount' => (float)$order_info['total'],
                'fiat_currency' => $order_info['currency_code'],
                'expiry_hours' => $expiry_hours,
                'description' => sprintf($this->language->get('text_order_description'), $order_info['order_id']),
                'customer_email' => $order_info['email'],
                'customer_name' => trim($order_info['firstname'] . ' ' . $order_info['lastname']),
                'callback_url' => $base_url . 'index.php?route=extension/voltxt/payment/voltxt.webhook',
                'success_url' => $base_url . 'index.php?route=extension/voltxt/payment/voltxt.callback&order_id=' . $order_info['order_id'] . '&voltxt_session_id=[session_id]&voltxt_payment_status=[payment_status]&voltxt_payment_tx=[payment_tx_id]',
                'cancel_url' => $base_url . 'index.php?route=extension/voltxt/payment/voltxt.callback&order_id=' . $order_info['order_id'] . '&voltxt_cancelled=1&voltxt_session_id=[session_id]',
                'metadata' => array(
                    'order_id' => $order_info['order_id'],
                    'customer_id' => $order_info['customer_id'],
                    'store_id' => $this->config->get('config_store_id'),
                    'platform' => 'opencart',
                    'version' => VERSION
                )
            );
            
            $response = $api_client->initiateDynamicPayment($payment_data);
            
            if ($response['success']) {
                $session_data = $response['data'];
                
                // Only save session data - NO order history
                $this->model_extension_voltxt_payment_voltxt->addSession(array(
                    'order_id' => $order_info['order_id'],
                    'session_id' => $session_data['session_id'],
                    'amount' => $order_info['total'],
                    'currency' => $order_info['currency_code'],
                    'network' => $network,
                    'status' => 'pending',
                    'payment_url' => $session_data['payment_url'],
                    'deposit_address' => $session_data['deposit_address'] ?? null,
                    'amount_sol' => $session_data['amount_sol'] ?? null,
                    'expiry_date' => $session_data['expiry_date']
                ));
                
                // REMOVED: No order history added here - following Paytm pattern
                if ($this->config->get('payment_voltxt_debug')) {
                    $this->log->write("Voltxt: Payment session created for order {$order_info['order_id']} without adding order history");
                }
                
                return array(
                    'success' => true,
                    'payment_url' => $session_data['payment_url'],
                    'session_id' => $session_data['session_id']
                );
                
            } else {
                return array(
                    'success' => false,
                    'error' => $response['error'] ?: $this->language->get('error_payment_failed')
                );
            }
            
        } catch (\Exception $e) {
            if ($this->config->get('payment_voltxt_debug')) {
                $this->log->write('Voltxt payment error: ' . $e->getMessage());
            }
            
            return array(
                'success' => false,
                'error' => $this->language->get('error_payment_failed')
            );
        }
    }
    
    private function isSessionValid(array $session, array $order_info): bool {
        if ($session['status'] !== 'pending' || (isset($session['expiry_date']) && strtotime($session['expiry_date']) < time())) {
            return false;
        }
        
        $amount_match = abs((float)$session['amount'] - (float)$order_info['total']) < 0.01;
        $currency_match = $session['currency'] === $order_info['currency_code'];
        
        return $amount_match && $currency_match;
    }
}