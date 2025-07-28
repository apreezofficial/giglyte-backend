<?php
require_once "db_connect.php";
require __DIR__ . '/vendor/autoload.php';

use Firebase\JWT\JWT;

header('Content-Type: application/json');

function sendResponse($statusCode, $status, $message, $data = []) {
    http_response_code($statusCode);
    echo json_encode(array_merge(['status' => $status, 'message' => $message], $data));
    exit;
}

$secretKey = getenv('JWT_SECRET') ?: "73hdubedrbrujdudyhdhdhy";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(405, 'error', 'Method not allowed');
}

$email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
$password = $_POST['password'] ?? '';

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sendResponse(400, 'error', 'Invalid email format');
}

if (empty($password)) {
    sendResponse(400, 'error', 'Password is required');
}

$stmt = $conn->prepare("SELECT id, username, email, password, role FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || !password_verify($password, $user['password'])) {
    sendResponse(401, 'error', 'Invalid email or password');
}

$payload = [
    'iss' => $_SERVER['HTTP_HOST'],
    'aud' => $_SERVER['HTTP_HOST'],
    'iat' => time(),
    'exp' => time() + (60 * 60 * 24), // 24 hours
    'user_id' => $user['id'],
    'role' => $user['role']
];

$jwt = JWT::encode($payload, $secretKey, 'HS256');

sendResponse(200, 'success', 'Login successful', [
    'token' => $jwt,
    'user' => [
        'id' => $user['id'],
        'username' => $user['username'],
        'email' => $user['email'],
        'role' => $user['role']
    ]
]);
