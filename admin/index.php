<?php include 'header.php';?>
<?php
$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get counts
$users_count = $conn->query("SELECT COUNT(*) AS total FROM users")->fetch_assoc()['total'];
$jobs_count = $conn->query("SELECT COUNT(*) AS total FROM jobs")->fetch_assoc()['total'];
$disputes_count = $conn->query("SELECT COUNT(*) AS total FROM disputes")->fetch_assoc()['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 dark:bg-gray-900 min-h-screen transition-colors duration-300">
    <div class="max-w-6xl mx-auto p-6">
        
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-100">Dashboard Overview</h1>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">

            <!-- Users -->
            <a href="users.php" class="block p-6 bg-white/80 dark:bg-gray-800/80 rounded-2xl shadow-lg 
                                       hover:scale-105 hover:shadow-2xl transition transform backdrop-blur">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-700 dark:text-gray-300">Total Users</h2>
                        <p class="mt-2 text-3xl font-bold text-indigo-600 dark:text-indigo-400"><?php echo $users_count; ?></p>
                    </div>
                    <div class="p-4 bg-indigo-100 dark:bg-indigo-900 rounded-full text-2xl">👤</div>
                </div>
            </a>

            <!-- Jobs -->
            <a href="jobs.php" class="block p-6 bg-white/80 dark:bg-gray-800/80 rounded-2xl shadow-lg 
                                      hover:scale-105 hover:shadow-2xl transition transform backdrop-blur">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-700 dark:text-gray-300">Total Jobs</h2>
                        <p class="mt-2 text-3xl font-bold text-green-600 dark:text-green-400"><?php echo $jobs_count; ?></p>
                    </div>
                    <div class="p-4 bg-green-100 dark:bg-green-900 rounded-full text-2xl">💼</div>
                </div>
            </a>

            <!-- Disputes -->
            <a href="disputes.php" class="block p-6 bg-white/80 dark:bg-gray-800/80 rounded-2xl shadow-lg 
                                          hover:scale-105 hover:shadow-2xl transition transform backdrop-blur">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-700 dark:text-gray-300">Total Disputes</h2>
                        <p class="mt-2 text-3xl font-bold text-red-600 dark:text-red-400"><?php echo $disputes_count; ?></p>
                    </div>
                    <div class="p-4 bg-red-100 dark:bg-red-900 rounded-full text-2xl">⚠️</div>
                </div>
            </a>

        </div>
    </div>

    <script>
        const toggleBtn = document.getElementById('theme-toggle');
        const htmlEl = document.documentElement;

        toggleBtn.addEventListener('click', () => {
            htmlEl.classList.toggle('dark');
            localStorage.setItem('theme', htmlEl.classList.contains('dark') ? 'dark' : 'light');
        });

        if (localStorage.getItem('theme') === 'dark') {
            htmlEl.classList.add('dark');
        }
    </script>
</body>
</html>