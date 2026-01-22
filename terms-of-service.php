<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
$page_title = "Terms of Service | " . SITE_NAME;
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
            <h1 class="text-3xl md:text-4xl font-bold text-gray-900">Terms of Service</h1>
            <p class="text-gray-600 mt-2">Last Updated: <?php echo date('F Y'); ?></p>
        </div>
    </section>

    <!-- Content -->
    <section class="py-12">
        <div class="container mx-auto px-4 max-w-4xl">
            <div class="bg-white rounded-lg shadow-sm p-8 prose max-w-none">
                <h2 class="text-2xl font-bold mb-4">1. Acceptance of Terms</h2>
                <p class="mb-4">By accessing and using this website, you accept and agree to be bound by the terms and provision of this agreement. In addition, when using this websites particular services, you shall be subject to any posted guidelines or rules applicable to such services.</p>

                <h2 class="text-2xl font-bold mb-4">2. Description of Service</h2>
                <p class="mb-4">Oleku provides users with access to educational resources, AI-powered study aids, and practice questions. You are responsible for obtaining access to the Service and that access may involve third party fees (such as Internet service provider or airtime charges).</p>

                <h2 class="text-2xl font-bold mb-4">3. User Account</h2>
                <p class="mb-4">To access certain features of the Service, you may be required to register for an account. You are responsible for maintaining the confidentiality of the password and account, and are fully responsible for all activities that occur under your password or account.</p>

                <h2 class="text-2xl font-bold mb-4">4. Intellectual Property</h2>
                <p class="mb-4">All content included on this site, such as text, graphics, logos, button icons, images, audio clips, digital downloads, data compilations, and software, is the property of Oleku or its content suppliers and protected by international copyright laws.</p>

                <h2 class="text-2xl font-bold mb-4">5. Limitation of Liability</h2>
                <p class="mb-4">In no event shall Oleku be liable for any direct, indirect, incidental, special, consequential or exemplary damages, including but not limited to, damages for loss of profits, goodwill, use, data or other intangible losses resulting from the use or the inability to use the service.</p>

                <h2 class="text-2xl font-bold mb-4">6. Changes to Terms</h2>
                <p class="mb-4">Oleku reserves the right to update or modify these Terms of Service at any time without prior notice. Your use of the Website following any such change constitutes your agreement to follow and be bound by the Terms of Service as changed.</p>
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
                    <a href="terms-of-service.php" class="text-white text-sm font-semibold">Terms of Service</a>
                    <a href="sitemap.php" class="text-gray-400 hover:text-white text-sm">Sitemap</a>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
