<?php
/**
 * Oleku Helper Functions
 * 
 * A collection of utility functions used throughout the Oleku platform.
 */

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load configuration
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/ai_helper.php';
require_once __DIR__ . '/class.pdf2text.php';

/**
 * Generate CSRF Token
 * @return string
 */
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Render CSRF Input Field
 * @return string
 */
function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

/**
 * Validate CSRF Token
 * @return bool
 */
function validate_csrf() {
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}

/**
 * Authentication & User Management
 */

/**
 * Check if user is logged in
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Require user to be logged in, redirect to login page if not
 */
function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        $next = urlencode($_SERVER['REQUEST_URI']);
        redirect(SITE_URL . '/auth/login.php?next=' . $next);
    }
}

/**
 * Check if user has admin privileges
 * @return bool
 */
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Require admin privileges
 */
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        setFlash('error', 'You do not have permission to access this page');
        redirect(SITE_URL . '/dashboard/');
    }
}

/**
 * Input Validation & Sanitization
 */

/**
 * Sanitize input data
 * @param mixed $data
 * @return string|array
 */
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email address
 * @param string $email
 * @return bool
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate password strength
 * @param string $password
 * @return array [isValid, errorMessage]
 */
function validatePassword($password) {
    if (strlen($password) < 8) {
        return [false, 'Password must be at least 8 characters long'];
    }
    if (!preg_match('/[A-Z]/', $password)) {
        return [false, 'Password must contain at least one uppercase letter'];
    }
    if (!preg_match('/[a-z]/', $password)) {
        return [false, 'Password must contain at least one lowercase letter'];
    }
    if (!preg_match('/[0-9]/', $password)) {
        return [false, 'Password must contain at least one number'];
    }
    return [true, ''];
}

/**
 * Flash Messaging System
 */

/**
 * Set a flash message
 * @param string $type
 * @param string $message
 */
function setFlash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Get and clear flash message
 * @return array|null
 */
function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Display flash messages
 */
function displayFlash() {
    $flash = getFlash();
    if ($flash) {
        $type = $flash['type'];
        $message = $flash['message'];
        echo "<div class='alert alert-{$type}'>{$message}</div>";
    }
}

/**
 * File Handling
 */

/**
 * Upload a file
 * @param array $file $_FILES array element
 * @param string $targetDir
 * @param array $allowedTypes
 * @param int $maxSize in MB
 * @return array [success, message, filePath]
 */
function uploadFile($file, $targetDir = 'uploads/', $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'], $maxSize = 5) {
    $targetDir = rtrim($targetDir, '/') . '/';
    
    // Create directory if it doesn't exist
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0755, true);
    }
    
    $fileName = basename($file['name']);
    $targetFile = $targetDir . uniqid() . '_' . $fileName;
    $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
    $fileSize = $file['size'];
    $uploadOk = true;
    $message = '';
    
    // Check if file already exists
    if (file_exists($targetFile)) {
        $uploadOk = false;
        $message = 'Sorry, file already exists.';
    }
    
    // Check file size
    if ($fileSize > $maxSize * 1024 * 1024) {
        $uploadOk = false;
        $message = "Sorry, your file is too large. Maximum size is {$maxSize}MB.";
    }
    
    // Allow certain file formats
    if (!in_array($file['type'], $allowedTypes)) {
        $uploadOk = false;
        $message = 'Sorry, only ' . implode(', ', $allowedTypes) . ' files are allowed.';
    }
    
    // Check if $uploadOk is set to false by an error
    if (!$uploadOk) {
        return [false, $message, null];
    }
    
    // If everything is ok, try to upload file
    if (move_uploaded_file($file['tmp_name'], $targetFile)) {
        return [true, 'File uploaded successfully', $targetFile];
    } else {
        return [false, 'Sorry, there was an error uploading your file.', null];
    }
}

/**
 * Delete a file
 * @param string $filePath
 * @return bool
 */
function deleteFile($filePath) {
    if (file_exists($filePath) && is_file($filePath)) {
        return unlink($filePath);
    }
    return false;
}

/**
 * Form Helpers
 */

/**
 * Set old form input
 * @param string $key
 * @param mixed $default
 * @return mixed
 */
function old($key, $default = '') {
    return $_SESSION['old'][$key] ?? $default;
}

/**
 * Set form input for next request
 * @param array $input
 */
function setOldInput($input) {
    $_SESSION['old'] = $input;
}

/**
 * Clear old form input
 */
function clearOldInput() {
    unset($_SESSION['old']);
}



/**
 * String Helpers
 */

/**
 * Generate a random string
 * @param int $length
 * @return string
 */
function str_random($length = 16) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString;
}

/**
 * Truncate a string to a specified length
 * @param string $string
 * @param int $length
 * @param string $append
 * @return string
 */
function str_limit($string, $length = 100, $append = '...') {
    if (mb_strlen($string) <= $length) {
        return $string;
    }
    return mb_substr($string, 0, $length) . $append;
}

/**
 * Date & Time Helpers
 */

/**
 * Format a date
 * @param string $date
 * @param string $format
 * @return string
 */
function format_date($date, $format = 'M j, Y') {
    $dateTime = new DateTime($date);
    return $dateTime->format($format);
}

/**
 * Get time ago string
 * @param string $datetime
 * @return string
 */
function time_ago($datetime) {
    $time = strtotime($datetime);
    $timeDiff = time() - $time;
    
    $tokens = [
        31536000 => 'year',
        2592000 => 'month',
        604800 => 'week',
        86400 => 'day',
        3600 => 'hour',
        60 => 'minute',
        1 => 'second'
    ];

    foreach ($tokens as $unit => $text) {
        if ($timeDiff < $unit) continue;
        $numberOfUnits = floor($timeDiff / $unit);
        return $numberOfUnits . ' ' . $text . (($numberOfUnits > 1) ? 's' : '') . ' ago';
    }
    
    return 'just now';
}

