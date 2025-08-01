<?php
require_once "db_connect.php";
session_start();
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: http://localhost:8080");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
function sendResponse($statusCode, $status, $message, $data = []) {
    http_response_code($statusCode);
    echo json_encode(['status' => $status, 'message' => $message, 'data' => $data]);
    exit;
}

// --- Auth Check ---
if (!isset($_SESSION['user_id'])) {
    sendResponse(401, 'error', 'Unauthorized. Please log in.');
}

$userId = $_SESSION['user_id'];
$orderId = intval($_POST['order_id'] ?? 0);
$action = $_POST['action'] ?? ''; // "accept" or "request_changes"
$feedback = trim($_POST['feedback'] ?? '');

if ($orderId <= 0 || !in_array($action, ['accept', 'request_changes'])) {
    sendResponse(400, 'error', 'Order ID and valid action are required');
}

// --- Check client ownership ---
$stmt = $conn->prepare("SELECT client_id, status FROM orders WHERE id = ?");
$stmt->execute([$orderId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) sendResponse(404, 'error', 'Order not found');
if ($order['client_id'] != $userId) sendResponse(403, 'error', 'Not your order');
if ($order['status'] !== 'delivered') sendResponse(400, 'error', 'Order is not in delivered state');

// --- Handle actions ---
if ($action === 'accept') {
    $newStatus = 'completed';
} else {
    $newStatus = 'revision_requested';
}

$stmt = $conn->prepare("UPDATE orders SET status = ?, client_feedback = ? WHERE id = ?");
$stmt->execute([$newStatus, $feedback, $orderId]);

sendResponse(200, 'success', 'Delivery review updated successfully', [
    'order_id' => $orderId,
    'new_status' => $newStatus,
    'feedback' => $feedback
]);
?>