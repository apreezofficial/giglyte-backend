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

// --- Input ---
$jobId = intval($_POST['job_id'] ?? 0);
$receiverId = intval($_POST['receiver_id'] ?? 0);
$message = trim($_POST['message'] ?? '');

if ($jobId <= 0 || $receiverId <= 0 || $message == '') {
    sendResponse(400, 'error', 'All fields are required');
}

// --- Validate if both users are part of this job ---
$stmt = $conn->prepare("
    SELECT j.client_id, p.freelancer_id
    FROM jobs j
    LEFT JOIN proposals p ON p.job_id = j.id AND p.status = 'accepted'
    WHERE j.id = ?
");
$stmt->execute([$jobId]);
$job = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$job) {
    sendResponse(404, 'error', 'Job not found');
}

// --- Check if sender & receiver belong to job ---
$participants = [$job['client_id'], $job['freelancer_id']];
if (!in_array($userId, $participants) || !in_array($receiverId, $participants)) {
    sendResponse(403, 'error', 'You are not authorized to send message on this job');
}

// --- Save Message ---
$stmt = $conn->prepare("INSERT INTO messages (job_id, sender_id, receiver_id, message) VALUES (?, ?, ?, ?)");
$stmt->execute([$jobId, $userId, $receiverId, $message]);

sendResponse(200, 'success', 'Message sent successfully', [
    'message_id' => $conn->lastInsertId()
]);