/**
 * Redirect to a URL
 * @param string $url
 */
function redirect($url) {
    // Ensure session is saved before redirecting
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
    
    if (!headers_sent()) {
        header("Location: $url");
    } else {
        echo "<script>window.location.href='$url';</script>";
        echo "<meta http-equiv='refresh' content='0;url=$url'>";
    }
    exit;
}

/**
 * Debugging
 */

/**
 * Dump and die
 * @param mixed $var
 */
function dd($var) {
    echo '<pre>';
    var_dump($var);
    echo '</pre>';
    die();
}

/**
 * Log a message to the error log
 * @param string $message
 * @param string $level
 */
function log_message($message, $level = 'INFO') {
    $logDir = __DIR__ . '/../logs';
    if (!file_exists($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] {$level}: {$message}" . PHP_EOL;
    error_log($logMessage, 3, $logDir . '/error.log');
}

// Set default timezone
date_default_timezone_set('UTC');

function ensure_material_tables() {
    global $pdo;
    $pdo->exec("CREATE TABLE IF NOT EXISTS materials (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id VARCHAR(64) NOT NULL,
        course VARCHAR(255) NOT NULL,
        file_path TEXT,
        notes MEDIUMTEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->exec("CREATE TABLE IF NOT EXISTS summaries (
        id INT AUTO_INCREMENT PRIMARY KEY,
        material_id INT NOT NULL,
        summary_text MEDIUMTEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX(material_id),
        CONSTRAINT fk_summaries_material FOREIGN KEY (material_id) REFERENCES materials(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->exec("CREATE TABLE IF NOT EXISTS explanations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        material_id INT NOT NULL,
        content MEDIUMTEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX(material_id),
        CONSTRAINT fk_explanations_material FOREIGN KEY (material_id) REFERENCES materials(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->exec("CREATE TABLE IF NOT EXISTS practice_questions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        material_id INT NOT NULL,
        question TEXT NOT NULL,
        option_a TEXT NULL,
        option_b TEXT NULL,
        option_c TEXT NULL,
        option_d TEXT NULL,
        answer CHAR(1) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX(material_id),
        CONSTRAINT fk_questions_material FOREIGN KEY (material_id) REFERENCES materials(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Ensure answer column exists (for existing tables)
    try {
        $pdo->query("SELECT answer FROM practice_questions LIMIT 1");
    } catch (PDOException $e) {
        $pdo->exec("ALTER TABLE practice_questions ADD COLUMN answer CHAR(1) NULL");
    }
}

function generate_summary($text, $maxSentences = 5) {
    $text = trim($text);
    if ($text === '') return '';
    $sentences = preg_split('/(?<=[.?!])\s+/u', $text);
    $stop = ['the','is','at','of','on','and','a','to','in','for','with','that','this','it','as','by','an','be','or','are','was','were','from','which'];
    $freq = [];
    $words = preg_split('/\W+/u', mb_strtolower($text));
    foreach ($words as $w) {
        if ($w === '' || in_array($w, $stop, true)) continue;
        $freq[$w] = ($freq[$w] ?? 0) + 1;
    }
    $scores = [];
    foreach ($sentences as $i => $s) {
        $score = 0;
        foreach (preg_split('/\W+/u', mb_strtolower($s)) as $w) {
            if (isset($freq[$w])) $score += $freq[$w];
        }
        $scores[$i] = $score;
    }
    arsort($scores);
    $topIdx = array_slice(array_keys($scores), 0, min($maxSentences, count($sentences)));
    sort($topIdx);
    $summary = [];
    foreach ($topIdx as $i) {
        $summary[] = $sentences[$i];
    }
    return implode(' ', $summary);
}

function generate_explanations($text, $maxPoints = 6) {
    $text = trim($text);
    if ($text === '') return [];
    $sentences = preg_split('/(?<=[.?!])\s+/u', $text);
    $points = [];
    foreach ($sentences as $s) {
        $s = trim($s);
        if ($s === '') continue;
        if (mb_strlen($s) > 300) continue;
        if (preg_match('/(definition|means|refers|is called|involves|consists|includes|types|steps)/i', $s)) {
            $points[] = $s;
        }
    }
    if (count($points) === 0) {
        $points = array_slice($sentences, 0, min($maxPoints, count($sentences)));
    } else {
        $points = array_slice($points, 0, min($maxPoints, count($points)));
    }
    return $points;
}

function generate_practice_questions($text, $count = 6) {
    $text = trim($text);
    if ($text === '') return [];
    $words = preg_split('/\W+/u', mb_strtolower($text));
    $stop = ['the','is','at','of','on','and','a','to','in','for','with','that','this','it','as','by','an','be','or','are','was','were','from','which','what','when','where','how','why'];
    $freq = [];
    foreach ($words as $w) {
        if ($w === '' || in_array($w, $stop, true) || mb_strlen($w) < 4) continue;
        $freq[$w] = ($freq[$w] ?? 0) + 1;
    }
    arsort($freq);
    $keywords = array_slice(array_keys($freq), 0, min($count, count($freq)));
    $questions = [];
    foreach ($keywords as $kw) {
        $question = "Fill in the blank: __________ is closely related to '{$kw}'.";
        $options = [$kw, $kw . 's', 'non-' . $kw, 'pre-' . $kw];
        shuffle($options);
        $answerIdx = array_search($kw, $options, true);
        $answer = ['A','B','C','D'][$answerIdx];
        $questions[] = [
            'question' => $question,
            'options' => $options,
            'answer' => $answer
        ];
    }
    return $questions;
}

function save_material($userId, $course, $filePath, $notes) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO materials (user_id, course, file_path, notes) VALUES (?, ?, ?, ?)");
    $stmt->execute([$userId, $course, $filePath, $notes]);
    return (int)$pdo->lastInsertId();
}

function save_summary($materialId, $summaryText) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO summaries (material_id, summary_text) VALUES (?, ?)");
    $stmt->execute([$materialId, $summaryText]);
    return (int)$pdo->lastInsertId();
}

function save_explanations($materialId, $points) {
    global $pdo;
    if (count($points) === 0) return 0;
    $content = "- " . implode("\n- ", $points);
    $stmt = $pdo->prepare("INSERT INTO explanations (material_id, content) VALUES (?, ?)");
    $stmt->execute([$materialId, $content]);
    return (int)$pdo->lastInsertId();
}

function save_practice_questions($materialId, $questions) {
    global $pdo;
    $count = 0;
    $stmt = $pdo->prepare("INSERT INTO practice_questions (material_id, question, option_a, option_b, option_c, option_d, answer) VALUES (?, ?, ?, ?, ?, ?, ?)");
    foreach ($questions as $q) {
        $opA = $q['options'][0] ?? '';
        $opB = $q['options'][1] ?? '';
        $opC = $q['options'][2] ?? '';
        $opD = $q['options'][3] ?? '';
        $ans = $q['answer'] ?? null;
        
        $stmt->execute([$materialId, $q['question'], $opA, $opB, $opC, $opD, $ans]);
        $count++;
    }
    return $count;
}

function get_recent_summaries($userId, $limit = 10) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT s.id, s.summary_text, s.created_at, m.course, m.file_path, m.notes, m.created_at AS material_created_at
        FROM summaries s
        JOIN materials m ON s.material_id = m.id
        WHERE m.user_id = ?
        ORDER BY s.created_at DESC
        LIMIT ?
    ");
    $stmt->execute([$userId, $limit]);
    return $stmt->fetchAll();
}

function get_recent_activities($userId, $limit = 10) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT m.id AS material_id, m.course, m.file_path, m.notes, m.created_at,
               s.summary_text,
               e.content AS explanation,
               (SELECT COUNT(*) FROM practice_questions pq WHERE pq.material_id = m.id) AS question_count
        FROM materials m
        LEFT JOIN summaries s ON s.material_id = m.id
        LEFT JOIN explanations e ON e.material_id = m.id
        WHERE m.user_id = ?
        ORDER BY m.created_at DESC
        LIMIT ?
    ");
    $stmt->execute([$userId, $limit]);
    return $stmt->fetchAll();
}

function ensure_email_verification_table() {
    global $pdo;
    $pdo->exec("CREATE TABLE IF NOT EXISTS email_verifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL,
        token VARCHAR(64) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        verified_at TIMESTAMP NULL DEFAULT NULL,
        UNIQUE KEY token_unique (token),
        INDEX email_idx (email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function create_verification_token($email) {
    global $pdo;
    ensure_email_verification_table();
    $token = bin2hex(random_bytes(16));
    $stmt = $pdo->prepare("INSERT INTO email_verifications (email, token) VALUES (?, ?)");
    $stmt->execute([$email, $token]);
    return $token;
}

function mark_email_verified($token) {
    global $pdo;
    ensure_email_verification_table();
    $stmt = $pdo->prepare("SELECT email FROM email_verifications WHERE token = ? AND verified_at IS NULL");
    $stmt->execute([$token]);
    $row = $stmt->fetch();
    if (!$row) return false;
    $email = $row['email'];
    $upd = $pdo->prepare("UPDATE email_verifications SET verified_at = NOW() WHERE token = ?");
    $upd->execute([$token]);
    return $email;
}

function isEmailVerified($email) {
    global $pdo;
    ensure_email_verification_table();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM email_verifications WHERE email = ? AND verified_at IS NOT NULL");
    $stmt->execute([$email]);
    return (int)$stmt->fetchColumn() > 0;
}

function send_verification_email($email, $token) {
    $verifyUrl = SITE_URL . '/auth/verify.php?token=' . urlencode($token);
    $subject = 'Verify your email for ' . SITE_NAME;
    $message = "Hello,\n\nPlease verify your email address by clicking the link below:\n\n{$verifyUrl}\n\nIf you did not sign up, you can ignore this email.\n\nRegards,\n" . SITE_NAME;
    $headers = "From: no-reply@" . parse_url(SITE_URL, PHP_URL_HOST) . "\r\n";
    $headers .= "Reply-To: no-reply@" . parse_url(SITE_URL, PHP_URL_HOST) . "\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    $ok = @mail($email, $subject, $message, $headers);
    if (!$ok) {
        log_message("Failed to send verification email to {$email}. Link: {$verifyUrl}", 'WARN');
    } else {
        log_message("Sent verification email to {$email}", 'INFO');
    }
    return $ok;
}

function ensure_user_accounts_table() {
    global $pdo;
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_accounts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL UNIQUE,
        name VARCHAR(255) DEFAULT NULL,
        password_hash VARCHAR(255) DEFAULT NULL,
        role ENUM('user','admin') NOT NULL DEFAULT 'user',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function create_user_account($email, $name, $password, $role = 'user') {
    global $pdo;
    ensure_user_accounts_table();
    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("INSERT INTO user_accounts (email, name, password_hash, role) VALUES (?, ?, ?, ?)");
    $stmt->execute([$email, $name, $hash, $role]);
    return (int)$pdo->lastInsertId();
}

function get_user_account($email) {
    global $pdo;
    ensure_user_accounts_table();
    $stmt = $pdo->prepare("SELECT * FROM user_accounts WHERE email = ?");
    $stmt->execute([$email]);
    return $stmt->fetch();
}

function verify_password_for_email($email, $password) {
    $user = get_user_account($email);
    if (!$user || empty($user['password_hash'])) return false;
    return password_verify($password, $user['password_hash']);
}

function set_user_password($email, $newPassword) {
    global $pdo;
    ensure_user_accounts_table();
    $hash = password_hash($newPassword, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("UPDATE user_accounts SET password_hash = ? WHERE email = ?");
    return $stmt->execute([$hash, $email]);
}

function ensure_password_reset_table() {
    global $pdo;
    $pdo->exec("CREATE TABLE IF NOT EXISTS password_resets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL,
        token VARCHAR(64) NOT NULL UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        used_at TIMESTAMP NULL DEFAULT NULL,
        INDEX email_idx (email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function create_password_reset_token($email) {
    global $pdo;
    ensure_password_reset_table();
    $token = bin2hex(random_bytes(16));
    $stmt = $pdo->prepare("INSERT INTO password_resets (email, token) VALUES (?, ?)");
    $stmt->execute([$email, $token]);
    return $token;
}

function send_reset_email($email, $token) {
    $resetUrl = SITE_URL . '/auth/reset.php?token=' . urlencode($token);
    $subject = 'Reset your password for ' . SITE_NAME;
    $message = "Hello,\n\nYou requested a password reset. Click the link below to set a new password:\n\n{$resetUrl}\n\nIf you did not request this, you can ignore this email.\n\nRegards,\n" . SITE_NAME;
    $headers = "From: no-reply@" . parse_url(SITE_URL, PHP_URL_HOST) . "\r\n";
    $headers .= "Reply-To: no-reply@" . parse_url(SITE_URL, PHP_URL_HOST) . "\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    $ok = @mail($email, $subject, $message, $headers);
    if (!$ok) {
        log_message("Failed to send reset email to {$email}. Link: {$resetUrl}", 'WARN');
    } else {
        log_message("Sent reset email to {$email}", 'INFO');
    }
    return $ok;
}

function get_email_by_reset_token($token, $expireHours = 24) {
    global $pdo;
    ensure_password_reset_table();
    $stmt = $pdo->prepare("SELECT email, created_at, used_at FROM password_resets WHERE token = ?");
    $stmt->execute([$token]);
    $row = $stmt->fetch();
    if (!$row) return false;
    if (!empty($row['used_at'])) return false;
    $created = strtotime($row['created_at']);
    if (time() - $created > $expireHours * 3600) return false;
    return $row['email'];
}

function mark_reset_token_used($token) {
    global $pdo;
    ensure_password_reset_table();
    $stmt = $pdo->prepare("UPDATE password_resets SET used_at = NOW() WHERE token = ?");
    $stmt->execute([$token]);
}

function build_fallback_text($course, $filePath) {
    $name = preg_replace('/\.[^.]+$/', '', basename($filePath));
    $tokens = array_unique(array_filter(preg_split('/\W+/u', $course . ' ' . $name)));
    $topics = implode(', ', array_slice($tokens, 0, 12));
    $text = "Course: {$course}. This material appears to cover the following topics: {$topics}. "
          . "Definitions, key concepts, examples, and typical problems may be included. "
          . "Focus on understanding core ideas, relationships between concepts, and how to apply formulas or reasoning to solve questions.";
    return $text;
}

function get_practice_questions_for_material($materialId, $limit = 3) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT question, option_a, option_b, option_c, option_d, answer FROM practice_questions WHERE material_id = :mid ORDER BY created_at DESC LIMIT :limit");
    $stmt->bindValue(':mid', $materialId);
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function ensure_cbt_settings_table() {
    global $pdo;
    $pdo->exec("CREATE TABLE IF NOT EXISTS cbt_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        english_count INT NOT NULL DEFAULT 60,
        other_count INT NOT NULL DEFAULT 40,
        subjects_json TEXT DEFAULT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function get_cbt_settings() {
    global $pdo;
    ensure_cbt_settings_table();
    $row = $pdo->query("SELECT * FROM cbt_settings ORDER BY id DESC LIMIT 1")->fetch();
    if (!$row) {
        $pdo->exec("INSERT INTO cbt_settings (english_count, other_count) VALUES (60, 40)");
        $row = $pdo->query("SELECT * FROM cbt_settings ORDER BY id DESC LIMIT 1")->fetch();
    }
    return $row;
}

function set_cbt_settings($englishCount, $otherCount, $subjects) {
    global $pdo;
    ensure_cbt_settings_table();
    $subjectsJson = is_array($subjects) ? json_encode(array_values($subjects)) : null;
    $stmt = $pdo->prepare("INSERT INTO cbt_settings (english_count, other_count, subjects_json) VALUES (?, ?, ?)");
    $stmt->execute([(int)$englishCount, (int)$otherCount, $subjectsJson]);
    return true;
}

function available_subjects() {
    $default = ['English','Mathematics','Physics','Chemistry','Biology','Economics','Government','Literature','CRS'];
    $cfg = get_cbt_settings();
    if ($cfg && !empty($cfg['subjects_json'])) {
        $subs = json_decode($cfg['subjects_json'], true);
        if (is_array($subs) && count($subs) > 0) return $subs;
    }
    return $default;
}

function ensure_jamb_question_bank_table() {
    global $pdo;
    $pdo->exec("CREATE TABLE IF NOT EXISTS jamb_questions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        subject VARCHAR(64) NOT NULL,
        question TEXT NOT NULL,
        option_a TEXT NOT NULL,
        option_b TEXT NOT NULL,
        option_c TEXT NOT NULL,
        option_d TEXT NOT NULL,
        answer CHAR(1) NOT NULL,
        topic VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX subject_idx (subject)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function seed_jamb_question_bank_if_empty() {
    global $pdo;
    ensure_jamb_question_bank_table();
    $count = (int)$pdo->query("SELECT COUNT(*) FROM jamb_questions")->fetchColumn();
    // Re-seed if we have fewer than 50 questions (implies only old seed data exists)
    // if ($count > 100) return;
    
    $seed = [
        'English' => [
            ['Choose the synonym of "rapid"',['slow','quick','late','dull'],'B'],
            ['Which is a vowel sound?',['b','t','a','k'],'C'],
            ['Comprehension tests understanding of',['numbers','texts','maps','music'],'B'],
            ['Lexis relates to',['words','images','numbers','notes'],'A'],
            ['Summary writing requires',['details','brevity','figures','tables'],'B'],
            ['Choose the synonym of "Candid"',['Deceptive','Frank','Secretive','Guarded'],'B'],
            ['Choose the antonym of "Arrogant"',['Proud','Humble','Loud','Rude'],'B'],
            ['The sound /θ/ is found in',['There','Then','Thin','That'],'C'],
            ['"To let the cat out of the bag" means',['To release a pet','To reveal a secret','To make noise','To steal'],'B'],
            ['Choose the correct spelling',['Priviledge','Privilege','Privelege','Previlage'],'B'],
            ['The plural of "Crisis" is',['Crises','Crisises','Crisus','Crisi'],'A'],
            ['Identify the noun phrase in: "The tall man is here"',['is here','The tall man','tall','man is'],'B'],
            ['Choose the option with stress on the first syllable',['Export (Verb)','Export (Noun)','Comply','Begin'],'B'],
            ['"He is good ___ football"',['in','at','on','with'],'B'],
            ['The register of medicine includes',['Stanza','Diagnosis','Debit','Plaintiff'],'B']
        ],
        'Mathematics' => [
            ['Solve: 2x=10, x=',['2','5','10','20'],'B'],
            ['Triangle angles sum equals',['90°','180°','270°','360°'],'B'],
            ['Mean of 2,4,6',['3','4','5','6'],'C'],
            ['Simplify: 3(2+1)',['3','6','9','12'],'C'],
            ['sin(90°)=',['0','1','-1','0.5'],'B'],
            ['Solve for x: x² - 5x + 6 = 0',['2, 3','-2, -3','1, 6','-1, -6'],'A'],
            ['The gradient of the line y = 3x - 2 is',['-2','2','3','-3'],'C'],
            ['Calculate the area of a circle with radius 7cm (π=22/7)',['154cm²','44cm²','22cm²','144cm²'],'A'],
            ['2⁵ × 2³ =',['2⁸','2¹⁵','2²','2³'],'A'],
            ['If P = {1,2,3} and Q = {3,4,5}, find P ∩ Q',['{1,2}','{3}','{4,5}','{1,2,3,4,5}'],'B'],
            ['Convert 101₂ to base 10',['2','3','5','4'],'C'],
            ['Differentiation of x² is',['x','2x','x/2','2'],'B'],
            ['Integration is the reverse of',['Multiplication','Differentiation','Addition','Subtraction'],'B'],
            ['Probability of getting a head in a coin toss',['1/2','1/4','1','0'],'A'],
            ['Find the median of 2, 5, 1, 4, 3',['1','2','3','4'],'C']
        ],
        'Physics' => [
            ['SI unit of force',['joule','watt','newton','pascal'],'C'],
            ['Heat transfer by waves',['conduction','convection','radiation','fusion'],'C'],
            ['Speed of light in vacuum ~',['3e6','3e8','3e10','3e12'],'B'],
            ['Ohm’s law: V=',['IR','I/R','R/I','VI'],'A'],
            ['Lens focusing relates to',['mechanics','optics','nuclear','quantum'],'B'],
            ['Scalar quantities have',['Magnitude only','Direction only','Magnitude and Direction','None'],'A'],
            ['Acceleration is the rate of change of',['Distance','Displacement','Velocity','Speed'],'C'],
            ['The unit of Power is',['Joule','Watt','Newton','Volt'],'B'],
            ['Sound waves cannot travel through',['Solids','Liquids','Gases','Vacuum'],'D'],
            ['A convex mirror is used as',['Shaving mirror','Driving mirror','Dentist mirror','Makeup mirror'],'B'],
            ['The core of a transformer is made of',['Steel','Copper','Soft Iron','Aluminum'],'C'],
            ['Radioactivity was discovered by',['Newton','Einstein','Becquerel','Faraday'],'C'],
            ['p-n junction is a',['Capacitor','Resistor','Diode','Inductor'],'C'],
            ['Specific heat capacity unit',['J/kgK','J/kg','J/K','J'],'A'],
            ['Upthrust is explained by',['Newton','Archimedes','Pascal','Hooke'],'B']
        ],
        'Chemistry' => [
            ['Atomic number equals',['protons','neutrons','electrons','mass'],'A'],
            ['Covalent bond shares',['protons','neutrons','electrons','photons'],'C'],
            ['Periodic table arranged by',['mass','volume','atomic number','density'],'C'],
            ['Hydrocarbons are',['organic','inorganic','salts','acids'],'A'],
            ['pH 1 indicates',['neutral','basic','acidic','buffer'],'C'],
            ['Isotopes have same',['Mass number','Neutron number','Proton number','Physical properties'],'C'],
            ['Boyle’s law relates',['P and T','V and T','P and V','P, V and T'],'C'],
            ['Oxidation is',['Loss of electrons','Gain of electrons','Gain of Hydrogen','Loss of Oxygen'],'A'],
            ['Alkanes have the general formula',['CnH2n+2','CnH2n','CnH2n-2','CnHn'],'A'],
            ['A catalyst',['Starts a reaction','Stops a reaction','Alters reaction rate','Is consumed'],'C'],
            ['Hardness of water is caused by',['Ca and Mg ions','Na and K ions','Cl and F ions','H and OH ions'],'A'],
            ['Sulphur (IV) Oxide is used as',['Bleaching agent','Fertilizer','Fuel','Food'],'A'],
            ['The most abundant gas in air is',['Oxygen','Nitrogen','Argon','Carbon Dioxide'],'B'],
            ['Ethanol is an',['Alkane','Alkene','Alkanol','Alkanoic Acid'],'C'],
            ['Faraday’s laws relate to',['Electrolysis','Gas laws','Thermodynamics','Kinetics'],'A']
        ],
        'Biology' => [
            ['Cell is the unit of',['life','matter','energy','time'],'A'],
            ['Photosynthesis occurs in',['mitochondria','chloroplasts','nucleus','ribosome'],'B'],
            ['Ecology studies',['cells','organs','interactions','atoms'],'C'],
            ['Human reproduction involves',['meiosis','osmosis','diffusion','evaporation'],'A'],
            ['Protein digestion starts in',['mouth','stomach','intestine','liver'],'B'],
            ['The powerhouse of the cell is',['Nucleus','Mitochondria','Ribosome','Vacuole'],'B'],
            ['Osmosis involves movement of',['Solute','Solvent','Ions','Gases'],'B'],
            ['Xylem transports',['Food','Water','Oxygen','Hormones'],'B'],
            ['A group of similar cells is a',['System','Organ','Tissue','Organism'],'C'],
            ['Short-sightedness is corrected by',['Convex lens','Concave lens','Plane mirror','Bifocal lens'],'B'],
            ['Malaria is caused by',['Virus','Bacteria','Plasmodium','Fungi'],'C'],
            ['The mammalian heart has',['2 chambers','3 chambers','4 chambers','5 chambers'],'C'],
            ['Photosynthesis product is',['Protein','Glucose','Fat','Vitamin'],'B'],
            ['Which is an abiotic factor?',['Predator','Temperature','Bacteria','Plant'],'B'],
            ['Mendel is the father of',['Evolution','Genetics','Cytology','Anatomy'],'B']
        ],
        'Economics' => [
            ['Economics is primarily concerned with',['wealth','choices','politics','history'],'B'],
            ['Demand law states price and quantity demanded are',['directly related','inversely related','unrelated','constant'],'B'],
            ['Elasticity measures',['taste','responsiveness','size','speed'],'B'],
            ['Money functions include',['investment','unit of account','taxation','production'],'B'],
            ['GDP stands for',['Gross Domestic Product','General Domestic Price','Global Demand Price','Gross Demand Product'],'A'],
            ['Scale of preference helps in',['Making choices','Production','Distribution','Exchange'],'A'],
            ['Opportunity cost is',['Money cost','Alternative forgone','Real cost','Fixed cost'],'B'],
            ['Factors of production include',['Land, Labor, Capital, Entrepreneur','Money, Bank, Market, Price','Gold, Silver, Bronze, Iron','Import, Export, Trade, Aid'],'A'],
            ['A monopolist is a',['Sole seller','Sole buyer','Government','Partnership'],'A'],
            ['Inflation is',['Fall in prices','Rise in prices','Constant prices','Zero prices'],'B'],
            ['Central Bank is responsible for',['Monetary policy','Fiscal policy','Trade policy','Education policy'],'A'],
            ['Utility means',['Usefulness','Satisfaction','Price','Value'],'B'],
            ['Public Limited Companies issue',['Shares','Bonds','Notes','Bills'],'A'],
            ['Budget deficit means',['Revenue > Expenditure','Expenditure > Revenue','Revenue = Expenditure','No budget'],'B'],
            ['ECOWAS promotes',['Regional trade','Global war','Sports','Religion'],'A']
        ],
        'Government' => [
            ['Democracy is a government by',['few','one','people','army'],'C'],
            ['Separation of powers relates to',['sectors','arms of government','regions','parties'],'B'],
            ['Constitution is a',['policy','basic law','speech','campaign'],'B'],
            ['ECOWAS is a',['court','regional body','company','party'],'B'],
            ['Rule of law implies',['arbitrariness','supremacy of law','militarism','elitism'],'B'],
            ['A system with two levels of government is',['Unitary','Federal','Confederal','Monarchy'],'B'],
            ['The executive arm',['Makes laws','Interprets laws','Implements laws','Punishes laws'],'C'],
            ['Franchise is the right to',['Speak','Vote','Travel','Work'],'B'],
            ['Sovereignty resides in the',['State','President','Army','Police'],'A'],
            ['Indirect Rule was introduced by',['Lugard','Clifford','Macaulay','Azikiwe'],'A'],
            ['The first military coup in Nigeria was in',['1960','1963','1966','1979'],'C'],
            ['UNO headquarters is in',['London','Paris','New York','Geneva'],'C'],
            ['OAU is now',['AU','ECOWAS','UN','EU'],'A'],
            ['Bicameral legislature has',['One chamber','Two chambers','Three chambers','Four chambers'],'B'],
            ['Pressure groups seek to',['Influence policy','Take power','Make profit','Build roads'],'A']
        ],
        'Literature' => [
            ['Prose is a form of',['drama','poetry','narrative','music'],'C'],
            ['A sonnet has',['10 lines','12 lines','14 lines','16 lines'],'C'],
            ['Dramatic irony occurs when',['audience knows more','actor knows more','writer knows more','none'],'A'],
            ['Metaphor is a',['comparison using like','direct comparison','exaggeration','sound device'],'B'],
            ['African literature includes',['only poetry','oral and written forms','only drama','only prose'],'B'],
            ['"The Invisible Teacher" is a',['Novel','Play','Poem','Biography'],'A'],
            ['A stanza of four lines is a',['Couplet','Quatrain','Sestet','Octave'],'B'],
            ['Theme refers to',['The setting','The central idea','The character','The plot'],'B'],
            ['Protagonist is the',['Villain','Hero/Heroine','Clown','Narrator'],'B'],
            ['Chinua Achebe wrote',['The Lion and the Jewel','Things Fall Apart','The Concubine','Trials of Brother Jero'],'B'],
            ['Wole Soyinka is a',['Novelist','Poet','Playwright','All of the above'],'D'],
            ['"Enjambment" is found in',['Drama','Poetry','Prose','News'],'B'],
            ['Personification gives human qualities to',['Humans','Animals','Inanimate objects','Gods'],'C'],
            ['Hyperbole is',['Understatement','Exaggeration','Irony','Satire'],'B'],
            ['Climax is the point of',['Highest tension','Beginning','Resolution','Introduction'],'A']
        ],
        'CRS' => [
            ['Who was the first king of Israel?',['David','Saul','Solomon','Samuel'],'B'],
            ['The detailed account of creation is found in Genesis chapter',['1 and 2','3 and 4','5 and 6','7 and 8'],'A'],
            ['The son of Abraham by Hagar was',['Isaac','Ishmael','Esau','Jacob'],'B'],
            ['Jesus was baptized by',['Peter','Paul','John the Baptist','James'],'C'],
            ['The first martyr of the Christian Church was',['Peter','Paul','Stephen','James'],'C'],
            ['God called Abraham from',['Ur','Haran','Canaan','Egypt'],'A'],
            ['Joseph was sold for',['20 shekels','30 shekels','40 shekels','50 shekels'],'A'],
            ['The Ten Commandments were given at',['Sinai','Horeb','Carmel','Olive'],'A'],
            ['Who denied Jesus three times?',['Judas','Peter','Thomas','Andrew'],'B'],
            ['The Holy Spirit descended on Pentecost in form of',['Dove','Fire','Wind','Water'],'B'],
            ['Saul was converted on the way to',['Jerusalem','Damascus','Antioch','Rome'],'B'],
            ['"I am the way, the truth and the life" was said by',['Moses','Elijah','Jesus','Paul'],'C'],
            ['The shortest verse in the Bible is',['Jesus wept','Pray without ceasing','Rejoice always','God is love'],'A'],
            ['Who wrote the Acts of the Apostles?',['Matthew','Mark','Luke','John'],'C'],
            ['The fruit of the Spirit includes',['Love','Hate','Envy','Pride'],'A']
        ]
    ];
    // Debug: print subjects
    if (php_sapi_name() === 'cli') {
        echo "Seeding subjects: " . implode(', ', array_keys($seed)) . "\n";
    }

    $stmt = $pdo->prepare("INSERT INTO jamb_questions (subject, question, option_a, option_b, option_c, option_d, answer, topic) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($seed as $subject => $rows) {
        foreach ($rows as $r) {
            // Simple check to avoid exact duplicates if running multiple times without truncate
            $check = $pdo->prepare("SELECT id FROM jamb_questions WHERE subject=? AND question=?");
            $check->execute([$subject, $r[0]]);
            if (!$check->fetch()) {
                try {
                    $stmt->execute([$subject, $r[0], $r[1][0], $r[1][1], $r[1][2], $r[1][3], $r[2], null]);
                    if (php_sapi_name() === 'cli') echo "Inserted $subject: {$r[0]}\n";
                } catch (Exception $e) {
                    if (php_sapi_name() === 'cli') echo "Error inserting $subject: " . $e->getMessage() . "\n";
                }
            } else {
                 // if (php_sapi_name() === 'cli') echo "Skipped $subject: {$r[0]} (Duplicate)\n";
            }
        }
    }
}

function get_jamb_bank_questions($subject, $count) {
    global $pdo;
    ensure_jamb_question_bank_table();
    seed_jamb_question_bank_if_empty();
    $stmt = $pdo->prepare("SELECT question, option_a, option_b, option_c, option_d, answer FROM jamb_questions WHERE subject = :subject ORDER BY RAND() LIMIT :limit");
    $stmt->bindValue(':subject', $subject);
    $stmt->bindValue(':limit', (int)$count, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll();
    $out = [];
    foreach ($rows as $row) {
        $opts = [$row['option_a'],$row['option_b'],$row['option_c'],$row['option_d']];
        $ansMap = ['A'=>0,'B'=>1,'C'=>2,'D'=>3];
        $ansIdx = $ansMap[$row['answer']] ?? 0;
        $out[] = ['q'=>$row['question'],'opts'=>$opts,'ans'=>$ansIdx];
    }
    return $out;
}
function get_mime_type($filePath) {
    $fi = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : false;
    if ($fi) {
        $mt = finfo_file($fi, $filePath);
        finfo_close($fi);
        if ($mt) return $mt;
    }
    $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    if ($ext === 'jpg' || $ext === 'jpeg') return 'image/jpeg';
    if ($ext === 'png') return 'image/png';
    if ($ext === 'pdf') return 'application/pdf';
    return 'application/octet-stream';
}

function ocr_extract_text($filePath) {
    global $site_url; // just in case
    $logFile = __DIR__ . '/../ai_debug.log';
    
    $mime = get_mime_type($filePath);
    $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

    file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "OCR: Processing $filePath ($mime)\n", FILE_APPEND);

    // Check for PDF by mime or extension
    if ($mime === 'application/pdf' || $mime === 'application/x-pdf' || $ext === 'pdf') {
        if (class_exists('PdfToText')) {
            try {
                $txt = PdfToText::getText($filePath);
                file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "OCR: PdfToText result length: " . strlen($txt) . "\n", FILE_APPEND);
                if (!empty($txt)) return $txt;
            } catch (Throwable $e) {
                file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "OCR: PdfToText Error: " . $e->getMessage() . "\n", FILE_APPEND);
            }
        } else {
             file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "OCR: PdfToText class missing\n", FILE_APPEND);
        }
        // Fallback or empty if not parseable
        return '';
    }

    if (strpos($mime, 'image/') !== 0) return '';
    $data = base64_encode(file_get_contents($filePath));
    
    // Use local helper if available
    if (function_exists('perform_ai_task')) {
        $res = perform_ai_task('ocr', ['mime'=>$mime, 'data'=>$data]);
        if ($res['ok'] && isset($res['data']['text'])) {
            return trim((string)$res['data']['text']);
        }
        return '';
    }

    $res = ai_call('ocr', ['mime'=>$mime,'data'=>$data]);
    if ($res['ok'] && isset($res['data']['text'])) {
        $txt = trim((string)$res['data']['text']);
        return $txt;
    }
    return '';
}

function ai_call($task, $payload) {
    $url = defined('AI_API_URL') ? constant('AI_API_URL') : (getenv('AI_API_URL') ?: (SITE_URL . '/api/ai.php'));
    $key = defined('AI_API_KEY') ? constant('AI_API_KEY') : (getenv('AI_API_KEY') ?: null);
    if (!$url || !$key) {
        return ['ok'=>false,'data'=>null,'error'=>'AI not configured'];
    }
    $ch = curl_init($url);
    $body = json_encode(['task'=>$task,'payload'=>$payload]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $key
    ]);
    $resp = curl_exec($ch);
    if ($resp === false) {
        $err = curl_error($ch);
        curl_close($ch);
        return ['ok'=>false,'data'=>null,'error'=>$err];
    }
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code >= 200 && $code < 300) {
        $json = json_decode($resp, true);
        return ['ok'=>true,'data'=>$json,'error'=>null];
    }
    return ['ok'=>false,'data'=>null,'error'=>$resp];
}

function ai_generate_summary($text, $instructions = '') {
    $logFile = __DIR__ . '/../ai_debug.log';
    if (function_exists('perform_ai_task')) {
        $res = perform_ai_task('summary', ['text'=>$text, 'instructions'=>$instructions]);
        if ($res['ok'] && isset($res['data']['summary'])) return (string)$res['data']['summary'];
        file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "AI Summary Error (Internal): " . ($res['error'] ?? 'Unknown') . "\n", FILE_APPEND);
        return '';
    }
    $res = ai_call('summary', ['text'=>$text, 'instructions'=>$instructions]);
    if ($res['ok'] && isset($res['data']['summary'])) return (string)$res['data']['summary'];
    file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "AI Summary Error (External): " . ($res['error'] ?? 'Unknown') . "\n", FILE_APPEND);
    return '';
}

function ai_generate_explanation($text, $instructions = '') {
    $logFile = __DIR__ . '/../ai_debug.log';
    if (function_exists('perform_ai_task')) {
        $res = perform_ai_task('explanation', ['text'=>$text, 'instructions'=>$instructions]);
        if ($res['ok'] && isset($res['data']['explanation'])) return (string)$res['data']['explanation'];
        file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "AI Explanation Error (Internal): " . ($res['error'] ?? 'Unknown') . "\n", FILE_APPEND);
        return '';
    }
    $res = ai_call('explanation', ['text'=>$text, 'instructions'=>$instructions]);
    if ($res['ok'] && isset($res['data']['explanation'])) return (string)$res['data']['explanation'];
    file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "AI Explanation Error (External): " . ($res['error'] ?? 'Unknown') . "\n", FILE_APPEND);
    return '';
}

function ai_generate_practice_questions($topic, $count = 10, $subject = '', $instructions = '') {
    $logFile = __DIR__ . '/../ai_debug.log';
    if (function_exists('perform_ai_task')) {
        $res = perform_ai_task('practice_questions', ['topic'=>$topic, 'subject'=>$subject, 'count'=>$count, 'instructions'=>$instructions]);
        if ($res['ok'] && isset($res['data']['questions'])) return $res['data']['questions'];
        file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "AI Questions Error (Internal): " . ($res['error'] ?? 'Unknown') . "\n", FILE_APPEND);
        return [];
    }
    $res = ai_call('practice_questions', ['topic'=>$topic, 'subject'=>$subject, 'count'=>$count, 'instructions'=>$instructions]);
    if ($res['ok'] && isset($res['data']['questions'])) return $res['data']['questions'];
    file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "AI Questions Error (External): " . ($res['error'] ?? 'Unknown') . "\n", FILE_APPEND);
    return [];
}

function jamb_syllabus_topics() {
    return [
        'English' => [
            'Comprehension','Lexis and Structure','Oral English','Summary Writing','Registers','Figures of Speech',
            'Sentence Types','Punctuation','Concord','Clauses and Phrases'
        ],
        'Mathematics' => [
            'Number and Numeration','Algebra','Indices and Logarithms','Mensuration','Geometry','Trigonometry',
            'Coordinate Geometry','Functions and Graphs','Statistics','Probability'
        ],
        'Physics' => [
            'Mechanics','Thermal Physics','Waves and Optics','Electricity and Magnetism','Modern Physics','Units and Measurements'
        ],
        'Chemistry' => [
            'Atomic Structure','Periodic Table','Chemical Bonding','States of Matter','Stoichiometry','Kinetics and Equilibrium',
            'Acids Bases Salts','Energetics','Electrochemistry','Organic Chemistry','Metals and Non-metals','Polymers'
        ],
        'Biology' => [
            'Cell Biology','Tissues and Organs','Nutrition','Transport System','Respiration and Excretion',
            'Support and Movement','Reproduction and Growth','Genetics and Heredity','Evolution','Ecology'
        ],
        'Economics' => [
            'Demand and Supply','Price Determination','Elasticity','Theory of Consumer','Theory of Firm',
            'Market Structures','National Income','Money and Banking','Public Finance','International Trade'
        ],
        'Government' => [
            'Meaning and Scope','Systems of Government','Organs of Government','Separation of Powers',
            'Constitution','Rule of Law','Citizenship','Political Parties','Elections','International Organizations'
        ],
        'Literature' => [
            'Prose','Poetry','Drama','Literary Devices','Figures of Speech','African Literature','Genres and Movements',
            'Appreciation and Criticism'
        ],
        'CRS' => [
            'The Sovereignty of God','The Creation','The Exodus','The Judges','The Kings','The Prophets',
            'The Life of Jesus','The Early Church','Epistles','Christian Living'
        ]
    ];
}
