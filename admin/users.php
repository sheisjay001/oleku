<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/security.php';
requireAdmin();

ensure_user_accounts_table();
$created = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf()) {
        setFlash('error', 'Security token mismatch. Please try again.');
    } else {
        $name = sanitize($_POST['name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'user';

        if (!isValidEmail($email) || $password === '') {
            setFlash('error', 'Provide a valid email and password.');
        } else {
            [$ok, $msg] = validatePassword($password);
            if (!$ok) {
                setFlash('error', $msg);
            } elseif (get_user_account($email)) {
                setFlash('error', 'User already exists.');
            } else {
                create_user_account($email, $name, $password, $role === 'admin' ? 'admin' : 'user');
                setFlash('success', 'User created successfully.');
                $created = true;
            }
        }
    }
    header('Location: ' . SITE_URL . '/admin/users.php');
    exit;
}

$stmt = $pdo->query("SELECT id, email, name, role, created_at FROM user_accounts ORDER BY created_at DESC LIMIT 100");
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users | <?php echo SITE_NAME; ?></title>
    
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
            <h1 class="text-3xl md:text-4xl font-bold mb-4">User Management</h1>
            <div class="max-w-2xl mx-auto"><?php displayFlash(); ?></div>
        </div>
    </section>

    <section class="section bg-white dark:bg-gray-900 -mt-12 relative z-20 pb-16">
        <div class="container mx-auto px-4 max-w-6xl">
            <div class="grid md:grid-cols-3 gap-8">
                <!-- Create User Form -->
                <div class="md:col-span-1">
                    <div class="card p-6 bg-white dark:bg-gray-800 dark:border-gray-700 shadow-lg rounded-2xl sticky top-24">
                        <h2 class="text-xl font-bold mb-6 text-gray-900 dark:text-white flex items-center gap-2">
                            <svg class="w-5 h-5 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
                            Add New User
                        </h2>
                        <form method="post" class="space-y-4">
                            <?php echo csrf_field(); ?>
                            <div>
                                <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">Full Name</label>
                                <input class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent transition" type="text" name="name" required placeholder="John Doe">
                            </div>
                            <div>
                                <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">Email Address</label>
                                <input class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent transition" type="email" name="email" required placeholder="john@example.com">
                            </div>
                            <div>
                                <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">Password</label>
                                <input class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent transition" type="password" name="password" required placeholder="••••••••">
                            </div>
                            <div>
                                <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">Role</label>
                                <select class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent transition" name="role">
                                    <option value="user">User</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                            <button class="w-full bg-primary-600 hover:bg-primary-700 text-white py-2.5 rounded-lg font-semibold shadow-md transition transform hover:-translate-y-0.5">Create User</button>
                        </form>
                    </div>
                </div>

                <!-- Users List -->
                <div class="md:col-span-2">
                    <div class="card bg-white dark:bg-gray-800 dark:border-gray-700 shadow-lg rounded-2xl overflow-hidden">
                        <div class="p-6 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center">
                            <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                <svg class="w-5 h-5 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                                Recent Users
                            </h2>
                            <span class="text-sm text-gray-500 dark:text-gray-400">Last 100 registered</span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-gray-50 dark:bg-gray-700/50 text-gray-600 dark:text-gray-300 text-sm uppercase tracking-wider">
                                        <th class="p-4 font-semibold">Email</th>
                                        <th class="p-4 font-semibold">Name</th>
                                        <th class="p-4 font-semibold">Role</th>
                                        <th class="p-4 font-semibold">Joined</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                    <?php foreach ($users as $u): ?>
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                                        <td class="p-4 text-gray-900 dark:text-white font-medium"><?php echo htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td class="p-4 text-gray-600 dark:text-gray-300"><?php echo htmlspecialchars($u['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td class="p-4">
                                            <span class="px-2.5 py-1 rounded-full text-xs font-semibold <?php echo $u['role'] === 'admin' ? 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300' : 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300'; ?>">
                                                <?php echo ucfirst($u['role']); ?>
                                            </span>
                                        </td>
                                        <td class="p-4 text-gray-500 dark:text-gray-400 text-sm"><?php echo date('M j, Y', strtotime($u['created_at'])); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
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
