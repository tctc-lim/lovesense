<?php
// Prevent PHP from outputting HTML errors
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Load configuration (outside web root for security)
require_once '../../../config.php';

// Set JSON content type
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Function to convert any currency to GHS using real-time exchange rates
function convertToGHS($amount, $from_currency) {
    // If already GHS, return as is
    if ($from_currency === 'GHS') {
        return $amount;
    }
    
    try {
        // Use API key from config
        $openEx_api_key = OPENEXCHANGE_API_KEY;
        
        // Get latest exchange rates
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://openexchangerates.org/api/latest.json?app_id={$openEx_api_key}");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code !== 200) {
            throw new Exception('Failed to fetch exchange rates');
        }
        
        $exchange_data = json_decode($response, true);
        
        if (!$exchange_data || !isset($exchange_data['rates'])) {
            throw new Exception('Invalid exchange rate data');
        }
        
        $rates = $exchange_data['rates'];
        
        // Check if both currencies exist in the rates
        if (!isset($rates[$from_currency]) || !isset($rates['GHS'])) {
            throw new Exception("Currency {$from_currency} or GHS not found in exchange rates");
        }
        
        // Convert: amount_in_foreign_currency * (GHS_rate / foreign_currency_rate)
        $ghs_amount = $amount * ($rates['GHS'] / $rates[$from_currency]);
        
        return round($ghs_amount, 2);
        
    } catch (Exception $e) {
        // Fallback: use approximate conversion rates if API fails
        $fallback_rates = [
            'USD' => 0.1,    // 1 USD ≈ 0.1 GHS (approximate)
            'GBP' => 0.08,   // 1 GBP ≈ 0.08 GHS (approximate)
            'NGN' => 0.01,   // 1 NGN ≈ 0.01 GHS (approximate)
            'EUR' => 0.11,   // 1 EUR ≈ 0.11 GHS (approximate)
        ];
        
        if (isset($fallback_rates[$from_currency])) {
            return $amount * $fallback_rates[$from_currency];
        }
        
        // If no fallback rate, return the original amount (assume it's already in GHS)
        return $amount;
    }
}

try {
    // Validate request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed');
    }

    // Get POST data
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        $input = $_POST;
    }

    // Validate required fields
    $required_fields = ['currency', 'email', 'session_type', 'selected_sessions'];
    foreach ($required_fields as $field) {
        if (empty($input[$field])) {
            throw new Exception("Missing required field: {$field}");
        }
    }

    // Validate email
    if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }

    // Paystack configuration (from config.php)
    $paystack_secret_key = PAYSTACK_SECRET_KEY;
    $paystack_public_key = PAYSTACK_PUBLIC_KEY;
    
    // Hardcoded prices for each country (same as your frontend)
    $session_prices = [
        'GHS' => [
            '200' => 200,   // 1 Session
            '550' => 550,   // 3 Sessions  
            '900' => 900    // 5 Sessions
        ],
        'NGN' => [
            '200' => 20000, // 1 Session
            '550' => 58000, // 3 Sessions
            '900' => 95000  // 5 Sessions
        ],
        'USD' => [
            '200' => 20,    // 1 Session
            '550' => 58,    // 3 Sessions
            '900' => 96     // 5 Sessions
        ],
        'GBP' => [
            '200' => 15,    // 1 Session
            '550' => 43,    // 3 Sessions
            '900' => 72     // 5 Sessions
        ]
    ];

    // Get the session type and currency
    $display_currency = strtoupper($input['currency']);
    $session_type = $input['session_type'];
    $selected_sessions = $input['selected_sessions']; // This will be '200', '550', or '900'
    
    if (!isset($session_prices[$display_currency])) {
        throw new Exception('Unsupported currency: ' . $display_currency);
    }
    
    if (!isset($session_prices[$display_currency][$selected_sessions])) {
        throw new Exception('Invalid session selection: ' . $selected_sessions);
    }

    // Get the hardcoded display amount for the user's currency
    $display_amount = $session_prices[$display_currency][$selected_sessions];
    
    // Convert the hardcoded amount to GHS using real-time exchange rates
    $ghs_amount = convertToGHS($display_amount, $display_currency);
    
    // If conversion failed, use the GHS price directly
    if ($ghs_amount <= 0) {
        $ghs_amount = $session_prices['GHS'][$selected_sessions];
    }
    
    // Paystack expects amount in pesewas (smallest unit), so multiply by 100
    $amount_in_pesewas = intval($ghs_amount * 100);

    // Prepare transaction data
    $transaction_data = [
        'amount' => $amount_in_pesewas,
        'currency' => 'GHS', // Always GHS for Ghana-based businesses
        'email' => $input['email'],
        'reference' => 'LS_' . time() . '_' . rand(1000, 9999),
        'callback_url' => 'https://mylovesense.online/payment-callback.php',
        'metadata' => [
            'session_type' => $input['session_type'],
            'display_currency' => $display_currency,
            'display_amount' => $display_amount,
            'customer_name' => $input['customer_name'] ?? '',
            'customer_phone' => $input['customer_phone'] ?? ''
        ]
    ];

    // Initialize Paystack transaction
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.paystack.co/transaction/initialize');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($transaction_data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $paystack_secret_key,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code !== 200) {
        throw new Exception('Paystack API error: HTTP ' . $http_code);
    }

    $paystack_response = json_decode($response, true);
    
    if (!$paystack_response || !$paystack_response['status']) {
        throw new Exception('Paystack initialization failed: ' . ($paystack_response['message'] ?? 'Unknown error'));
    }

    // Return success response
    echo json_encode([
        'status' => true,
        'message' => 'Payment initialized successfully',
        'data' => [
            'authorization_url' => $paystack_response['data']['authorization_url'],
            'access_code' => $paystack_response['data']['access_code'],
            'reference' => $paystack_response['data']['reference'],
            'display_currency' => $display_currency,
            'display_amount' => $display_amount,
            'ghs_amount' => $ghs_amount,
            'session_type' => $input['session_type'],
            'conversion_info' => "Your payment of {$display_amount} {$display_currency} will be processed as {$ghs_amount} GHS"
        ]
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => false,
        'message' => $e->getMessage()
    ]);
}
?>