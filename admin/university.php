<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/security.php';
requireAdmin();

ensure_material_tables();
$stmt = $pdo->query("SELECT m.id, m.course, m.file_path, m.notes, m.created_at, m.user_id FROM materials m ORDER BY m.created_at DESC LIMIT 50");
$materials = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>University Module | <?php echo SITE_NAME; ?></title>
    
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
            <h1 class="text-3xl md:text-4xl font-bold mb-4">University Module</h1>
            <p class="text-blue-200 mb-6">Monitor student uploads and AI assistant usage.</p>
        </div>
    </section>

    <section class="section bg-white dark:bg-gray-900 -mt-12 relative z-20 pb-16">
        <div class="container mx-auto px-4 max-w-5xl">
            <div class="card bg-white dark:bg-gray-800 dark:border-gray-700 shadow-lg rounded-2xl overflow-hidden mb-8">
                <div class="p-6 border-b border-gray-100 dark:border-gray-700">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <svg class="w-5 h-5 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                        Recent Uploads
                    </h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-700/50 text-gray-600 dark:text-gray-300 text-sm uppercase tracking-wider">
                                <th class="p-4 font-semibold">Course</th>
                                <th class="p-4 font-semibold">File</th>
                                <th class="p-4 font-semibold">Notes</th>
                                <th class="p-4 font-semibold">Uploaded</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            <?php foreach ($materials as $m): ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                                <td class="p-4 text-gray-900 dark:text-white font-medium"><?php echo htmlspecialchars($m['course'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="p-4">
                                    <a class="inline-flex items-center gap-1 text-primary-600 dark:text-primary-400 hover:underline" href="<?php echo htmlspecialchars($m['file_path'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                                        Open File
                                    </a>
                                </td>
                                <td class="p-4 text-gray-600 dark:text-gray-300 text-sm max-w-xs truncate"><?php echo htmlspecialchars(mb_strimwidth($m['notes'], 0, 80, '...'), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="p-4 text-gray-500 dark:text-gray-400 text-sm"><?php echo date('M j, Y', strtotime($m['created_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card p-6 bg-blue-50 dark:bg-gray-800 border border-blue-100 dark:border-gray-700 rounded-2xl flex items-start gap-4">
                <div class="bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 p-3 rounded-xl shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">AI Usage Configuration</h3>
                    <p class="text-gray-600 dark:text-gray-300">
                        Integration available via AI API helpers. Configure credentials in <code class="bg-white dark:bg-gray-900 px-2 py-1 rounded border border-gray-200 dark:border-gray-700 text-sm font-mono">config/secrets.php</code>.
                    </p>
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
