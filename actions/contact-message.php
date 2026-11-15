
<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /froshgah/pages/contact.php');
    exit;
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    $_SESSION['contact_error'] = 'Your form session has expired. Please try again.';
    header('Location: /froshgah/pages/contact.php');
    exit;
}

// Basic honeypot spam check.
if (!empty($_POST['website'] ?? '')) {
    // Do not reveal the spam check to the sender.
    $_SESSION['contact_success'] = 'Thank you. Your message has been received.';
    header('Location: /froshgah/pages/contact.php');
    exit;
}

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');

$errors = [];

if ($name === '' || mb_strlen($name) > 100) {
    $errors[] = 'Please enter a valid name.';
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
    $errors[] = 'Please enter a valid email address.';
}

if (strlen($phone) > 30) {
    $errors[] = 'The phone number is too long.';
}

if ($subject === '' || mb_strlen($subject) > 150) {
    $errors[] = 'Please enter a valid subject.';
}

if ($message === '' || mb_strlen($message) > 5000) {
    $errors[] = 'Please enter a message of no more than 5,000 characters.';
}

if (!empty($errors)) {
    $_SESSION['contact_error'] = implode(' ', $errors);
    header('Location: /froshgah/pages/contact.php');
    exit;
}

try {
    $statement = $pdo->prepare("
        INSERT INTO contact_messages
            (name, email, phone, subject, message)
        VALUES
            (:name, :email, :phone, :subject, :message)
    ");

    $statement->execute([
        ':name' => $name,
        ':email' => $email,
        ':phone' => $phone !== '' ? $phone : null,
        ':subject' => $subject,
        ':message' => $message
    ]);

    $_SESSION['contact_success'] = 'Thank you! Your message has been received.';

} catch (PDOException $e) {
    error_log('Contact form error: ' . $e->getMessage());
    $_SESSION['contact_error'] = 'We could not save your message. Please try again later.';
}

header('Location: /froshgah/pages/contact.php');
exit;
