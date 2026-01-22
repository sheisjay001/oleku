<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/security.php';
requireAdmin();
?>
<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | <?php echo SITE_NAME; ?></title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: { 50:'#f0f9ff', 100:'#e0f2fe', 500:'#0ea5e9', 600:'#0284c7', 700:'#0369a1', 800:'#075985', 900:'#0B2C4D' }
                    },
                    fontFamily: { sans: ['Inter', 'sans-serif'] }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
    <style>[x-cloak] { display: none !important; } body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-50 text-gray-900 dark:bg-gray-900 dark:text-gray-100 transition-colors duration-300" x-data="{ 
    darkMode: localStorage.getItem('darkMode') === 'true',
    toggleTheme() {
        this.darkMode = !this.darkMode;
        localStorage.setItem('darkMode', this.darkMode);
        if (this.darkMode) { document.documentElement.classList.add('dark'); } else { document.documentElement.classList.remove('dark'); }
    }
}" x-init="$watch('darkMode', val => val ? document.documentElement.classList.add('dark') : document.documentElement.classList.remove('dark')); if(darkMode) document.documentElement.classList.add('dark');">

    <nav class="bg-primary-900 text-white shadow-lg sticky top-0 z-50">
        <div class="container mx-auto px-4 py-3 flex justify-between items-center">
            <a href="<?php echo SITE_URL; ?>/admin/" class="text-2xl font-bold tracking-tight flex items-center gap-2">
                <span class="bg-white text-primary-900 px-2 py-1 rounded-md text-lg">O</span> Oleku Admin
            </a>
            <div class="flex items-center gap-6">
                <div class="hidden md:flex items-center gap-6 text-sm font-medium">
                    <a href="<?php echo SITE_URL; ?>/dashboard/" class="hover:text-primary-200 transition">User View</a>
                    <a href="<?php echo SITE_URL; ?>/admin/users.php" class="hover:text-primary-200 transition">Users</a>
                    <a href="<?php echo SITE_URL; ?>/admin/jamb.php" class="hover:text-primary-200 transition">JAMB</a>
                    <a href="<?php echo SITE_URL; ?>/admin/university.php" class="hover:text-primary-200 transition">University</a>
                </div>
                <button @click="toggleTheme()" class="p-2 rounded-full hover:bg-white/10 transition focus:outline-none">
                    <svg x-show="!darkMode" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path></svg>
                    <svg x-show="darkMode" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                </button>
                <a href="<?php echo SITE_URL; ?>/auth/logout.php" class="text-sm bg-white/10 px-4 py-2 rounded-lg hover:bg-white/20 transition">Logout</a>
            </div>
        </div>
    </nav>

    <section class="bg-primary-900 text-white pb-20 pt-10 px-4 rounded-b-[3rem] shadow-xl relative overflow-hidden">
        <div class="container mx-auto px-4 text-center relative z-10">
            <h1 class="text-4xl md:text-5xl font-bold mb-4">Admin Dashboard</h1>
            <p class="text-blue-200 mb-6">Manage users and oversee JAMB and University modules.</p>
        </div>
    </section>

    <section class="section bg-white dark:bg-gray-900 -mt-12 relative z-20 pb-16">
        <div class="container mx-auto px-4 max-w-5xl">
            <div class="grid md:grid-cols-3 gap-8">
                <a href="<?php echo SITE_URL; ?>/admin/users.php" class="card p-6 dark:bg-gray-800 dark:border-gray-700 hover:shadow-lg transition group">
                    <h3 class="text-xl font-semibold mb-2 group-hover:text-primary-500 transition">Users</h3>
                    <p class="text-gray-600 dark:text-gray-400">View and manage registered users.</p>
                </a>
                <a href="<?php echo SITE_URL; ?>/admin/jamb.php" class="card p-6 dark:bg-gray-800 dark:border-gray-700 hover:shadow-lg transition group">
                    <h3 class="text-xl font-semibold mb-2 group-hover:text-primary-500 transition">JAMB Module</h3>
                    <p class="text-gray-600 dark:text-gray-400">Configure CBT settings and subjects.</p>
                </a>
                <a href="<?php echo SITE_URL; ?>/admin/university.php" class="card p-6 dark:bg-gray-800 dark:border-gray-700 hover:shadow-lg transition group">
                    <h3 class="text-xl font-semibold mb-2 group-hover:text-primary-500 transition">University Module</h3>
                    <p class="text-gray-600 dark:text-gray-400">Monitor AI assistant usage and uploads.</p>
                </a>
            </div>
        </div>
    </section>

    <footer class="bg-primary-900 text-white/60 py-8 border-t border-white/10">
        <div class="container mx-auto px-4 text-center">
            <p>&copy; <?php echo date('Y'); ?> Oleku. Built for Excellence.</p>
        </div>
    </footer>
</body>
</html>
