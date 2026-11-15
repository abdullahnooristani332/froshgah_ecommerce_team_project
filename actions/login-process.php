
<?php

/**
 * Froshgah - Secure Login Processing
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

// Accept POST requests only.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /froshgah/pages/login.php');
    exit;
}

// Verify CSRF token.
if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    exit('Invalid form submission. Please refresh the page and try again.');
}

// Collect submitted information.
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

// Validate input types and values.
if (
    !is_string($email)
    || !is_string($password)
    || !filter_var($email, FILTER_VALIDATE_EMAIL)
    || strlen($email) > 150
    || $password === ''
) {
    $_SESSION['login_error'] = 'Invalid email address or password.';
    header('Location: /froshgah/pages/login.php');
    exit;
}

try {

    // Find the account using a prepared statement.
    $sql = "
        SELECT
            id,
            name,
            email,
            password,
            role,
            status
        FROM users
        WHERE email = :email
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':email' => $email
    ]);

    $user = $stmt->fetch();

    // Verify account, password, role, and account status.
    if (
        !$user
        || !password_verify($password, $user['password'])
        || $user['status'] !== 'active'
        || !in_array($user['role'], ['customer', 'admin'], true)
    ) {
        // Use a generic message to avoid revealing account details.
        $_SESSION['login_error'] = 'Invalid email address or password.';

        header('Location: /froshgah/pages/login.php');
        exit;
    }

    // Regenerate the session ID after successful authentication.
    // This helps prevent session fixation attacks.
    regenerateUserSession();

    // Remove any previous authenticated session information.
    unset(
        $_SESSION['user_id'],
        $_SESSION['user_name'],
        $_SESSION['user_email'],
        $_SESSION['user_role']
    );

    // Store the authenticated user's information.
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role'];

    // Record the time of successful authentication.
    $_SESSION['login_time'] = time();
    $_SESSION['last_activity'] = time();

    // Generate a new CSRF token for the authenticated session.
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    // Clear any previous login error.
    unset($_SESSION['login_error']);

    // Redirect to the homepage.
    header('Location: /froshgah/');
    exit;

} catch (Throwable $e) {

    // Keep technical details in the server error log.
    error_log('Froshgah login error: ' . $e->getMessage());

    $_SESSION['login_error'] =
        'A system error occurred. Please try again later.';

    header('Location: /froshgah/pages/login.php');
    exit;
}
