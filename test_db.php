<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load database configuration
require 'config/database.php';

// Test connection
try {
    echo "<h2>Testing Database Connection</h2>";
    echo "Host: " . DB_HOST . "<br>";
    echo "Database: " . DB_NAME . "<br>";
    echo "User: " . DB_USER . "<br>";
    
    // Test connection
    $pdo = getDbConnection();
    
    // Test query
    $stmt = $pdo->query("SELECT 1");
    $result = $stmt->fetch();
    
    echo "<p style='color:green;'>✅ Database connection successful!</p>";
    
} catch (PDOException $e) {
    echo "<h3 style='color:red;'>❌ Connection failed</h3>";
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>Error Code:</strong> " . $e->getCode() . "</p>";
    
    // Additional debugging
    echo "<h4>Debug Info:</h4>";
    echo "<pre>";
    if (isset($pdo) && $pdo instanceof PDO) {
        echo "PDO::ATTR_SERVER_INFO: " . $pdo->getAttribute(PDO::ATTR_SERVER_INFO) . "\n";
        echo "PDO::ATTR_SERVER_VERSION: " . $pdo->getAttribute(PDO::ATTR_SERVER_VERSION) . "\n";
    } else {
        echo "No PDO connection established.\n";
    }
    echo "</pre>";
}

// Show PHP info for debugging
echo "<h3>PHP Info:</h3>";
ob_start();
phpinfo(INFO_MODULES);
$phpinfo = ob_get_clean();
$phpinfo = preg_replace('%^.*<body>(.*)</body>.*$%ms', '$1', $phpinfo);
echo $phpinfo;
?>
