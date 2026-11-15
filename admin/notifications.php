
<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/admin-auth.php';

$adminId = (int) $_SESSION['user_id'];

$successMessage = $_SESSION['admin_notification_success'] ?? '';
$errorMessage = $_SESSION['admin_notification_error'] ?? '';

unset(
    $_SESSION['admin_notification_success'],
    $_SESSION['admin_notification_error']
);

// Retrieve notifications belonging only to this administrator.
$statement = $pdo->prepare("
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
        ON o.id = n.order_id
    WHERE n.user_id = ?
    ORDER BY n.created_at DESC, n.id DESC
");

$statement->execute([$adminId]);
$notifications = $statement->fetchAll();

// Count unread notifications.
$countStatement = $pdo->prepare("
    SELECT COUNT(*)
    FROM notifications
    WHERE user_id = ?
      AND is_read = 0
");

$countStatement->execute([$adminId]);
$unreadCount = (int) $countStatement->fetchColumn();

function adminNotificationEscape($value): string
{
    return htmlspecialchars(
        (string) ($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

$pageTitle = 'Admin Notifications - Froshgah';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

?>

<main class="admin-notifications-page">

    <div class="admin-notifications-container">

        <div class="admin-notifications-heading">

            <div>
                <h1>Admin Notifications</h1>
                <p>
                    Stay informed about new customer orders and store activity.
                </p>
            </div>

            <div class="admin-notifications-unread-count">
                <?= $unreadCount; ?> Unread
            </div>

        </div>

        <?php if ($successMessage !== ''): ?>

            <div class="alert-message success">
                <?= adminNotificationEscape($successMessage); ?>
            </div>

        <?php endif; ?>

        <?php if ($errorMessage !== ''): ?>

            <div class="alert-message error">
                <?= adminNotificationEscape($errorMessage); ?>
            </div>

        <?php endif; ?>

        <?php if ($unreadCount > 0): ?>

            <form
                action="/froshgah/actions/admin-mark-notifications-read.php"
                method="POST"
                class="admin-notifications-mark-all-form"
            >

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= adminNotificationEscape(generateCsrfToken()); ?>"
                >

                <input
                    type="hidden"
                    name="mark_all"
                    value="1"
                >

                <button type="submit">
                    Mark All as Read
                </button>

            </form>

        <?php endif; ?>

        <section class="admin-notifications-list">

            <?php if (empty($notifications)): ?>

                <div class="admin-notifications-empty">
                    <div class="admin-notifications-empty-icon">
                        <span>🔔</span>
                    </div>

                    <h2>No notifications yet</h2>

                    <p>
                        New customer orders and other store notifications
                        will appear here.
                    </p>
                </div>

            <?php else: ?>

                <?php foreach ($notifications as $notification): ?>

                    <article class="admin-notification-card <?= (int) $notification['is_read'] === 0 ? 'unread' : 'read'; ?>">

                        <div class="admin-notification-icon">
                            <span>
                                <?= $notification['type'] === 'new_order'
                                    ? '🛍️'
                                    : '🔔'; ?>
                            </span>
                        </div>

                        <div class="admin-notification-content">

                            <div class="admin-notification-title-row">

                                <h3>
                                    <?= adminNotificationEscape(
                                        $notification['title']
                                    ); ?>
                                </h3>

                                <?php if ((int) $notification['is_read'] === 0): ?>

                                    <span class="admin-notification-unread-label">
                                        New
                                    </span>

                                <?php endif; ?>

                            </div>

                            <p>
                                <?= adminNotificationEscape(
                                    $notification['message']
                                ); ?>
                            </p>

                            <div class="admin-notification-meta">

                                <span>
                                    <?= adminNotificationEscape(
                                        date(
                                            'd M Y, h:i A',
                                            strtotime($notification['created_at'])
                                        )
                                    ); ?>
                                </span>

                                <?php if ($notification['order_number']): ?>

                                    <span>
                                        Order:
                                        <?= adminNotificationEscape(
                                            $notification['order_number']
                                        ); ?>
                                    </span>

                                <?php endif; ?>

                            </div>

                            <div class="admin-notification-actions">

                                <?php if (!empty($notification['order_id'])): ?>

                                    <a
                                        href="/froshgah/admin/order-details.php?id=<?= (int) $notification['order_id']; ?>"
                                        class="admin-notification-view-order"
                                    >
                                        View Order
                                    </a>

                                <?php endif; ?>

                                <?php if ((int) $notification['is_read'] === 0): ?>

                                    <form
                                        action="/froshgah/actions/admin-mark-notifications-read.php"
                                        method="POST"
                                    >

                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= adminNotificationEscape(generateCsrfToken()); ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="notification_id"
                                            value="<?= (int) $notification['id']; ?>"
                                        >

                                        <button
                                            type="submit"
                                            class="admin-notification-mark-read"
                                        >
                                            Mark as Read
                                        </button>

                                    </form>

                                <?php endif; ?>

                            </div>

                        </div>

                    </article>

                <?php endforeach; ?>

            <?php endif; ?>

        </section>

    </div>

</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
