
<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

if (($_SESSION['user_role'] ?? '') !== 'customer') {
    http_response_code(403);
    exit('Access denied.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}

// Verify CSRF token.
if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $_SESSION['notification_error'] = 'Invalid security token. Please try again.';
    header('Location: /froshgah/pages/notifications.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];

try {

    // Mark all unread notifications as read.
    if (isset($_POST['mark_all'])) {

        $statement = $pdo->prepare("
            UPDATE notifications
            SET is_read = 1
            WHERE user_id = ?
              AND is_read = 0
        ");

        $statement->execute([$userId]);

        $_SESSION['notification_success'] =
            'All notifications have been marked as read.';

    } else {

        // Mark one notification as read.
        $notificationId = filter_input(
            INPUT_POST,
            'notification_id',
            FILTER_VALIDATE_INT
        );

        if (!$notificationId || $notificationId < 1) {
            $_SESSION['notification_error'] = 'Invalid notification.';
            header('Location: /froshgah/pages/notifications.php');
            exit;
        }

        $statement = $pdo->prepare("
            UPDATE notifications
            SET is_read = 1
            WHERE id = ?
              AND user_id = ?
        ");

        $statement->execute([
            $notificationId,
            $userId
        ]);

        $_SESSION['notification_success'] =
            'Notification marked as read.';
    }

} catch (PDOException $e) {

    error_log('Notification update error: ' . $e->getMessage());

    $_SESSION['notification_error'] =
        'Unable to update notifications. Please try again.';

}

header('Location: /froshgah/pages/notifications.php');
exit;
