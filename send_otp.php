<?php
header("Content-Type: application/json");
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ===============================
// 📦 PHPMailer Manual Include
// ===============================
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ===============================
// 🔧 Brevo SMTP Configuration
// ===============================
$brevoHost = 'smtp-relay.brevo.com';
$brevoPort = 587;
$brevoUsername = '98cda6002@smtp-brevo.com'; // ✅ Your Brevo SMTP login
$brevoPassword = '3RbCvP5T8BYKSx4Q'; // ✅ Your Brevo SMTP master password (not API key)

// ===============================
// 🔧 Supabase Configuration
// ===============================
$supabase_url = 'https://yfbgtsqdmbbvclalttbz.supabase.co';
$supabase_key = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InlmYmd0c3FkbWJidmNsYWx0dGJ6Iiwicm9sZSI6InNlcnZpY2Vfcm9sZSIsImlhdCI6MTc1OTY5MDEwOSwiZXhwIjoyMDc1MjY2MTA5fQ.KHZ2dRd7WZWahDlYsP4TC_PJR6uLgCHh1PN5_Jg9WuU';
$table = 'email_otps';

// ===============================
// 📨 Receive data from app
// ===============================
$data = json_decode(file_get_contents("php://input"), true);
$email = $data["email"] ?? '';

if (empty($email)) {
    echo json_encode(["status" => "error", "message" => "Missing email address"]);
    exit;
}

// ===============================
// 🔢 Generate OTP
// ===============================
$otp = rand(100000, 999999);
$expires_at = date("Y-m-d H:i:s", time() + 300); // expires in 5 minutes

// ===============================
// 📨 Send OTP via Brevo SMTP
// ===============================
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
    $mail->Body = "<h2>Your verification code is: <b>$otp</b></h2><p>This code will expire in 5 minutes.</p>";

    $mail->send();

    // ===============================
    // 💾 Save OTP to Supabase
    // ===============================
    $payload = json_encode([
        "email" => $email,
        "otp" => $otp,
        "expires_at" => $expires_at,
        "verified" => false
    ]);

    $ch = curl_init("$supabase_url/rest/v1/$table");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => [
            "Content-Type: application/json",
            "apikey: $supabase_key",
            "Authorization: Bearer $supabase_key",
            "Prefer: return=representation"
        ]
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    echo json_encode([
        "status" => "success",
        "message" => "OTP sent successfully to $email",
        "otp" => $otp, // (optional for debugging, remove in production)
        "supabase_response" => json_decode($response, true)
    ]);

} catch (Exception $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Mailer Error: {$mail->ErrorInfo}"
    ]);
}
?>
