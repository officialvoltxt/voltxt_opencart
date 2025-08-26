<?php
/**
 * Voltxt API Client Library - OpenCart 4.x Compatible
 * OpenCart Payment Gateway Module
 *
 * Handles all API communication with the Voltxt backend
 *
 * @package    OpenCart
 * @author     Voltxt
 * @copyright  2025 Voltxt
 * @version    1.0.0
 * @link       https://voltxt.io
 */

class VoltxtApiClient {
    
    /**
     * API configuration
     */
    private string $api_key;
    private string $network;
    private string $api_base_url;
    private int $timeout;
    private string $user_agent;
    
    /**
     * Constructor
     *
     * @param string $api_key The Voltxt API key
     * @param string $network The network (testnet/mainnet)
     * @throws Exception If configuration is invalid
     */
    public function __construct(string $api_key, string $network = 'testnet') {
        $this->api_key = $api_key;
        // Ensure network is lowercased for consistency
        $this->network = strtolower($network);
        $this->api_base_url = 'https://api.voltxt.io/api';
        $this->timeout = 30; // seconds
        $this->user_agent = 'OpenCart-Voltxt/1.0.0 (OpenCart/' . (defined('VERSION') ? VERSION : '4.0.x') . ')'; // Dynamic version
        
        $this->validateConfig();
    }
    
    /**
     * Test API connection
     *
     * @param array $test_data Additional test data
     * @return array Response array
     */
    public function testConnection(array $test_data = []): array {
        $payload = array_merge([
            'api_key' => $this->api_key,
            'network' => $this->network,
            'platform' => 'opencart',
            'user_agent' => $this->user_agent
        ], $test_data);
        
        $response = $this->makeRequest('/plugin/test-connection', $payload, 'POST');
        $this->logApiCall('/plugin/test-connection', $payload, $response, 'POST');
        return $response;
    }
    
    /**
     * Initiate dynamic payment session
     *
     * @param array $payment_data Payment session data
     * @return array Response array
     */
    public function initiateDynamicPayment(array $payment_data): array {
        $payload = array_merge([
            'api_key' => $this->api_key,
            'network' => $this->network,
            'platform' => 'opencart',
            'user_agent' => $this->user_agent
        ], $payment_data);
        
        $response = $this->makeRequest('/dynamic-payment/initiate', $payload, 'POST');
        $this->logApiCall('/dynamic-payment/initiate', $payload, $response, 'POST');
        return $response;
    }
    
    /**
     * Get payment session status
     *
     * @param string $session_id Payment session ID
     * @return array Response array
     */
    public function getPaymentStatus(string $session_id): array {
        $query_params = [
            'api_key' => $this->api_key,
            'network' => $this->network
        ];
        
        $endpoint = '/dynamic-payment/' . urlencode($session_id) . '/status?' . http_build_query($query_params);
        
        $response = $this->makeRequest($endpoint, [], 'GET');
        $this->logApiCall($endpoint, $query_params, $response, 'GET');
        return $response;
    }
    
    /**
     * Cancel payment session
     *
     * @param string $session_id Payment session ID
     * @return array Response array
     */
    public function cancelPaymentSession(string $session_id): array {
        $payload = [
            'api_key' => $this->api_key,
            'network' => $this->network
        ];
        
        $endpoint = '/dynamic-payment/' . urlencode($session_id) . '/cancel';
        
        $response = $this->makeRequest($endpoint, $payload, 'POST');
        $this->logApiCall($endpoint, $payload, $response, 'POST');
        return $response;
    }
    
