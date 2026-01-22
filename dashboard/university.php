<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/security.php';
requireLogin();

// Fetch recent activities
$activities = get_recent_activities($_SESSION['user_id'] ?? '');
?>
<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>University Dashboard | <?php echo SITE_NAME; ?></title>
    
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
    mobileMenuOpen: false,
    toggleTheme() {
        this.darkMode = !this.darkMode;
        localStorage.setItem('darkMode', this.darkMode);
        if (this.darkMode) { document.documentElement.classList.add('dark'); } else { document.documentElement.classList.remove('dark'); }
    }
}" x-init="$watch('darkMode', val => val ? document.documentElement.classList.add('dark') : document.documentElement.classList.remove('dark')); if(darkMode) document.documentElement.classList.add('dark');">

    <nav class="bg-primary-900 text-white shadow-lg sticky top-0 z-50">
        <div class="container mx-auto px-4 py-3 flex justify-between items-center relative">
            <a href="<?php echo SITE_URL; ?>/dashboard/" class="text-2xl font-bold tracking-tight flex items-center gap-2">
                <span class="bg-white text-primary-900 px-2 py-1 rounded-md text-lg">O</span> Oleku
            </a>
            <div class="flex items-center gap-4">
                <div class="hidden md:flex items-center gap-6 text-sm font-medium">
                    <a href="<?php echo SITE_URL; ?>/dashboard/" class="hover:text-primary-200 transition">Home</a>
                    <a href="<?php echo SITE_URL; ?>/jamb-subjects.php" class="hover:text-primary-200 transition">JAMB Prep</a>
                    <?php if (isAdmin()): ?><a href="<?php echo SITE_URL; ?>/admin/" class="hover:text-primary-200 transition">Admin</a><?php endif; ?>
                </div>
                <button @click="toggleTheme()" class="p-2 rounded-full hover:bg-white/10 transition focus:outline-none">
                    <svg x-show="!darkMode" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path></svg>
                    <svg x-show="darkMode" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                </button>
                <button class="md:hidden p-2 text-white focus:outline-none" @click="mobileMenuOpen = !mobileMenuOpen">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path></svg>
                </button>
                <a href="<?php echo SITE_URL; ?>/auth/logout.php" class="hidden md:inline-block text-sm bg-white/10 px-4 py-2 rounded-lg hover:bg-white/20 transition">Logout</a>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div x-show="mobileMenuOpen" 
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-2"
             class="md:hidden absolute top-full left-0 right-0 bg-primary-900 border-t border-white/10 shadow-xl z-50" 
             @click.away="mobileMenuOpen = false"
             x-cloak>
            <div class="flex flex-col p-4 space-y-4 text-center">
                <a href="<?php echo SITE_URL; ?>/dashboard/" class="text-white hover:text-primary-200 transition py-2">Home</a>
                <a href="<?php echo SITE_URL; ?>/jamb-subjects.php" class="text-white hover:text-primary-200 transition py-2">JAMB Prep</a>
                <?php if (isAdmin()): ?><a href="<?php echo SITE_URL; ?>/admin/" class="text-white hover:text-primary-200 transition py-2">Admin</a><?php endif; ?>
                <a href="<?php echo SITE_URL; ?>/auth/logout.php" class="text-white hover:text-primary-200 transition py-2">Logout</a>
            </div>
        </div>
    </nav>

    <section class="bg-primary-900 text-white pb-20 pt-10 px-4 rounded-b-[3rem] shadow-xl relative overflow-hidden">
        <div class="container mx-auto px-4 text-center relative z-10">
            <h1 class="text-4xl md:text-5xl font-bold mb-4">University Dashboard</h1>
            <p class="text-blue-200 mb-6">Access the AI Study Assistant and your course materials.</p>
        </div>
    </section>

    <section class="section bg-white dark:bg-gray-900 -mt-12 relative z-20 pb-16">
        <div class="container mx-auto px-4 max-w-4xl">
            <div class="grid md:grid-cols-2 gap-8">
                <a href="<?php echo SITE_URL; ?>/university/index.php" class="card p-6 dark:bg-gray-800 dark:border-gray-700 hover:shadow-lg transition group">
                    <h3 class="text-xl font-semibold mb-2 group-hover:text-purple-400 transition">AI Study Assistant</h3>
                    <p class="text-gray-600 dark:text-gray-400">Upload materials and get summaries, explanations, and practice.</p>
                </a>
                <div class="card p-6 dark:bg-gray-800 dark:border-gray-700">
                    <h3 class="text-xl font-semibold mb-4">Recent Activity</h3>
                    <?php if (empty($activities)): ?>
                        <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg text-sm text-gray-500 dark:text-gray-400 text-center">
                            No recent activity found. Start by uploading a material!
                        </div>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($activities as $act): ?>
                                <div class="flex items-start gap-3 p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition border border-transparent hover:border-gray-100 dark:hover:border-gray-700">
                                    <div class="bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 p-2 rounded-lg shrink-0">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                    </div>
                                    <div>
                                        <h4 class="font-medium text-gray-900 dark:text-gray-100"><?php echo htmlspecialchars($act['course']); ?></h4>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1"><?php echo time_ago($act['created_at']); ?></p>
                                        <div class="flex flex-wrap gap-2 text-xs">
                                            <?php if ($act['summary_text']): ?>
                                                <span class="bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 px-2 py-0.5 rounded">Summary</span>
                                            <?php endif; ?>
                                            <?php if ($act['question_count'] > 0): ?>
                                                <span class="bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 px-2 py-0.5 rounded"><?php echo $act['question_count']; ?> Questions</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
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
