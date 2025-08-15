<?php
session_start();
require_once '../db_connect.php'; // Include database connection

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // Validate input
    if (empty($username) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        // Check user credentials
        $stmt = $conn->prepare("SELECT * FROM admins WHERE username = ? AND role = 'admin' AND status = 'active'");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            // Successful login
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_username'] = $user['username'];
            header("Location: index.php");
            exit;
        } else {
            $error = "Invalid username or password.";
        }
    }
}

// Protect dashboard: redirect to login if not authenticated
if (isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - JobPost</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'dark-blue': '#1E3A8A',
                        'light-blue': '#3B82F6',
                    }
                }
            }
        }
    </script>
    <!-- Theme Toggle Script -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const html = document.documentElement;
            const themeToggle = document.getElementById('theme-toggle');
            
            if (localStorage.getItem('theme') === 'dark') {
                html.classList.add('dark');
                themeToggle.innerHTML = `
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>`;
            } else {
                html.classList.remove('dark');
                themeToggle.innerHTML = `
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                    </svg>`;
            }

            themeToggle.addEventListener('click', () => {
                html.classList.toggle('dark');
                localStorage.setItem('theme', html.classList.contains('dark') ? 'dark' : 'light');
                themeToggle.innerHTML = html.classList.contains('dark')
                    ? `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>`
                    : `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                    </svg>`;
            });
        });
    </script>
</head>
<body class="bg-gray-100 dark:bg-gray-900 text-gray-900 dark:text-gray-100 flex items-center justify-center min-h-screen">
    <div class="w-full max-w-md p-8 bg-white dark:bg-gray-800 rounded-lg shadow-lg">
        <h2 class="text-2xl font-bold text-center text-dark-blue dark:text-gray-100 mb-6">Admin Login</h2>
        
        <!-- Error Message -->
        <?php if (isset($error)): ?>
            <div class="mb-4 p-3 bg-red-100 dark:bg-red-900 text-red-700 dark:text-red-200 rounded">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Login Form -->
        <form method="POST" class="space-y-6">
            <div>
                <label for="username" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Username</label>
                <input type="text" id="username" name="username" required
                       class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-light-blue focus:border-light-blue dark:bg-gray-700 dark:text-gray-100"
                       placeholder="Enter your username">
            </div>
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Password</label>
                <input type="password" id="password" name="password" required
                       class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-light-blue focus:border-light-blue dark:bg-gray-700 dark:text-gray-100"
                       placeholder="Enter your password">
            </div>
            <div>
                <button type="submit"
                        class="w-full py-2 px-4 bg-light-blue text-white font-semibold rounded-md hover:bg-dark-blue focus:outline-none focus:ring-2 focus:ring-light-blue">
                    Log In
                </button>
            </div>
        </form>

        <!-- Theme Toggle Button -->
        <div class="mt-6 flex justify-center">
            <button id="theme-toggle" class="p-2 rounded-full hover:bg-light-blue">
                <!-- Icon changes based on theme -->
            </button>
        </div>
    </div>
</body>
</html>