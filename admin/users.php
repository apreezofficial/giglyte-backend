<?php
session_start();
require_once '../db_connect.php'; // Include database connection

// Protect page: redirect to login if not authenticated
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}
error_reporting(1);
// Handle search/filter
$search = $_GET['search'] ?? '';
$role = $_GET['role'] ?? '';
$status = $_GET['status'] ?? '';

$whereClauses = [];
$params = [];
$sql = "SELECT * FROM users";

if (!empty($search)) {
    $whereClauses[] = "(username LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if (!empty($role)) {
    $whereClauses[] = "role = ?";
    $params[] = $role;
}
if (!empty($status)) {
    $whereClauses[] = "active = ?";
    $params[] = $status === 'active' ? 1 : 0;
}

if (!empty($whereClauses)) {
    $sql .= " WHERE " . implode(" AND ", $whereClauses);
}

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_POST['user_id'] ?? '';
    $action = $_POST['action'] ?? '';

    if ($action === 'suspend' || $action === 'reactivate') {
        $newStatus = $action === 'suspend' ? 0 : 1;
        $stmt = $conn->prepare("UPDATE users SET active = ? WHERE id = ?");
        $stmt->execute([$newStatus, $userId]);
    } elseif ($action === 'reset_password') {
        $newPassword = bin2hex(random_bytes(8)); // Generate random 16-char password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hashedPassword, $userId]);
        // Store new password temporarily for display (in production, email it)
        $_SESSION['new_password'][$userId] = $newPassword;
    } elseif ($action === 'change_role') {
        $newRole = $_POST['new_role'] ?? '';
        if (in_array($newRole, ['client', 'freelancer', 'admin'])) {
            $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
            $stmt->execute([$newRole, $userId]);
        }
    }

    // Refresh page to reflect changes
    header("Location: users.php" . (!empty($_GET) ? '?' . http_build_query($_GET) : ''));
    exit;
}

// Fetch user profile if viewing
$profileUser = null;
if (isset($_GET['view'])) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_GET['view']]);
    $profileUser = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fetch services (jobs for clients, freelancer_profiles for freelancers)
    $services = [];
    if ($profileUser['role'] === 'client') {
        $stmt = $conn->prepare("SELECT * FROM jobs WHERE client_id = ?");
        $stmt->execute([$profileUser['id']]);
        $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } elseif ($profileUser['role'] === 'freelancer') {
        $stmt = $conn->prepare("SELECT * FROM freelancer_profiles WHERE user_id = ?");
        $stmt->execute([$profileUser['id']]);
        $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Fetch completed orders
    $stmt = $conn->prepare("SELECT COUNT(*) FROM orders WHERE (client_id = ? OR freelancer_id = ?) AND status = 'completed'");
    $stmt->execute([$profileUser['id'], $profileUser['id']]);
    $completedOrders = $stmt->fetchColumn();
}
?>

<?php include 'header.php'; // Include header ?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Tailwind Configuration for Custom Colors -->
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
        // Theme toggle functionality
        document.addEventListener('DOMContentLoaded', () => {
            const html = document.documentElement;
            const themeToggle = document.getElementById('theme-toggle');
            
            // Check for saved theme preference
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
                if (html.classList.contains('dark')) {
                    localStorage.setItem('theme', 'dark');
                    themeToggle.innerHTML = `
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>`;
                } else {
                    localStorage.setItem('theme', 'light');
                    themeToggle.innerHTML = `
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                        </svg>`;
                }
            });
        });

        // Hamburger menu toggle
        function toggleMenu() {
            const menu = document.getElementById('mobile-menu');
            menu.classList.toggle('hidden');
        }
    </script>
