
<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$userId = (int) $_SESSION['user_id'];

$statement = $pdo->prepare("
    SELECT
        o.id,
        o.order_number,
        o.total_amount,
        o.status,
        o.created_at,
        COUNT(oi.id) AS item_count
    FROM orders o
    LEFT JOIN order_items oi ON oi.order_id = o.id
    WHERE o.user_id = ?
    GROUP BY
        o.id,
        o.order_number,
        o.total_amount,
        o.status,
        o.created_at
    ORDER BY o.created_at DESC
");

$statement->execute([$userId]);
$orders = $statement->fetchAll();

function myOrdersEscape($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

?>

<div class="orders-page">

    <div class="orders-heading">
        <h1>My Orders</h1>
        <p>View and track your Froshgah purchases.</p>
    </div>

    <?php if (empty($orders)): ?>

        <div class="orders-empty">
            <h2>You have not placed any orders yet.</h2>
            <p>Your completed purchases will appear here.</p>

            <a href="/froshgah/pages/products.php" class="orders-button">
                Start Shopping
            </a>
        </div>

    <?php else: ?>

        <div class="orders-table-wrapper">

            <table class="orders-table">

                <thead>
                    <tr>
                        <th>Order Number</th>
                        <th>Date</th>
                        <th>Items</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Details</th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($orders as $order): ?>

                        <tr>
                            <td>
                                <strong>
                                    <?= myOrdersEscape($order['order_number']); ?>
                                </strong>
                            </td>

                            <td>
                                <?= myOrdersEscape(
                                    date('M d, Y', strtotime($order['created_at']))
                                ); ?>
                            </td>

                            <td>
                                <?= (int) $order['item_count']; ?>
                            </td>

                            <td>
                                $<?= number_format((float) $order['total_amount'], 2); ?>
                            </td>

                            <td>
                                <span class="order-status-badge status-<?= myOrdersEscape($order['status']); ?>">
                                    <?= ucfirst(myOrdersEscape($order['status'])); ?>
                                </span>
                            </td>

                            <td>
                                <a
                                    class="order-details-link"
                                    href="/froshgah/pages/order-details.php?id=<?= (int) $order['id']; ?>"
                                >
                                    View Details
                                </a>
                            </td>
                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
