
<?php

require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . '/../config/database.php';

// Only POST requests are allowed.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /froshgah/admin/inventory.php');
    exit;
}

// Verify CSRF token.
if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {

    $_SESSION['inventory_error'] =
        'Invalid security token. Please try again.';

    header('Location: /froshgah/admin/inventory.php');
    exit;
}

// Validate product ID.
$productId = filter_var(
    $_POST['product_id'] ?? '',
    FILTER_VALIDATE_INT
);

// Validate stock quantity.
$stockInput = $_POST['stock'] ?? '';

if (
    !$productId ||
    $productId < 1 ||
    !is_string($stockInput) ||
    !preg_match('/^\d+$/', $stockInput)
) {

    $_SESSION['inventory_error'] =
        'Please provide a valid product and stock quantity.';

    header('Location: /froshgah/admin/inventory.php');
    exit;
}

$stock = filter_var(
    $stockInput,
    FILTER_VALIDATE_INT
);

if ($stock === false || $stock < 0 || $stock > 1000000000) {

    $_SESSION['inventory_error'] =
        'Stock quantity must be between 0 and 1,000,000,000.';

    header('Location: /froshgah/admin/inventory.php');
    exit;
}

try {

    $pdo->beginTransaction();

    // Lock the product while its inventory is being updated.
    $checkStatement = $pdo->prepare("
        SELECT id, name, stock, status
        FROM products
        WHERE id = ?
        LIMIT 1
        FOR UPDATE
    ");

    $checkStatement->execute([$productId]);

    $product = $checkStatement->fetch();

    if (!$product) {
        throw new Exception('Product not found.');
    }

    // Preserve intentionally inactive products.
    // Replenished out-of-stock products become active.
    $updateStatement = $pdo->prepare("
        UPDATE products
        SET
            stock = ?,
            status = CASE
                WHEN status = 'inactive' THEN 'inactive'
                WHEN ? = 0 THEN 'out_of_stock'
                WHEN status = 'out_of_stock' THEN 'active'
                ELSE status
            END,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = ?
    ");

    $updateStatement->execute([
        $stock,
        $stock,
        $productId
    ]);

    $pdo->commit();

    $_SESSION['inventory_message'] =
        'Stock for "' . $product['name'] . '" was successfully updated.';

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Inventory update error: ' . $e->getMessage());

    $_SESSION['inventory_error'] =
        'Could not update the product stock. Please try again.';

}

header('Location: /froshgah/admin/inventory.php');
exit;
