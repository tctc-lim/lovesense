<?php 
// $from = "no-reply@mylovesense.online";
// $service = $_POST['service'];
// $name = $_POST['name'];
// $phone = $_POST['phone'];
//$email = $_POST['email'];
// $message = $_POST['message'];
$to = "info.mylovesense@gmail.com";

$name = $_POST['name'];
$email = $_POST['email'];
$phone_number = $_POST['phone_number'];
$message = $_POST['message'];
$subject = 'Message from LoveSense Contact Form';
$from = 'no-reply@mylovesense.online';
// To send HTML mail, the Content-type header must be set
$headers  = 'MIME-Version: 1.0' . "\r\n";
$headers .= 'Content-type: text/html; charset=iso-8859-1'."\r\n"; 
// Create email headers
$headers .= 'From: '.$from."\r\n".'Reply-To: '.$from."\r\n".'X-Mailer: PHP/'.phpversion();
$msg = "Name: ".$name."<br>"."Email: ".$email."<br>"."Phone: ".$phone_number."<br>"."Message: ".$message;
// Sending email
if(mail($to, $subject, $msg,$headers)){
    $arr['status']= 200;
	$arr['statusText']= "Contact message received.";
	echo json_encode($arr);

} 
else{
    $arr['status']= "400";
	$arr['statusText']= "An error occurred while trying to send your contact form.";
	echo json_encode($arr);
}



?>