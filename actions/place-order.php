
<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/notification-functions.php';

requireLogin();

// Only customers can place orders.
if (($_SESSION['user_role'] ?? '') !== 'customer') {
    http_response_code(403);
    exit('Only customer accounts can place orders.');
}

// Accept POST requests only.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /froshgah/pages/checkout.php');
    exit;
}

// Verify CSRF token.
if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {

    $_SESSION['checkout_error'] =
        'Invalid form submission. Please try again.';

    header('Location: /froshgah/pages/checkout.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];

// Collect delivery information.
$fullName = trim($_POST['full_name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$city = trim($_POST['city'] ?? '');
$address = trim($_POST['address'] ?? '');
$paymentMethod = $_POST['payment_method'] ?? '';

// Preserve customer input if validation fails.
$_SESSION['checkout_old'] = [
    'full_name' => $fullName,
    'phone' => $phone,
    'city' => $city,
    'address' => $address
];

// Validate delivery information.
if (
    $fullName === '' ||
    $phone === '' ||
    $city === '' ||
    $address === '' ||
    strlen($fullName) > 150 ||
    strlen($phone) > 30 ||
    strlen($city) > 100 ||
    strlen($address) > 1000
) {

    $_SESSION['checkout_error'] =
        'Please complete all delivery fields correctly.';

    header('Location: /froshgah/pages/checkout.php');
    exit;
}

// Cash on Delivery is the only supported payment method.
if ($paymentMethod !== 'cash_on_delivery') {

    $_SESSION['checkout_error'] =
        'Please select a valid payment method.';

    header('Location: /froshgah/pages/checkout.php');
    exit;
}

try {

    // Start a database transaction.
    $pdo->beginTransaction();

    // Lock the customer's cart.
    $cartStmt = $pdo->prepare("
        SELECT id
        FROM cart
        WHERE user_id = ?
        LIMIT 1
        FOR UPDATE
    ");

    $cartStmt->execute([$userId]);

    $cart = $cartStmt->fetch();

    if (!$cart) {
        throw new Exception('Your shopping cart was not found.');
    }

    $cartId = (int) $cart['id'];

    // Retrieve cart items and lock their product rows.
    $itemsStmt = $pdo->prepare("
        SELECT
            ci.id AS cart_item_id,
            ci.quantity,
            p.id AS product_id,
            p.name,
            p.price,
            p.stock,
            p.status
        FROM cart_items ci
        INNER JOIN products p
            ON p.id = ci.product_id
        WHERE ci.cart_id = ?
        ORDER BY p.id ASC
        FOR UPDATE
    ");

    $itemsStmt->execute([$cartId]);

    $items = $itemsStmt->fetchAll();

    if (empty($items)) {
        throw new Exception('Your shopping cart is empty.');
    }

    // Calculate order subtotal using current database prices.
    $subtotalCents = 0;

    foreach ($items as $item) {

        $quantity = (int) $item['quantity'];
        $stock = (int) $item['stock'];

        $priceCents = (int) round(
            (float) $item['price'] * 100
        );

        // Check product availability.
        if ($item['status'] !== 'active') {
            throw new Exception(
                $item['name'] . ' is currently unavailable.'
            );
        }

        // Validate quantity.
        if ($quantity < 1) {
            throw new Exception(
                'An invalid quantity was found for ' .
                $item['name'] . '.'
            );
        }

        // Verify available stock.
        if ($stock < $quantity) {
            throw new Exception(
                'Insufficient stock for ' . $item['name'] .
                '. Available quantity: ' . $stock . '.'
            );
        }

        // Validate product price.
        if ($priceCents < 0) {
            throw new Exception(
                'An invalid price was found for ' .
                $item['name'] . '.'
            );
        }

        $subtotalCents += $priceCents * $quantity;
    }

    // Free shipping.
    $shippingCents = 0;

    $totalCents = $subtotalCents + $shippingCents;

    $totalAmount = number_format(
        $totalCents / 100,
        2,
        '.',
        ''
    );

    // Generate a unique order number.
    $orderNumber =
        'FR-' . date('Ymd') . '-' .
        strtoupper(bin2hex(random_bytes(4)));

    // Prepare delivery information.
    $shippingAddress =
        "Full Name: " . $fullName . "\n" .
        "Phone: " . $phone . "\n" .
        "City: " . $city . "\n" .
        "Address: " . $address;

    // Create the order.
    $orderStmt = $pdo->prepare("
        INSERT INTO orders (
            user_id,
            order_number,
            total_amount,
            shipping_address,
            status
        )
        VALUES (?, ?, ?, ?, 'pending')
    ");

    $orderStmt->execute([
        $userId,
        $orderNumber,
        $totalAmount,
        $shippingAddress
    ]);

    $orderId = (int) $pdo->lastInsertId();

    // Prepare order item insertion.
    $orderItemStmt = $pdo->prepare("
        INSERT INTO order_items (
            order_id,
            product_id,
            product_name,
            quantity,
            price
        )
        VALUES (?, ?, ?, ?, ?)
    ");

    // Prepare stock update.
    $stockStmt = $pdo->prepare("
        UPDATE products
        SET
            status = CASE
                WHEN stock = ? THEN 'out_of_stock'
                ELSE status
            END,
            stock = stock - ?
        WHERE id = ?
          AND status = 'active'
          AND stock >= ?
    ");

    // Save order items and reduce inventory.
    foreach ($items as $item) {

        $quantity = (int) $item['quantity'];

        $productId = (int) $item['product_id'];

        $price = number_format(
            (float) $item['price'],
            2,
            '.',
            ''
        );

        // Save product details in the order.
        $orderItemStmt->execute([
            $orderId,
            $productId,
            $item['name'],
            $quantity,
            $price
        ]);

        // Reduce product stock.
        $stockStmt->execute([
            $quantity,
            $quantity,
            $productId,
            $quantity
        ]);

        // Confirm that exactly one product row was updated.
        if ($stockStmt->rowCount() !== 1) {
            throw new Exception(
                'Stock could not be reserved for ' .
                $item['name'] . '.'
            );
        }
    }

    // Create the pending Cash on Delivery payment.
    $paymentStmt = $pdo->prepare("
        INSERT INTO payments (
            order_id,
            payment_method,
            transaction_id,
            amount,
            status
        )
        VALUES (?, 'cash_on_delivery', NULL, ?, 'pending')
    ");

    $paymentStmt->execute([
        $orderId,
        $totalAmount
    ]);

    // Clear purchased items from the customer's cart.
    $clearCartStmt = $pdo->prepare("
        DELETE FROM cart_items
        WHERE cart_id = ?
    ");

    $clearCartStmt->execute([$cartId]);

    /*
    |--------------------------------------------------------------------------
    | AUTOMATIC ORDER NOTIFICATION
    |--------------------------------------------------------------------------
    | Create a notification for the customer.
    | This happens inside the same transaction as the order.
    */

    $notificationTitle = 'Order placed successfully!';

    $notificationMessage =
        'Your order ' . $orderNumber .
        ' has been received successfully. ' .
        'Your payment method is Cash on Delivery. ' .
        'We will process your order soon.';

    $notificationCreated = createNotification(
        $pdo,
        $userId,
        $notificationTitle,
        $notificationMessage,
        'order',
        $orderId
    );

    if (!$notificationCreated) {
        throw new Exception(
            'The order notification could not be created.'
        );
    }

    // Commit order, order items, payment, stock,
    // cart changes, and notification together.

// Notify all active administrators about the new order.
// This must run before committing the transaction.

notifyAdminsAboutNewOrder(
    $pdo,
    (int) $orderId,
    (string) $orderNumber,
    (string) $fullName,
    (float) $totalAmount
);

    $pdo->commit();

    // Remove saved checkout form information.
    unset($_SESSION['checkout_old']);

    // Set success message.
    $_SESSION['order_success'] =
        'Your order has been placed successfully.';

    // Redirect to order confirmation page.
    header(
        'Location: /froshgah/pages/order-success.php?order=' .
        urlencode($orderNumber)
    );

    exit;

} catch (Throwable $e) {

    // Roll back all database changes if something fails.
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // Record technical details in the server error log.
    error_log(
        'Froshgah Place Order Error: ' . $e->getMessage()
    );

    // Show safe error messages to the customer.
    if ($e instanceof PDOException) {

        $_SESSION['checkout_error'] =
            'A database error occurred while placing your order. Please try again.';

    } elseif ($e instanceof Exception) {

        $_SESSION['checkout_error'] = $e->getMessage();

    } else {

        $_SESSION['checkout_error'] =
            'We could not place your order. Please try again.';
    }

    // Preserve checkout information.
    header('Location: /froshgah/pages/checkout.php');
    exit;
}
