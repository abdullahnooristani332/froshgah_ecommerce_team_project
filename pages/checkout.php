
<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

// Only customers can use checkout.
if (($_SESSION['user_role'] ?? '') !== 'customer') {
    http_response_code(403);
    exit('Only customer accounts can access checkout.');
}

$userId = (int) $_SESSION['user_id'];

$pageTitle = 'Checkout - Froshgah';

// Escape output safely.
function checkoutEscape($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}

// Retrieve the checkout error.
$checkoutError = $_SESSION['checkout_error'] ?? '';
unset($_SESSION['checkout_error']);

// Retrieve previously submitted form values, if any.
$oldCheckout = $_SESSION['checkout_old'] ?? [];
unset($_SESSION['checkout_old']);

$fullName = $oldCheckout['full_name']
    ?? ($_SESSION['user_name'] ?? '');

$phone = $oldCheckout['phone'] ?? '';
$city = $oldCheckout['city'] ?? '';
$address = $oldCheckout['address'] ?? '';

// Get the customer's cart.
$cartStatement = $pdo->prepare("
    SELECT id
    FROM cart
    WHERE user_id = ?
    LIMIT 1
");

$cartStatement->execute([$userId]);

$cart = $cartStatement->fetch();

$items = [];
$subtotalCents = 0;
$cartAvailable = true;
$unavailableProducts = [];

if ($cart) {

    // Retrieve cart products and their current database prices.
    $itemStatement = $pdo->prepare("
        SELECT
            ci.id AS cart_item_id,
            ci.quantity,
            p.id AS product_id,
            p.name,
            p.price,
            p.stock,
            p.status,
            (
                SELECT pi.image_path
                FROM product_images pi
                WHERE pi.product_id = p.id
                ORDER BY pi.is_primary DESC, pi.id ASC
                LIMIT 1
            ) AS image_path
        FROM cart_items ci
        INNER JOIN products p
            ON p.id = ci.product_id
        WHERE ci.cart_id = ?
        ORDER BY ci.id DESC
    ");

    $itemStatement->execute([(int) $cart['id']]);

    $items = $itemStatement->fetchAll();

    foreach ($items as $item) {

        $quantity = (int) $item['quantity'];
        $priceCents = (int) round(
            (float) $item['price'] * 100
        );

        $subtotalCents += $priceCents * $quantity;

        // Check whether this product can be purchased.
        if (
            $item['status'] !== 'active' ||
            (int) $item['stock'] < $quantity ||
            $quantity < 1
        ) {

            $cartAvailable = false;

            $unavailableProducts[] = $item['name'];
        }
    }
}

// No cart or no cart items: return to the shopping cart.
if (!$cart || empty($items)) {

    $_SESSION['cart_error'] =
        'Your shopping cart is empty. Add products before checkout.';

    header('Location: /froshgah/pages/cart.php');
    exit;
}

// Free shipping in the current development version.
$shippingCents = 0;

$totalCents = $subtotalCents + $shippingCents;

$subtotal = $subtotalCents / 100;
$shippingFee = $shippingCents / 100;
$total = $totalCents / 100;

// Include the common page layout.
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

?>

<main class="container section checkout-page">

    <!-- Checkout Heading -->

    <div class="checkout-heading">

        <h1>Checkout</h1>

        <p>
            Enter your delivery information and review your order
            before completing your purchase.
        </p>

    </div>

    <!-- Error Message -->

    <?php if ($checkoutError !== ''): ?>

        <div class="alert-message error" role="alert">

            <?php echo checkoutEscape($checkoutError); ?>

        </div>

    <?php endif; ?>

    <!-- Cart Availability Warning -->

    <?php if (!$cartAvailable): ?>

        <div class="alert-message error" role="alert">

            <strong>Some products cannot currently be purchased.</strong>

            <p>
                One or more products are unavailable or have insufficient
                stock. Please return to your cart and correct the quantities.
            </p>

            <?php if (!empty($unavailableProducts)): ?>

                <ul>

                    <?php foreach ($unavailableProducts as $productName): ?>

                        <li>
                            <?php echo checkoutEscape($productName); ?>
                        </li>

                    <?php endforeach; ?>

                </ul>

            <?php endif; ?>

            <a href="/froshgah/pages/cart.php">
                Return to Shopping Cart
            </a>

        </div>

    <?php endif; ?>

    <div class="checkout-layout">

        <!-- LEFT: DELIVERY FORM -->

        <section class="checkout-form-section">

            <div class="checkout-section-heading">

                <h2>Delivery Information</h2>

                <p>
                    Please provide the information required to deliver
                    your order.
                </p>

            </div>

            <form
                action="/froshgah/actions/place-order.php"
                method="POST"
                class="checkout-form"
            >

                <!-- CSRF Protection -->

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?php echo checkoutEscape(generateCsrfToken()); ?>"
                >

                <!-- Full Name -->

                <div class="form-group">

                    <label for="full_name">
                        Full Name
                    </label>

                    <input
                        type="text"
                        id="full_name"
                        name="full_name"
                        value="<?php echo checkoutEscape($fullName); ?>"
                        maxlength="150"
                        autocomplete="name"
                        placeholder="Enter your full name"
                        required
                        <?php echo !$cartAvailable ? 'disabled' : ''; ?>
                    >

                </div>

                <!-- Phone -->

                <div class="form-group">

                    <label for="phone">
                        Phone Number
                    </label>

                    <input
                        type="tel"
                        id="phone"
                        name="phone"
                        value="<?php echo checkoutEscape($phone); ?>"
                        maxlength="30"
                        autocomplete="tel"
                        placeholder="Enter your phone number"
                        required
                        <?php echo !$cartAvailable ? 'disabled' : ''; ?>
                    >

                </div>

                <!-- City -->

                <div class="form-group">

                    <label for="city">
                        City
                    </label>

                    <input
                        type="text"
                        id="city"
                        name="city"
                        value="<?php echo checkoutEscape($city); ?>"
                        maxlength="100"
                        autocomplete="address-level2"
                        placeholder="Enter your city"
                        required
                        <?php echo !$cartAvailable ? 'disabled' : ''; ?>
                    >

                </div>

                <!-- Address -->

                <div class="form-group">

                    <label for="address">
                        Complete Delivery Address
                    </label>

                    <textarea
                        id="address"
                        name="address"
                        rows="5"
                        maxlength="1000"
                        autocomplete="street-address"
                        placeholder="Enter your area, street, house number, and other delivery details"
                        required
                        <?php echo !$cartAvailable ? 'disabled' : ''; ?>
                    ><?php echo checkoutEscape($address); ?></textarea>

                </div>

                <!-- Payment Method -->

                <div class="form-group">

                    <label for="payment_method">
                        Payment Method
                    </label>

                    <select
                        id="payment_method"
                        name="payment_method"
                        required
                        <?php echo !$cartAvailable ? 'disabled' : ''; ?>
                    >

                        <option value="cash_on_delivery" selected>
                            Cash on Delivery
                        </option>

                    </select>

                    <small>
                        You will pay the delivery person when your order
                        arrives.
                    </small>

                </div>

                <!-- Payment Information -->

                <div class="checkout-payment-information">

                    <strong>Cash on Delivery</strong>

                    <p>
                        Your order will be registered as pending.
                        Payment will be recorded as pending until
                        the payment is collected.
                    </p>

                </div>

                <!-- Submit -->

                <button
                    type="submit"
                    class="place-order-button"
                    <?php echo !$cartAvailable ? 'disabled' : ''; ?>
                >

                    Place Order

                </button>

                <a
                    href="/froshgah/pages/cart.php"
                    class="checkout-back-link"
                >
                    Return to Shopping Cart
                </a>

            </form>

        </section>

        <!-- RIGHT: ORDER SUMMARY -->

        <aside class="checkout-summary">

            <div class="checkout-summary-heading">

                <h2>Your Order</h2>

                <span>
                    <?php echo count($items); ?>
                    product(s)
                </span>

            </div>

            <!-- Products -->

            <div class="checkout-products-list">

                <?php foreach ($items as $item): ?>

                    <?php

                    $itemQuantity = (int) $item['quantity'];

                    $itemPrice = (float) $item['price'];

                    $itemSubtotal = $itemPrice * $itemQuantity;

                    ?>

                    <div class="checkout-product">

                        <div class="checkout-product-image">

                            <?php if (!empty($item['image_path'])): ?>

                                <img
                                    src="/froshgah/<?php echo checkoutEscape($item['image_path']); ?>"
                                    alt="<?php echo checkoutEscape($item['name']); ?>"
                                >

                            <?php else: ?>

                                <div class="checkout-no-image">
                                    No Image
                                </div>

                            <?php endif; ?>

                        </div>

                        <div class="checkout-product-info">

                            <h3>
                                <?php echo checkoutEscape($item['name']); ?>
                            </h3>

                            <p>
                                Quantity:
                                <?php echo $itemQuantity; ?>
                            </p>

                            <p class="checkout-product-price">

                                $<?php echo number_format($itemPrice, 2); ?>
                                each

                            </p>

                            <strong>

                                $<?php echo number_format($itemSubtotal, 2); ?>

                            </strong>

                            <?php if (
                                $item['status'] !== 'active' ||
                                (int) $item['stock'] < $itemQuantity
                            ): ?>

                                <small class="checkout-stock-warning">
                                    Availability problem
                                </small>

                            <?php endif; ?>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

            <!-- Total Calculation -->

            <div class="checkout-calculation">

                <div class="checkout-total-row">

                    <span>Subtotal</span>

                    <strong>
                        $<?php echo number_format($subtotal, 2); ?>
                    </strong>

                </div>

                <div class="checkout-total-row">

                    <span>Shipping</span>

                    <strong>
                        <?php if ($shippingFee == 0): ?>
                            Free
                        <?php else: ?>
                            $<?php echo number_format($shippingFee, 2); ?>
                        <?php endif; ?>
                    </strong>

                </div>

                <div class="checkout-total-row grand-total">

                    <span>Total Amount</span>

                    <strong>
                        $<?php echo number_format($total, 2); ?>
                    </strong>

                </div>

            </div>

            <div class="checkout-note">

                <p>
                    <strong>Important:</strong>
                    The final order total and product availability
                    will be checked again by the server when you place
                    your order.
                </p>

            </div>

        </aside>

    </div>

</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
