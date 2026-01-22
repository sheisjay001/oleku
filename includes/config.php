<?php
// Database configuration
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
if (php_sapi_name() === 'cli' || $host === 'localhost' || $host === '127.0.0.1' || $host === 'localhost:8000') {
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'oleku_db');
    define('DB_PORT', '3306');
} else {
    define('DB_HOST', 'gateway01.us-east-1.prod.aws.tidbcloud.com');
    define('DB_USER', '4GEPzUw9JQ5eSiB.root');
    define('DB_PASS', 'GaQKNEzzncKlZ7Hs');
    define('DB_NAME', 'test');
    define('DB_PORT', '4000');
}

// Site configuration
define('SITE_NAME', 'Oleku');

if (php_sapi_name() === 'cli') {
    define('SITE_URL', 'http://localhost:8000');
} elseif ($host === 'localhost' || $host === '127.0.0.1') {
    // Assuming the project folder is 'oleku' in htdocs
    define('SITE_URL', 'http://' . $host . '/oleku');
} elseif ($host === 'localhost:8000') {
    define('SITE_URL', 'http://localhost:8000');
} else {
    define('SITE_URL', 'https://oleku.vercel.app'); // Updated to generic or user's hosting URL if known, keeping safe default or user's previous
}

// Admin configuration
define('ADMIN_EMAILS', ['soteriamaa@gmail.com']);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Error reporting (turn off in production)
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Optional secrets
if (file_exists(__DIR__ . '/../config/secrets.php')) {
    require_once __DIR__ . '/../config/secrets.php';
}

// Security Headers
// require_once __DIR__ . '/security.php'; // Already included in most files, but safe to keep or remove if consistent

// Create database connection
try {
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME;
    
    // TiDB requires SSL for secure connections, though some configurations allow non-SSL. 
    // Adding SSL options just in case, but standard PDO usually handles it if the server supports it.
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];
    
    // Check if we need SSL (usually for TiDB Cloud)
    if (DB_PORT === '4000') {
        $options[PDO::MYSQL_ATTR_SSL_CA] = __DIR__ . '/cacert.pem'; // Standard CA cert location if needed, but often works without explicit CA if system has it.
        // For now, let's try without explicit CA unless it fails, but add the flag if possible.
        // Actually, TiDB cloud often requires --ssl-ca. 
        // Let's stick to basic connection first, but with SSL enabled implicitly by client.
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false; // Disable strict verification to avoid issues if cert bundle missing
    }

    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch(PDOException $e) {
    // In production, log this error instead of displaying it
    die("Connection failed: " . $e->getMessage());
}
?>
