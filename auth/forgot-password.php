<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/security.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf()) {
        setFlash('error', 'Security token mismatch. Please try again.');
        redirect(SITE_URL . '/auth/forgot-password.php');
    }

    $email = sanitize($_POST['email'] ?? '');
    
    // Check if user exists (mocked logic as we don't have full email sending yet)
    // In a real app, you would generate a token and email it.
    // For now, we will simulate the process or just redirect.
    
    if (!isValidEmail($email)) {
        setFlash('error', 'Please enter a valid email address.');
    } else {
        // Mock success for now
        setFlash('success', 'If an account exists, a reset link has been sent (Simulated).');
    }
    redirect(SITE_URL . '/auth/forgot-password.php');
}
?>
<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | <?php echo SITE_NAME; ?></title>
    <link rel="icon" type="image/svg+xml" href="<?php echo SITE_URL; ?>/assets/images/logo.svg">
    
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
        <div class="container mx-auto px-4 py-3 flex justify-between items-center">
            <a href="<?php echo SITE_URL; ?>/" class="text-2xl font-bold tracking-tight flex items-center gap-2">
                <span class="bg-white text-primary-900 px-2 py-1 rounded-md text-lg">O</span> Oleku
            </a>
            
            <div class="flex items-center gap-6">
                <div class="hidden md:flex items-center gap-6 text-sm font-medium">
                    <a href="<?php echo SITE_URL; ?>/" class="hover:text-primary-200 transition">Home</a>
                    <a href="<?php echo SITE_URL; ?>/jamb-subjects.php" class="hover:text-primary-200 transition">JAMB Prep</a>
                    <a href="<?php echo SITE_URL; ?>/university/index.php" class="hover:text-primary-200 transition">University</a>
                    <a href="<?php echo SITE_URL; ?>/auth/login.php" class="bg-white text-primary-900 px-4 py-2 rounded-lg hover:bg-gray-100 transition font-bold">Login</a>
                </div>

                <!-- Dark Mode Toggle -->
                <button @click="toggleTheme()" class="p-2 rounded-full hover:bg-white/10 transition focus:outline-none" aria-label="Toggle Dark Mode">
                    <svg x-show="!darkMode" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path></svg>
                    <svg x-show="darkMode" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                </button>

                <!-- Mobile Menu Button -->
                <button class="md:hidden p-2 text-white focus:outline-none" @click="mobileMenuOpen = !mobileMenuOpen">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path></svg>
                </button>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div x-show="mobileMenuOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-2" class="md:hidden absolute top-full left-0 right-0 bg-primary-900 border-t border-white/10 shadow-xl z-50" @click.away="mobileMenuOpen = false" x-cloak>
            <div class="flex flex-col p-4 space-y-4 text-center">
                <a href="<?php echo SITE_URL; ?>/" class="text-white hover:text-primary-200 transition py-2">Home</a>
                <a href="<?php echo SITE_URL; ?>/jamb-subjects.php" class="text-white hover:text-primary-200 transition py-2">JAMB Prep</a>
                <a href="<?php echo SITE_URL; ?>/university/index.php" class="text-white hover:text-primary-200 transition py-2">University</a>
                <a href="<?php echo SITE_URL; ?>/auth/login.php" class="bg-white text-primary-900 px-4 py-3 rounded-lg hover:bg-gray-100 transition font-bold">Login</a>
            </div>
        </div>
    </nav>

    <div class="min-h-[calc(100vh-140px)] flex items-center justify-center p-4">
        <div class="w-full max-w-md bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden border border-gray-100 dark:border-gray-700">
            <div class="p-8">
                <div class="text-center mb-8">
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">Forgot Password?</h1>
                    <p class="text-gray-600 dark:text-gray-400">Enter your email to reset your password.</p>
                </div>

                <?php displayFlash(); ?>

                <form method="post" class="space-y-6">
                    <?php echo csrf_field(); ?>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email Address</label>
                        <input type="email" name="email" required class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent outline-none transition" placeholder="you@example.com">
                    </div>

                    <button type="submit" class="w-full bg-primary-600 hover:bg-primary-700 text-white font-bold py-3 rounded-xl shadow-lg hover:shadow-xl transition transform hover:-translate-y-0.5">
                        Send Reset Link
                    </button>
                </form>

                <p class="mt-8 text-center text-sm text-gray-600 dark:text-gray-400">
                    Remember your password? 
                    <a href="<?php echo SITE_URL; ?>/auth/login.php" class="text-primary-600 dark:text-primary-400 font-semibold hover:underline">Login</a>
                </p>
            </div>
        </div>
    </div>

    <footer class="bg-white dark:bg-gray-900 py-6 border-t border-gray-100 dark:border-gray-800">
        <div class="container mx-auto px-4 text-center text-gray-500 dark:text-gray-400 text-sm">
            &copy; <?php echo date('Y'); ?> Oleku. Built for Excellence.
        </div>
    </footer>
</body>
</html>
