
<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

// Require customer login.
requireLogin();

// Only accept POST requests.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /froshgah/pages/products.php');
    exit;
}

// Verify CSRF token.
if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    exit('Invalid request. Please try again.');
}

// Validate product ID.
$productId = filter_var(
    $_POST['product_id'] ?? null,
    FILTER_VALIDATE_INT
);

// Validate quantity.
$quantity = filter_var(
    $_POST['quantity'] ?? null,
    FILTER_VALIDATE_INT
);

if (!$productId || $productId < 1 || !$quantity || $quantity < 1) {
    $_SESSION['cart_error'] = 'Invalid product or quantity.';
    header('Location: /froshgah/pages/products.php');
    exit;
}

try {

    // Start database transaction.
    $pdo->beginTransaction();

    // Lock the product row while checking stock.
    $productSql = "
        SELECT id, name, stock, price, status
        FROM products
        WHERE id = :id
        FOR UPDATE
    ";

    $productStmt = $pdo->prepare($productSql);

    $productStmt->execute([
        ':id' => $productId
    ]);

    $product = $productStmt->fetch();

    // Check product availability.
    if (!$product || $product['status'] !== 'active') {
        throw new Exception('This product is unavailable.');
    }

    // Check available stock.
    if ($quantity > (int)$product['stock']) {
        throw new Exception('The requested quantity exceeds available stock.');
    }

    // Find or create the customer's cart.
    $cartSql = "
        SELECT id
        FROM cart
        WHERE user_id = :user_id
        LIMIT 1
        FOR UPDATE
    ";

    $cartStmt = $pdo->prepare($cartSql);

    $cartStmt->execute([
        ':user_id' => $_SESSION['user_id']
    ]);

    $cart = $cartStmt->fetch();

    if (!$cart) {

        $createCartSql = "
            INSERT INTO cart (user_id)
            VALUES (:user_id)
        ";

        $createCartStmt = $pdo->prepare($createCartSql);

        $createCartStmt->execute([
            ':user_id' => $_SESSION['user_id']
        ]);

        $cartId = (int)$pdo->lastInsertId();

    } else {

        $cartId = (int)$cart['id'];

    }

    // Check whether this product is already in the cart.
    $itemSql = "
        SELECT id, quantity
        FROM cart_items
        WHERE cart_id = :cart_id
        AND product_id = :product_id
        LIMIT 1
        FOR UPDATE
    ";

    $itemStmt = $pdo->prepare($itemSql);

    $itemStmt->execute([
        ':cart_id' => $cartId,
        ':product_id' => $productId
    ]);

    $existingItem = $itemStmt->fetch();

    if ($existingItem) {

        // Add the requested quantity to the existing quantity.
        $newQuantity = (int)$existingItem['quantity'] + $quantity;

        if ($newQuantity > (int)$product['stock']) {
            throw new Exception('The requested quantity exceeds available stock.');
        }

        $updateSql = "
            UPDATE cart_items
            SET quantity = :quantity
            WHERE id = :id
        ";

        $updateStmt = $pdo->prepare($updateSql);

        $updateStmt->execute([
            ':quantity' => $newQuantity,
            ':id' => $existingItem['id']
        ]);

    } else {

        // Add a new product to the cart.
        $insertSql = "
            INSERT INTO cart_items
                (cart_id, product_id, quantity)
            VALUES
                (:cart_id, :product_id, :quantity)
        ";

        $insertStmt = $pdo->prepare($insertSql);

        $insertStmt->execute([
            ':cart_id' => $cartId,
            ':product_id' => $productId,
            ':quantity' => $quantity
        ]);

    }

    // Update cart timestamp.
    $updateCartSql = "
        UPDATE cart
        SET updated_at = CURRENT_TIMESTAMP
        WHERE id = :cart_id
    ";

    $updateCartStmt = $pdo->prepare($updateCartSql);

    $updateCartStmt->execute([
        ':cart_id' => $cartId
    ]);

    // Complete transaction.
    $pdo->commit();

    $_SESSION['cart_message'] =
        $product['name'] . ' has been added to your cart.';

    header('Location: /froshgah/pages/cart.php');
    exit;

} catch (Exception $e) {

    // Undo database changes if an error occurs.
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['cart_error'] = $e->getMessage();

    header('Location: /froshgah/pages/products.php');
    exit;

}
