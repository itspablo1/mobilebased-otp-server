<?php
header("Content-Type: application/json");
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ✅ Include PHPMailer manually
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ✅ Brevo SMTP config
$brevoHost = 'smtp-relay.brevo.com';
$brevoPort = 587;
$brevoUsername = 'YOUR_BREVO_LOGIN_EMAIL@example.com'; // ← your Brevo account email
$brevoPassword = 'xsmtpsib-dc7df805c089fdb6d7fb5f642f63040b1254ba101daa2329835aa78392915c7e-AFPwg27MWXda4G5K';

// ✅ Read JSON input
$data = json_decode(file_get_contents("php://input"), true);
if (!isset($data['email']) || empty($data['email'])) {
    echo json_encode(["status" => "error", "message" => "Missing email address"]);
    exit;
}

$email = $data['email'];
$otp = rand(100000, 999999);

// ✅ Send Email via PHPMailer
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host = $brevoHost;
    $mail->SMTPAuth = true;
    $mail->Username = $brevoUsername;
    $mail->Password = $brevoPassword;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = $brevoPort;

    $mail->setFrom($brevoUsername, 'MobileBased OTP System');
    $mail->addAddress($email);
    $mail->isHTML(true);
    $mail->Subject = 'Your OTP Code';
    $mail->Body = "<h2>Your verification code is: <b>$otp</b></h2>";

    $mail->send();

    echo json_encode([
        "status" => "success",
        "message" => "OTP sent successfully to $email"
    ]);

} catch (Exception $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Mailer Error: {$mail->ErrorInfo}"
    ]);
}
?>
