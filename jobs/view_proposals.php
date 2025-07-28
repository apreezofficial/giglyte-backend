<?php
require_once "db_connect.php";
session_start();
header('Content-Type: application/json');

function sendResponse($statusCode, $status, $message, $data = []) {
    http_response_code($statusCode);
    echo json_encode(array_merge(['status' => $status, 'message' => $message], $data));
    exit;
}

// --- Check login ---
if (!isset($_SESSION['user_id'])) {
    sendResponse(401, 'error', 'Unauthorized. Please log in.');
}

$userId = $_SESSION['user_id'];

// --- Check role ---
$stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || $user['role'] !== 'client') {
    sendResponse(403, 'error', 'Only clients can view proposals');
}

// --- Get job_id ---
$jobId = intval($_GET['job_id'] ?? 0);
if ($jobId <= 0) {
    sendResponse(400, 'error', 'Job ID required');
}

// --- Check if job belongs to this client ---
$stmt = $conn->prepare("SELECT id FROM jobs WHERE id = ? AND client_id = ?");
$stmt->execute([$jobId, $userId]);
$job = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$job) {
    sendResponse(403, 'error', 'You are not authorized to view proposals for this job');
}

// --- Get proposals ---
$stmt = $conn->prepare("
    SELECT p.id AS proposal_id, p.cover_letter, p.proposed_amount, p.estimated_days, p.status,
           u.id AS freelancer_id, u.username, u.full_name, u.rating, u.profile_image
    FROM proposals p
    INNER JOIN users u ON p.freelancer_id = u.id
    WHERE p.job_id = ?
    ORDER BY p.created_at DESC
");
$stmt->execute([$jobId]);
$proposals = $stmt->fetchAll(PDO::FETCH_ASSOC);

sendResponse(200, 'success', 'Proposals fetched successfully', ['proposals' => $proposals]);
?>