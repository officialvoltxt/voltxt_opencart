<?php
/**
 * Voltxt Solana Payment Gateway - OpenCart 4.x Admin Language File
 */

// Heading
$_['heading_title'] = 'Voltxt Solana Payment Gateway';

// Text
$_['text_extension'] = 'Extensions';
$_['text_success'] = 'Success: You have modified Voltxt payment module!';
$_['text_edit'] = 'Edit Voltxt Payment Gateway';
$_['text_voltxt'] = 'Voltxt';
$_['text_mainnet'] = 'Mainnet (Live Payments)';
$_['text_testnet'] = 'Testnet (Testing)';
$_['text_connection_success'] = 'Connection successful!';
$_['text_testing_connection'] = 'Testing connection...';
$_['text_all_zones'] = 'All Zones';
$_['text_wallet_configured'] = 'Configured';
$_['text_wallet_not_configured'] = 'Not Configured';
$_['warning_no_wallet'] = 'Warning: No destination wallet configured in your Voltxt account';
$_['text_store_name'] = 'Store Name';
$_['text_store_network'] = 'Store Network';
$_['text_wallet_status'] = 'Wallet Status';
$_['text_version'] = 'Module Version';

// Entry
$_['entry_api_key'] = 'API Key';
$_['entry_network'] = 'Solana Network';
$_['entry_expiry_hours'] = 'Payment Expiry (Hours)';
$_['entry_order_status'] = 'Completed Order Status';
$_['entry_pending_status'] = 'Pending Order Status';
$_['entry_cancelled_status'] = 'Cancelled Order Status';
$_['entry_failed_status'] = 'Failed Order Status';
$_['entry_geo_zone'] = 'Geo Zone';
$_['entry_status'] = 'Status';
$_['entry_sort_order'] = 'Sort Order';
$_['entry_debug'] = 'Debug Logging';
$_['entry_test_connection'] = 'Test API Connection';

// Help
$_['help_api_key'] = 'Enter your 32-character Voltxt API Key from app.voltxt.io. This is required for payment processing.';
$_['help_network'] = 'Select Solana network. Use Testnet for testing and Mainnet for live payments.';
$_['help_expiry_hours'] = 'Number of hours before a payment session expires. Minimum 1 hour, maximum 168 hours (7 days).';
$_['help_order_status'] = 'Order status when payment is successfully completed.';
$_['help_debug'] = 'Enable debug logging to track API requests and webhook events in the system log.';
$_['help_test_connection'] = 'Test your API connection to verify credentials and store configuration.';

// Button
$_['button_test_connection'] = 'Test Connection';

// Error
$_['error_permission'] = 'Warning: You do not have permission to modify Voltxt payment module!';
$_['error_api_key_required'] = 'API Key is required!';
$_['error_api_key_length'] = 'API Key must be exactly 32 characters long!';
$_['error_expiry_hours'] = 'Payment expiry must be between 1 and 168 hours!';
$_['error_connection_failed'] = 'Connection test failed';
$_['error_invalid_network'] = 'Invalid network selected';
$_['error_warning'] = 'Warning: Please check the form carefully for errors!';

// Tabs
$_['tab_general'] = 'General Settings';
$_['tab_order_status'] = 'Order Status';
$_['tab_advanced'] = 'Advanced';

// Webhook
$_['text_webhook_url'] = 'Webhook URL';
$_['help_webhook_url'] = 'Copy this URL and configure it in your Voltxt dashboard for automatic payment updates.';

// Version
$_['text_voltxt_version'] = 'Voltxt OpenCart Gateway v1.0.0';