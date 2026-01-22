<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
$page_title = "Sitemap | " . SITE_NAME;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container mx-auto px-4">
            <a href="<?php echo isLoggedIn() ? (isAdmin() ? SITE_URL . '/admin/' : SITE_URL . '/dashboard/') : 'index.php'; ?>" class="navbar-brand">Oleku</a>
            <div class="nav-links">
                <a href="<?php echo isLoggedIn() ? (isAdmin() ? SITE_URL . '/admin/' : SITE_URL . '/dashboard/') : SITE_URL . '/'; ?>">Home</a>
                <a href="jamb-subjects.php">JAMB Prep</a>
            </div>
        </div>
    </nav>

    <!-- Header -->
    <section class="bg-white py-12 border-b border-gray-200">
        <div class="container mx-auto px-4 text-center">
            <h1 class="text-3xl md:text-4xl font-bold text-gray-900">Sitemap</h1>
            <p class="text-gray-600 mt-2">Navigate through our website</p>
        </div>
    </section>

    <!-- Content -->
    <section class="py-12">
        <div class="container mx-auto px-4 max-w-4xl">
            <div class="bg-white rounded-lg shadow-sm p-8">
                <div class="grid md:grid-cols-2 gap-8">
                    <!-- Main Pages -->
                    <div>
                        <h2 class="text-xl font-bold mb-4 text-primary border-b pb-2">Main Pages</h2>
                        <ul class="space-y-2 text-gray-700">
                            <li><a href="index.php" class="hover:text-primary"><i class="fas fa-home mr-2 w-5"></i>Home</a></li>
                            <li><a href="jamb-subjects.php" class="hover:text-primary"><i class="fas fa-book mr-2 w-5"></i>JAMB Preparation</a></li>
                            <li><a href="university/index.php" class="hover:text-primary"><i class="fas fa-graduation-cap mr-2 w-5"></i>University AI Assistant</a></li>
                            <li><a href="dashboard/index.php" class="hover:text-primary"><i class="fas fa-tachometer-alt mr-2 w-5"></i>Dashboard</a></li>
                        </ul>
                    </div>

                    <!-- Authentication -->
                    <div>
                        <h2 class="text-xl font-bold mb-4 text-primary border-b pb-2">Authentication</h2>
                        <ul class="space-y-2 text-gray-700">
                            <li><a href="auth/login.php" class="hover:text-primary"><i class="fas fa-sign-in-alt mr-2 w-5"></i>Login</a></li>
                            <li><a href="auth/get-started.php" class="hover:text-primary"><i class="fas fa-user-plus mr-2 w-5"></i>Register</a></li>
                            <li><a href="auth/forgot-password.php" class="hover:text-primary"><i class="fas fa-key mr-2 w-5"></i>Forgot Password</a></li>
                        </ul>
                    </div>

                    <!-- JAMB Subjects -->
                    <div>
                        <h2 class="text-xl font-bold mb-4 text-primary border-b pb-2">JAMB Subjects</h2>
                        <ul class="space-y-2 text-gray-700">
                            <li><a href="jamb-english.php" class="hover:text-primary"><i class="fas fa-language mr-2 w-5"></i>English Language</a></li>
                            <li><a href="jamb-mathematics.php" class="hover:text-primary"><i class="fas fa-calculator mr-2 w-5"></i>Mathematics</a></li>
                            <li><a href="jamb-physics.php" class="hover:text-primary"><i class="fas fa-atom mr-2 w-5"></i>Physics</a></li>
                            <li><a href="jamb-chemistry.php" class="hover:text-primary"><i class="fas fa-flask mr-2 w-5"></i>Chemistry</a></li>
                            <li><a href="jamb-biology.php" class="hover:text-primary"><i class="fas fa-dna mr-2 w-5"></i>Biology</a></li>
                            <li><a href="jamb-economics.php" class="hover:text-primary"><i class="fas fa-chart-line mr-2 w-5"></i>Economics</a></li>
                            <li><a href="jamb-government.php" class="hover:text-primary"><i class="fas fa-landmark mr-2 w-5"></i>Government</a></li>
                            <li><a href="jamb-literature.php" class="hover:text-primary"><i class="fas fa-book-open mr-2 w-5"></i>Literature in English</a></li>
                            <li><a href="jamb-crs.php" class="hover:text-primary"><i class="fas fa-bible mr-2 w-5"></i>CRS</a></li>
                        </ul>
                    </div>

                    <!-- Legal -->
                    <div>
                        <h2 class="text-xl font-bold mb-4 text-primary border-b pb-2">Legal</h2>
                        <ul class="space-y-2 text-gray-700">
                            <li><a href="privacy-policy.php" class="hover:text-primary"><i class="fas fa-shield-alt mr-2 w-5"></i>Privacy Policy</a></li>
                            <li><a href="terms-of-service.php" class="hover:text-primary"><i class="fas fa-file-contract mr-2 w-5"></i>Terms of Service</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="text-white pt-16 pb-8" style="background-color:#0B2C4D">
        <div class="container mx-auto px-4">
            <div class="border-t border-gray-800 pt-8 text-center">
                <p class="text-white text-sm mb-4">
                    &copy; <?php echo date('Y'); ?> Oleku. All rights reserved.
                </p>
                <div class="flex justify-center space-x-6">
                    <a href="privacy-policy.php" class="text-gray-400 hover:text-white text-sm">Privacy Policy</a>
                    <a href="terms-of-service.php" class="text-gray-400 hover:text-white text-sm">Terms of Service</a>
                    <a href="sitemap.php" class="text-white text-sm font-semibold">Sitemap</a>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
