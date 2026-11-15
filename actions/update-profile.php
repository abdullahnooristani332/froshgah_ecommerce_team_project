
<?php

require_once __DIR__ . '/../includes/auth.php';
requireLogin();

require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /froshgah/pages/profile.php');
    exit;
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    exit('Invalid form submission.');
}

$userId = (int) $_SESSION['user_id'];

$name = trim($_POST['name'] ?? '');
$phone = trim($_POST['phone'] ?? '');

$nameLength = function_exists('mb_strlen')
    ? mb_strlen($name, 'UTF-8')
    : strlen($name);

if ($name === '' || $nameLength > 150) {
    $_SESSION['profile_error'] = 'Please enter a valid name.';
    header('Location: /froshgah/pages/profile.php');
    exit;
}

if (strlen($phone) > 30) {
    $_SESSION['profile_error'] = 'Phone number is too long.';
    header('Location: /froshgah/pages/profile.php');
    exit;
}

try {

    $stmt = $pdo->prepare("
        UPDATE users
        SET name = :name,
            phone = :phone
        WHERE id = :id
    ");

    $stmt->execute([
        ':name' => $name,
        ':phone' => $phone !== '' ? $phone : null,
        ':id' => $userId
    ]);

    $_SESSION['user_name'] = $name;

    $_SESSION['profile_success'] =
        'Your profile has been updated successfully.';

} catch (PDOException $e) {

    error_log('Profile update error: ' . $e->getMessage());

    $_SESSION['profile_error'] =
        'Unable to update your profile. Please try again.';
}

header('Location: /froshgah/pages/profile.php');
exit;
