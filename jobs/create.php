<?php
require_once "../db_connect.php";
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

try {
    // --- Get user role ---
    $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        sendResponse(403, 'error', 'Only Users can create jobs');
    }

    // --- Check method ---
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse(405, 'error', 'Method not allowed');
    }

    // --- Collect & validate data ---
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $budget      = $_POST['budget'] ?? 0.00;
    $skills      = $_POST['skills'] ?? ''; // comma-separated

    if (empty($title) || empty($description)) {
        sendResponse(400, 'error', 'Title and description are required');
    }
    if (!is_numeric($budget) || $budget < 0) {
        sendResponse(400, 'error', 'Invalid budget');
    }

    // --- Insert job ---
    $stmt = $conn->prepare("
        INSERT INTO jobs (client_id, title, description, budget, status, created_at) 
        VALUES (?, ?, ?, ?, 'open', NOW())
    ");
    $stmt->execute([$_SESSION['user_id'], $title, $description, $budget]);

    $jobId = $conn->lastInsertId();
    $addedSkills = [];
    if (!empty($skills)) {
        $skillsArray = array_filter(array_map('trim', explode(',', $skills)));
        $stmtSkill = $conn->prepare("INSERT INTO job_skills (job_id, skill) VALUES (?, ?)");
        foreach ($skillsArray as $skill) {
            $stmtSkill->execute([$jobId, $skill]);
            $addedSkills[] = $skill;
        }
    }

    sendResponse(200, 'success', 'Job created successfully', [
        'job_id' => $jobId,
        'skills' => $addedSkills
    ]);

} catch (PDOException $e) {
    sendResponse(500, 'error', 'Database error', ['details' => $e->getMessage()]);
}
?>