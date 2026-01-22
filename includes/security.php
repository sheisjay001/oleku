<?php
/**
 * Security Headers and Enhancements
 */

// Prevent Clickjacking
header('X-Frame-Options: SAMEORIGIN');

// Prevent MIME type sniffing
header('X-Content-Type-Options: nosniff');

// Enable XSS filtering in browser (for older browsers)
header('X-XSS-Protection: 1; mode=block');

// Control Referrer Policy
header('Referrer-Policy: strict-origin-when-cross-origin');

// Strict Transport Security (HSTS) - 1 year
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

// Content Security Policy (CSP)
// Allowing 'unsafe-inline' and 'unsafe-eval' for now to support existing scripts/styles and Tailwind CDN
// In a stricter environment, these should be removed and hashes/nonces used.
header("Content-Security-Policy: default-src 'self' https: data:; script-src 'self' 'unsafe-inline' 'unsafe-eval' https:; style-src 'self' 'unsafe-inline' https:; img-src 'self' data: https:; font-src 'self' https: data:; connect-src 'self' https:;");

// Disable PHP version exposure
header('X-Powered-By: Oleku Platform');
?>
