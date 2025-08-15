<?php
require_once "db_connect.php";
session_start();
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: http://localhost:8080");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$step = $_GET['step'] ?? 'one';

function sendResponse($statusCode, $status, $message, $data = []) {
    http_response_code($statusCode);
    $response = ['status' => $status, 'message' => $message];
    if (!empty($data)) $response['data'] = $data;
    echo json_encode($response);
    exit;
}

try {
    switch ($step) {
        case 'one':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST')
                sendResponse(405, 'error', 'Method not allowed');

            $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
            $password = trim($_POST['password'] ?? '');

            if (!filter_var($email, FILTER_VALIDATE_EMAIL))
                sendResponse(400, 'error', 'Invalid email format');

            if (strlen($password) < 6)
                sendResponse(400, 'error', 'Password too short (min 6 characters)');

            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->rowCount() > 0)
                sendResponse(409, 'error', 'Email already registered');

            $token = rand(111111, 999999);
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

            $stmt = $conn->prepare("INSERT INTO temp_users (email, password, token, verified) VALUES (?, ?, ?, 0)");
            $stmt->execute([$email, $hashedPassword, $token]);

            sendResponse(200, 'success', 'Step 1 complete, verify email', ['token' => $token]);
            break;

        case 'two':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST')
                sendResponse(405, 'error', 'Method not allowed');

            $code = $_POST['code'] ?? '';
            if (empty($code)) sendResponse(400, 'error', 'Code required');

            $stmt = $conn->prepare("SELECT * FROM temp_users WHERE token = ?");
            $stmt->execute([$code]);
            $tempUser = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$tempUser)
                sendResponse(404, 'error', 'Invalid or expired token');

            $stmt = $conn->prepare("UPDATE temp_users SET verified = 1 WHERE token = ?");
            $stmt->execute([$code]);

            sendResponse(200, 'success', 'Email verified, proceed to next step');
            break;

        case 'three':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST')
                sendResponse(405, 'error', 'Method not allowed');

            $code  = trim($_POST['code'] ?? '');
            $email = trim($_POST['email'] ?? '');

            if (empty($code)) sendResponse(400, 'error', 'Verification code required');
            if (empty($email)) sendResponse(400, 'error', 'Email is required');

            $stmt = $conn->prepare("SELECT * FROM temp_users WHERE token = ? AND verified = 1 AND email = ?");
            $stmt->execute([$code, $email]);
            $tempUser = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$tempUser)
                sendResponse(400, 'error', 'Invalid or expired verification code, restart signup');

            $required = ['full_name', 'username', 'role'];
            foreach ($required as $field) {
                if (empty($_POST[$field]))
                    sendResponse(400, 'error', ucfirst($field) . ' is required');
            }

            $full_name = htmlspecialchars(trim($_POST['full_name']));
            $username  = htmlspecialchars(trim($_POST['username']));
            $role      = ($_POST['role'] === 'freelancer') ? 'freelancer' : 'client';

            $phone   = $_POST['phone'] ?? null;
            $country = $_POST['country'] ?? null;
            $city    = $_POST['city'] ?? null;
            $bio     = $_POST['bio'] ?? null;

            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->rowCount() > 0)
                sendResponse(409, 'error', 'Username already taken');

            try {
                $conn->beginTransaction();

                $stmt = $conn->prepare("
                    INSERT INTO users (username, email, password, full_name, role, phone, country, city, bio) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $username,
                    $tempUser['email'],
                    $tempUser['password'],
                    $full_name,
                    $role,
                    $phone,
                    $country,
                    $city,
                    $bio
                ]);

                $stmt = $conn->prepare("DELETE FROM temp_users WHERE token = ?");
                $stmt->execute([$code]);

                $conn->commit();
                sendResponse(200, 'success', 'Signup complete, you can login now');
            } catch (Exception $e) {
                $conn->rollBack();
                sendResponse(500, 'error', 'Server error: ' . $e->getMessage());
            }
            break;

        default:
            sendResponse(400, 'error', 'Invalid step');
    }
} catch (PDOException $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    sendResponse(500, 'error', 'Database error', ['details' => $e->getMessage()]);
}