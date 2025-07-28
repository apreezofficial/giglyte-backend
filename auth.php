<?php
require __DIR__ . '/vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

header('Content-Type: application/json');

function sendResponse($statusCode, $status, $message) {
    http_response_code($statusCode);
    echo json_encode(['status' => $status, 'message' => $message]);
    exit;
}

$secretKey = getenv('JWT_SECRET') ?: "change_this_to_a_secure_key";

$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
    sendResponse(401, 'error', 'Authorization token missing');
}

$jwt = $matches[1];

try {
    $decoded = JWT::decode($jwt, new Key($secretKey, 'HS256'));
    $user_id = $decoded->user_id;
    $user_role = $decoded->role;
} catch (Exception $e) {
    sendResponse(401, 'error', 'Invalid or expired token');
}
?>
