
<?php

/**
 * Froshgah - Secure Customer Registration
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

// Accept POST requests only.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /froshgah/pages/register.php');
    exit;
}

// Verify CSRF token.
if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    exit('Invalid form submission. Please refresh the page and try again.');
}

// Safely collect submitted values.
$name = is_string($_POST['name'] ?? null)
    ? trim($_POST['name'])
    : '';

$email = is_string($_POST['email'] ?? null)
    ? trim($_POST['email'])
    : '';

$phone = is_string($_POST['phone'] ?? null)
    ? trim($_POST['phone'])
    : '';

$password = is_string($_POST['password'] ?? null)
    ? $_POST['password']
    : '';

$confirmPassword = is_string($_POST['confirm_password'] ?? null)
    ? $_POST['confirm_password']
    : '';

// Preserve non-sensitive form values if validation fails.
$_SESSION['register_old_name'] = $name;
$_SESSION['register_old_email'] = $email;
$_SESSION['register_old_phone'] = $phone;

// Validate name.
$nameLength = function_exists('mb_strlen')
    ? mb_strlen($name, 'UTF-8')
    : strlen($name);

if ($name === '' || $nameLength > 150) {
    $_SESSION['register_error'] = 'Please enter a valid name.';
    header('Location: /froshgah/pages/register.php');
    exit;
}

// Validate email.
if (
    !filter_var($email, FILTER_VALIDATE_EMAIL)
    || strlen($email) > 150
) {
    $_SESSION['register_error'] =
        'Please enter a valid email address.';

    header('Location: /froshgah/pages/register.php');
    exit;
}

// Validate phone.
if (strlen($phone) > 30) {
    $_SESSION['register_error'] = 'Phone number is too long.';
    header('Location: /froshgah/pages/register.php');
    exit;
}

// Validate password.
// The 72-byte limit maintains compatibility with bcrypt,
// which is commonly used by PASSWORD_DEFAULT.
if (strlen($password) < 8 || strlen($password) > 72) {
    $_SESSION['register_error'] =
        'Password must contain between 8 and 72 characters.';

    header('Location: /froshgah/pages/register.php');
    exit;
}

// Confirm password.
if ($password !== $confirmPassword) {
    $_SESSION['register_error'] = 'Passwords do not match.';

    header('Location: /froshgah/pages/register.php');
    exit;
}

try {

    // Hash the password before storing it.
    $hashedPassword = password_hash(
        $password,
        PASSWORD_DEFAULT
    );

    // Always create a customer account.
    // Never accept a role from the registration form.
    $sql = "
        INSERT INTO users (
            name,
            email,
            password,
            phone,
            role,
            status
        )
        VALUES (
            :name,
            :email,
            :password,
            :phone,
            'customer',
            'active'
        )
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':name' => $name,
        ':email' => $email,
        ':password' => $hashedPassword,
        ':phone' => $phone !== '' ? $phone : null
    ]);

    // Clear saved form values.
    unset(
        $_SESSION['register_old_name'],
        $_SESSION['register_old_email'],
        $_SESSION['register_old_phone']
    );

    // Set registration success message.
    $_SESSION['success_message'] =
        'Your account has been created successfully. Please log in.';

    header('Location: /froshgah/pages/login.php');
    exit;

} catch (PDOException $e) {

    // Handle duplicate email and database constraint errors.
    if ($e->getCode() === '23000') {
        $_SESSION['register_error'] =
            'This email address is already registered.';
    } else {
        error_log(
            'Froshgah registration error: ' . $e->getMessage()
        );

        $_SESSION['register_error'] =
            'Registration failed. Please try again later.';
    }

    header('Location: /froshgah/pages/register.php');
    exit;

} catch (Throwable $e) {

    error_log(
        'Froshgah registration error: ' . $e->getMessage()
    );

    $_SESSION['register_error'] =
        'Registration failed. Please try again later.';

    header('Location: /froshgah/pages/register.php');
    exit;
}
    