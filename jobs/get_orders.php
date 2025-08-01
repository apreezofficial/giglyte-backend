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

// Get role
$stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) sendResponse(403, 'error', 'User not found');

try {
    if ($user['role'] === 'client') {
        $stmt = $conn->prepare("
            SELECT o.id AS order_id, o.status, o.created_at, o.updated_at,
                   j.title AS job_title,
                   u.username AS freelancer_username, u.full_name AS freelancer_name
            FROM orders o
            JOIN jobs j ON o.job_id = j.id
            JOIN users u ON o.freelancer_id = u.id
            WHERE o.client_id = ?
            ORDER BY o.created_at DESC
        ");
        $stmt->execute([$userId]);
    } else {
        $stmt = $conn->prepare("
            SELECT o.id AS order_id, o.status, o.created_at, o.updated_at,
                   j.title AS job_title,
                   u.username AS client_username, u.full_name AS client_name
            FROM orders o
            JOIN jobs j ON o.job_id = j.id
            JOIN users u ON o.client_id = u.id
            WHERE o.freelancer_id = ?
            ORDER BY o.created_at DESC
        ");
        $stmt->execute([$userId]);
    }

    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    sendResponse(200, 'success', 'Orders fetched successfully', ['orders' => $orders]);

} catch (PDOException $e) {
    sendResponse(500, 'error', 'Database error', ['details' => $e->getMessage()]);
}
?>