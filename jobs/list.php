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

// --- Only freelancers can view for now will edit later ---
if (!isset($_SESSION['user_id'])) {
    sendResponse(401, 'error', 'Unauthorized. Please log in.');
}

$stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    sendResponse(403, 'error', 'Only freelancers can view jobs');
}

// --- Filters ---
$keyword = isset($_GET['search']) ? '%' . $_GET['search'] . '%' : '%';
$minBudget = isset($_GET['min_budget']) ? floatval($_GET['min_budget']) : 0;
$maxBudget = isset($_GET['max_budget']) ? floatval($_GET['max_budget']) : 100000000;
$skillFilter = isset($_GET['skill']) ? trim($_GET['skill']) : '';

try {
    $query = "
        SELECT j.id, j.title, j.description, j.budget, j.status, j.created_at, u.full_name as client_name
        FROM jobs j
        JOIN users u ON j.client_id = u.id
        WHERE j.status = 'open' 
          AND (j.title LIKE :keyword OR j.description LIKE :keyword)
          AND j.budget BETWEEN :minBudget AND :maxBudget
    ";

    $params = [
        ':keyword' => $keyword,
        ':minBudget' => $minBudget,
        ':maxBudget' => $maxBudget
    ];

    if (!empty($skillFilter)) {
        $query .= " AND EXISTS (SELECT 1 FROM job_skills js WHERE js.job_id = j.id AND js.skill = :skillFilter)";
        $params[':skillFilter'] = $skillFilter;
    }

    $query .= " ORDER BY j.created_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // --- Include job skills for each job ---
    foreach ($jobs as &$job) {
        $stmtSkills = $conn->prepare("SELECT skill FROM job_skills WHERE job_id = ?");
        $stmtSkills->execute([$job['id']]);
        $job['skills'] = $stmtSkills->fetchAll(PDO::FETCH_COLUMN);
    }

    sendResponse(200, 'success', 'Jobs fetched successfully', ['jobs' => $jobs]);

} catch (PDOException $e) {
    sendResponse(500, 'error', 'Database error', ['details' => $e->getMessage()]);
}
?>