<?php
// Payment callback handler for Paystack
require_once '../config.php';
header('Content-Type: application/json');

try {
    // Get the reference from the callback
    $reference = $_GET['reference'] ?? '';
    
    if (empty($reference)) {
        throw new Exception('No reference provided');
    }

    // Verify the transaction with Paystack (from config.php)
    $paystack_secret_key = PAYSTACK_SECRET_KEY;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.paystack.co/transaction/verify/' . $reference);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $paystack_secret_key
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code !== 200) {
        throw new Exception('Failed to verify transaction');
    }

    $paystack_response = json_decode($response, true);
    
    if (!$paystack_response || !$paystack_response['status']) {
        throw new Exception('Transaction verification failed');
    }

    $transaction = $paystack_response['data'];
    
    // Check if payment was successful
    if ($transaction['status'] === 'success') {
        // Payment successful - you can save to database, send emails, etc.
        $metadata = $transaction['metadata'] ?? [];
        
        // Log successful payment (you might want to save to database)
        error_log("Payment successful: " . json_encode($transaction));
        
        // Redirect to success page
        header('Location: payment-success.html?reference=' . $reference);
        exit();
    } else {
        // Payment failed
        header('Location: payment-failed.html?reference=' . $reference);
        exit();
    }

} catch (Exception $e) {
    error_log("Payment callback error: " . $e->getMessage());
    header('Location: payment-failed.html?error=' . urlencode($e->getMessage()));
    exit();
}
?>