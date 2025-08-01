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

// --- Check login ---
if (!isset($_SESSION['user_id'])) {
    sendResponse(401, 'error', 'Unauthorized. Please log in.');
}

$userId = $_SESSION['user_id'];

// --- Check role ---
$stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    sendResponse(403, 'error', 'Only freelancers can apply to jobs');
}

// --- Only POST allowed ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(405, 'error', 'Method not allowed');
}

if ($_SERVER['CONTENT_TYPE'] === 'application/json') {
    $data = json_decode(file_get_contents('php://input'), true);
    $jobId = intval($data['job_id'] ?? 0);
    $coverLetter = trim($data['cover_letter'] ?? '');
    $proposedAmount = floatval($data['proposed_amount'] ?? 0);
    $estimatedDays = intval($data['estimated_days'] ?? 0);
} else {
    $jobId = intval($_POST['job_id'] ?? 0);
    $coverLetter = trim($_POST['cover_letter'] ?? '');
    $proposedAmount = floatval($_POST['proposed_amount'] ?? 0);
    $estimatedDays = intval($_POST['estimated_days'] ?? 0);
}

if ($jobId <= 0 || empty($coverLetter) || $proposedAmount <= 0 || $estimatedDays <= 0) {
    sendResponse(400, 'error', 'All fields are required');
}

// --- Check job exists and is open ---
$stmt = $conn->prepare("SELECT * FROM jobs WHERE id = ? AND status = 'open'");
$stmt->execute([$jobId]);
$job = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$job) {
    sendResponse(404, 'error', 'Job not found or not open for proposals');
}

// --- Check if already applied ---
$stmt = $conn->prepare("SELECT id FROM proposals WHERE job_id = ? AND freelancer_id = ?");
$stmt->execute([$jobId, $userId]);
if ($stmt->rowCount() > 0) {
    sendResponse(409, 'error', 'You already applied to this job');
}

// --- Insert proposal ---
$stmt = $conn->prepare("
    INSERT INTO proposals (job_id, freelancer_id, cover_letter, proposed_amount, estimated_days, status) 
    VALUES (?, ?, ?, ?, ?, 'pending')
");
$stmt->execute([$jobId, $userId, $coverLetter, $proposedAmount, $estimatedDays]);

sendResponse(200, 'success', 'Proposal submitted successfully');
?>