
<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

// Only administrators can access this page.
if (($_SESSION['user_role'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Access denied. Administrator permission required.');
}

// Allowed order statuses.
$allowedStatuses = [
    'pending',
    'confirmed',
    'processing',
    'shipped',
    'delivered',
    'cancelled'
];

// Get selected filter.
$selectedStatus = $_GET['status'] ?? 'all';

if (
    $selectedStatus !== 'all' &&
    !in_array($selectedStatus, $allowedStatuses, true)
) {
    $selectedStatus = 'all';
}

// Get order statistics.
$statsStatement = $pdo->query("
    SELECT
        COUNT(*) AS total_orders,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_orders,
        SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) AS confirmed_orders,
        SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) AS processing_orders,
        SUM(CASE WHEN status = 'shipped' THEN 1 ELSE 0 END) AS shipped_orders,
        SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) AS delivered_orders,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled_orders
    FROM orders
");

$orderStats = $statsStatement->fetch();

// Retrieve all orders with customer information.
$sql = "
    SELECT
        o.id,
        o.order_number,
        o.total_amount,
        o.status,
        o.created_at,
        u.name AS customer_name,
        u.email AS customer_email,
        COUNT(oi.id) AS item_count
    FROM orders o
    INNER JOIN users u
        ON u.id = o.user_id
    LEFT JOIN order_items oi
        ON oi.order_id = o.id
";

$params = [];

if ($selectedStatus !== 'all') {
    $sql .= " WHERE o.status = ?";
    $params[] = $selectedStatus;
}

$sql .= "
    GROUP BY
        o.id,
        o.order_number,
        o.total_amount,
        o.status,
        o.created_at,
        u.name,
        u.email
    ORDER BY o.created_at DESC
";

$statement = $pdo->prepare($sql);
$statement->execute($params);

$orders = $statement->fetchAll();

// Support both existing and updated flash-message keys.
$flashMessage =
    $_SESSION['admin_order_message']
    ?? $_SESSION['admin_success']
    ?? '';

$flashError =
    $_SESSION['admin_order_error']
    ?? $_SESSION['admin_error']
    ?? '';

unset(
    $_SESSION['admin_order_message'],
    $_SESSION['admin_order_error'],
    $_SESSION['admin_success'],
    $_SESSION['admin_error']
);

