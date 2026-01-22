<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
$page_title = "Privacy Policy | " . SITE_NAME;
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
            <h1 class="text-3xl md:text-4xl font-bold text-gray-900">Privacy Policy</h1>
            <p class="text-gray-600 mt-2">Last Updated: <?php echo date('F Y'); ?></p>
        </div>
    </section>

    <!-- Content -->
    <section class="py-12">
        <div class="container mx-auto px-4 max-w-4xl">
            <div class="bg-white rounded-lg shadow-sm p-8 prose max-w-none">
                <h2 class="text-2xl font-bold mb-4">1. Introduction</h2>
                <p class="mb-4">Welcome to Oleku. We respect your privacy and are committed to protecting your personal data. This privacy policy will inform you as to how we look after your personal data when you visit our website and tell you about your privacy rights and how the law protects you.</p>

                <h2 class="text-2xl font-bold mb-4">2. Data We Collect</h2>
                <p class="mb-4">We may collect, use, store and transfer different kinds of personal data about you which we have grouped together follows:</p>
                <ul class="list-disc pl-6 mb-4 space-y-2">
                    <li><strong>Identity Data</strong> includes first name, last name, username or similar identifier.</li>
                    <li><strong>Contact Data</strong> includes email address.</li>
                    <li><strong>Technical Data</strong> includes internet protocol (IP) address, your login data, browser type and version, time zone setting and location, browser plug-in types and versions, operating system and platform and other technology on the devices you use to access this website.</li>
                    <li><strong>Usage Data</strong> includes information about how you use our website, products and services.</li>
                </ul>

                <h2 class="text-2xl font-bold mb-4">3. How We Use Your Data</h2>
                <p class="mb-4">We will only use your personal data when the law allows us to. Most commonly, we will use your personal data in the following circumstances:</p>
                <ul class="list-disc pl-6 mb-4 space-y-2">
                    <li>Where we need to perform the contract we are about to enter into or have entered into with you.</li>
                    <li>Where it is necessary for our legitimate interests (or those of a third party) and your interests and fundamental rights do not override those interests.</li>
                    <li>Where we need to comply with a legal or regulatory obligation.</li>
                </ul>

                <h2 class="text-2xl font-bold mb-4">4. Data Security</h2>
                <p class="mb-4">We have put in place appropriate security measures to prevent your personal data from being accidentally lost, used or accessed in an unauthorized way, altered or disclosed.</p>

                <h2 class="text-2xl font-bold mb-4">5. Your Legal Rights</h2>
                <p class="mb-4">Under certain circumstances, you have rights under data protection laws in relation to your personal data, including the right to request access, correction, erasure, restriction, transfer, to object to processing, to portability of data and (where the lawful ground of processing is consent) to withdraw consent.</p>

                <h2 class="text-2xl font-bold mb-4">6. Contact Us</h2>
                <p class="mb-4">If you have any questions about this privacy policy or our privacy practices, please contact us at: soteriamaa@gmail.com</p>
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
                    <a href="privacy-policy.php" class="text-white text-sm font-semibold">Privacy Policy</a>
                    <a href="terms-of-service.php" class="text-gray-400 hover:text-white text-sm">Terms of Service</a>
                    <a href="sitemap.php" class="text-gray-400 hover:text-white text-sm">Sitemap</a>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
