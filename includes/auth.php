
<?php

/**
 * Froshgah - Authentication and CSRF Security
 */

require_once __DIR__ . '/../config/session.php';


/**
 * Check whether a valid user ID exists in the session.
 */
function isLoggedIn(): bool
{
    if (!isset($_SESSION['user_id'])) {
        return false;
    }

    $userId = filter_var(
        $_SESSION['user_id'],
        FILTER_VALIDATE_INT
    );

    return $userId !== false && $userId > 0;
}


/**
 * Get the currently logged-in user's session information.
 */
function currentUser(): ?array
{
    if (!isLoggedIn()) {
        return null;
    }

    return [
        'id' => (int) $_SESSION['user_id'],
        'name' => (string) ($_SESSION['user_name'] ?? ''),
        'email' => (string) ($_SESSION['user_email'] ?? ''),
        'role' => (string) ($_SESSION['user_role'] ?? 'customer')
    ];
}


/**
 * Regenerate the session ID after successful authentication.
 *
 * Call this immediately after verifying a user's login credentials
 * and before redirecting to the protected page.
 */
function regenerateUserSession(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
}


/**
 * Redirect visitors who are not logged in.
 */
function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: /froshgah/pages/login.php');
        exit;
    }
}


/**
 * Allow access only to administrators.
 */
function requireAdmin(): void
{
    requireLogin();

    if (($_SESSION['user_role'] ?? '') !== 'admin') {
        http_response_code(403);
        exit('Access denied. Administrator permission required.');
    }
}


/**
 * Generate or retrieve the CSRF token for the current session.
 */
function generateCsrfToken(): string
{
    if (
        !isset($_SESSION['csrf_token'])
        || !is_string($_SESSION['csrf_token'])
        || $_SESSION['csrf_token'] === ''
    ) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}


/**
 * Verify a submitted CSRF token.
 */
function verifyCsrfToken(?string $token): bool
{
    if (
        !isset($_SESSION['csrf_token'])
        || !is_string($_SESSION['csrf_token'])
        || !is_string($token)
        || $token === ''
    ) {
        return false;
    }

    return hash_equals($_SESSION['csrf_token'], $token);
}
