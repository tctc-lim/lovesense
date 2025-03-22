<?php
// Prevent PHP from outputting HTML errors
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Set JSON content type
header('Content-Type: application/json');

try {
    // Validate request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed');
    }

    // Get POST data
    $data = $_POST;

    // Validate required fields
    $required_fields = ['first_name', 'last_name', 'email', 'phone', 'service', 'appointmentDate', 'time', 'sessions', 'price'];
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            throw new Exception("Missing required field: {$field}");
        }
    }

    // Validate email
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }

    // Prepare email content
    $to = "info.mylovesense@gmail.com";
    $subject = "New Booking Request from {$data['first_name']} {$data['last_name']}";

    $message = "
    <html>
    <head>
        <title>New Booking Request</title>
    </head>
    <body>
        <h2>Booking Details:</h2>
        <p><strong>Name:</strong> {$data['first_name']} {$data['last_name']}</p>
        <p><strong>Email:</strong> {$data['email']}</p>
        <p><strong>Phone:</strong> {$data['phone']}</p>
        <p><strong>Service:</strong> {$data['service']}</p>
        <p><strong>Date:</strong> {$data['appointmentDate']}</p>
        <p><strong>Time:</strong> {$data['time']}</p>
        <p><strong>Sessions:</strong> {$data['sessions']}</p>
        <p><strong>Price:</strong> {$data['price']}</p>
        " . (!empty($data['promoCode']) ? "<p><strong>Promo Code:</strong> {$data['promoCode']}</p>" : "") . "
    </body>
    </html>
    ";

    // Updated Email headers with proper display name and different reply-to
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=utf-8',
        'From: My Love Sense <no-reply@mylovesense.online>',
        'Reply-To: bookings@mylovesense.online',
        'X-Mailer: PHP/' . phpversion()
    ];

    // Send email to admin
    $mail_sent = mail($to, $subject, $message, implode("\r\n", $headers));

    // Check if email was sent successfully
    if (!$mail_sent) {
        throw new Exception('Failed to send email');
    }

    // Success response
    echo json_encode([
        'success' => true,
        'message' => 'Booking request received'
    ]);

} catch (Exception $e) {
    // Error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>