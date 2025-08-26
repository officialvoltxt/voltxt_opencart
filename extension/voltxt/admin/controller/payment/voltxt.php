<?php
/**
 * Voltxt Solana Payment Gateway - OpenCart 4.x Admin Controller
 */

namespace Opencart\Admin\Controller\Extension\Voltxt\Payment;

class Voltxt extends \Opencart\System\Engine\Controller {
    
    private array $error = array();
    
    public function index(): void {
        $this->load->language('extension/voltxt/payment/voltxt');
        
        $this->document->setTitle($this->language->get('heading_title'));
        
        $this->load->model('setting/setting');
        
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('payment_voltxt', $this->request->post);
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
        }
        
        // Breadcrumbs
        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/voltxt/payment/voltxt', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        // Action URLs
        $data['save'] = $this->url->link('extension/voltxt/payment/voltxt.save', 'user_token=' . $this->session->data['user_token'], true);
        $data['back'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);
        $data['test_connection_url'] = $this->url->link('extension/voltxt/payment/voltxt.testConnection', 'user_token=' . $this->session->data['user_token'], true);
        
        // Configuration values
        $data['payment_voltxt_api_key'] = $this->config->get('payment_voltxt_api_key');
        $data['payment_voltxt_network'] = $this->config->get('payment_voltxt_network') ?: 'testnet';
        $data['payment_voltxt_expiry_hours'] = $this->config->get('payment_voltxt_expiry_hours') ?: '24';
        
        // Order Statuses
        $this->load->model('localisation/order_status');
        $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();
        
        $data['payment_voltxt_order_status_id'] = $this->config->get('payment_voltxt_order_status_id') ?: 2;
        $data['payment_voltxt_pending_status_id'] = $this->config->get('payment_voltxt_pending_status_id') ?: 1;
        $data['payment_voltxt_cancelled_status_id'] = $this->config->get('payment_voltxt_cancelled_status_id') ?: 7;
        $data['payment_voltxt_failed_status_id'] = $this->config->get('payment_voltxt_failed_status_id') ?: 10;
        
        // Geo Zones
        $this->load->model('localisation/geo_zone');
        $data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();
        $data['payment_voltxt_geo_zone_id'] = $this->config->get('payment_voltxt_geo_zone_id') ?: 0;
        
        $data['payment_voltxt_status'] = $this->config->get('payment_voltxt_status');
        $data['payment_voltxt_sort_order'] = $this->config->get('payment_voltxt_sort_order') ?: 0;
        $data['payment_voltxt_debug'] = $this->config->get('payment_voltxt_debug');
        
        // Error messages
        $data['error_warning'] = $this->error['warning'] ?? '';
        $data['error_api_key'] = $this->error['api_key'] ?? '';
        $data['error_expiry_hours'] = $this->error['expiry_hours'] ?? '';
        
        // Language strings
        $data['heading_title'] = $this->language->get('heading_title');
        $data['text_edit'] = $this->language->get('text_edit');
        $data['text_enabled'] = $this->language->get('text_enabled');
        $data['text_disabled'] = $this->language->get('text_disabled');
        $data['text_all_zones'] = $this->language->get('text_all_zones');
        $data['text_mainnet'] = $this->language->get('text_mainnet');
        $data['text_testnet'] = $this->language->get('text_testnet');
        $data['text_yes'] = $this->language->get('text_yes');
        $data['text_no'] = $this->language->get('text_no');
        $data['entry_api_key'] = $this->language->get('entry_api_key');
        $data['entry_network'] = $this->language->get('entry_network');
        $data['entry_expiry_hours'] = $this->language->get('entry_expiry_hours');
        $data['entry_order_status'] = $this->language->get('entry_order_status');
        $data['entry_pending_status'] = $this->language->get('entry_pending_status');
        $data['entry_cancelled_status'] = $this->language->get('entry_cancelled_status');
        $data['entry_failed_status'] = $this->language->get('entry_failed_status');
        $data['entry_geo_zone'] = $this->language->get('entry_geo_zone');
        $data['entry_status'] = $this->language->get('entry_status');
        $data['entry_sort_order'] = $this->language->get('entry_sort_order');
        $data['entry_debug'] = $this->language->get('entry_debug');
        $data['entry_test_connection'] = $this->language->get('entry_test_connection');
        $data['help_api_key'] = $this->language->get('help_api_key');
        $data['help_network'] = $this->language->get('help_network');
        $data['help_expiry_hours'] = $this->language->get('help_expiry_hours');
        $data['help_order_status'] = $this->language->get('help_order_status');
        $data['help_debug'] = $this->language->get('help_debug');
        $data['help_test_connection'] = $this->language->get('help_test_connection');
        $data['button_save'] = $this->language->get('button_save');
        $data['button_cancel'] = $this->language->get('button_cancel');
        $data['button_test_connection'] = $this->language->get('button_test_connection');
        $data['text_webhook_url'] = $this->language->get('text_webhook_url');
        $data['help_webhook_url'] = $this->language->get('help_webhook_url');
        $data['tab_general'] = $this->language->get('tab_general');
        $data['tab_order_status'] = $this->language->get('tab_order_status');
        $data['tab_advanced'] = $this->language->get('tab_advanced');
        $data['text_voltxt_version'] = $this->language->get('text_voltxt_version');
        $data['text_testing_connection'] = $this->language->get('text_testing_connection');
        $data['text_wallet_configured'] = $this->language->get('text_wallet_configured');
        $data['text_wallet_not_configured'] = $this->language->get('text_wallet_not_configured');
        $data['warning_no_wallet'] = $this->language->get('warning_no_wallet');
        $data['text_store_name'] = $this->language->get('text_store_name');
        $data['text_store_network'] = $this->language->get('text_store_network');
        $data['text_wallet_status'] = $this->language->get('text_wallet_status');
        $data['text_connection_success'] = $this->language->get('text_connection_success');
        $data['error_connection_failed'] = $this->language->get('error_connection_failed');
        
