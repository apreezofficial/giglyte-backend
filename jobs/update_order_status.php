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

if (!isset($_SESSION['user_id'])) {
    sendResponse(401, 'error', 'Unauthorized. Please log in.');
}

$userId = $_SESSION['user_id'];
$orderId = intval($_POST['order_id'] ?? 0);
$newStatus = $_POST['status'] ?? '';

if ($orderId <= 0 || !in_array($newStatus, ['in_progress', 'completed', 'cancelled'])) {
    sendResponse(400, 'error', 'Invalid order ID or status');
}

// --- Check order exists ---
$stmt = $conn->prepare("
    SELECT client_id, freelancer_id, status 
    FROM orders 
    WHERE id = ?
");
$stmt->execute([$orderId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    sendResponse(404, 'error', 'Order not found');
}

// --- Only participants can update ---
if ($order['client_id'] != $userId && $order['freelancer_id'] != $userId) {
    sendResponse(403, 'error', 'You are not authorized to update this order');
}

// --- Rules (example) ---
// - Freelancer cannot mark as cancelled (only client can cancel)
// - Client cannot mark as completed (only freelancer can mark completed work)
if ($newStatus == 'cancelled' && $order['client_id'] != $userId) {
    sendResponse(403, 'error', 'Only client can cancel order');
}
if ($newStatus == 'completed' && $order['freelancer_id'] != $userId) {
    sendResponse(403, 'error', 'Only freelancer can mark completed');
}

// --- Update order status ---
$stmt = $conn->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
$stmt->execute([$newStatus, $orderId]);

sendResponse(200, 'success', 'Order status updated', ['order_id' => $orderId, 'status' => $newStatus]);
?>