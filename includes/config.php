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
    define('SITE_URL', 'https://oleku.vercel.app'); 
}

// Admin configuration
define('ADMIN_EMAILS', ['soteriamaa@gmail.com']);

// Error reporting (turn off in production)
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Optional secrets
if (file_exists(__DIR__ . '/../config/secrets.php')) {
    require_once __DIR__ . '/../config/secrets.php';
}

// Create database connection
try {
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME;
    
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];
    
    if (DB_PORT === '4000') {
        $options[PDO::MYSQL_ATTR_SSL_CA] = __DIR__ . '/cacert.pem';
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false; 
    }

    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Custom Database Session Handler
class DbSessionHandler implements SessionHandlerInterface {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    #[\ReturnTypeWillChange]
    public function open($savePath, $sessionName) {
        // Ensure table exists
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS sessions (
            id VARCHAR(128) PRIMARY KEY,
            data TEXT,
            access INT(10) UNSIGNED
        )");
        return true;
    }

    #[\ReturnTypeWillChange]
    public function close() {
        return true;
    }

    #[\ReturnTypeWillChange]
    public function read($id) {
        $stmt = $this->pdo->prepare("SELECT data FROM sessions WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            return $row['data'];
        }
        return '';
    }

    #[\ReturnTypeWillChange]
    public function write($id, $data) {
        $access = time();
        $stmt = $this->pdo->prepare("REPLACE INTO sessions (id, data, access) VALUES (:id, :data, :access)");
        return $stmt->execute([':id' => $id, ':data' => $data, ':access' => $access]);
    }

    #[\ReturnTypeWillChange]
    public function destroy($id) {
        $stmt = $this->pdo->prepare("DELETE FROM sessions WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    #[\ReturnTypeWillChange]
    public function gc($maxlifetime) {
        $old = time() - $maxlifetime;
        $stmt = $this->pdo->prepare("DELETE FROM sessions WHERE access < :old");
        $stmt->execute([':old' => $old]);
        return $stmt->rowCount();
    }
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    // Use DB handler
    $handler = new DbSessionHandler($pdo);
    session_set_save_handler($handler, true);

    // Set session cookie parameters
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '', 
        'secure' => isset($_SERVER['HTTPS']), 
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}
