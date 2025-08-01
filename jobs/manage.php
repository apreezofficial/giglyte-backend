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

$action = $_GET['action'] ?? '';

try {
    // --- Verify user role ---
    $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || $user['role'] !== 'client') {
        sendResponse(403, 'error', 'Only clients can manage jobs');
    }

    switch ($action) {
        case 'edit':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                sendResponse(405, 'error', 'Method not allowed');
            }

            $jobId = $_POST['job_id'] ?? 0;
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $budget = $_POST['budget'] ?? 0.00;
            $skills = $_POST['skills'] ?? '';

            if (empty($title) || empty($description)) {
                sendResponse(400, 'error', 'Title and description are required');
            }

            // --- Check job ownership ---
            $stmt = $conn->prepare("SELECT * FROM jobs WHERE id = ? AND client_id = ?");
            $stmt->execute([$jobId, $_SESSION['user_id']]);
            $job = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$job) {
                sendResponse(404, 'error', 'Job not found or not owned by you');
            }

            // --- Update job ---
            $stmt = $conn->prepare("UPDATE jobs SET title=?, description=?, budget=?, updated_at=NOW() WHERE id=?");
            $stmt->execute([$title, $description, $budget, $jobId]);

            // --- Update skills ---
            $conn->prepare("DELETE FROM job_skills WHERE job_id=?")->execute([$jobId]);
            if (!empty($skills)) {
                $skillsArray = array_filter(array_map('trim', explode(',', $skills)));
                $stmtSkill = $conn->prepare("INSERT INTO job_skills (job_id, skill) VALUES (?, ?)");
                foreach ($skillsArray as $skill) {
                    $stmtSkill->execute([$jobId, $skill]);
                }
            }

            sendResponse(200, 'success', 'Job updated successfully');
            break;

        case 'delete':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                sendResponse(405, 'error', 'Method not allowed');
            }
            $jobId = $_POST['job_id'] ?? 0;

            // --- Check job ownership ---
            $stmt = $conn->prepare("SELECT * FROM jobs WHERE id = ? AND client_id = ?");
            $stmt->execute([$jobId, $_SESSION['user_id']]);
            $job = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$job) {
                sendResponse(404, 'error', 'Job not found or not owned by you');
            }

            $conn->prepare("DELETE FROM jobs WHERE id = ?")->execute([$jobId]);
            sendResponse(200, 'success', 'Job deleted successfully');
            break;

        default:
            sendResponse(400, 'error', 'Invalid action');
    }

} catch (PDOException $e) {
    sendResponse(500, 'error', 'Database error', ['details' => $e->getMessage()]);
}
?>