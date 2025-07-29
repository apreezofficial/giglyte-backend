<?php
require_once "db_connect.php";
session_start();
header('Content-Type: application/json');

function sendResponse($statusCode, $status, $message, $data = []) {
    http_response_code($statusCode);
    echo json_encode(['status' => $status, 'message' => $message, 'data' => $data]);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    sendResponse(401, 'error', 'Unauthorized. Please log in.');
}

$userId = $_SESSION['user_id'];
$proposalId = intval($_POST['proposal_id'] ?? 0);

if ($proposalId <= 0) sendResponse(400, 'error', 'Proposal ID required');

// Fetch proposal
$stmt = $conn->prepare("
    SELECT p.id, p.job_id, p.freelancer_id, j.client_id, j.status 
    FROM proposals p 
    JOIN jobs j ON p.job_id = j.id
    WHERE p.id = ?
");
$stmt->execute([$proposalId]);
$proposal = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$proposal) sendResponse(404, 'error', 'Proposal not found');
if ($proposal['client_id'] !== $userId) sendResponse(403, 'error', 'Not authorized to accept this proposal');
if ($proposal['status'] === 'accepted') sendResponse(400, 'error', 'Proposal already accepted');

// Accept proposal
$conn->beginTransaction();

try {
    // Mark proposal accepted
    $conn->prepare("UPDATE proposals SET status = 'accepted' WHERE id = ?")
         ->execute([$proposalId]);

    // Mark job as in progress
    $conn->prepare("UPDATE jobs SET status = 'in_progress' WHERE id = ?")
         ->execute([$proposal['job_id']]);

    // Create order
    $stmt = $conn->prepare("
        INSERT INTO orders (job_id, proposal_id, client_id, freelancer_id, status)
        VALUES (?, ?, ?, ?, 'in_progress')
    ");
    $stmt->execute([$proposal['job_id'], $proposalId, $proposal['client_id'], $proposal['freelancer_id']]);

    $orderId = $conn->lastInsertId();
    $conn->commit();

    sendResponse(200, 'success', 'Proposal accepted and order created', ['order_id' => $orderId]);

} catch (Exception $e) {
    $conn->rollBack();
    sendResponse(500, 'error', 'Could not create order', ['details' => $e->getMessage()]);
}
?>