// Safely escape output.
function adminOrdersEscape($value): string
{
    return htmlspecialchars(
        (string) ($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

// Load page layout.
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

?>

<div class="admin-orders-page">

    <!-- PAGE HEADING -->

    <div class="admin-orders-heading">

        <div>
            <h1>Order Management</h1>

            <p>
                View customer orders, inspect order details,
                and manage order statuses.
            </p>
        </div>

    </div>

    <!-- SUCCESS MESSAGE -->

    <?php if ($flashMessage !== ''): ?>

        <div class="alert-message success">
            <?= adminOrdersEscape($flashMessage); ?>
        </div>

    <?php endif; ?>

    <!-- ERROR MESSAGE -->

    <?php if ($flashError !== ''): ?>

        <div class="alert-message error">
            <?= adminOrdersEscape($flashError); ?>
        </div>

    <?php endif; ?>

    <!-- ORDER STATISTICS -->

    <div class="admin-order-statistics">

        <div class="admin-order-stat-card">

            <span>Total Orders</span>

            <strong>
                <?= (int) ($orderStats['total_orders'] ?? 0); ?>
            </strong>

        </div>

        <div class="admin-order-stat-card">

            <span>Pending</span>

            <strong>
                <?= (int) ($orderStats['pending_orders'] ?? 0); ?>
            </strong>

        </div>

        <div class="admin-order-stat-card">

            <span>Confirmed</span>

            <strong>
                <?= (int) ($orderStats['confirmed_orders'] ?? 0); ?>
            </strong>

        </div>

        <div class="admin-order-stat-card">

            <span>Processing</span>

            <strong>
                <?= (int) ($orderStats['processing_orders'] ?? 0); ?>
            </strong>

        </div>

        <div class="admin-order-stat-card">

            <span>Shipped</span>

            <strong>
                <?= (int) ($orderStats['shipped_orders'] ?? 0); ?>
            </strong>

        </div>

        <div class="admin-order-stat-card">

            <span>Delivered</span>

            <strong>
                <?= (int) ($orderStats['delivered_orders'] ?? 0); ?>
            </strong>

        </div>

        <div class="admin-order-stat-card">

            <span>Cancelled</span>

            <strong>
                <?= (int) ($orderStats['cancelled_orders'] ?? 0); ?>
            </strong>

        </div>

    </div>

    <!-- ORDER STATUS FILTERS -->

    <div class="admin-order-filters">

        <a
            href="?status=all"
            class="<?= $selectedStatus === 'all' ? 'active' : ''; ?>"
        >
            All Orders
        </a>

        <?php foreach ($allowedStatuses as $status): ?>

            <a
                href="?status=<?= urlencode($status); ?>"
                class="<?= $selectedStatus === $status ? 'active' : ''; ?>"
            >
                <?= ucfirst($status); ?>
            </a>

        <?php endforeach; ?>

    </div>

    <!-- ORDERS TABLE -->

    <?php if (empty($orders)): ?>

        <div class="orders-empty">

            <h2>No orders found.</h2>

            <p>
                Orders matching this status will appear here.
            </p>

        </div>

    <?php else: ?>

        <div class="orders-table-wrapper">

            <table class="orders-table admin-orders-table">

                <thead>

                    <tr>
                        <th>Order Number</th>
                        <th>Customer</th>
                        <th>Items</th>
                        <th>Total</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>View Details</th>
                        <th>Update Status</th>
                    </tr>

                </thead>

                <tbody>

                    <?php foreach ($orders as $order): ?>

                        <tr>

                            <!-- ORDER NUMBER -->

                            <td>
                                <strong>
                                    <?= adminOrdersEscape(
                                        $order['order_number']
                                    ); ?>
                                </strong>
                            </td>

                            <!-- CUSTOMER -->

                            <td>

                                <?= adminOrdersEscape(
                                    $order['customer_name']
                                ); ?>

                                <small class="admin-customer-email">

                                    <?= adminOrdersEscape(
                                        $order['customer_email']
                                    ); ?>

                                </small>

                            </td>

                            <!-- NUMBER OF ITEMS -->

                            <td>
                                <?= (int) $order['item_count']; ?>
                            </td>

                            <!-- TOTAL -->

                            <td>
                                $<?= number_format(
                                    (float) $order['total_amount'],
                                    2
                                ); ?>
                            </td>

                            <!-- ORDER DATE -->

                            <td>

                                <?= adminOrdersEscape(
                                    date(
                                        'M d, Y',
                                        strtotime($order['created_at'])
                                    )
                                ); ?>

                            </td>

                            <!-- ORDER STATUS -->

                            <td>

                                <span class="order-status-badge status-<?= adminOrdersEscape(
                                    $order['status']
                                ); ?>">

                                    <?= ucfirst(
                                        adminOrdersEscape(
                                            $order['status']
                                        )
                                    ); ?>

                                </span>

                            </td>

                            <!-- VIEW ORDER DETAILS -->

                            <td>

                                <a
                                    href="/froshgah/admin/order-details.php?id=<?= (int) $order['id']; ?>"
                                    class="admin-view-order-button"
                                >
                                    View Details
                                </a>

                            </td>

                            <!-- UPDATE ORDER STATUS -->

                            <td>

                                <?php if (
                                    !in_array(
                                        $order['status'],
                                        ['delivered', 'cancelled'],
                                        true
                                    )
                                ): ?>

                                    <form
                                        action="/froshgah/actions/update-order-status.php"
                                        method="POST"
                                        class="admin-status-form"
                                    >

                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= adminOrdersEscape(
                                                generateCsrfToken()
                                            ); ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="order_id"
                                            value="<?= (int) $order['id']; ?>"
                                        >

                                        <select
                                            name="status"
                                            required
                                        >

                                            <?php if (
                                                $order['status'] === 'pending'
                                            ): ?>

                                                <option value="confirmed">
                                                    Confirmed
                                                </option>

                                                <option value="cancelled">
                                                    Cancelled
                                                </option>

                                            <?php elseif (
                                                $order['status'] === 'confirmed'
                                            ): ?>

                                                <option value="processing">
                                                    Processing
                                                </option>

                                                <option value="cancelled">
                                                    Cancelled
                                                </option>

                                            <?php elseif (
                                                $order['status'] === 'processing'
                                            ): ?>

                                                <option value="shipped">
                                                    Shipped
                                                </option>

                                                <option value="cancelled">
                                                    Cancelled
                                                </option>

                                            <?php elseif (
                                                $order['status'] === 'shipped'
                                            ): ?>

                                                <option value="delivered">
                                                    Delivered
                                                </option>

                                            <?php endif; ?>

                                        </select>

                                        <button type="submit">
                                            Update
                                        </button>

                                    </form>

                                <?php else: ?>

                                    <span class="terminal-order-status">
                                        No further changes
                                    </span>

                                <?php endif; ?>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
