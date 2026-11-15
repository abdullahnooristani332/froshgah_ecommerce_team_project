
<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/admin-auth.php';

// Get dashboard statistics.
$totalProducts = (int) $pdo->query("
    SELECT COUNT(*) FROM products
")->fetchColumn();

$totalCategories = (int) $pdo->query("
    SELECT COUNT(*) FROM categories
")->fetchColumn();

$totalCustomers = (int) $pdo->query("
    SELECT COUNT(*) FROM users
    WHERE role = 'customer'
")->fetchColumn();

$totalOrders = (int) $pdo->query("
    SELECT COUNT(*) FROM orders
")->fetchColumn();

$pendingOrders = (int) $pdo->query("
    SELECT COUNT(*) FROM orders
    WHERE status = 'pending'
")->fetchColumn();

$totalSales = (float) $pdo->query("
    SELECT COALESCE(SUM(total_amount), 0)
    FROM orders
    WHERE status = 'delivered'
")->fetchColumn();

// Get recent orders.
$recentStatement = $pdo->query("
    SELECT
        o.id,
        o.order_number,
        o.total_amount,
        o.status,
        o.created_at,
        u.name AS customer_name
    FROM orders o
    INNER JOIN users u ON u.id = o.user_id
    ORDER BY o.created_at DESC
    LIMIT 8
");

$recentOrders = $recentStatement->fetchAll();

function dashboardEscape($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

?>

<div class="admin-dashboard">

    <div class="admin-dashboard-heading">
        <div>
            <h1>Admin Dashboard</h1>
            <p>Welcome to the Froshgah administration panel.</p>
        </div>
    </div>

    <div class="dashboard-stats">

        <div class="dashboard-stat-card">
            <span>Total Products</span>
            <strong><?= $totalProducts; ?></strong>
            <a href="/froshgah/admin/products.php">Manage Products</a>
        </div>

        <div class="dashboard-stat-card">
            <span>Total Categories</span>
            <strong><?= $totalCategories; ?></strong>
            <a href="/froshgah/admin/categories.php">Manage Categories</a>
        </div>

        <div class="dashboard-stat-card">
            <span>Total Customers</span>
            <strong><?= $totalCustomers; ?></strong>
            <!-- <a href="/froshgah/admin/customers.php">Manage Customers</a> -->
            <small>Registered customer accounts</small>
        </div>

        <div class="dashboard-stat-card">
            <span>Total Orders</span>
            <strong><?= $totalOrders; ?></strong>
            <a href="/froshgah/admin/orders.php">Manage Orders</a>
        </div>

        <div class="dashboard-stat-card">
            <span>Pending Orders</span>
            <strong><?= $pendingOrders; ?></strong>
            <a href="/froshgah/admin/orders.php?status=pending">View Pending</a>
        </div>

        <div class="dashboard-stat-card">
            <span>Delivered Sales</span>
            <strong>$<?= number_format($totalSales, 2); ?></strong>
            <small>Based on delivered orders</small>
        </div>

    </div>

    <section class="dashboard-section">

        <div class="dashboard-section-heading">
            <h2>Recent Orders</h2>
            <a href="/froshgah/admin/orders.php">View All Orders</a>
        </div>

        <?php if (empty($recentOrders)): ?>

            <div class="orders-empty">
                <p>No orders have been placed yet.</p>
            </div>

        <?php else: ?>

            <div class="orders-table-wrapper">

                <table class="orders-table">

                    <thead>
                        <tr>
                            <th>Order Number</th>
                            <th>Customer</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($recentOrders as $order): ?>

                            <tr>
                                <td>
                                    <?= dashboardEscape($order['order_number']); ?>
                                </td>

                                <td>
                                    <?= dashboardEscape($order['customer_name']); ?>
                                </td>

                                <td>
                                    $<?= number_format((float) $order['total_amount'], 2); ?>
                                </td>

                                <td>
                                    <span class="order-status-badge status-<?= dashboardEscape($order['status']); ?>">
                                        <?= ucfirst(dashboardEscape($order['status'])); ?>
                                    </span>
                                </td>

                                <td>
                                    <?= dashboardEscape(
                                        date('M d, Y', strtotime($order['created_at']))
                                    ); ?>
                                </td>
                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php endif; ?>

    </section>

    <section class="dashboard-quick-links">

        <h2>Quick Administration</h2>

        <div class="dashboard-quick-grid">

            <a href="/froshgah/admin/products.php">
                <strong>Product Management</strong>
                <span>Add, edit, and manage store products.</span>
            </a>

            <a href="/froshgah/admin/categories.php">
                <strong>Category Management</strong>
                <span>Create and organize product categories.</span>
            </a>

            <a href="/froshgah/admin/customers.php">
                <strong>Customer Management</strong>
                <span>View registered customer accounts.</span>
            </a>

            <a href="/froshgah/admin/orders.php">
                <strong>Order Management</strong>
                <span>Review and process customer orders.</span>
            </a>

        </div>

    </section>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
