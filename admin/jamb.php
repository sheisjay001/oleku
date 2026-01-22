<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/security.php';
requireAdmin();

ensure_cbt_settings_table();
$allSubjects = ['English','Mathematics','Physics','Chemistry','Biology','Economics','Government','Literature','CRS'];
$settings = get_cbt_settings();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf()) {
        setFlash('error', 'Security token mismatch. Please try again.');
    } else {
        $english = (int)($_POST['english_count'] ?? 60);
        $other = (int)($_POST['other_count'] ?? 40);
        $subs = $_POST['subjects'] ?? $allSubjects;
        set_cbt_settings($english, $other, $subs);
        setFlash('success', 'CBT settings updated successfully.');
    }
    header('Location: ' . SITE_URL . '/admin/jamb.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JAMB Module Settings | <?php echo SITE_NAME; ?></title>
    
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
            <h1 class="text-3xl md:text-4xl font-bold mb-4">JAMB Module</h1>
            <div class="max-w-2xl mx-auto"><?php displayFlash(); ?></div>
        </div>
    </section>

    <section class="section bg-white dark:bg-gray-900 -mt-12 relative z-20 pb-16">
        <div class="container mx-auto px-4 max-w-4xl">
            <div class="card p-8 bg-white dark:bg-gray-800 dark:border-gray-700 shadow-lg rounded-2xl">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 rounded-full bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center text-primary-600 dark:text-primary-400">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Configure CBT Settings</h2>
                </div>
                
                <form method="post" class="space-y-6">
                    <?php echo csrf_field(); ?>
                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">English Questions Count</label>
                            <input class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent transition" type="number" name="english_count" value="<?php echo (int)$settings['english_count']; ?>" min="1" max="100">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Number of questions for English Language.</p>
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Other Subjects Count</label>
                            <input class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent transition" type="number" name="other_count" value="<?php echo (int)$settings['other_count']; ?>" min="1" max="100">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Number of questions for other subjects.</p>
                        </div>
                    </div>
                    
                    <div class="border-t border-gray-100 dark:border-gray-700 pt-6">
                        <label class="block mb-4 text-sm font-medium text-gray-700 dark:text-gray-300">Available Subjects</label>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                            <?php
                            $current = [];
                            if (!empty($settings['subjects_json'])) {
                                $tmp = json_decode($settings['subjects_json'], true);
                                if (is_array($tmp)) $current = $tmp;
                            } else {
                                $current = $allSubjects;
                            }
                            foreach ($allSubjects as $s):
                            ?>
                            <label class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 dark:border-gray-700 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                                <input type="checkbox" name="subjects[]" value="<?php echo $s; ?>" <?php echo in_array($s, $current, true) ? 'checked' : ''; ?> class="w-4 h-4 text-primary-600 rounded border-gray-300 focus:ring-primary-500">
                                <span class="text-gray-700 dark:text-gray-300"><?php echo $s; ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="pt-4">
                        <button class="w-full md:w-auto px-8 bg-primary-600 hover:bg-primary-700 text-white py-3 rounded-lg font-semibold shadow-md transition transform hover:-translate-y-0.5">Save Configuration</button>
                    </div>
                </form>
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
