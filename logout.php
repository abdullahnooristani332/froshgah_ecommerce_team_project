<?php

require_once __DIR__ . '/includes/auth.php';

// Only allow logout through POST.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /froshgah/');
    exit;
}

// Verify CSRF token.
if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    exit('Invalid logout request.');
}

// Clear all session data.
$_SESSION = [];

// Remove the session cookie.
if (ini_get('session.use_cookies')) {

    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        [
            'expires' => time() - 42000,
            'path' => $params['path'],
            'domain' => $params['domain'],
            'secure' => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => $params['samesite'] ?? 'Lax'
        ]
    );
}

// Destroy the session.
session_destroy();

// Redirect to homepage.
header('Location: /froshgah/');
exit;