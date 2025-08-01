<?php
require_once "db_connect.php";
session_start();
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: http://localhost:8080");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
function sendResponse($statusCode, $status, $message, $data = []) {
    http_response_code($statusCode);
    echo json_encode(array_merge(['status' => $status, 'message' => $message], $data));
    exit;
}

// --- Auth Check ---
if (!isset($_SESSION['user_id'])) {
    sendResponse(401, 'error', 'Unauthorized. Please log in.');
}

$userId = $_SESSION['user_id'];

// --- Fetch Wallet ---
$stmt = $conn->prepare("SELECT balance FROM wallets WHERE user_id = ?");
$stmt->execute([$userId]);
$wallet = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$wallet) {
    // create wallet if not exist
    $stmt = $conn->prepare("INSERT INTO wallets (user_id, balance) VALUES (?, 0)");
    $stmt->execute([$userId]);
    $wallet = ['balance' => 0.00];
}

// --- Fetch Transactions ---
$stmt = $conn->prepare("SELECT type, amount, description, created_at FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
$stmt->execute([$userId]);
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

sendResponse(200, 'success', 'Wallet fetched successfully', [
    'balance' => $wallet['balance'],
    'transactions' => $transactions
]);
?>