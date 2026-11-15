
<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . '/../includes/notification-functions.php';

// Only POST requests are allowed.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /froshgah/admin/orders.php');
    exit;
}

// Verify CSRF token.
if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {

    $_SESSION['admin_error'] =
        'Invalid security token. Please try again.';

    header('Location: /froshgah/admin/orders.php');
    exit;
}

// Get submitted values.
$orderId = filter_input(
    INPUT_POST,
    'order_id',
    FILTER_VALIDATE_INT
);

$newStatus = trim($_POST['status'] ?? '');

// Validate order ID.
if (!$orderId || $orderId < 1) {

    $_SESSION['admin_error'] = 'Invalid order ID.';

    header('Location: /froshgah/admin/orders.php');
    exit;
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

// Validate new status.
if (!in_array($newStatus, $allowedStatuses, true)) {

    $_SESSION['admin_error'] = 'Invalid order status.';

    header('Location: /froshgah/admin/orders.php');
    exit;
}

// Customer notification messages.
$notificationMessages = [

    'confirmed' => [
        'title' => 'Your order has been confirmed!',
        'message' => 'Your order has been confirmed by our administration team. We will prepare it for processing.'
    ],

    'processing' => [
        'title' => 'Your order is being processed!',
        'message' => 'Your order is now being prepared by our team. Thank you for shopping with Froshgah.'
    ],

    'shipped' => [
        'title' => 'Your order has been shipped!',
        'message' => 'Your order has been shipped and is on its way. Please prepare to receive your delivery.'
    ],

    'delivered' => [
        'title' => 'Your order has been delivered!',
        'message' => 'Your order has been marked as delivered. Thank you for shopping with Froshgah!'
    ],

    'cancelled' => [
        'title' => 'Your order has been cancelled.',
        'message' => 'Your order has been cancelled. Please contact our support team if you have any questions.'
    ],

    'pending' => [
        'title' => 'Your order status has been updated.',
        'message' => 'Your order status has been changed to Pending.'
    ]

];

try {

    // Start transaction.
    $pdo->beginTransaction();

    // Lock the order and retrieve its customer.
    $orderStmt = $pdo->prepare("
        SELECT
            id,
            user_id,
            order_number,
            status
        FROM orders
        WHERE id = ?
        LIMIT 1
        FOR UPDATE
    ");

    $orderStmt->execute([$orderId]);

    $order = $orderStmt->fetch();

    if (!$order) {
        throw new Exception('The requested order was not found.');
    }

    $customerId = (int) $order['user_id'];

    $orderNumber = $order['order_number'];

    $oldStatus = $order['status'];

    // Prevent changing an order to its existing status.
    if ($oldStatus === $newStatus) {
        throw new Exception(
            'The order already has this status.'
        );
    }

    // A cancelled order cannot be reopened through this action.
    // This prevents stock from becoming inconsistent.
    if ($oldStatus === 'cancelled') {
        throw new Exception(
            'A cancelled order cannot be reopened through this action.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | RESTORE PRODUCT STOCK WHEN AN ORDER IS CANCELLED
    |--------------------------------------------------------------------------
    */

    if ($newStatus === 'cancelled') {

        // Retrieve all order items.
        $itemsStmt = $pdo->prepare("
            SELECT
                product_id,
                quantity
            FROM order_items
            WHERE order_id = ?
        ");

        $itemsStmt->execute([$orderId]);

        $orderItems = $itemsStmt->fetchAll();

        // Restore stock for products that still exist.
        $restoreStockStmt = $pdo->prepare("
            UPDATE products
            SET
                stock = stock + ?,
                status = CASE
                    WHEN status = 'inactive' THEN 'inactive'
                    ELSE 'active'
                END
            WHERE id = ?
        ");

        foreach ($orderItems as $item) {

            if ($item['product_id'] === null) {
                continue;
            }

            $productId = (int) $item['product_id'];

            $quantity = (int) $item['quantity'];

            if ($quantity < 1) {
                throw new Exception(
                    'Invalid order item quantity.'
                );
            }

            $restoreStockStmt->execute([
                $quantity,
                $productId
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE ORDER STATUS
    |--------------------------------------------------------------------------
    */

    $updateStmt = $pdo->prepare("
        UPDATE orders
        SET status = ?
        WHERE id = ?
    ");

    $updateStmt->execute([
        $newStatus,
        $orderId
    ]);

    /*
    |--------------------------------------------------------------------------
    | CREATE CUSTOMER NOTIFICATION
    |--------------------------------------------------------------------------
    */

    $notificationData = $notificationMessages[$newStatus];

    $notificationTitle = $notificationData['title'];

    $notificationMessage =
        $notificationData['message'] .
        ' Order number: ' . $orderNumber . '.';

    $notificationCreated = createNotification(
        $pdo,
        $customerId,
        $notificationTitle,
        $notificationMessage,
        'order',
        $orderId
    );

    if (!$notificationCreated) {
        throw new Exception(
            'Could not create the customer notification.'
        );
    }

    // Commit status update, stock restoration,
    // and notification together.
    $pdo->commit();

    $_SESSION['admin_success'] =
        'Order status updated successfully. The customer has been notified.';

} catch (Throwable $e) {

    // Roll back failed operations.
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log(
        'Froshgah Update Order Status Error: ' .
        $e->getMessage()
    );

    $_SESSION['admin_error'] =
        $e instanceof Exception
            ? $e->getMessage()
            : 'Unable to update the order status. Please try again.';
}

header('Location: /froshgah/admin/orders.php');
exit;
