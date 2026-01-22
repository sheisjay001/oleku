<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Simple auth check - require login
requireLogin();

$logFile = __DIR__ . '/ai_debug.log';

echo '<!DOCTYPE html><html><head><title>AI Debug Log</title></head><body style="font-family:monospace; padding: 20px;">';
echo '<h1>AI Debug Log</h1>';
echo '<p><a href="university/index.php">Back to University</a> | <a href="?clear=1">Clear Log</a> | <a href="?test_conn=1">Test API Connection</a> | <a href="?test_func=1">Test AI Function</a></p>';

if (isset($_GET['test_func'])) {
    echo '<h3>Internal AI Function Test</h3>';
    if (function_exists('perform_ai_task')) {
        echo "Function perform_ai_task exists.<br>";
        echo "Testing summary generation...<br>";
        $res = perform_ai_task('summary', ['text' => 'This is a test of the AI system. It should summarize this short text.']);
        if ($res['ok']) {
            echo "<div style='color:green'><strong>Success!</strong></div>";
            echo "<pre>" . htmlspecialchars(print_r($res['data'], true)) . "</pre>";
        } else {
            echo "<div style='color:red'><strong>Failed:</strong> " . htmlspecialchars($res['error']) . "</div>";
        }
    } else {
        echo "<div style='color:red'>Function perform_ai_task NOT found.</div>";
    }
    echo "<hr>";
}

if (isset($_GET['test_conn'])) {
    echo '<h3>API Connection Test</h3>';
    $url = 'https://api.groq.com/openai/v1/chat/completions';
    echo "Testing connection to $url...<br>";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    // Just a HEAD-like request or invalid auth to check reachability
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer test']);
    
    $resp = curl_exec($ch);
    $err = curl_error($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    
    if ($err) {
        echo "<div style='color:red'><strong>Connection Failed:</strong> $err</div>";
        echo "<div>Your hosting provider might be blocking outgoing connections.</div>";
    } else {
        echo "<div style='color:green'><strong>Connection Successful!</strong> (HTTP Code: " . $info['http_code'] . ")</div>";
        echo "<div>Response length: " . strlen($resp) . " bytes</div>";
    }
    echo "<hr>";
}

if (isset($_GET['clear'])) {
    file_put_contents($logFile, '');
    echo '<p style="color:green">Log cleared.</p>';
}

if (file_exists($logFile)) {
    $content = file_get_contents($logFile);
    if (empty($content)) {
        echo '<p>Log is empty.</p>';
    } else {
        echo '<div style="background:#f0f0f0; padding:10px; border:1px solid #ccc; white-space: pre-wrap;">';
        echo htmlspecialchars($content);
        echo '</div>';
    }
} else {
    echo '<p>No log file found (ai_debug.log).</p>';
}

echo '</body></html>';
