<?php
/**
 * Voltxt Solana Payment Gateway - OpenCart 4.x Catalog Language File
 */

// Text
$_['text_title'] = 'Pay with Solana (Voltxt)';
$_['text_payment_info'] = 'Fast, secure cryptocurrency payment powered by Solana blockchain';
$_['text_network'] = 'Network';
$_['text_expires_in'] = 'Payment expires in';
$_['text_hours'] = 'hours';
$_['text_loading'] = 'Processing payment...';
$_['text_solana'] = 'Solana (SOL)';
$_['text_order_total'] = 'Order Total';
$_['text_payment_method'] = 'Payment Method';
$_['text_payment_instructions'] = 'Click the button below to proceed with your Solana payment. You will be redirected to a secure payment page.';
$_['text_security_notice'] = 'Your payment is secured by blockchain technology and processed through Voltxt payment gateway.';
$_['text_powered_by'] = 'Powered by';
$_['text_voltxt_gateway'] = 'Voltxt Payment Gateway';
$_['text_secure_payment'] = 'Secure payment powered by Voltxt';

// Order descriptions
$_['text_order_description'] = 'OpenCart Order #%s';
$_['text_order_pending'] = 'Awaiting Solana payment via Voltxt. Session ID: %s';
$_['text_payment_completed'] = 'Voltxt payment completed. Transaction ID: %s. Amount received: %s.';
$_['text_payment_cancelled'] = 'Voltxt payment for session %s was cancelled by the customer.';
$_['text_payment_expired'] = 'Voltxt payment session %s expired.';
$_['text_payment_detected'] = 'Voltxt payment detected for session %s. Awaiting blockchain confirmation.';
$_['text_payment_underpaid'] = 'Voltxt payment for order was underpaid. Expected: %s, Received: %s. Order marked as failed.';

// Button text
$_['button_confirm'] = 'Pay with Solana';

// Error messages
$_['error_api_key_missing'] = 'Payment gateway configuration error. Please contact store administrator.';
$_['error_payment_failed'] = 'Payment initialization failed. Please try again or contact support.';
$_['error_payment_cancelled'] = 'Payment was cancelled. You can try again or choose a different payment method.';
$_['error_invalid_order'] = 'Invalid order information. Please contact support.';
$_['error_invalid_payment_method'] = 'Invalid payment method selected.';

// Accessibility
$_['aria_payment_button'] = 'Proceed to Solana payment';
$_['aria_loading'] = 'Loading payment information';