        // Webhook URL
        $data['webhook_url'] = HTTP_CATALOG . 'index.php?route=extension/voltxt/payment/voltxt.webhook';
        $data['user_token'] = $this->session->data['user_token'];
        
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('extension/voltxt/payment/voltxt', $data));
    }
    
    public function save(): void {
        $this->load->language('extension/voltxt/payment/voltxt');
        
        $json = array();
        
        if (!$this->user->hasPermission('modify', 'extension/voltxt/payment/voltxt')) {
            $json['error']['warning'] = $this->language->get('error_permission');
        }
        
        if (empty($this->request->post['payment_voltxt_api_key'])) {
            $json['error']['api_key'] = $this->language->get('error_api_key_required');
        } elseif (strlen(trim($this->request->post['payment_voltxt_api_key'])) !== 32) {
            $json['error']['api_key'] = $this->language->get('error_api_key_length');
        }
        
        $expiry_hours = (int)($this->request->post['payment_voltxt_expiry_hours'] ?? 0);
        if ($expiry_hours < 1 || $expiry_hours > 168) {
            $json['error']['expiry_hours'] = $this->language->get('error_expiry_hours');
        }
        
        if (!isset($json['error'])) {
            $this->load->model('setting/setting');
            
            $this->request->post['payment_voltxt_status'] = isset($this->request->post['payment_voltxt_status']) ? 1 : 0;
            $this->request->post['payment_voltxt_debug'] = isset($this->request->post['payment_voltxt_debug']) ? 1 : 0;
            
            $this->model_setting_setting->editSetting('payment_voltxt', $this->request->post);
            
            $json['success'] = $this->language->get('text_success');
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function testConnection(): void {
        $this->load->language('extension/voltxt/payment/voltxt');
        
        $json = array();
        
        $api_key = isset($this->request->post['api_key']) ? trim($this->request->post['api_key']) : '';
        $network = isset($this->request->post['network']) ? trim($this->request->post['network']) : 'testnet';
        
        if (empty($api_key)) {
            $json['success'] = false;
            $json['error'] = $this->language->get('error_api_key_required');
        } elseif (strlen($api_key) !== 32) {
            $json['success'] = false;
            $json['error'] = $this->language->get('error_api_key_length');
        } elseif (!in_array($network, array('testnet', 'mainnet'))) {
            $json['success'] = false;
            $json['error'] = $this->language->get('error_invalid_network');
        } else {
            try {
                require_once(DIR_EXTENSION . 'voltxt/system/library/voltxt_api_client.php');
                
                $api_client = new \VoltxtApiClient($api_key, $network);
                
                $test_data = array(
                    'store_name' => $this->config->get('config_name') ?: 'OpenCart Store',
                    'platform' => 'opencart',
                    'version' => VERSION
                );
                
                $response = $api_client->testConnection($test_data);
                
                if ($response['success']) {
                    $store = $response['data']['store'] ?? [];
                    
                    $json['success'] = true;
                    $json['store_name'] = $store['name'] ?? 'N/A';
                    $json['network'] = $store['network'] ?? 'N/A';
                    $json['has_wallet'] = $store['has_destination_wallet'] ?? false;
                    $json['message'] = $this->language->get('text_connection_success');
                } else {
                    $json['success'] = false;
                    $json['error'] = $response['error'] ?? $this->language->get('error_connection_failed');
                }
                
            } catch (\Exception $e) {
                $json['success'] = false;
                $json['error'] = $this->language->get('error_connection_failed') . ': ' . $e->getMessage();
                
                if ($this->config->get('payment_voltxt_debug')) {
                    $this->log->write('Voltxt API Test Error: ' . $e->getMessage());
                }
            }
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    protected function validate(): bool {
        if (!$this->user->hasPermission('modify', 'extension/voltxt/payment/voltxt')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (empty($this->request->post['payment_voltxt_api_key'])) {
            $this->error['api_key'] = $this->language->get('error_api_key_required');
        } elseif (strlen(trim($this->request->post['payment_voltxt_api_key'])) !== 32) {
            $this->error['api_key'] = $this->language->get('error_api_key_length');
        }

        $expiry_hours = (int)($this->request->post['payment_voltxt_expiry_hours'] ?? 0);
        if ($expiry_hours < 1 || $expiry_hours > 168) {
            $this->error['expiry_hours'] = $this->language->get('error_expiry_hours');
        }

        if ($this->error && !isset($this->error['warning'])) {
            $this->error['warning'] = $this->language->get('error_warning');
        }

        return !$this->error;
    }
    
    public function install(): void {
        $this->load->model('extension/voltxt/payment/voltxt');
        $this->model_extension_voltxt_payment_voltxt->install();
    }
    
    public function uninstall(): void {
        $this->load->model('extension/voltxt/payment/voltxt');
        $this->model_extension_voltxt_payment_voltxt->uninstall();
    }
}