</head>
<main class="container mx-auto px-4 py-6">
    <h1 class="text-3xl font-bold text-dark-blue dark:text-gray-100 mb-6">User Management</h1>

    <!-- Search and Filter Form -->
    <form method="GET" class="mb-6 flex flex-col md:flex-row gap-4">
        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
               placeholder="Search by username or email"
               class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-gray-100">
        <select name="role" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-gray-100">
            <option value="">All Roles</option>
            <option value="client" <?php echo $role === 'client' ? 'selected' : ''; ?>>Client</option>
            <option value="freelancer" <?php echo $role === 'freelancer' ? 'selected' : ''; ?>>Freelancer</option>
            <option value="admin" <?php echo $role === 'admin' ? 'selected' : ''; ?>>Admin</option>
        </select>
        <select name="status" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-gray-100">
            <option value="">All Statuses</option>
            <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
            <option value="suspended" <?php echo $status === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
        </select>
        <button type="submit" class="px-4 py-2 bg-light-blue text-white rounded-md hover:bg-dark-blue">Filter</button>
    </form>

    <!-- User Profile Modal -->
    <?php if ($profileUser): ?>
        <div class="fixed inset-0 bg-gray-800 bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg w-full max-w-2xl">
                <h2 class="text-2xl font-bold text-dark-blue dark:text-gray-100 mb-4"><?php echo htmlspecialchars($profileUser['username']); ?>'s Profile</h2>
                <div class="space-y-4">
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($profileUser['email']); ?></p>
                    <p><strong>Full Name:</strong> <?php echo htmlspecialchars($profileUser['full_name'] ?? 'N/A'); ?></p>
                    <p><strong>Role:</strong> <?php echo htmlspecialchars($profileUser['role']); ?></p>
                    <p><strong>Status:</strong> <?php echo $profileUser['active'] ? 'Active' : 'Suspended'; ?></p>
                    <p><strong>Rating:</strong> <?php echo number_format($profileUser['rating'], 2); ?> (<?php echo $profileUser['total_reviews']; ?> reviews)</p>
                    <p><strong>Completed Orders:</strong> <?php echo $completedOrders; ?></p>
                    <p><strong>Bio:</strong> <?php echo htmlspecialchars($profileUser['bio'] ?? 'N/A'); ?></p>
                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($profileUser['phone'] ?? 'N/A'); ?></p>
                    <p><strong>Location:</strong> <?php echo htmlspecialchars($profileUser['city'] ?? 'N/A'); ?>, <?php echo htmlspecialchars($profileUser['country'] ?? 'N/A'); ?></p>
                    <p><strong>Address:</strong> <?php echo htmlspecialchars($profileUser['address'] ?? 'N/A'); ?></p>
                    <?php if ($profileUser['profile_image']): ?>
                        <p><strong>Profile Image:</strong> <img src="<?php echo htmlspecialchars($profileUser['profile_image']); ?>" alt="Profile Image" class="w-24 h-24 rounded-full"></p>
                    <?php endif; ?>
                    <h3 class="text-xl font-semibold">Services</h3>
                    <?php if ($services): ?>
                        <ul class="list-disc pl-5">
                            <?php foreach ($services as $service): ?>
                                <li><?php echo htmlspecialchars($service['title']); ?> (<?php echo $profileUser['role'] === 'client' ? 'Budget: $' . number_format($service['budget'], 2) : 'Hourly Rate: $' . number_format($service['hourly_rate'], 2); ?>)</li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p>No services listed.</p>
                    <?php endif; ?>
                </div>
                <div class="mt-6 flex justify-end">
                    <a href="users.php<?php echo !empty($_GET) ? '?' . http_build_query(array_diff_key($_GET, ['view' => ''])) : ''; ?>"
                       class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600">Close</a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Users Table -->
    <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-dark-blue dark:bg-gray-700 text-white">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Username</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Email</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Role</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                <?php foreach ($users as $user): ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($user['username']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($user['email']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($user['role']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap"><?php echo $user['active'] ? 'Active' : 'Suspended'; ?></td>
                        <td class="px-6 py-4 whitespace-nowrap space-x-2">
                            <a href="users.php?view=<?php echo $user['id'] . (!empty($_GET) ? '&' . http_build_query($_GET) : ''); ?>"
                               class="text-light-blue hover:text-dark-blue">View</a>
                            <form method="POST" class="inline">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                <input type="hidden" name="action" value="<?php echo $user['active'] ? 'suspend' : 'reactivate'; ?>">
                                <button type="submit" class="text-light-blue hover:text-dark-blue">
                                    <?php echo $user['active'] ? 'Suspend' : 'Reactivate'; ?>
                                </button>
                            </form>
                            <form method="POST" class="inline">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                <input type="hidden" name="action" value="reset_password">
                                <button type="submit" class="text-light-blue hover:text-dark-blue">Reset Password</button>
                            </form>
                            <form method="POST" class="inline">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                <input type="hidden" name="action" value="change_role">
                                <select name="new_role" onchange="this.form.submit()"
                                        class="border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 dark:text-gray-100">
                                    <option value="client" <?php echo $user['role'] === 'client' ? 'selected' : ''; ?>>Client</option>
                                    <option value="freelancer" <?php echo $user['role'] === 'freelancer' ? 'selected' : ''; ?>>Freelancer</option>
                                    <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                </select>
                            </form>
                            <?php if (isset($_SESSION['new_password'][$user['id']])): ?>
                                <span class="text-green-600 dark:text-green-400">
                                    New Password: <?php echo htmlspecialchars($_SESSION['new_password'][$user['id']]); ?>
                                </span>
                                <?php unset($_SESSION['new_password'][$user['id']]); ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>
</body>
</html>