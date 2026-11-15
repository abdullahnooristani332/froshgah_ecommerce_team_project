
<?php

/**
 * Froshgah - Secure Session Configuration
 *
 * This file must be included before any HTML output.
 */

if (session_status() === PHP_SESSION_NONE) {

    // Prevent PHP from accepting session IDs through URLs.
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_trans_sid', '0');

    // Prevent JavaScript from reading the session cookie.
    ini_set('session.cookie_httponly', '1');

    // Use cookies only for this application.
    $cookiePath = '/froshgah/';

    // Enable Secure cookies when the application uses HTTPS.
    // This remains false on ordinary XAMPP localhost HTTP.
    $isHttps = (
        isset($_SERVER['HTTPS'])
        && $_SERVER['HTTPS'] !== ''
        && strtolower((string) $_SERVER['HTTPS']) !== 'off'
    );

    session_name('FROSHGAH_SESSION');

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => $cookiePath,
        'domain' => '',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    // Start the session.
    session_start();
}
