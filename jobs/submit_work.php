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
$deliveryMessage = trim($_POST['delivery_message'] ?? '');
$deliveryFile = $_FILES['delivery_file'] ?? null;

if ($orderId <= 0 || $deliveryMessage === '') {
    sendResponse(400, 'error', 'Order ID and delivery message are required');
}

// --- Validate freelancer ownership ---
$stmt = $conn->prepare("SELECT freelancer_id, status FROM orders WHERE id = ?");
$stmt->execute([$orderId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) sendResponse(404, 'error', 'Order not found');
if ($order['freelancer_id'] != $userId) sendResponse(403, 'error', 'Not your order');
if ($order['status'] !== 'in_progress') sendResponse(400, 'error', 'Order not in progress');

// --- Handle file upload (optional) ---
$filePath = null;
if ($deliveryFile && $deliveryFile['error'] === UPLOAD_ERR_OK) {
    $ext = pathinfo($deliveryFile['name'], PATHINFO_EXTENSION);
    $fileName = uniqid('delivery_', true) . '.' . $ext;
    $filePath = "uploads/deliveries/" . $fileName;
    move_uploaded_file($deliveryFile['tmp_name'], $filePath);
}

// --- Update order with delivery ---
$stmt = $conn->prepare("
    UPDATE orders 
    SET delivery_message = ?, delivery_file = ?, status = 'delivered', delivered_at = NOW()
    WHERE id = ?
");
$stmt->execute([$deliveryMessage, $filePath, $orderId]);

sendResponse(200, 'success', 'Work submitted successfully', [
    'order_id' => $orderId,
    'message' => $deliveryMessage,
    'file' => $filePath
]);
?>