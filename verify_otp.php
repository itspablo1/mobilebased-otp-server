<?php
header("Content-Type: application/json");
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ===============================
// 🔧 Supabase Configuration
// ===============================
$supabase_url = 'https://yfbgtsqdmbbvclalttbz.supabase.co';
$supabase_key = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InlmYmd0c3FkbWJidmNsYWx0dGJ6Iiwicm9sZSI6InNlcnZpY2Vfcm9sZSIsImlhdCI6MTc1OTY5MDEwOSwiZXhwIjoyMDc1MjY2MTA5fQ.KHZ2dRd7WZWahDlYsP4TC_PJR6uLgCHh1PN5_Jg9WuU';
$table = 'email_otps';

// ===============================
// 📩 Receive data from client/app
// ===============================
$data = json_decode(file_get_contents("php://input"), true);
$email = $data["email"] ?? '';
$otp = $data["otp"] ?? '';

if (empty($email) || empty($otp)) {
    echo json_encode(["status" => "error", "message" => "Missing email or OTP"]);
    exit;
}

// ===============================
// 🔍 Step 1: Find matching OTP
// ===============================
$url = "$supabase_url/rest/v1/$table?email=eq." . urlencode($email) . "&otp=eq." . urlencode($otp);
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "apikey: $supabase_key",
        "Authorization: Bearer $supabase_key"
    ]
]);
$response = curl_exec($ch);
curl_close($ch);

$records = json_decode($response, true);

if (empty($records)) {
    echo json_encode(["status" => "error", "message" => "Invalid OTP"]);
    exit;
}

$record = $records[0];

// ===============================
// ⏳ Step 2: Check expiration
// ===============================
$current_time = time();
$expiry_time = strtotime($record["expires_at"]);

if ($current_time > $expiry_time) {
    echo json_encode(["status" => "expired", "message" => "OTP expired. Please request a new one."]);
    exit;
}

// ===============================
// ✅ Step 3: Mark verified = true
// ===============================
$update_url = "$supabase_url/rest/v1/$table?email=eq." . urlencode($email);
$payload = json_encode(["verified" => true]);

$ch = curl_init($update_url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => "PATCH",
    CURLOPT_POSTFIELDS => $payload,
    CURLOPT_HTTPHEADER => [
        "Content-Type: application/json",
        "apikey: $supabase_key",
        "Authorization: Bearer $supabase_key",
        "Prefer: return=representation"
    ]
]);
$update_response = curl_exec($ch);
curl_close($ch);

echo json_encode([
    "status" => "success",
    "message" => "Email verified successfully!",
    "email" => $email
]);
?>
