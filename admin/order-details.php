
<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/admin-auth.php';

// Validate order ID.
$orderId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$orderId || $orderId < 1) {
    $_SESSION['admin_error'] = 'Invalid order ID.';
    header('Location: /froshgah/admin/orders.php');
    exit;
}

// Fetch order and customer information.
$orderStmt = $pdo->prepare("
    SELECT
        o.id,
        o.order_number,
        o.user_id,
        o.total_amount,
        o.shipping_address,
        o.status,
        o.created_at,
        u.name AS customer_name,
        u.email AS customer_email,
        u.phone AS customer_phone,
        p.payment_method,
        p.transaction_id,
        p.amount AS payment_amount,
        p.status AS payment_status,
        p.created_at AS payment_created_at
    FROM orders o
    INNER JOIN users u
        ON o.user_id = u.id
    LEFT JOIN payments p
        ON p.order_id = o.id
    WHERE o.id = ?
    LIMIT 1
");

$orderStmt->execute([$orderId]);
$order = $orderStmt->fetch();

if (!$order) {
    $_SESSION['admin_error'] = 'Order not found.';
    header('Location: /froshgah/admin/orders.php');
    exit;
}

// Fetch products belonging to this order.
$itemsStmt = $pdo->prepare("
    SELECT
        oi.product_id,
        oi.product_name,
        oi.quantity,
        oi.price,
        (oi.quantity * oi.price) AS item_total
    FROM order_items oi
    WHERE oi.order_id = ?
    ORDER BY oi.id ASC
");

$itemsStmt->execute([$orderId]);
$orderItems = $itemsStmt->fetchAll();

// Calculate item subtotal from order item snapshots.
$itemSubtotal = 0;

foreach ($orderItems as $item) {
    $itemSubtotal += (float) $item['item_total'];
}

// Escape output safely.
function orderEscape($value): string
{
    return htmlspecialchars(
        (string) ($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

// Format status labels.
function orderStatusLabel($status): string
{
    return ucwords(str_replace('_', ' ', (string) $status));
}

$pageTitle = 'Order Details - Froshgah Admin';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

?>

<main class="admin-order-details-page">

    <div class="admin-order-details-container">

        <div class="admin-order-details-heading">

            <div>
                <a
                    href="/froshgah/admin/orders.php"
                    class="admin-order-back-link"
                >
                    &larr; Back to Orders
                </a>

                <h1>Order Details</h1>

                <p>
                    Complete information about this customer order.
                </p>
            </div>

            <div class="admin-order-number">
                <span>Order Number</span>
                <strong>
                    <?= orderEscape($order['order_number']) ?>
                </strong>
            </div>

        </div>

        <!-- ORDER STATUS -->

        <section class="admin-order-status-section">

            <div>
                <span class="admin-order-label">Order Status</span>

                <strong class="admin-order-status-badge status-<?= orderEscape($order['status']) ?>">
                    <?= orderEscape(orderStatusLabel($order['status'])) ?>
                </strong>
            </div>

            <div>
                <span class="admin-order-label">Order Date</span>

                <strong>
                    <?= orderEscape(date(
                        'd M Y, h:i A',
                        strtotime($order['created_at'])
                    )) ?>
                </strong>
            </div>

            <div>
                <span class="admin-order-label">Order Total</span>

                <strong class="admin-order-total">
                    <?= number_format((float) $order['total_amount'], 2) ?>
                </strong>
            </div>

        </section>

        <div class="admin-order-details-grid">

            <!-- CUSTOMER INFORMATION -->

            <section class="admin-order-card">

                <h2>
                    <span class="admin-order-icon">👤</span>
                    Customer Information
                </h2>

                <div class="admin-order-info-row">
                    <span>Customer Name</span>
                    <strong>
                        <?= orderEscape($order['customer_name']) ?>
                    </strong>
                </div>

                <div class="admin-order-info-row">
                    <span>Email Address</span>
                    <strong>
                        <?= orderEscape($order['customer_email']) ?>
                    </strong>
                </div>

                <div class="admin-order-info-row">
                    <span>Phone Number</span>
                    <strong>
                        <?= orderEscape($order['customer_phone'] ?: 'Not provided') ?>
                    </strong>
                </div>

                <div class="admin-order-info-row">
                    <span>Customer ID</span>
                    <strong>
                        <?= (int) $order['user_id'] ?>
                    </strong>
                </div>

            </section>

            <!-- SHIPPING INFORMATION -->

            <section class="admin-order-card">

                <h2>
                    <span class="admin-order-icon">📍</span>
                    Delivery Information
                </h2>

                <div class="admin-order-shipping-address">
                    <?= nl2br(orderEscape($order['shipping_address'])) ?>
                </div>

                <p class="admin-order-muted">
                    This is the delivery address submitted during checkout.
                </p>

            </section>

            <!-- PAYMENT INFORMATION -->

            <section class="admin-order-card">

                <h2>
                    <span class="admin-order-icon">💳</span>
                    Payment Information
                </h2>

                <div class="admin-order-info-row">
                    <span>Payment Method</span>
                    <strong>
                        <?= orderEscape(
                            $order['payment_method'] ?: 'Not recorded'
                        ) ?>
                    </strong>
                </div>

                <div class="admin-order-info-row">
                    <span>Payment Status</span>

                    <strong class="admin-payment-status payment-<?= orderEscape($order['payment_status'] ?? 'pending') ?>">
                        <?= orderEscape(
                            orderStatusLabel(
                                $order['payment_status'] ?? 'pending'
                            )
                        ) ?>
                    </strong>
                </div>

                <div class="admin-order-info-row">
                    <span>Payment Amount</span>
                    <strong>
                        <?= number_format(
                            (float) ($order['payment_amount'] ?? 0),
                            2
                        ) ?>
                    </strong>
                </div>

                <div class="admin-order-info-row">
                    <span>Transaction ID</span>
                    <strong>
                        <?= orderEscape(
                            $order['transaction_id'] ?: 'Not available'
                        ) ?>
                    </strong>
                </div>

                <p class="admin-order-muted">
                    For Cash on Delivery, payment normally remains pending
                    until the cash is collected and the administrator
                    updates its payment status.
                </p>

            </section>

            <!-- ORDER SUMMARY -->

            <section class="admin-order-card">

                <h2>
                    <span class="admin-order-icon">📦</span>
                    Order Summary
                </h2>

                <div class="admin-order-info-row">
                    <span>Total Product Types</span>
                    <strong><?= count($orderItems) ?></strong>
                </div>

                <div class="admin-order-info-row">
                    <span>Total Quantity</span>

                    <strong>
                        <?php
                        $totalQuantity = 0;

                        foreach ($orderItems as $item) {
                            $totalQuantity += (int) $item['quantity'];
                        }

                        echo $totalQuantity;
                        ?>
                    </strong>
                </div>

                <div class="admin-order-info-row">
                    <span>Items Subtotal</span>
                    <strong>
                        <?= number_format($itemSubtotal, 2) ?>
                    </strong>
                </div>

                <div class="admin-order-info-row">
                    <span>Recorded Order Total</span>
                    <strong>
                        <?= number_format(
                            (float) $order['total_amount'],
                            2
                        ) ?>
                    </strong>
                </div>

            </section>

        </div>

        <!-- ORDERED PRODUCTS -->

        <section class="admin-order-card admin-order-products-card">

            <div class="admin-order-products-heading">

                <div>
                    <h2>Purchased Products</h2>
                    <p>
                        Products included in this customer's order.
                    </p>
                </div>

                <span class="admin-order-product-count">
                    <?= count($orderItems) ?> product types
                </span>

            </div>

            <?php if (empty($orderItems)): ?>

                <div class="admin-order-empty">
                    No products were found for this order.
                </div>

            <?php else: ?>

                <div class="admin-order-table-wrapper">

                    <table class="admin-order-table">

                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Product ID</th>
                                <th>Unit Price</th>
                                <th>Quantity</th>
                                <th>Total</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php foreach ($orderItems as $item): ?>

                                <tr>

                                    <td>
                                        <strong>
                                            <?= orderEscape($item['product_name']) ?>
                                        </strong>
                                    </td>

                                    <td>
                                        <?= $item['product_id'] === null
                                            ? 'Deleted product'
                                            : (int) $item['product_id'] ?>
                                    </td>

                                    <td>
                                        <?= number_format(
                                            (float) $item['price'],
                                            2
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= (int) $item['quantity'] ?>
                                    </td>

                                    <td>
                                        <strong>
                                            <?= number_format(
                                                (float) $item['item_total'],
                                                2
                                            ) ?>
                                        </strong>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                        <tfoot>
                            <tr>
                                <td colspan="4">Order Total</td>
                                <td>
                                    <?= number_format(
                                        (float) $order['total_amount'],
                                        2
                                    ) ?>
                                </td>
                            </tr>
                        </tfoot>

                    </table>

                </div>

            <?php endif; ?>

        </section>

        <div class="admin-order-details-footer">

            <a
                href="/froshgah/admin/orders.php"
                class="admin-order-back-button"
            >
                Back to All Orders
            </a>

        </div>

    </div>

</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
