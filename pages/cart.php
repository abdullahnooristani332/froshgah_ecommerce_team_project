
<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

// Require login.
requireLogin();

$pageTitle = "Shopping Cart - Froshgah";

// Get the customer's cart.
$cartSql = "
    SELECT id
    FROM cart
    WHERE user_id = :user_id
    LIMIT 1
";

$cartStmt = $pdo->prepare($cartSql);

$cartStmt->execute([
    ':user_id' => $_SESSION['user_id']
]);

$cart = $cartStmt->fetch();

$cartItems = [];
$grandTotal = 0;

if ($cart) {

    $cartId = (int)$cart['id'];

    // Retrieve cart items and product details.
    $itemsSql = "
        SELECT
            ci.id AS cart_item_id,
            ci.quantity,
            p.id AS product_id,
            p.name,
            p.price,
            p.stock,
            p.status,
            pi.image_path
        FROM cart_items ci
        INNER JOIN products p
            ON ci.product_id = p.id
        LEFT JOIN product_images pi
            ON p.id = pi.product_id
            AND pi.is_primary = 1
        WHERE ci.cart_id = :cart_id
        ORDER BY ci.id DESC
    ";

    $itemsStmt = $pdo->prepare($itemsSql);

    $itemsStmt->execute([
        ':cart_id' => $cartId
    ]);

    $cartItems = $itemsStmt->fetchAll();

    // Calculate total.
    foreach ($cartItems as $item) {

        $itemSubtotal =
            (float)$item['price'] * (int)$item['quantity'];

        $grandTotal += $itemSubtotal;

    }

}

// Retrieve temporary messages.
$successMessage = $_SESSION['cart_message'] ?? '';
$errorMessage = $_SESSION['cart_error'] ?? '';

unset(
    $_SESSION['cart_message'],
    $_SESSION['cart_error']
);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

?>

<main class="container section">

    <div class="cart-heading">

        <h1>Shopping Cart</h1>

        <p>
            Review your selected products before checkout.
        </p>

    </div>

    <?php if ($successMessage): ?>

        <div class="alert alert-success">
            <?php echo htmlspecialchars($successMessage); ?>
        </div>

    <?php endif; ?>

    <?php if ($errorMessage): ?>

        <div class="alert alert-error">
            <?php echo htmlspecialchars($errorMessage); ?>
        </div>

    <?php endif; ?>

    <?php if (count($cartItems) > 0): ?>

        <div class="cart-layout">

            <div class="cart-items">

                <?php foreach ($cartItems as $item): ?>

                    <?php

                    $itemSubtotal =
                        (float)$item['price'] * (int)$item['quantity'];

                    $isAvailable =
                        $item['status'] === 'active'
                        && (int)$item['stock'] >= (int)$item['quantity'];

                    ?>

                    <div class="cart-item">

                        <div class="cart-item-image">

                            <?php if (!empty($item['image_path'])): ?>

                                <img
                                    src="/froshgah/<?php echo htmlspecialchars($item['image_path']); ?>"
                                    alt="<?php echo htmlspecialchars($item['name']); ?>"
                                >

                            <?php else: ?>

                                <div class="product-placeholder">
                                    No Image
                                </div>

                            <?php endif; ?>

                        </div>

                        <div class="cart-item-info">

                            <h3>
                                <?php echo htmlspecialchars($item['name']); ?>
                            </h3>

                            <p class="cart-item-price">

                                Price:

                                $<?php echo number_format((float)$item['price'], 2); ?>

                            </p>

                            <p>

                                Quantity:

                                <?php echo (int)$item['quantity']; ?>

                            </p>

                            <p class="cart-item-subtotal">

                                Subtotal:

                                $<?php echo number_format($itemSubtotal, 2); ?>

                            </p>

                            <?php if (!$isAvailable): ?>

                                <p class="cart-unavailable">
                                    This product is currently unavailable
                                    or has insufficient stock.
                                </p>

                            <?php endif; ?>

                            <div class="cart-item-actions">

                                <?php if ($item['status'] === 'active' && (int)$item['stock'] > 0): ?>

                                    <form
                                        action="/froshgah/actions/update-cart.php"
                                        method="POST"
                                        class="cart-update-form"
                                    >

                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?php echo htmlspecialchars(generateCsrfToken()); ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="item_id"
                                            value="<?php echo (int)$item['cart_item_id']; ?>"
                                        >

                                        <input
                                            type="number"
                                            name="quantity"
                                            value="<?php echo (int)$item['quantity']; ?>"
                                            min="1"
                                            max="<?php echo (int)$item['stock']; ?>"
                                            required
                                        >

                                        <button type="submit" class="btn btn-small">
                                            Update
                                        </button>

                                    </form>

                                <?php endif; ?>

                                <form
                                    action="/froshgah/actions/remove-from-cart.php"
                                    method="POST"
                                >

                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?php echo htmlspecialchars(generateCsrfToken()); ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="item_id"
                                        value="<?php echo (int)$item['cart_item_id']; ?>"
                                    >

                                    <button
                                        type="submit"
                                        class="remove-cart-btn"
                                    >
                                        Remove
                                    </button>

                                </form>

                            </div>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

            <aside class="cart-summary">

                <h2>Order Summary</h2>

                <div class="summary-row">

                    <span>Items</span>

                    <span><?php echo count($cartItems); ?></span>

                </div>

                <div class="summary-row">

                    <span>Subtotal</span>

                    <span>
                        $<?php echo number_format($grandTotal, 2); ?>
                    </span>

                </div>

                <div class="summary-row">

                    <span>Shipping</span>

                    <span>Calculated at checkout</span>

                </div>

                <hr>

                <div class="summary-total">

                    <span>Cart Total</span>

                    <strong>
                        $<?php echo number_format($grandTotal, 2); ?>
                    </strong>

                </div>

                <?php

                $hasUnavailableItems = false;

                foreach ($cartItems as $item) {

                    if (
                        $item['status'] !== 'active'
                        || (int)$item['stock'] < (int)$item['quantity']
                    ) {
                        $hasUnavailableItems = true;
                        break;
                    }

                }

                ?>

                <?php if (!$hasUnavailableItems): ?>

                    <a
                        href="/froshgah/pages/checkout.php"
                        class="btn checkout-btn"
                    >
                        Proceed to Checkout
                    </a>

                <?php else: ?>

                    <button class="btn checkout-btn" disabled>
                        Resolve Unavailable Items First
                    </button>

                <?php endif; ?>

                <a
                    href="/froshgah/pages/products.php"
                    class="continue-shopping"
                >
                    Continue Shopping
                </a>

            </aside>

        </div>

    <?php else: ?>

        <div class="empty-cart">

            <h2>Your Cart Is Empty</h2>

            <p>
                You have not added any products to your shopping cart yet.
            </p>

            <a
                href="/froshgah/pages/products.php"
                class="btn"
            >
                Start Shopping
            </a>

        </div>

    <?php endif; ?>

</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
