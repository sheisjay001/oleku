<?php
if (file_exists(__DIR__ . '/../config/database.php')) {
    require_once __DIR__ . '/../config/database.php';
}
if (!defined('DB_HOST')) define('DB_HOST', 'sql101.infinityfree.com');
if (!defined('DB_USER')) define('DB_USER', 'if0_40673034');
if (!defined('DB_PASS')) define('DB_PASS', 'C9fNTBPWFeYNjg');
if (!defined('DB_NAME')) define('DB_NAME', 'if0_40673034_oleku');
define('SITE_NAME', 'Oleku');
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$baseUrl = $scheme . '://' . $host;
if ($host === 'localhost') {
    $baseUrl .= '/oleku';
}
define('SITE_URL', $baseUrl);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(E_ALL);
ini_set('display_errors', 0);
try {
    if (function_exists('getDbConnection')) {
        $pdo = getDbConnection();
    } else {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
