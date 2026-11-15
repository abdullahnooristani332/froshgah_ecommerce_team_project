
<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

if (($_SESSION['user_role'] ?? '') !== 'customer') {
    http_response_code(403);
    exit('Only customer accounts can manage shopping carts.');
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

if ($itemId === false || $itemId === null || $itemId < 1) {
    $_SESSION['cart_error'] = 'Invalid shopping cart item.';
    header('Location: /froshgah/pages/cart.php');
    exit;
}

try {

    // Delete only the selected item from the current customer's cart.
    $stmt = $pdo->prepare("
        DELETE ci
        FROM cart_items ci
        INNER JOIN cart c
            ON c.id = ci.cart_id
        WHERE ci.id = ?
          AND c.user_id = ?
    ");

    $stmt->execute([
        $itemId,
        (int) $_SESSION['user_id']
    ]);

    if ($stmt->rowCount() > 0) {

        $_SESSION['cart_message'] =
            'Product successfully removed from your shopping cart.';

    } else {

        $_SESSION['cart_error'] =
            'The selected cart item was not found.';

    }

} catch (Throwable $e) {

    error_log('Remove cart item error: ' . $e->getMessage());

    $_SESSION['cart_error'] =
        'We could not remove this product. Please try again.';
}

header('Location: /froshgah/pages/cart.php');
exit;
