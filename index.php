<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/security.php';
?>
<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Oleku - AI-Powered Learning for Nigerian Students</title>
    <link rel="icon" type="image/svg+xml" href="assets/images/logo.svg">
    
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#0B2C4D">
    
    <style>
        [x-cloak] { display: none !important; }
        body { font-family: 'Inter', sans-serif; }
    </style>
    
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('sw.js');
            });
        }
    </script>
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
        <div class="container mx-auto px-4 py-3 flex justify-between items-center relative">
            <a href="<?php echo isset($_SESSION['user_id']) ? (isset($_SESSION['user_role']) && $_SESSION['user_role']==='admin' ? 'admin/' : 'dashboard/') : 'index.php'; ?>" class="text-2xl font-bold tracking-tight flex items-center gap-2">
                <span class="bg-white text-primary-900 px-2 py-1 rounded-md text-lg">O</span> Oleku
            </a>
            
            <div class="flex items-center gap-6">
                <div class="hidden md:flex items-center gap-6 text-sm font-medium">
                    <a href="index.php" class="hover:text-primary-200 transition">Home</a>
                    <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="jamb-subjects.php" class="hover:text-primary-200 transition">JAMB Prep</a>
                    <a href="auth/get-started.php?next=university/index.php" class="hover:text-primary-200 transition">University</a>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="<?php echo (isset($_SESSION['user_role']) && $_SESSION['user_role']==='admin') ? 'admin/' : 'dashboard/'; ?>" class="bg-white/10 px-4 py-2 rounded-lg hover:bg-white/20 transition">Dashboard</a>
                    <?php else: ?>
                        <a href="auth/login.php" class="hover:text-primary-200 transition">Login</a>
                        <a href="auth/get-started.php" class="bg-white text-primary-900 px-4 py-2 rounded-lg hover:bg-gray-100 transition font-bold">Get Started</a>
                    <?php endif; ?>
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
                <a href="index.php" class="text-white hover:text-primary-200 transition py-2">Home</a>
                <a href="jamb-subjects.php" class="text-white hover:text-primary-200 transition py-2">JAMB Prep</a>
                <a href="auth/get-started.php?next=university/index.php" class="text-white hover:text-primary-200 transition py-2">University</a>
                
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="<?php echo (isset($_SESSION['user_role']) && $_SESSION['user_role']==='admin') ? 'admin/' : 'dashboard/'; ?>" class="bg-white/10 text-white px-4 py-3 rounded-lg hover:bg-white/20 transition font-medium">Dashboard</a>
                <?php else: ?>
                    <a href="auth/get-started.php" class="bg-white text-primary-900 px-4 py-3 rounded-lg hover:bg-gray-100 transition font-bold">Get Started</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="bg-primary-900 text-white pb-24 pt-16 px-4 rounded-b-[3rem] shadow-xl relative overflow-hidden">
        <div class="absolute inset-0 opacity-10 bg-[url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI0MCIgaGVpZ2h0PSI0MCIgdmlld0JveD0iMCAwIDQwIDQwIj48ZyBmaWxsLXJ1bGU9ImV2ZW5vZGQiPjxwYXRoIGQ9Ik0wIDQwaDQwVjBIMHY0MHptMjAgMjBoMjBWMjBIMHYyMHpNNDAgNDBWMjBIMHYyMGg0MHoiIGZpbGw9IiNmZmYiIGZpbGwtb3BhY2l0eT0iMC4xIi8+PC9nPjwvc3ZnPg==')]"></div>
        <div class="container mx-auto text-center relative z-10 max-w-4xl">
            <span class="inline-block py-1 px-3 rounded-full bg-blue-500/20 text-blue-200 text-sm font-semibold mb-6 border border-blue-400/30">🚀 The #1 AI Learning Platform in Nigeria</span>
            <h1 class="text-4xl md:text-6xl font-bold mb-6 leading-tight text-white">
                <span class="text-yellow-400">Master Your Courses.</span><br/>
                <span class="text-blue-400">Ace Your Exams.</span>
            </h1>
            <p class="text-xl text-blue-100 mb-10 max-w-2xl mx-auto leading-relaxed">
                Personalized AI assistance for JAMB UTME candidates and University students. Study smarter, not harder.
            </p>
            <?php if (!isset($_SESSION['user_id'])): ?>
            <div class="flex flex-col sm:flex-row justify-center gap-4">
                <a href="auth/get-started.php?next=jamb-subjects.php" class="bg-white text-primary-900 hover:bg-gray-100 px-8 py-4 rounded-xl font-bold shadow-lg shadow-blue-900/20 transition transform hover:-translate-y-1 flex items-center justify-center gap-2">
                    <i class="fas fa-graduation-cap"></i> Start JAMB Prep
                </a>
                <a href="auth/get-started.php?next=index.php#university" class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-4 rounded-xl font-bold shadow-lg shadow-blue-600/30 transition transform hover:-translate-y-1 border border-white/20 flex items-center justify-center gap-2">
                    <i class="fas fa-university"></i> University Students
                </a>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-20 bg-white dark:bg-gray-900">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl font-bold text-center mb-16 dark:text-white">How It Works</h2>
            <div class="grid md:grid-cols-3 gap-10">
                <div class="text-center p-8 rounded-2xl bg-gray-50 dark:bg-gray-800 border border-gray-100 dark:border-gray-700 hover:shadow-xl transition duration-300">
                    <div class="w-20 h-20 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center mx-auto mb-6 text-blue-600 dark:text-blue-400 text-3xl">
                        <i class="fas fa-file-upload"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-3 dark:text-white">1. Upload Materials</h3>
                    <p class="text-gray-600 dark:text-gray-400">Take a picture or upload your lecture notes, textbooks, or handouts.</p>
                </div>
                <div class="text-center p-8 rounded-2xl bg-gray-50 dark:bg-gray-800 border border-gray-100 dark:border-gray-700 hover:shadow-xl transition duration-300">
                    <div class="w-20 h-20 bg-purple-100 dark:bg-purple-900/30 rounded-full flex items-center justify-center mx-auto mb-6 text-purple-600 dark:text-purple-400 text-3xl">
                        <i class="fas fa-robot"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-3 dark:text-white">2. AI Analysis</h3>
                    <p class="text-gray-600 dark:text-gray-400">Our AI instantly generates simplified explanations, summaries, and quizzes.</p>
                </div>
                <div class="text-center p-8 rounded-2xl bg-gray-50 dark:bg-gray-800 border border-gray-100 dark:border-gray-700 hover:shadow-xl transition duration-300">
                    <div class="w-20 h-20 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mx-auto mb-6 text-green-600 dark:text-green-400 text-3xl">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-3 dark:text-white">3. Ace Your Exams</h3>
                    <p class="text-gray-600 dark:text-gray-400">Practice with realistic CBT exams and track your progress to success.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- JAMB Section -->
    <section id="jamb" class="py-20 bg-gray-50 dark:bg-gray-800/50">
        <div class="container mx-auto px-4">
            <div class="max-w-5xl mx-auto bg-white dark:bg-gray-800 rounded-3xl shadow-2xl overflow-hidden flex flex-col md:flex-row">
                <div class="md:w-1/2 p-10 flex flex-col justify-center">
                    <span class="text-blue-600 dark:text-blue-400 font-bold tracking-wider text-sm uppercase mb-2">JAMB UTME 2026</span>
                    <h2 class="text-3xl font-bold mb-6 dark:text-white">Comprehensive JAMB Preparation</h2>
                    <p class="mb-8 text-gray-600 dark:text-gray-300 leading-relaxed">
                        Don't just study hard, study smart. Our platform covers the entire JAMB syllabus with AI-powered insights and realistic practice tests.
                    </p>
                    <ul class="space-y-4 mb-8">
                        <li class="flex items-center gap-3 text-gray-700 dark:text-gray-300">
                            <i class="fas fa-check-circle text-green-500"></i> Full Syllabus Coverage
                        </li>
                        <li class="flex items-center gap-3 text-gray-700 dark:text-gray-300">
                            <i class="fas fa-check-circle text-green-500"></i> CBT Simulation
                        </li>
                        <li class="flex items-center gap-3 text-gray-700 dark:text-gray-300">
                            <i class="fas fa-check-circle text-green-500"></i> Instant Performance Analysis
                        </li>
                    </ul>
                    <a href="auth/get-started.php?next=jamb-subjects.php" class="inline-block bg-primary-900 hover:bg-primary-800 text-white px-8 py-3 rounded-xl font-bold transition shadow-lg text-center">
                        Start JAMB Preparation
                    </a>
                </div>
                <div class="md:w-1/2 bg-blue-600 relative overflow-hidden flex items-center justify-center p-10">
                    <div class="absolute inset-0 opacity-20 bg-[url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI0MCIgaGVpZ2h0PSI0MCIgdmlld0JveD0iMCAwIDQwIDQwIj48ZyBmaWxsPSIjZmZmIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiPjxwYXRoIGQ9Ik0wIDQwaDQwVjBIMHY0MHptMjAgMjBoMjBWMjBIMHYyMHpNNDAgNDBWMjBIMHYyMGg0MHoiIGZpbGwtb3BhY2l0eT0iMC4xIi8+PC9nPjwvc3ZnPg==')]"></div>
                    <i class="fas fa-laptop-code text-9xl text-white/20"></i>
                </div>
            </div>
        </div>
    </section>

    <!-- University Section -->
    <section id="university" class="py-20 bg-white dark:bg-gray-900">
        <div class="container mx-auto px-4">
            <div class="max-w-5xl mx-auto bg-primary-900 rounded-3xl shadow-2xl overflow-hidden flex flex-col-reverse md:flex-row text-white">
                <div class="md:w-1/2 bg-purple-900 relative overflow-hidden flex items-center justify-center p-10">
                    <div class="absolute inset-0 opacity-20 bg-[url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI0MCIgaGVpZ2h0PSI0MCIgdmlld0JveD0iMCAwIDQwIDQwIj48ZyBmaWxsPSIjZmZmIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiPjxwYXRoIGQ9Ik0wIDQwaDQwVjBIMHY0MHptMjAgMjBoMjBWMjBIMHYyMHpNNDAgNDBWMjBIMHYyMGg0MHoiIGZpbGwtb3BhY2l0eT0iMC4xIi8+PC9nPjwvc3ZnPg==')]"></div>
                    <i class="fas fa-brain text-9xl text-white/20"></i>
                </div>
                <div class="md:w-1/2 p-10 flex flex-col justify-center">
                    <span class="text-purple-300 font-bold tracking-wider text-sm uppercase mb-2">University Students</span>
                    <h2 class="text-3xl font-bold mb-6">AI Study Assistant</h2>
                    <p class="mb-8 text-blue-100 leading-relaxed">
                        Struggling with complex course materials? Upload your notes and let our AI break them down into simple summaries and explanations.
                    </p>
                    <ul class="space-y-4 mb-8">
                        <li class="flex items-center gap-3 text-blue-100">
                            <i class="fas fa-magic text-purple-400"></i> Smart Summaries
                        </li>
                        <li class="flex items-center gap-3 text-blue-100">
                            <i class="fas fa-question-circle text-purple-400"></i> Generate Practice Questions
                        </li>
                        <li class="flex items-center gap-3 text-blue-100">
                            <i class="fas fa-chalkboard-teacher text-purple-400"></i> 24/7 AI Tutor
                        </li>
                    </ul>
                    <?php if (!isset($_SESSION['user_id'])): ?>
                    <a href="auth/get-started.php?next=university/index.php" class="inline-block bg-white text-primary-900 hover:bg-gray-100 px-8 py-3 rounded-xl font-bold transition shadow-lg text-center">
                        Try AI Assistant
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Ads -->
    <?php include 'includes/ads.php'; ?>

    <!-- Footer -->
    <footer class="bg-primary-900 text-white/80 py-12 border-t border-white/10">
        <div class="container mx-auto px-4">
            <div class="grid md:grid-cols-4 gap-8 mb-8">
                <div class="col-span-1 md:col-span-2">
                    <div class="flex items-center gap-2 mb-4 text-white">
                        <span class="bg-white text-primary-900 px-2 py-1 rounded-md font-bold">O</span>
                        <span class="text-xl font-bold">Oleku</span>
                    </div>
                    <p class="text-sm leading-relaxed max-w-xs mb-6 text-white">
                        Empowering Nigerian students with cutting-edge AI tools for academic excellence. Built with love for excellence.
                    </p>
                    <div class="flex gap-4">
                        <a href="#" class="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center hover:bg-white/20 transition"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center hover:bg-white/20 transition"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center hover:bg-white/20 transition"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
                <div>
                    <h4 class="font-bold text-white mb-4">Platform</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="#" class="hover:text-white transition">Home</a></li>
                        <li><a href="auth/get-started.php?next=jamb-subjects.php" class="hover:text-white transition">JAMB Prep</a></li>
                        <li><a href="auth/get-started.php?next=university/index.php" class="hover:text-white transition">University</a></li>
                        <li><a href="auth/login.php" class="hover:text-white transition">Login</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-bold text-white mb-4">Contact</h4>
                    <ul class="space-y-2 text-sm">
                        <li><i class="fas fa-envelope mr-2"></i> soteriamaa@gmail.com</li>
                        <li><i class="fas fa-phone mr-2"></i> +234 9129122383</li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-white/10 pt-8 text-center text-sm text-white">
                <p>&copy; <?php echo date('Y'); ?> Oleku. All rights reserved.</p>
            </div>
        </div>
    </footer>

</body>
</html>
