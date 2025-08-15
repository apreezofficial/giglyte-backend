<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../db_connect.php';
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en" class="dark scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'dark-blue': '#1E3A8A',
                        'light-blue': '#3B82F6',
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif']
                    },
                    backdropBlur: {
                        xs: '2px'
                    }
                }
            }
        }
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const html = document.documentElement;
            const themeToggle = document.getElementById('theme-toggle');
            if (localStorage.getItem('theme') === 'dark') {
                html.classList.add('dark');
            } else {
                html.classList.remove('dark');
            }
            themeToggle.addEventListener('click', () => {
                html.classList.toggle('dark');
                localStorage.setItem('theme', html.classList.contains('dark') ? 'dark' : 'light');
            });
        });
        function toggleMenu() {
            document.getElementById('mobile-menu').classList.toggle('hidden');
        }
    </script>
</head>
<body class="bg-gray-100 dark:bg-gray-900 text-gray-900 dark:text-gray-100 font-sans">
    <header class="sticky top-0 z-50 backdrop-blur-md bg-gradient-to-r from-dark-blue to-light-blue dark:from-gray-800 dark:to-gray-900 shadow-lg">
        <nav class="container mx-auto px-4 py-4 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <div class="bg-white/20 dark:bg-black/20 p-2 rounded-lg backdrop-blur-sm">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                </div>
                <span class="text-2xl font-extrabold text-white tracking-wide">Giglyte</span>
            </div>
            <div class="hidden md:flex items-center gap-6">
                <?php
                $links = [
                    ['Dashboard', 'index.php', 'M4 6a2 2 0 012-2h2...'],
                    ['Users', 'users.php', 'M12 4.354a4 4 0 110 5.292...'],
                    ['Skills & Category', 'categories.php', 'M21 13.255A23.931 23.931 0 0112...'],
                    ['Jobs', 'jobs.php', 'M9 5H7a2 2 0 00-2 2v12a2...'],
                    ['Disputes', 'disputes.php', 'M12 8v4m0 4h.01M21 12...'],
                    ['Payments', 'payments.php', 'M17 9V7a2 2 0 00-2-2H5...'],
                    ['Orders', 'orders.php', 'M3 10h18M3 14h18m-9...'],
                    ['Settings', 'settings.php', 'M10.325 4.317c.426-1.756...']
                ];
                foreach ($links as $l) {
                    echo '<a href="'.$l[1].'" class="flex items-center gap-2 px-3 py-2 rounded-lg text-white/80 hover:text-white hover:bg-white/10 transition-all">';
                    echo '<span>'.$l[0].'</span></a>';
                }
                ?>
                <button id="theme-toggle" class="p-2 rounded-full hover:bg-white/20 transition-all">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646..."></path></svg>
                </button>
            </div>
            <div class="md:hidden">
                <button onclick="toggleMenu()" class="p-2 rounded-lg bg-white/20 dark:bg-black/20 text-white hover:bg-white/30 transition-all">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path></svg>
                </button>
            </div>
        </nav>
        <div id="mobile-menu" class="md:hidden hidden bg-dark-blue/90 dark:bg-gray-900/90 backdrop-blur-md">
            <div class="flex flex-col py-4 space-y-3 px-4">
                <?php
                foreach ($links as $l) {
                    echo '<a href="'.$l[1].'" class="flex items-center gap-2 px-3 py-2 rounded-lg text-white/80 hover:text-white hover:bg-white/10 transition-all">';
                    echo '<span>'.$l[0].'</span></a>';
                }
                ?>
                <button id="theme-toggle" class="flex items-center gap-2 px-3 py-2 rounded-lg text-white/80 hover:text-white hover:bg-white/10 transition-all">
                    <span>Toggle Theme</span>
                </button>
            </div>
        </div>
    </header>
    <main class="container mx-auto px-4 py-6">

    </main>
</body>
</html>