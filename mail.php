<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed');
    }

    $data = $_POST;

    // Validate required fields
    $required_fields = ['first_name', 'last_name', 'email', 'phone', 'service', 'appointmentDate', 'time', 'sessions', 'price', 'country'];
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            throw new Exception("Missing required field: {$field}");
        }
    }

    $email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }

    // Sanitize input to prevent header injection and XSS
    $first_name = htmlspecialchars(strip_tags($data['first_name']));
    $last_name = htmlspecialchars(strip_tags($data['last_name']));
    $phone = htmlspecialchars(strip_tags($data['phone']));
    $service = htmlspecialchars(strip_tags($data['service']));
    $appointmentDate = htmlspecialchars(strip_tags($data['appointmentDate']));
    $time = htmlspecialchars(strip_tags($data['time']));
    $sessions = htmlspecialchars(strip_tags($data['sessions']));
    $price = htmlspecialchars(strip_tags($data['price']));
    $country = htmlspecialchars(strip_tags($data['country']));
    $address = !empty($data['address']) && $data['address'] !== 'address' ? htmlspecialchars(strip_tags($data['address'])) : 'Not provided';
    $message = !empty($data['message']) && $data['message'] !== 'null' ? htmlspecialchars(strip_tags($data['message'])) : 'Not provided';
    $promoCode = !empty($data['promoCode']) ? htmlspecialchars(strip_tags($data['promoCode'])) : '';

    // Prepare admin email content
    $to_admin = "hq.mylovesense@gmail.com";
    $subject_admin = "New Booking Request from $first_name $last_name";

    $boundary = md5(uniqid(time()));
    $message_admin = "--$boundary\r\n";
    $message_admin .= "Content-Type: text/html; charset=utf-8\r\n\r\n";
    $message_admin .= "
    <html>
    <head>
        <title>New Booking Request</title>
    </head>
    <body>
        <h2>Booking Details:</h2>
        <p><strong>Name:</strong> $first_name $last_name</p>
        <p><strong>Email:</strong> $email</p>
        <p><strong>Phone:</strong> $phone</p>
        <p><strong>Country:</strong> $country</p>
        <p><strong>Address:</strong> $address</p>
        <p><strong>Service:</strong> $service</p>
        <p><strong>Date:</strong> $appointmentDate</p>
        <p><strong>Time:</strong> $time</p>
        <p><strong>Sessions:</strong> $sessions</p>
        <p><strong>Price:</strong> $price</p>
        " . ($promoCode ? "<p><strong>Promo Code:</strong> $promoCode</p>" : "") . "
        <p><strong>Message:</strong> $message</p>
    </body>
    </html>
    \r\n";
    $message_admin .= "--$boundary--\r\n";

    $headers_admin = [
        'MIME-Version: 1.0',
        "Content-Type: multipart/mixed; boundary=\"$boundary\"",
        'From: My Love Sense <no-reply@mylovesense.online>',
        'Reply-To: bookings@mylovesense.online',
        'X-Mailer: PHP/' . phpversion(),
        'BCC: admin.backup@mylovesense.online'
    ];

    $mail_sent_admin = mail($to_admin, $subject_admin, $message_admin, implode("\r\n", $headers_admin));

    $to_customer = $email;
    $subject_customer = "Your Booking Confirmation with My Love Sense";

    $message_customer = "--$boundary\r\n";
    $message_customer .= "Content-Type: text/html; charset=utf-8\r\n\r\n";
    $message_customer .= "
    <html>
    <head>
        <title>Booking Confirmation</title>
    </head>
    <body>
        <h2>Thank You for Your Booking!</h2>
        <p>Dear $first_name $last_name,</p>
        <p>Your booking has been successfully received. Below are the details:</p>
        <p><strong>Service:</strong> $service</p>
        <p><strong>Date:</strong> $appointmentDate</p>
        <p><strong>Time:</strong> $time</p>
        <p><strong>Sessions:</strong> $sessions</p>
        <p><strong>Price:</strong> $price</p>
        <p><strong>Country:</strong> $country</p>
        <p><strong>Address:</strong> $address</p>
        " . ($promoCode ? "<p><strong>Promo Code:</strong> $promoCode</p>" : "") . "
        <p><strong>Message:</strong> $message</p>
        <p>We will contact you soon to confirm your appointment.</p>
        <p>Best regards,<br>My Love Sense Team</p>
    </body>
    </html>
    \r\n";
    $message_customer .= "--$boundary--\r\n";

    $headers_customer = [
        'MIME-Version: 1.0',
        "Content-Type: multipart/mixed; boundary=\"$boundary\"",
        'From: My Love Sense <no-reply@mylovesense.online>',
        'Reply-To: support@mylovesense.online',
        'X-Mailer: PHP/' . phpversion()
    ];

    $mail_sent_customer = mail($to_customer, $subject_customer, $message_customer, implode("\r\n", $headers_customer));

    if (!$mail_sent_admin || !$mail_sent_customer) {
        throw new Exception('Failed to send one or more emails');
    }

    // Success response
    echo json_encode([
        'success' => true,
        'message' => 'Booking request received and confirmation sent'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);

    error_log("Mail error: " . $e->getMessage());
}
?>