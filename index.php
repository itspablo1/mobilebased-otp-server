<?php
header("Content-Type: application/json");
echo json_encode([
    "status" => "ok",
    "message" => "Server is live and PHP is running!",
    "routes" => [
        "/send_otp.php",
        "/verify_otp.php"
    ]
]);
?>
