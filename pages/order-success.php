
<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$userId = (int) $_SESSION['user_id'];
$orderNumber = trim($_GET['order'] ?? '');

if ($orderNumber === '') {
    header("Location: /froshgah/index.php");
    exit;
}

// Only allow the customer who owns the order to view it.
$orderStatement = $pdo->prepare("
    SELECT
        id,
        order_number,
        total_amount,
        shipping_address,
        status,
        created_at
    FROM orders
    WHERE order_number = ?
      AND user_id = ?
    LIMIT 1
");

$orderStatement->execute([$orderNumber, $userId]);
$order = $orderStatement->fetch();

if (!$order) {
    http_response_code(404);
    exit("Order not found.");
}

// Get the items belonging to this order.
$itemsStatement = $pdo->prepare("
    SELECT
        product_name,
        quantity,
        price
    FROM order_items
    WHERE order_id = ?
");

$itemsStatement->execute([$order['id']]);
$orderItems = $itemsStatement->fetchAll();

function successEscape($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$shippingDetails = preg_split(
    "/\r\n|\n|\r/",
    $order['shipping_address']
);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

?>

<div class="order-success-page">

    <div class="order-success-card">

        <div class="success-icon">✓</div>

        <h1>Order Placed Successfully!</h1>

        <p class="success-description">
            Thank you for shopping with Froshgah.
            Your order has been received.
        </p>

        <div class="order-number-box">
            <span>Order Number</span>
            <strong><?= successEscape($order['order_number']); ?></strong>
        </div>

        <div class="order-status">
            <span>Order Status</span>
            <strong><?= ucfirst(successEscape($order['status'])); ?></strong>
        </div>

        <h2>Order Summary</h2>

        <div class="success-order-items">

            <?php foreach ($orderItems as $item): ?>

                <div class="success-order-item">

                    <div>
                        <strong>
                            <?= successEscape($item['product_name']); ?>
                        </strong>

                        <p>
                            Quantity: <?= (int) $item['quantity']; ?>
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

        <div class="success-total">
            <span>Total Amount</span>

            <strong>
                $<?= number_format((float) $order['total_amount'], 2); ?>
            </strong>
        </div>

        <h2>Delivery Information</h2>

        <div class="success-address">
            <?php foreach ($shippingDetails as $detail): ?>
                <p><?= successEscape($detail); ?></p>
            <?php endforeach; ?>
        </div>

        <div class="payment-information">
            <strong>Payment Method:</strong>
            Cash on Delivery
        </div>

        <p class="success-note">
            Please pay the delivery person when your order arrives.
        </p>

        <div class="success-actions">
            <a href="/froshgah/pages/products.php" class="continue-shopping">
                Continue Shopping
            </a>

            <a href="/froshgah/index.php" class="back-home">
                Back to Home
            </a>
        </div>

    </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
