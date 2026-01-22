<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/security.php';
$page_title = "JAMB Chemistry | " . SITE_NAME;
?>
<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    
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

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
            <a href="<?php echo isLoggedIn() ? (isAdmin() ? SITE_URL . '/admin/' : SITE_URL . '/dashboard/') : SITE_URL . '/'; ?>" class="text-2xl font-bold tracking-tight flex items-center gap-2">
                <span class="bg-white text-primary-900 px-2 py-1 rounded-md text-lg">O</span> Oleku
            </a>
            
            <div class="flex items-center gap-6">
                <div class="hidden md:flex items-center gap-6 text-sm font-medium">
                    <a href="<?php echo SITE_URL; ?>/" class="hover:text-primary-200 transition">Home</a>
                    <a href="<?php echo SITE_URL; ?>/jamb-subjects.php" class="text-white font-bold bg-white/10 rounded py-2 px-3">JAMB Prep</a>
                    <?php if (isLoggedIn()): ?>
                        <a href="<?php echo SITE_URL; ?>/university/index.php" class="hover:text-primary-200 transition">University</a>
                        <a href="<?php echo SITE_URL; ?>/dashboard/" class="bg-white/10 px-4 py-2 rounded-lg hover:bg-white/20 transition">Dashboard</a>
                    <?php else: ?>
                        <a href="<?php echo SITE_URL; ?>/auth/login.php" class="hover:text-primary-200 transition">Login</a>
                        <a href="<?php echo SITE_URL; ?>/auth/get-started.php" class="bg-white text-primary-900 px-4 py-2 rounded-lg hover:bg-gray-100 transition font-bold">Get Started</a>
                    <?php endif; ?>
                </div>

                <!-- Dark Mode Toggle -->
                <button @click="toggleTheme()" class="p-2 rounded-full hover:bg-white/10 transition focus:outline-none" aria-label="Toggle Dark Mode">
                    <svg x-show="!darkMode" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path></svg>
                    <svg x-show="darkMode" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                </button>
                
                <!-- Mobile Menu Button -->
                <button class="md:hidden p-2 text-white" @click="mobileMenuOpen = !mobileMenuOpen">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path></svg>
                </button>
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
                <?php if (isLoggedIn()): ?>
                    <a href="<?php echo SITE_URL; ?>/dashboard/" class="text-white hover:text-primary-200 transition py-2">Home</a>
                    <a href="<?php echo SITE_URL; ?>/jamb-subjects.php" class="text-white font-bold bg-white/10 rounded py-2">JAMB Prep</a>
                    <a href="<?php echo SITE_URL; ?>/university/index.php" class="text-white hover:text-primary-200 transition py-2">University</a>
                    <?php if (isAdmin()): ?>
                        <a href="<?php echo SITE_URL; ?>/admin/" class="text-white hover:text-primary-200 transition py-2">Admin</a>
                    <?php endif; ?>
                    <a href="<?php echo SITE_URL; ?>/auth/logout.php" class="bg-white/10 text-white px-4 py-3 rounded-lg hover:bg-white/20 transition font-medium">Logout</a>
                <?php else: ?>
                    <a href="<?php echo SITE_URL; ?>/" class="text-white hover:text-primary-200 transition py-2">Home</a>
                    <a href="<?php echo SITE_URL; ?>/jamb-subjects.php" class="text-white font-bold bg-white/10 rounded py-2">JAMB Prep</a>
                    <a href="<?php echo SITE_URL; ?>/auth/login.php" class="text-white hover:text-primary-200 transition py-2">Login</a>
                    <a href="<?php echo SITE_URL; ?>/auth/get-started.php" class="bg-white text-primary-900 px-4 py-3 rounded-lg hover:bg-gray-100 transition font-bold">Get Started</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <header class="bg-primary-900 text-white pb-20 pt-10 px-4 rounded-b-[3rem] shadow-xl relative overflow-hidden">
        <div class="absolute inset-0 opacity-10 bg-[url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI0MCIgaGVpZ2h0PSI0MCIgdmlld0JveD0iMCAwIDQwIDQwIj48ZyBmaWxsLXJ1bGU9ImV2ZW5vZGQiPjxwYXRoIGQ9Ik0wIDQwaDQwVjBIMHY0MHptMjAgMjBoMjBWMjBIMHYyMHpNNDAgNDBWMjBIMHYyMGg0MHoiIGZpbGw9IiNmZmYiIGZpbGwtb3BhY2l0eT0iMC4xIi8+PC9nPjwvc3ZnPg==')]"></div>
        <div class="container mx-auto max-w-5xl text-center relative z-10">
            <h1 class="text-4xl md:text-6xl font-bold mb-6">Chemistry</h1>
            <p class="text-xl text-blue-200 max-w-3xl mx-auto mb-8">
                Atomic Structure, Periodic Table, Chemical Bonding, Organic Chemistry
            </p>
            <div class="flex flex-wrap justify-center gap-4">
                <a href="<?php echo SITE_URL; ?>/jamb-cbt.php?practice=Chemistry" class="bg-white text-primary-900 hover:bg-gray-100 px-8 py-3 rounded-xl font-bold shadow-lg transition flex items-center gap-2">
                    <i class="fas fa-book-open"></i> Practice Chemistry
                </a>
                <a href="<?php echo SITE_URL; ?>/jamb-cbt.php?setup=1" class="bg-primary-600 hover:bg-primary-700 text-white px-8 py-3 rounded-xl font-bold shadow-lg transition flex items-center gap-2 border border-white/20">
                    <i class="fas fa-clock"></i> Start CBT Exam
                </a>
            </div>
        </div>
    </header>

    <!-- Content Section -->
    <section class="section -mt-12 relative z-20 pb-16">
        <div class="container mx-auto px-4 max-w-4xl">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden p-8">
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center text-blue-600 dark:text-blue-400">
                        <i class="fas fa-list-ul text-2xl"></i>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Key Topics</h2>
                </div>
                
                <div class="grid md:grid-cols-2 gap-4">
                    <?php
                    $topics = [
                        "Atomic Structure",
                        "Periodic Table",
                        "Chemical Bonding",
                        "States of Matter",
                        "Stoichiometry and Chemical Calculations",
                        "Chemical Kinetics and Equilibrium",
                        "Acids, Bases and Salts",
                        "Energetics and Thermochemistry",
                        "Electrochemistry",
                        "Organic Chemistry",
                        "Metals and Non-metals",
                        "Polymers"
                    ];
                    foreach ($topics as $topic): ?>
                    <div class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                        <i class="fas fa-check-circle text-green-500"></i>
                        <span class="text-gray-700 dark:text-gray-300 font-medium"><?php echo $topic; ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

    <footer class="bg-primary-900 text-white/60 py-8 border-t border-white/10 mt-12">
        <div class="container mx-auto px-4 text-center">
            <p>&copy; <?php echo date('Y'); ?> Oleku. Built for Excellence.</p>
        </div>
    </footer>
</body>
</html>
