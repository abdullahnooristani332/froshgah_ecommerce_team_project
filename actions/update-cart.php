
<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

// Only customer accounts can manage a shopping cart.
if (($_SESSION['user_role'] ?? '') !== 'customer') {
    http_response_code(403);
    exit('Only customer accounts can update shopping carts.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /froshgah/pages/cart.php');
    exit;
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    exit('Invalid request. Please refresh the page and try again.');
}

$itemId = filter_var(
    $_POST['item_id'] ?? null,
    FILTER_VALIDATE_INT
);

$quantity = filter_var(
    $_POST['quantity'] ?? null,
    FILTER_VALIDATE_INT
);

if (
    $itemId === false || $itemId === null || $itemId < 1 ||
    $quantity === false || $quantity === null ||
    $quantity < 1 || $quantity > 1000000
) {
    $_SESSION['cart_error'] = 'Please enter a valid product quantity.';
    header('Location: /froshgah/pages/cart.php');
    exit;
}

try {

    $pdo->beginTransaction();

    // Find and lock the customer's cart item and product.
    $sql = "
        SELECT
            ci.id,
            p.stock,
            p.status
        FROM cart_items ci
        INNER JOIN cart c
            ON c.id = ci.cart_id
        INNER JOIN products p
            ON p.id = ci.product_id
        WHERE ci.id = ?
          AND c.user_id = ?
        FOR UPDATE
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $itemId,
        (int) $_SESSION['user_id']
    ]);

    $item = $stmt->fetch();

    if (!$item) {
        throw new Exception('This cart item could not be found.');
    }

    if ($item['status'] !== 'active') {
        throw new Exception('This product is currently unavailable.');
    }

    $availableStock = (int) $item['stock'];

    if ($availableStock < 1) {
        throw new Exception('This product is out of stock.');
    }

    if ($quantity > $availableStock) {
        throw new Exception(
            'Only ' . $availableStock .
            ' unit(s) are currently available.'
        );
    }

    // Update only the cart item belonging to this customer.
    $updateStmt = $pdo->prepare("
        UPDATE cart_items ci
        INNER JOIN cart c ON c.id = ci.cart_id
        SET ci.quantity = ?
        WHERE ci.id = ?
          AND c.user_id = ?
    ");

    $updateStmt->execute([
        $quantity,
        $itemId,
        (int) $_SESSION['user_id']
    ]);

    $pdo->commit();

    $_SESSION['cart_message'] =
        'Your shopping cart has been updated successfully.';

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Update cart error: ' . $e->getMessage());

    $_SESSION['cart_error'] =
        $e instanceof Exception
            ? $e->getMessage()
            : 'We could not update your cart. Please try again.';
}

header('Location: /froshgah/pages/cart.php');
exit;
