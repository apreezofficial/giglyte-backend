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
$jobId = intval($_GET['job_id'] ?? 0);
if ($jobId <= 0) {
    sendResponse(400, 'error', 'Job ID required');
}

// --- Validate job participants ---
$stmt = $conn->prepare("
    SELECT j.client_id, p.freelancer_id
    FROM jobs j
    LEFT JOIN proposals p ON p.job_id = j.id AND p.status = 'accepted'
    WHERE j.id = ?
");
$stmt->execute([$jobId]);
$job = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$job) sendResponse(404, 'error', 'Job not found');

$participants = [$job['client_id'], $job['freelancer_id']];
if (!in_array($userId, $participants)) {
    sendResponse(403, 'error', 'Not authorized to view messages');
}

// --- Mark all messages sent to this user as read ---
$stmt = $conn->prepare("UPDATE messages SET is_read = 1 WHERE job_id = ? AND receiver_id = ?");
$stmt->execute([$jobId, $userId]);

// --- Update last active time for user ---
$stmt = $conn->prepare("UPDATE users SET last_active = NOW() WHERE id = ?");
$stmt->execute([$userId]);

// --- Fetch messages ---
$stmt = $conn->prepare("
    SELECT m.id, m.sender_id, m.receiver_id, m.message, m.is_read, m.created_at, 
           s.username AS sender_name, r.username AS receiver_name
    FROM messages m
    JOIN users s ON m.sender_id = s.id
    JOIN users r ON m.receiver_id = r.id
    WHERE m.job_id = ?
    ORDER BY m.created_at ASC
");
$stmt->execute([$jobId]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

sendResponse(200, 'success', 'Messages fetched & marked as read', ['messages' => $messages]);