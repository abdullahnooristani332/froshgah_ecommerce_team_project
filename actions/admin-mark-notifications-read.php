
<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/admin-auth.php';

// Only POST requests are allowed.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /froshgah/admin/notifications.php');
    exit;
}

// Verify CSRF token.
if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {

    $_SESSION['admin_notification_error'] =
        'Invalid security token. Please try again.';

    header('Location: /froshgah/admin/notifications.php');
    exit;
}

$adminId = (int) $_SESSION['user_id'];

try {

    if (isset($_POST['mark_all'])) {

        // Mark only this administrator's notifications as read.
        $statement = $pdo->prepare("
            UPDATE notifications
            SET is_read = 1
            WHERE user_id = ?
              AND is_read = 0
        ");

        $statement->execute([$adminId]);

        $_SESSION['admin_notification_success'] =
            'All notifications have been marked as read.';

    } else {

        $notificationId = filter_input(
            INPUT_POST,
            'notification_id',
            FILTER_VALIDATE_INT
        );

        if (!$notificationId || $notificationId < 1) {
            throw new Exception('Invalid notification ID.');
        }

        // Update only a notification owned by this administrator.
        $statement = $pdo->prepare("
            UPDATE notifications
            SET is_read = 1
            WHERE id = ?
              AND user_id = ?
        ");

        $statement->execute([
            $notificationId,
            $adminId
        ]);

        if ($statement->rowCount() === 0) {

            // It may already have been read, so verify ownership.
            $check = $pdo->prepare("
                SELECT id
                FROM notifications
                WHERE id = ?
                  AND user_id = ?
                LIMIT 1
            ");

            $check->execute([
                $notificationId,
                $adminId
            ]);

            if (!$check->fetch()) {
                throw new Exception('Notification not found.');
            }
        }

        $_SESSION['admin_notification_success'] =
            'Notification marked as read.';
    }

} catch (Throwable $e) {

    error_log(
        'Admin Notification Error: ' . $e->getMessage()
    );

    $_SESSION['admin_notification_error'] =
        $e instanceof Exception
            ? $e->getMessage()
            : 'Unable to update the notification.';
}

header('Location: /froshgah/admin/notifications.php');
exit;
