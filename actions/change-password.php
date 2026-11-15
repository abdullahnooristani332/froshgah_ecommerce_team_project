
<?php

/**
 * Froshgah - Secure Password Change
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

// The customer must be logged in.
requireLogin();

// Accept POST requests only.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /froshgah/pages/profile.php');
    exit;
}

// Verify CSRF token.
if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    exit('Invalid form submission. Please refresh the page and try again.');
}

// Safely collect submitted passwords.
$currentPassword = is_string($_POST['current_password'] ?? null)
    ? $_POST['current_password']
    : '';

$newPassword = is_string($_POST['new_password'] ?? null)
    ? $_POST['new_password']
    : '';

$confirmPassword = is_string($_POST['confirm_password'] ?? null)
    ? $_POST['confirm_password']
    : '';

$userId = (int) $_SESSION['user_id'];

// Validate that all password fields were submitted.
if (
    $currentPassword === ''
    || $newPassword === ''
    || $confirmPassword === ''
) {
    $_SESSION['profile_error'] =
        'Please complete all password fields.';

    header('Location: /froshgah/pages/profile.php');
    exit;
}

// Validate new password length.
if (
    strlen($newPassword) < 8
    || strlen($newPassword) > 72
) {
    $_SESSION['profile_error'] =
        'Your new password must contain between 8 and 72 characters.';

    header('Location: /froshgah/pages/profile.php');
    exit;
}

// Confirm the new password.
if ($newPassword !== $confirmPassword) {
    $_SESSION['profile_error'] =
        'The new passwords do not match.';

    header('Location: /froshgah/pages/profile.php');
    exit;
}

try {

    // Retrieve the user's current password and account status.
    $stmt = $pdo->prepare("
        SELECT
            password,
            status
        FROM users
        WHERE id = :id
        LIMIT 1
    ");

    $stmt->execute([
        ':id' => $userId
    ]);

    $user = $stmt->fetch();

    // Ensure the account exists and is active.
    if (!$user || $user['status'] !== 'active') {
        $_SESSION['profile_error'] =
            'Your account is unavailable. Please contact the administrator.';

        header('Location: /froshgah/pages/profile.php');
        exit;
    }

    // Verify the current password.
    if (!password_verify($currentPassword, $user['password'])) {
        $_SESSION['profile_error'] =
            'Your current password is incorrect.';

        header('Location: /froshgah/pages/profile.php');
        exit;
    }

    // Prevent the user from reusing the current password.
    if (password_verify($newPassword, $user['password'])) {
        $_SESSION['profile_error'] =
            'Your new password must be different from your current password.';

        header('Location: /froshgah/pages/profile.php');
        exit;
    }

    // Securely hash the new password.
    $newHash = password_hash(
        $newPassword,
        PASSWORD_DEFAULT
    );

    // Update only the currently authenticated user's password.
    $update = $pdo->prepare("
        UPDATE users
        SET password = :password
        WHERE id = :id
          AND status = 'active'
    ");

    $update->execute([
        ':password' => $newHash,
        ':id' => $userId
    ]);

    // Confirm that the password was updated.
    if ($update->rowCount() !== 1) {
        $_SESSION['profile_error'] =
            'Your password could not be changed. Please try again.';

        header('Location: /froshgah/pages/profile.php');
        exit;
    }

    // Regenerate the session ID after the security-sensitive change.
    regenerateUserSession();

    // Generate a fresh CSRF token.
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    // Refresh session activity timestamps.
    $_SESSION['last_activity'] = time();

    // Set success message.
    $_SESSION['profile_success'] =
        'Your password has been changed successfully.';

} catch (Throwable $e) {

    // Log technical details privately.
    error_log(
        'Froshgah password change error: ' . $e->getMessage()
    );

    $_SESSION['profile_error'] =
        'Unable to change your password. Please try again.';
}

header('Location: /froshgah/pages/profile.php');
exit;
