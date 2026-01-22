<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/security.php';

// If already logged in, redirect to dashboard
// if (isLoggedIn()) {
//    $defaultNext = (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') ? '/admin/' : '/dashboard/index.php';
//    redirect(SITE_URL . $defaultNext);
// }

$next = sanitize($_GET['next'] ?? ($_POST['next'] ?? ''));
if ($next === '') { $next = '/dashboard/index.php'; }
if (strpos($next, 'http') === 0) { $next = '/'; }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf()) {
        setFlash('error', 'Session expired. Please try again.');
        redirect($_SERVER['REQUEST_URI']);
    }
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if (!isValidEmail($email) || $password === '') {
        setFlash('error', 'Invalid credentials');
        redirect(SITE_URL . '/auth/login.php?next=' . urlencode($next));
    }
    ensure_user_accounts_table();
    $account = get_user_account($email);
    if (!$account || !verify_password_for_email($email, $password)) {
        setFlash('error', 'Incorrect email or password');
        redirect(SITE_URL . '/auth/login.php?next=' . urlencode($next));
    }
    
    // Regenerate session ID to prevent session fixation
    session_regenerate_id(true);
    
    $_SESSION['user_id'] = 'u_' . $account['id'];
    $_SESSION['user_email'] = $email;
    $_SESSION['user_name'] = $account['name'] ?? '';
    $_SESSION['user_role'] = $account['role'] ?? (in_array($email, ADMIN_EMAILS, true) ? 'admin' : 'user');
    $_SESSION['email_verified'] = isEmailVerified($email);
    setFlash('success', 'Logged in');
    
    $defaultNext = (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') ? '/admin/' : '/dashboard/index.php';
    $target = $next ?: $defaultNext;
    
    // Prevent redirect loop if target is login page
    if (strpos($target, 'login.php') !== false) {
        $target = $defaultNext;
    }
    
    $redirectUrl = SITE_URL . '/' . ltrim($target, '/');
    
    // Ensure session is written before redirect
    session_write_close();
    
    redirect($redirectUrl);
}
?>
<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | <?php echo SITE_NAME; ?></title>
    <link rel="icon" type="image/svg+xml" href="<?php echo SITE_URL; ?>/assets/images/logo.svg">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#f0f9ff',
                            100: '#e0f2fe',
                            500: '#0ea5e9',
                            600: '#0284c7',
                            700: '#0369a1',
                            800: '#075985',
                            900: '#0B2C4D',
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
    <link rel="manifest" href="<?php echo SITE_URL; ?>/manifest.json">
    <meta name="theme-color" content="#0B2C4D">
    
    <style>
        [x-cloak] { display: none !important; }
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 text-gray-900 dark:bg-gray-900 dark:text-gray-100 transition-colors duration-300" x-data="{ 
    darkMode: localStorage.getItem('darkMode') === 'true',
    mobileMenuOpen: false,
    toggleTheme() {
        this.darkMode = !this.darkMode;
        localStorage.setItem('darkMode', this.darkMode);
        if (this.darkMode) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    }
}" x-init="$watch('darkMode', val => val ? document.documentElement.classList.add('dark') : document.documentElement.classList.remove('dark')); if(darkMode) document.documentElement.classList.add('dark');">

    <!-- Navigation -->
    <nav class="bg-primary-900 text-white shadow-lg sticky top-0 z-50">
        <div class="container mx-auto px-4 py-3 flex justify-between items-center">
            <a href="<?php echo SITE_URL; ?>/" class="text-2xl font-bold tracking-tight flex items-center gap-2">
                <span class="bg-white text-primary-900 px-2 py-1 rounded-md text-lg">O</span> Oleku
            </a>
            
            <div class="flex items-center gap-6">
                <div class="hidden md:flex items-center gap-6 text-sm font-medium">
                    <a href="<?php echo SITE_URL; ?>/" class="hover:text-primary-200 transition">Home</a>
                    <a href="<?php echo SITE_URL; ?>/jamb-subjects.php" class="hover:text-primary-200 transition">JAMB Prep</a>
                </div>

                <!-- Mobile Menu Button -->
                <button class="md:hidden p-2 text-white focus:outline-none" @click="mobileMenuOpen = !mobileMenuOpen">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path></svg>
                </button>

                <!-- Dark Mode Toggle -->
                <button @click="toggleTheme()" class="p-2 rounded-full hover:bg-white/10 transition focus:outline-none" aria-label="Toggle Dark Mode">
                    <svg x-show="!darkMode" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path></svg>
                    <svg x-show="darkMode" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                </button>
            </div>
        </div>
        
        <!-- Mobile Menu -->
        <div x-show="mobileMenuOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-2" class="md:hidden absolute top-full left-0 right-0 bg-primary-900 border-t border-white/10 shadow-xl z-50" @click.away="mobileMenuOpen = false" x-cloak>
            <div class="flex flex-col p-4 space-y-4 text-center">
                <a href="<?php echo SITE_URL; ?>/" class="text-white hover:text-primary-200 transition py-2">Home</a>
                <a href="<?php echo SITE_URL; ?>/jamb-subjects.php" class="text-white hover:text-primary-200 transition py-2">JAMB Prep</a>
                <a href="<?php echo SITE_URL; ?>/university/index.php" class="text-white hover:text-primary-200 transition py-2">University</a>
                <a href="<?php echo SITE_URL; ?>/auth/get-started.php" class="bg-white text-primary-900 px-4 py-3 rounded-lg hover:bg-gray-100 transition font-bold">Get Started</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <header class="bg-primary-900 text-white pb-20 pt-10 px-4 rounded-b-[3rem] shadow-xl relative overflow-hidden">
        <div class="absolute inset-0 opacity-10 bg-[url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI0MCIgaGVpZ2h0PSI0MCIgdmlld0JveD0iMCAwIDQwIDQwIj48ZyBmaWxsLXJ1bGU9ImV2ZW5vZGQiPjxwYXRoIGQ9Ik0wIDQwaDQwVjBIMHY0MHptMjAgMjBoMjBWMjBIMHYyMHpNNDAgNDBWMjBIMHYyMGg0MHoiIGZpbGw9IiNmZmYiIGZpbGwtb3BhY2l0eT0iMC4xIi8+PC9nPjwvc3ZnPg==')]"></div>
        <div class="container mx-auto px-4 text-center relative z-10">
            <h1 class="text-4xl md:text-5xl font-bold mb-4 text-white">Welcome Back! 👋</h1>
            <p class="text-blue-200 text-lg">Login to access your personalized dashboard.</p>
            <?php displayFlash(); ?>
        </div>
    </header>

    <!-- Login Form Section -->
    <main class="container mx-auto px-4 max-w-md -mt-16 relative z-20 pb-16">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8 border border-gray-100 dark:border-gray-700">
            <form method="post" action="">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="next" value="<?php echo htmlspecialchars($next, ENT_QUOTES, 'UTF-8'); ?>">
                
                <div class="mb-5">
                    <label class="block mb-2 text-sm font-semibold text-gray-700 dark:text-gray-300">Email Address</label>
                    <input class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent outline-none transition" type="email" name="email" required placeholder="you@example.com">
                </div>
                
                <div class="mb-6">
                    <label class="block mb-2 text-sm font-semibold text-gray-700 dark:text-gray-300">Password</label>
                    <input class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent outline-none transition" type="password" name="password" required placeholder="••••••••">
                </div>
                
                <button class="w-full bg-primary-600 hover:bg-primary-700 text-white py-3 rounded-xl font-bold shadow-lg shadow-primary-600/20 transition transform hover:-translate-y-0.5">Login</button>
                
                <div class="mt-6 text-center space-y-2">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Don't have an account?
                        <a class="text-primary-600 dark:text-primary-400 font-semibold hover:underline" href="<?php echo SITE_URL; ?>/auth/get-started.php?next=<?php echo urlencode($next); ?>#register">Sign up free</a>
                    </p>
                    <p class="text-sm">
                        <a class="text-gray-500 dark:text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 transition" href="<?php echo SITE_URL; ?>/auth/forgot-password.php">Forgot password?</a>
                    </p>
                </div>
            </form>
        </div>
    </main>

    <footer class="bg-primary-900 text-white/60 py-8 border-t border-white/10">
        <div class="container mx-auto px-4 text-center">
            <p>&copy; <?php echo date('Y'); ?> Oleku. Built for Excellence.</p>
        </div>
    </footer>
</body>
</html>
