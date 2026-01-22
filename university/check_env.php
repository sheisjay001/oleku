<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Only allow admin or simple protection
// For now, open but safe
header('Content-Type: text/plain');

echo "=== System Diagnostic ===\n";
echo "PHP Version: " . phpversion() . "\n";
echo "Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "\n";
echo "Loaded Extensions: " . implode(', ', get_loaded_extensions()) . "\n";

echo "\n=== PDF Capabilities ===\n";
echo "PdfToText class exists: " . (class_exists('PdfToText') ? 'Yes' : 'No') . "\n";
echo "gzuncompress exists: " . (function_exists('gzuncompress') ? 'Yes' : 'No') . "\n";

echo "\n=== AI Configuration ===\n";
echo "GROQ_API_KEY defined: " . (defined('GROQ_API_KEY') ? 'Yes' : 'No') . "\n";
if (defined('GROQ_API_KEY')) {
    echo "Key length: " . strlen(GROQ_API_KEY) . "\n";
    echo "Key start: " . substr(GROQ_API_KEY, 0, 4) . "...\n";
} else {
    echo "Key: MISSING\n";
}

echo "\n=== Log File ===\n";
$logFile = __DIR__ . '/../ai_debug.log';
echo "Log file path: $logFile\n";
echo "Log file exists: " . (file_exists($logFile) ? 'Yes' : 'No') . "\n";
echo "Log file writable: " . (is_writable(dirname($logFile)) ? 'Dir Yes' : 'Dir No') . "\n";

if (file_exists($logFile)) {
    echo "\n--- Last 50 lines of ai_debug.log ---\n";
    $lines = file($logFile);
    $last = array_slice($lines, -50);
    echo implode("", $last);
    echo "\n--- End Log ---\n";
}

echo "\n=== Test AI Connection ===\n";
if (defined('GROQ_API_KEY')) {
    $ch = curl_init('https://api.groq.com/openai/v1/models');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . GROQ_API_KEY
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    echo "Groq API Status: $code\n";
    if ($code !== 200) {
        echo "Response: $resp\n";
    } else {
        echo "Connection OK.\n";
    }
    curl_close($ch);
}

echo "\n=== Done ===\n";
?>
