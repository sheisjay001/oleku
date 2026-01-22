<?php
$error_codes = [
    400 => 'Bad Request',
    401 => 'Unauthorized',
    403 => 'Forbidden',
    404 => 'Page Not Found',
    500 => 'Internal Server Error'
];

$code = isset($_GET['code']) ? (int)$_GET['code'] : 404;
$message = $error_codes[$code] ?? 'An error occurred';

http_response_code($code);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Error <?= $code ?> - <?= $message ?></title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
        h1 { font-size: 50px; }
        .error { color: #e74c3c; }
    </style>
</head>
<body>
    <h1>Error <?= $code ?></h1>
    <p class="error"><?= $message ?></p>
    <p>Sorry, something went wrong.</p>
    <p><a href="/">Return to Homepage</a></p>
</body>
</html>