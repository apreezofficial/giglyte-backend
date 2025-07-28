<?php
require_once "db_connect.php";
session_start();
header('Content-Type: application/json');

$step = $_GET['step'] ?? 'one';

function sendResponse($statusCode, $status, $message, $data = []) {
    http_response_code($statusCode);
    echo json_encode(array_merge(['status' => $status, 'message' => $message], $data));
    exit;
}

try {
    switch ($step) {
        case 'one':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST')
                sendResponse(405, 'error', 'Method not allowed');

            $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
            $password = trim($_POST['password']);

            if (!filter_var($email, FILTER_VALIDATE_EMAIL))
                sendResponse(400, 'error', 'Invalid email format');
            if (strlen($password) < 6)
                sendResponse(400, 'error', 'Password too short (min 6 characters)');

            // Check if email already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->rowCount() > 0)
                sendResponse(409, 'error', 'Email already registered');

            $token = bin2hex(random_bytes(16));
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

            $stmt = $conn->prepare("INSERT INTO temp_users (email, password, token) VALUES (?, ?, ?)");
            $stmt->execute([$email, $hashedPassword, $token]);

            sendResponse(200, 'success', 'Step 1 complete, verify email', ['token' => $token]);
            break;
        case 'two':
            $token = $_GET['token'] ?? '';
            if (empty($token)) sendResponse(400, 'error', 'Token required');

            $stmt = $conn->prepare("SELECT * FROM temp_users WHERE token = ?");
            $stmt->execute([$token]);
            $tempUser = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$tempUser)
                sendResponse(404, 'error', 'Invalid or expired token');

            $_SESSION['temp_user_id'] = $tempUser['id'];
            sendResponse(200, 'success', 'Email verified, proceed to next step');
            break;
        case 'three':
            if (!isset($_SESSION['temp_user_id']))
                sendResponse(400, 'error', 'No user session found, restart signup');

            if ($_SERVER['REQUEST_METHOD'] !== 'POST')
                sendResponse(405, 'error', 'Method not allowed');

            $required = ['full_name','username','role'];
            foreach ($required as $field) {
                if (empty($_POST[$field]))
                    sendResponse(400, 'error', ucfirst($field) . ' is required');
            }

            $full_name  = htmlspecialchars(trim($_POST['full_name']));
            $username   = htmlspecialchars(trim($_POST['username']));
            $role       = ($_POST['role'] === 'freelancer') ? 'freelancer' : 'client';

            // Optional fields
            $phone      = $_POST['phone'] ?? null;
            $country    = $_POST['country'] ?? null;
            $city       = $_POST['city'] ?? null;
            $bio        = $_POST['bio'] ?? null;

            // Validate username
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->rowCount() > 0)
                sendResponse(409, 'error', 'Username already taken');

            $stmt = $conn->prepare("SELECT * FROM temp_users WHERE id = ?");
            $stmt->execute([$_SESSION['temp_user_id']]);
            $tempUser = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$tempUser)
                sendResponse(404, 'error', 'Temporary user not found');

            // Finalize Registration
            $conn->beginTransaction();
            $stmt = $conn->prepare("
                INSERT INTO users (username, email, password, full_name, role, phone, country, city, bio) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $username, $tempUser['email'], $tempUser['password'], 
                $full_name, $role, $phone, $country, $city, $bio
            ]);

            $stmt = $conn->prepare("DELETE FROM temp_users WHERE id = ?");
            $stmt->execute([$_SESSION['temp_user_id']]);

            $conn->commit();
            unset($_SESSION['temp_user_id']);

            sendResponse(201, 'success', 'Signup complete, you can login now');
            break;

        default:
            sendResponse(400, 'error', 'Invalid step');
    }

} catch (PDOException $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    sendResponse(500, 'error', 'Database error', ['details' => $e->getMessage()]);
}
?>