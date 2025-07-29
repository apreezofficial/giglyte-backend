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

if (!$user || $user['role'] !== 'freelancer') {
    sendResponse(403, 'error', 'Only freelancers can create proposals');
}

// --- Collect input ---
$jobId = intval($_POST['job_id'] ?? 0);
$coverLetter = trim($_POST['cover_letter'] ?? '');
$proposedAmount = floatval($_POST['proposed_amount'] ?? 0);
$estimatedDays = intval($_POST['estimated_days'] ?? 0);

if ($jobId <= 0 || empty($coverLetter) || $proposedAmount <= 0 || $estimatedDays <= 0) {
    sendResponse(400, 'error', 'All fields are required and must be valid');
}

// --- Check if job exists and is open ---
$stmt = $conn->prepare("SELECT status FROM jobs WHERE id = ?");
$stmt->execute([$jobId]);
$job = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$job) {
    sendResponse(404, 'error', 'Job not found');
}
if ($job['status'] !== 'open') {
    sendResponse(400, 'error', 'Job is not open for proposals');
}

// --- Check if proposal already exists ---
$stmt = $conn->prepare("SELECT id FROM proposals WHERE job_id = ? AND freelancer_id = ?");
$stmt->execute([$jobId, $userId]);
if ($stmt->fetch()) {
    sendResponse(409, 'error', 'You have already submitted a proposal for this job');
}

// --- Insert proposal ---
$stmt = $conn->prepare("
    INSERT INTO proposals (job_id, freelancer_id, cover_letter, proposed_amount, estimated_days, status) 
    VALUES (?, ?, ?, ?, ?, 'pending')
");
$stmt->execute([$jobId, $userId, $coverLetter, $proposedAmount, $estimatedDays]);

sendResponse(201, 'success', 'Proposal submitted successfully');
?>