    /**
     * Validate webhook signature (if implemented by Voltxt)
     * This is a placeholder, as actual signature validation depends on Voltxt's implementation.
     * Often, it involves an HMAC hash of the payload using a shared secret (like the API key).
     *
     * @param string $raw_payload Raw JSON payload received
     * @param string $signature Webhook signature header (e.g., X-Voltxt-Signature)
     * @return bool True if valid, false otherwise
     */
    public function validateWebhookSignature(string $raw_payload, string $signature): bool {
        if (empty($this->api_key) || empty($signature) || empty($raw_payload)) {
            return false;
        }
        
        // This is a common pattern for HMAC-SHA256 signatures.
        // You MUST confirm with Voltxt's API documentation for their exact signature generation method.
        $expected_signature = hash_hmac('sha256', $raw_payload, $this->api_key);
        
        // Use hash_equals for constant time comparison to prevent timing attacks
        return hash_equals($expected_signature, $signature);
    }
    
    /**
     * Make HTTP request to Voltxt API
     *
     * @param string $endpoint API endpoint (e.g., '/dynamic-payment/initiate')
     * @param array $data Request data to be JSON encoded for POST, or query params for GET
     * @param string $method HTTP method ('GET' or 'POST')
     * @return array Response array (success, error, data, etc.)
     */
    private function makeRequest(string $endpoint, array $data = [], string $method = 'POST'): array {
        $url = $this->api_base_url . $endpoint;
        
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'User-Agent: ' . $this->user_agent,
            'X-Platform: opencart',
            'X-Plugin-Version: 1.0.0' // Explicit plugin version
        ];
        
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 10, // Max time to wait for connection
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => true, // Verify SSL certificate
            CURLOPT_SSL_VERIFYHOST => 2,   // Check Common Name and verify against hostname
            CURLOPT_FOLLOWLOCATION => false, // Do not follow redirects manually
            CURLOPT_MAXREDIRS => 0,          // Explicitly set max redirects to 0
            CURLOPT_FAILONERROR => false,    // Do not fail on HTTP errors (allows us to read response body)
            CURLOPT_USERAGENT => $this->user_agent
        ]);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if (!empty($data)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        } elseif ($method === 'GET' && !empty($data)) {
            // Append GET data to URL
            $url_parts = parse_url($url);
            $query_string = isset($url_parts['query']) ? $url_parts['query'] . '&' : '';
            $url .= (isset($url_parts['query']) ? '&' : '?') . http_build_query($data);
            curl_setopt($ch, CURLOPT_URL, $url);
        }
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
        
        return $this->processResponse($response, $http_code, $curl_error, $url, $info);
    }
    
    /**
     * Process API response
     *
     * @param string|false $response Raw response body from cURL
     * @param int $http_code HTTP status code
     * @param string $curl_error cURL error message
     * @param string $url Request URL
     * @param array $info cURL info array
     * @return array Processed response array
     */
    private function processResponse($response, int $http_code, string $curl_error, string $url, array $info): array {
        // Handle cURL errors first
        if ($curl_error) {
            return [
                'success' => false,
                'error' => $this->getUserFriendlyError($curl_error),
                'error_code' => $this->getErrorCode($curl_error),
                'http_code' => 0, // Indicate no HTTP response
                'debug_info' => [
                    'curl_error' => $curl_error,
                    'url' => $url,
                    'total_time' => $info['total_time'] ?? 0,
                    'request_size' => $info['request_size'] ?? 0
                ]
            ];
        }
        
        // Decode JSON response
        $decoded = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            // Log raw response if JSON decoding fails for debugging
            $error_message = 'Invalid JSON response from payment gateway: ' . json_last_error_msg();
            return [
                'success' => false,
                'error' => $error_message,
                'error_code' => 'INVALID_JSON_RESPONSE',
                'http_code' => $http_code,
                'debug_info' => [
                    'raw_response' => substr($response, 0, 500) . (strlen($response) > 500 ? '...' : ''), // Truncate long responses
                    'json_error_msg' => json_last_error_msg(),
                    'url' => $url
                ]
            ];
        }
        
        // Handle HTTP errors and API-level errors
        if ($http_code < 200 || $http_code >= 300) {
            // Check if the API itself returned an error message in the JSON payload
            if (isset($decoded['error'])) {
                return [
                    'success' => false,
                    'error' => $decoded['error'], // API's specific error message
                    'error_code' => $decoded['error_code'] ?? 'API_HTTP_ERROR',
                    'http_code' => $http_code,
                    'details' => $decoded // Include full decoded payload for debugging
                ];
            } else {
                // Generic HTTP error message
                return [
                    'success' => false,
                    'error' => $this->getHttpErrorMessage($http_code),
                    'error_code' => 'HTTP_ERROR_' . $http_code,
                    'http_code' => $http_code,
                    'debug_info' => [
                        'response_body' => $response,
                        'url' => $url
                    ]
                ];
            }
        }
        
        // If status is 2xx and 'success' flag is explicitly false in API response
        if (isset($decoded['success']) && !$decoded['success']) {
             return [
                'success' => false,
                'error' => $decoded['error'] ?? 'Payment gateway error (API reported failure)',
                'error_code' => $decoded['error_code'] ?? 'API_REPORTED_FAILURE',
                'http_code' => $http_code,
                'details' => $decoded
            ];
        }

        // Successful response
        return [
            'success' => true,
            'data' => $decoded['data'] ?? $decoded, // 'data' key is common, but sometimes the root is the data
            'message' => $decoded['message'] ?? 'Request successful',
            'http_code' => $http_code
        ];
    }
    
    /**
     * Get user-friendly error message for cURL errors
     *
     * @param string $curl_error cURL error message
     * @return string User-friendly error
     */
    private function getUserFriendlyError(string $curl_error): string {
        if (strpos($curl_error, 'Could not resolve host') !== false || strpos($curl_error, 'getaddrinfo') !== false) {
            return 'Unable to connect to the payment gateway server. Please check your internet connection or DNS settings.';
        }
        
        if (strpos($curl_error, 'Connection timed out') !== false || strpos($curl_error, 'Operation timed out') !== false) {
            return 'Connection to the payment gateway timed out. This might be a temporary network issue. Please try again.';
        }
        
        if (strpos($curl_error, 'SSL') !== false || strpos($curl_error, 'certificate') !== false) {
            return 'Secure connection to payment gateway failed. This could indicate an issue with your server\'s SSL configuration or a problem with the gateway\'s certificate. Please contact support.';
        }
        
        return 'A network connection error occurred. Please try again or contact support if the issue persists.';
    }
    
    /**
     * Get error code from cURL error
     *
     * @param string $curl_error cURL error message
     * @return string Error code
     */
    private function getErrorCode(string $curl_error): string {
        if (strpos($curl_error, 'Could not resolve host') !== false) {
            return 'DNS_RESOLUTION_FAILED';
        }
        
        if (strpos($curl_error, 'Connection timed out') !== false) {
            return 'CONNECTION_TIMEOUT';
        }
        
        if (strpos($curl_error, 'SSL') !== false) {
            return 'SSL_ERROR';
        }
        
        return 'CURL_NETWORK_ERROR';
    }
    
    /**
     * Get HTTP error message
     *
     * @param int $http_code HTTP status code
     * @return string Error message
     */
    private function getHttpErrorMessage(int $http_code): string {
        $messages = [
            400 => 'Bad request to payment gateway (invalid data)',
            401 => 'Invalid API credentials (Unauthorized)',
            403 => 'Access denied by payment gateway (Forbidden)',
            404 => 'Payment gateway endpoint not found',
            429 => 'Too many requests to payment gateway (Rate Limit Exceeded)',
            500 => 'Payment gateway internal server error',
            502 => 'Payment gateway temporarily unavailable (Bad Gateway)',
            503 => 'Payment gateway is currently under maintenance or overloaded',
            504 => 'Payment gateway request timed out'
        ];
        
        return $messages[$http_code] ?? "An unexpected payment gateway error occurred (HTTP $http_code)";
    }
    
    /**
     * Validate configuration parameters
     *
     * @throws Exception If configuration is invalid
     */
    private function validateConfig(): void {
        if (empty($this->api_key)) {
            throw new Exception('Voltxt API key is required and cannot be empty.');
        }
        
        if (strlen($this->api_key) !== 32) {
            throw new Exception('Voltxt API key must be exactly 32 characters long.');
        }
        
        if (!in_array($this->network, ['testnet', 'mainnet'])) {
            throw new Exception('Voltxt network must be either "testnet" or "mainnet".');
        }
        
        if (!function_exists('curl_init')) {
            throw new Exception('The cURL PHP extension is required for Voltxt payment processing. Please enable it.');
        }
        
        if (!function_exists('json_decode')) {
            throw new Exception('The JSON PHP extension is required for Voltxt payment processing. Please enable it.');
        }
    }
    
    /**
     * Get API client configuration info
     *
     * @return array Configuration details
     */
    public function getConfig(): array {
        return [
            'api_base_url' => $this->api_base_url,
            'network' => $this->network,
            'timeout' => $this->timeout,
            'user_agent' => $this->user_agent,
            'has_api_key' => !empty($this->api_key),
            'api_key_length' => strlen($this->api_key),
            'curl_available' => function_exists('curl_init'),
            'json_available' => function_exists('json_decode'),
            'openssl_available' => extension_loaded('openssl')
        ];
    }
    
    /**
     * Set custom timeout for cURL requests
     *
     * @param int $timeout Timeout in seconds (min 5, max 120)
     */
    public function setTimeout(int $timeout): void {
        $this->timeout = max(5, min(120, $timeout));
    }
    
    /**
     * Set custom user agent string
     *
     * @param string $user_agent Custom user agent string
     */
    public function setUserAgent(string $user_agent): void {
        $this->user_agent = trim($user_agent) ?: $this->user_agent; // Ensure it's not empty after trim
    }
    
    /**
     * Check if the base API endpoint is reachable and returns a success status (e.g., /health endpoint)
     *
     * @return bool True if API is reachable and healthy, false otherwise
     */
    public function isApiReachable(): bool {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->api_base_url . '/health', // Assuming a /health or /status endpoint
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5, // Short timeout for health check
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_NOBODY => true, // Only fetch headers, no body
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_USERAGENT => $this->user_agent
        ]);
        
        $result = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // Consider reachable if cURL executed without error and HTTP code is 2xx
        return $result !== false && $http_code >= 200 && $http_code < 300;
    }
    
    /**
     * Get a hardcoded list of supported fiat currencies (can be extended to fetch from API)
     *
     * @return array List of supported currency codes
     */
    public function getSupportedCurrencies(): array {
        return ['USD', 'EUR', 'GBP', 'AUD', 'CAD', 'JPY', 'NZD', 'CHF', 'SGD', 'HKD', 'CNY', 'INR', 'BRL'];
    }
    
    /**
     * Format a float amount to a specific number of decimal places suitable for API transmission
     *
     * @param float $amount The raw float amount
     * @param string $currency The currency code (e.g., 'USD', 'SOL')
     * @return float Formatted amount
     */
    public function formatAmount(float $amount, string $currency = 'USD'): float {
        // SOL typically has 9 decimal places (lamports), fiat usually 2
        $decimals = (strtoupper($currency) === 'SOL') ? 9 : 2;
        return round($amount, $decimals);
    }
    
    /**
     * Generate a unique request ID, useful for tracing requests through logs
     *
     * @return string Unique request ID
     */
    public function generateRequestId(): string {
        return 'opencart_' . uniqid() . '_' . time();
    }
    
    /**
     * Validate the basic structure and known event type of an incoming webhook payload
     *
     * @param array $payload The decoded webhook payload
     * @return bool True if the payload has expected structure and a recognized event type
     */
    public function validateWebhookPayload(array $payload): bool {
        $required_fields = ['event_type', 'session_id'];
        
        foreach ($required_fields as $field) {
            if (!isset($payload[$field]) || empty($payload[$field])) {
                return false;
            }
        }
        
        $valid_events = [
            'payment_completed',
            'payment_received', 
            'payment_cancelled',
            'payment_expired',
            'invoice_expired', // Sometimes used interchangeably with payment_expired
            'payment_detected',
            'overpaid', // Handle if Voltxt supports this
            'underpaid' // Handle if Voltxt supports this
        ];
        
        return in_array($payload['event_type'], $valid_events);
    }
    
    /**
     * Build standard OpenCart callback URLs with placeholders that Voltxt can replace
     *
     * @param string $base_url The base URL of your OpenCart catalog (e.g., HTTP_CATALOG or HTTPS_CATALOG)
     * @return array An associative array of webhook, callback, success, and cancel URLs
     */
    public function buildCallbackUrls(string $base_url): array {
        $base_url = rtrim($base_url, '/'); // Ensure no trailing slash
        
        // OpenCart 4.x routing uses `|` to specify controller methods
        $webhook_url = $base_url . '/index.php?route=extension/voltxt/catalog/payment/voltxt|webhook';
        
        $callback_url = $base_url . '/index.php?route=extension/voltxt/catalog/payment/voltxt|callback' .
            '&order_id=[order_id]' .
            '&voltxt_session_id=[session_id]' .
            '&voltxt_payment_status=[payment_status]' .
            '&voltxt_payment_tx=[payment_tx_id]';
        
        $success_url = $base_url . '/index.php?route=checkout/success' . // Direct to OC success page
            '&voltxt_session_id=[session_id]' .
            '&voltxt_payment_status=[payment_status]' .
            '&voltxt_payment_tx=[payment_tx_id]';
        
        $cancel_url = $base_url . '/index.php?route=checkout/checkout' . // Direct to OC checkout page
            '&voltxt_cancelled=1' .
            '&voltxt_session_id=[session_id]';
        
        return [
            'webhook_url' => $webhook_url,
            'callback_url' => $callback_url,
            'success_url' => $success_url,
            'cancel_url' => $cancel_url
        ];
    }
    
    /**
     * Log API call details. Requires a logger (e.g., OpenCart's built-in log).
     * This method assumes you will provide a logger instance or use OpenCart's internal log.
     *
     * @param string $endpoint The API endpoint called
     * @param array $request_data The data sent in the request (e.g., payload)
     * @param array $response_data The processed response from the API call
     * @param string $method The HTTP method used (GET/POST)
     */
    public function logApiCall(string $endpoint, array $request_data, array $response_data, string $method = 'POST'): void {
        // This is a placeholder for where logging would actually occur.
        // In OpenCart, you'd typically use $this->log->write() from within a controller or model.
        // For a library, you might pass a logger object or rely on a global error_log.
        $log_entry = [
            'timestamp' => date('c'),
            'type' => 'Voltxt API Call',
            'method' => $method,
            'endpoint' => $endpoint,
            'network' => $this->network,
            'request_data' => $request_data,
            'response_data' => $response_data
        ];
        
        // A simple fallback to PHP's error_log if no specific logger is injected.
        if (function_exists('error_log')) {
            error_log(json_encode($log_entry, JSON_PRETTY_PRINT));
        }
    }
    
    /**
     * Get system requirements status for the API client to function correctly.
     *
     * @return array Associative array indicating status of various requirements
     */
    public function getSystemRequirements(): array {
        return [
            'php_version' => [
                'required' => '7.4.0',
                'current' => PHP_VERSION,
                'status' => version_compare(PHP_VERSION, '7.4.0', '>=')
            ],
            'curl' => [
                'required' => true,
                'status' => function_exists('curl_init'),
                'version' => function_exists('curl_version') ? curl_version()['version'] : 'unavailable'
            ],
            'json' => [
                'required' => true,
                'status' => function_exists('json_decode') && function_exists('json_encode')
            ],
            'openssl' => [
                'required' => true,
                'status' => extension_loaded('openssl'),
                'version' => defined('OPENSSL_VERSION_TEXT') ? OPENSSL_VERSION_TEXT : 'unavailable'
            ]
        ];
    }
}