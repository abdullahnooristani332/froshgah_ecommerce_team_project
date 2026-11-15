
<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$userId = (int) $_SESSION['user_id'];
$orderId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$orderId || $orderId < 1) {
    header("Location: /froshgah/pages/my-orders.php");
    exit;
}

// Retrieve only an order belonging to the logged-in customer.
$orderStatement = $pdo->prepare("
    SELECT
        id,
        order_number,
        total_amount,
        shipping_address,
        status,
        created_at
    FROM orders
    WHERE id = ?
      AND user_id = ?
    LIMIT 1
");

$orderStatement->execute([$orderId, $userId]);
$order = $orderStatement->fetch();

if (!$order) {
    http_response_code(404);
    exit("Order not found.");
}

// Retrieve the products saved in this order.
$itemStatement = $pdo->prepare("
    SELECT
        product_name,
        quantity,
        price
    FROM order_items
    WHERE order_id = ?
    ORDER BY id ASC
");

$itemStatement->execute([$orderId]);
$items = $itemStatement->fetchAll();

// Retrieve payment information.
$paymentStatement = $pdo->prepare("
    SELECT payment_method, amount, status
    FROM payments
    WHERE order_id = ?
    ORDER BY id DESC
    LIMIT 1
");

$paymentStatement->execute([$orderId]);
$payment = $paymentStatement->fetch();

function orderDetailsEscape($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$addressLines = preg_split(
    "/\r\n|\n|\r/",
    $order['shipping_address']
);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

?>

<div class="orders-page">

    <div class="orders-heading">
        <h1>Order Details</h1>
        <p>
            Order:
            <strong><?= orderDetailsEscape($order['order_number']); ?></strong>
        </p>
    </div>

    <div class="order-detail-card">

        <div class="order-detail-top">

            <div>
                <span class="detail-label">Order Date</span>
                <strong>
                    <?= orderDetailsEscape(
                        date('M d, Y H:i', strtotime($order['created_at']))
                    ); ?>
                </strong>
            </div>

            <div>
                <span class="detail-label">Order Status</span>
                <span class="order-status-badge status-<?= orderDetailsEscape($order['status']); ?>">
                    <?= ucfirst(orderDetailsEscape($order['status'])); ?>
                </span>
            </div>

        </div>

        <h2>Purchased Products</h2>

        <div class="order-detail-items">

            <?php foreach ($items as $item): ?>

                <div class="order-detail-item">

                    <div>
                        <strong>
                            <?= orderDetailsEscape($item['product_name']); ?>
                        </strong>

                        <p>
                            Quantity: <?= (int) $item['quantity']; ?>
                        </p>

                        <p>
                            Unit Price:
                            $<?= number_format((float) $item['price'], 2); ?>
                        </p>
                    </div>

                    <strong>
                        $<?= number_format(
                            (float) $item['price'] * (int) $item['quantity'],
                            2
                        ); ?>
                    </strong>

                </div>

            <?php endforeach; ?>

        </div>

        <div class="order-detail-total">
            <span>Order Total</span>
            <strong>
                $<?= number_format((float) $order['total_amount'], 2); ?>
            </strong>
        </div>

        <h2>Delivery Address</h2>

        <div class="order-delivery-address">
            <?php foreach ($addressLines as $line): ?>
                <p><?= orderDetailsEscape($line); ?></p>
            <?php endforeach; ?>
        </div>

        <h2>Payment Information</h2>

        <?php if ($payment): ?>

            <div class="payment-detail-row">
                <span>Payment Method</span>
                <strong>
                    <?= orderDetailsEscape(
                        ucwords(str_replace('_', ' ', $payment['payment_method']))
                    ); ?>
                </strong>
            </div>

            <div class="payment-detail-row">
                <span>Payment Status</span>
                <strong>
                    <?= ucfirst(orderDetailsEscape($payment['status'])); ?>
                </strong>
            </div>

            <div class="payment-detail-row">
                <span>Payment Amount</span>
                <strong>
                    $<?= number_format((float) $payment['amount'], 2); ?>
                </strong>
            </div>

        <?php else: ?>

            <p>Payment information is not available.</p>

        <?php endif; ?>

        <a href="/froshgah/pages/my-orders.php" class="orders-button">
            Back to My Orders
        </a>

    </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
