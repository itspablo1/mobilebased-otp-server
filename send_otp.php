<?php
header("Content-Type: application/json");
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ✅ Your Brevo API Key (keep it secret)
$apiKey = "xsmtpsib-dc7df805c089fdb6d7fb5f642f63040b1254ba101daa2329835aa78392915c7e-AFPwg27MWXda4G5K";

// Get email from Android app
$data = json_decode(file_get_contents("php://input"), true);
$email = $data["email"] ?? '';

if (empty($email)) {
    echo json_encode(["status" => "error", "message" => "Missing email address"]);
    exit;
}

// Generate 6-digit OTP
$otp = rand(100000, 999999);
$subject = "Your OTP Code";
$body = "
    <h2>Mobile-Based Alarm System</h2>
    <p>Hello!</p>
    <p>Your verification code is:</p>
    <h1 style='color:#FF5F15;'>$otp</h1>
    <p>This code will expire in 5 minutes.</p>
";

// Prepare email payload
$data = [
  "sender" => ["name" => "Mobile-Based Alarm System", "email" => "youremail@example.com"],
  "to" => [["email" => $email]],
  "subject" => $subject,
  "htmlContent" => $body
];

// Send via Brevo API
$ch = curl_init("https://api.brevo.com/v3/smtp/email");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($data),
    CURLOPT_HTTPHEADER => [
        "Content-Type: application/json",
        "api-key: $apiKey"
    ]
]);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// ✅ Return JSON result
if ($http_code >= 200 && $http_code < 300) {
    echo json_encode(["status" => "success", "message" => "OTP sent successfully", "otp" => $otp]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed to send email", "response" => $response]);
}
?>
