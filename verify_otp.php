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
// 📨 Receive data from app
// ===============================
$data = json_decode(file_get_contents("php://input"), true);
$email = $data["email"] ?? '';
$otp = $data["otp"] ?? '';

if (empty($email) || empty($otp)) {
    echo json_encode(["status" => "error", "message" => "Missing fields"]);
    exit;
}

// ===============================
// 🔍 Verify OTP in Supabase
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
// ⏳ Check if expired
// ===============================
if (strtotime($record["expires_at"]) < time()) {
    echo json_encode(["status" => "error", "message" => "OTP expired"]);
    exit;
}

// ===============================
// ✅ Mark as verified
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
curl_exec($ch);
curl_close($ch);

echo json_encode(["status" => "success", "message" => "Email verified successfully!"]);
?>
