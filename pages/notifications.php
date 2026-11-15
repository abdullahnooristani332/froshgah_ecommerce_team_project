
<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

if (($_SESSION['user_role'] ?? '') !== 'customer') {
    http_response_code(403);
    exit('Access denied.');
}

$pageTitle = 'My Notifications - Froshgah';

$userId = (int) $_SESSION['user_id'];

$successMessage = $_SESSION['notification_success'] ?? '';
unset($_SESSION['notification_success']);

$errorMessage = $_SESSION['notification_error'] ?? '';
unset($_SESSION['notification_error']);

// Get all notifications belonging to this customer.
$notificationStatement = $pdo->prepare("
    SELECT
        n.id,
        n.order_id,
        n.title,
        n.message,
        n.type,
        n.is_read,
        n.created_at,
        o.order_number
    FROM notifications n
    LEFT JOIN orders o
        ON n.order_id = o.id
    WHERE n.user_id = ?
    ORDER BY n.created_at DESC, n.id DESC
");

$notificationStatement->execute([$userId]);

$notifications = $notificationStatement->fetchAll();

// Count unread notifications.
$unreadStatement = $pdo->prepare("
    SELECT COUNT(*)
    FROM notifications
    WHERE user_id = ?
      AND is_read = 0
");

$unreadStatement->execute([$userId]);

$unreadCount = (int) $unreadStatement->fetchColumn();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

?>

<main class="container notifications-page">

    <div class="notifications-heading">

        <div>
            <h1>My Notifications</h1>

            <p>
                Stay updated about your orders and account activities.
            </p>
        </div>

        <div class="notification-count-summary">
            <?= $unreadCount ?> unread
        </div>

    </div>

    <?php if ($successMessage !== ''): ?>

        <div class="alert alert-success">
            <?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?>
        </div>

    <?php endif; ?>

    <?php if ($errorMessage !== ''): ?>

        <div class="alert alert-error">
            <?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?>
        </div>

    <?php endif; ?>

    <?php if ($unreadCount > 0): ?>

        <form
            action="/froshgah/actions/mark-notifications-read.php"
            method="POST"
            class="mark-all-notifications-form"
        >

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars(generateCsrfToken(), ENT_QUOTES, 'UTF-8') ?>"
            >

            <input type="hidden" name="mark_all" value="1">

            <button type="submit" class="btn btn-primary">
                Mark All as Read
            </button>

        </form>

    <?php endif; ?>

    <?php if (empty($notifications)): ?>

        <div class="notifications-empty">

            <div class="notifications-empty-icon">
                <span>🔔</span>
            </div>

            <h2>No Notifications Yet</h2>

            <p>
                You will see order updates and important messages here.
            </p>

            <a href="/froshgah/pages/products.php" class="btn btn-primary">
                Explore Products
            </a>

        </div>

    <?php else: ?>

        <div class="notifications-list">

            <?php foreach ($notifications as $notification): ?>

                <?php
                    $isRead = (int) $notification['is_read'] === 1;

                    $notificationType = $notification['type'] ?? 'general';

                    $notificationIcon = '🔔';

                    if ($notificationType === 'order') {
                        $notificationIcon = '📦';
                    } elseif ($notificationType === 'payment') {
                        $notificationIcon = '💳';
                    } elseif ($notificationType === 'account') {
                        $notificationIcon = '👤';
                    }
                ?>

                <article class="notification-card <?= $isRead ? 'is-read' : 'is-unread' ?>">

                    <div class="notification-card-icon">
                        <?= $notificationIcon ?>
                    </div>

                    <div class="notification-card-content">

                        <div class="notification-card-top">

                            <h3>
                                <?= htmlspecialchars(
                                    $notification['title'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </h3>

                            <?php if (!$isRead): ?>

                                <span class="notification-unread-label">
                                    New
                                </span>

                            <?php endif; ?>

                        </div>

                        <p>
                            <?= nl2br(htmlspecialchars(
                                $notification['message'],
                                ENT_QUOTES,
                                'UTF-8'
                            )) ?>
                        </p>

                        <div class="notification-card-meta">

                            <span>
                                <?= htmlspecialchars(
                                    date('M d, Y - h:i A', strtotime($notification['created_at'])),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </span>

                            <?php if (!empty($notification['order_number'])): ?>

                                <span>
                                    Order:
                                    <?= htmlspecialchars(
                                        $notification['order_number'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </span>

                            <?php endif; ?>

                        </div>

                        <?php if (!$isRead): ?>

                            <form
                                action="/froshgah/actions/mark-notifications-read.php"
                                method="POST"
                                class="mark-notification-form"
                            >

                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?= htmlspecialchars(generateCsrfToken(), ENT_QUOTES, 'UTF-8') ?>"
                                >

                                <input
                                    type="hidden"
                                    name="notification_id"
                                    value="<?= (int) $notification['id'] ?>"
                                >

                                <button type="submit" class="notification-read-button">
                                    Mark as Read
                                </button>

                            </form>

                        <?php endif; ?>

                        <?php if (!empty($notification['order_id'])): ?>

                            <a
                                href="/froshgah/pages/order-details.php?id=<?= (int) $notification['order_id'] ?>"
                                class="notification-order-link"
                            >
                                View Order
                            </a>

                        <?php endif; ?>

                    </div>

                </article>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
