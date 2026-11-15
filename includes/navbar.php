
<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/database.php';

/*
|--------------------------------------------------------------------------
| Current page and user information
|--------------------------------------------------------------------------
*/

$currentPage = basename($_SERVER['PHP_SELF'] ?? '');

$currentRole = $_SESSION['user_role'] ?? '';

$isCustomer = isLoggedIn() && $currentRole === 'customer';
$isAdmin = isLoggedIn() && $currentRole === 'admin';

$unreadNotificationCount = 0;

/*
|--------------------------------------------------------------------------
| Notification counter
|--------------------------------------------------------------------------
*/

if (isLoggedIn() && in_array($currentRole, ['customer', 'admin'], true)) {

    try {

        $notificationStatement = $pdo->prepare("
            SELECT COUNT(*)
            FROM notifications
            WHERE user_id = ?
              AND is_read = 0
        ");

        $notificationStatement->execute([
            (int) $_SESSION['user_id']
        ]);

        $unreadNotificationCount =
            (int) $notificationStatement->fetchColumn();

    } catch (PDOException $e) {

        error_log('Navbar notification error: ' . $e->getMessage());

        $unreadNotificationCount = 0;
    }
}

/*
|--------------------------------------------------------------------------
| Navigation helper
|--------------------------------------------------------------------------
*/

function froshgahNavActive(array $pages): string
{
    global $currentPage;

    return in_array($currentPage, $pages, true)
        ? ' active'
        : '';
}

?>

<!-- FONT AWESOME -->
<link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"
>

<!-- FROSHGAH NAVBAR CSS -->
<link
    rel="stylesheet"
    href="/froshgah/assets/css/navbar.css?v=<?= filemtime(__DIR__ . '/../assets/css/navbar.css'); ?>"
>


<header class="site-header">

    <!-- =========================================
         PROMOTIONAL TOP BAR
    ========================================== -->

    <div class="top-bar">

        <div class="navbar-container top-bar-content">

            <div class="top-bar-message">

                <i class="fa-solid fa-location-dot"></i>

                <span>
                    Bringing a better shopping experience to Afghanistan
                </span>

            </div>


            <div class="top-bar-right">

                <span>
                    <i class="fa-solid fa-money-bill-wave"></i>
                    Cash on Delivery
                </span>

                <span class="top-bar-divider"></span>

                <span>
                    <i class="fa-solid fa-box"></i>
                    Easy Order Management
                </span>

            </div>

        </div>

    </div>


    <!-- =========================================
         MAIN NAVIGATION
    ========================================== -->

    <div class="main-navbar">

        <div class="navbar-container navbar-main-content">


            <!-- BRAND LOGO -->

            <a href="/froshgah/" class="navbar-logo">

              <img src="/froshgah/assets/images/thelogo.png" alt="" style="height:80px; width:160px">

            </a>


            <!-- MOBILE MENU BUTTON -->

            <button
                type="button"
                class="mobile-menu-toggle"
                id="mobileMenuToggle"
                aria-label="Open navigation menu"
                aria-expanded="false"
                aria-controls="mainNavigation"
            >
                <i class="fa-solid fa-bars"></i>
            </button>


            <!-- SEARCH BAR -->

            <form
                action="/froshgah/pages/products.php"
                method="GET"
                class="navbar-search"
            >

                <i class="fa-solid fa-magnifying-glass search-leading-icon"></i>

                <input
                    type="search"
                    name="search"
                    placeholder="Search products, brands and more..."
                    aria-label="Search products"
                    value="<?= htmlspecialchars(
                        $currentPage === 'products.php'
                            ? ($_GET['search'] ?? '')
                            : '',
                        ENT_QUOTES,
                        'UTF-8'
                    ); ?>"
                >

                <button type="submit" aria-label="Search products">
                    Search
                </button>

            </form>


            <!-- ACTION ICONS -->

            <div class="navbar-actions">


                <!-- CUSTOMER / ADMIN ACCOUNT -->

                <?php if (isLoggedIn()): ?>

                    <div class="account-dropdown">

                        <button
                            type="button"
                            class="navbar-action account-trigger"
                            id="accountTrigger"
                            aria-label="Open account menu"
                            aria-expanded="false"
                        >

                            <span class="action-icon">
                                <i class="fa-regular fa-user"></i>
                            </span>

                            <span class="action-label">
                                <small>Welcome</small>

                                <strong>
                                    <?= htmlspecialchars(
                                        $_SESSION['user_name'] ?? 'User',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>
                                </strong>
                            </span>

                            <i class="fa-solid fa-chevron-down account-chevron"></i>

                            <?php if ($unreadNotificationCount > 0): ?>
                                <span class="account-indicator"></span>
                            <?php endif; ?>

                        </button>


                        <!-- ACCOUNT DROPDOWN -->

                        <div class="account-dropdown-menu" id="accountDropdownMenu">

                            <div class="account-menu-header">

                                <div class="account-avatar">
                                    <i class="fa-solid fa-user"></i>
                                </div>

                                <div>
                                    <strong>
                                        <?= htmlspecialchars(
                                            $_SESSION['user_name'] ?? 'User',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>
                                    </strong>

                                    <small>
                                        <?= $isAdmin
                                            ? 'Administrator Account'
                                            : 'Customer Account'; ?>
                                    </small>
                                </div>

                            </div>


                            <?php if ($isCustomer): ?>

                                <a href="/froshgah/pages/profile.php">
                                    <i class="fa-regular fa-user"></i>
                                    My Profile
                                </a>

                                <a href="/froshgah/pages/my-orders.php">
                                    <i class="fa-solid fa-box"></i>
                                    My Orders
                                </a>

                                <a href="/froshgah/pages/my-reviews.php">
                                    <i class="fa-regular fa-star"></i>
                                    My Reviews
                                </a>

                                <a href="/froshgah/pages/notifications.php">

                                    <i class="fa-regular fa-bell"></i>

                                    Notifications

                                    <?php if ($unreadNotificationCount > 0): ?>
                                        <span class="notification-badge">
                                            <?= $unreadNotificationCount > 99
                                                ? '99+'
                                                : $unreadNotificationCount; ?>
                                        </span>
                                    <?php endif; ?>

                                </a>


                            <?php elseif ($isAdmin): ?>

                                <a href="/froshgah/admin/dashboard.php">
                                    <i class="fa-solid fa-gauge-high"></i>
                                    Admin Dashboard
                                </a>

                                <a href="/froshgah/admin/notifications.php">

                                    <i class="fa-regular fa-bell"></i>

                                    Notifications

                                    <?php if ($unreadNotificationCount > 0): ?>
                                        <span class="notification-badge">
                                            <?= $unreadNotificationCount > 99
                                                ? '99+'
                                                : $unreadNotificationCount; ?>
                                        </span>
                                    <?php endif; ?>

                                </a>

                                <div class="dropdown-separator"></div>

                                <a href="/froshgah/admin/products.php">
                                    <i class="fa-solid fa-box-open"></i>
                                    Manage Products
                                </a>

                                <a href="/froshgah/admin/categories.php">
                                    <i class="fa-solid fa-list"></i>
                                    Manage Categories
                                </a>

                                <a href="/froshgah/admin/inventory.php">
                                    <i class="fa-solid fa-warehouse"></i>
                                    Inventory Management
                                </a>

                                <a href="/froshgah/admin/orders.php">
                                    <i class="fa-solid fa-cart-shopping"></i>
                                    Manage Orders
                                </a>

                                <a href="/froshgah/admin/payments.php">
                                    <i class="fa-solid fa-credit-card"></i>
                                    Manage Payments
                                </a>

                                <a href="/froshgah/admin/customers.php">
                                    <i class="fa-solid fa-users"></i>
                                    Manage Customers
                                </a>

                                <a href="/froshgah/admin/reviews.php">
                                    <i class="fa-solid fa-star"></i>
                                    Manage Reviews
                                </a>

                            <?php endif; ?>


                            <div class="dropdown-separator"></div>


                            <!-- LOGOUT -->

                            <form
                                action="/froshgah/logout.php"
                                method="POST"
                                class="account-logout-form"
                            >

                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?= htmlspecialchars(
                                        generateCsrfToken(),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>"
                                >

                                <button type="submit" class="dropdown-logout">

                                    <i class="fa-solid fa-right-from-bracket"></i>

                                    Sign Out

                                </button>

                            </form>

                        </div>

                    </div>


                <?php else: ?>

                    <!-- GUEST LOGIN -->

                    <a
                        href="/froshgah/pages/login.php"
                        class="navbar-action guest-login"
                        title="Login to your account"
                    >

                        <span class="action-icon">
                            <i class="fa-regular fa-user"></i>
                        </span>

                        <span class="action-label">
                            <small>Welcome</small>
                            <strong>Login</strong>
                        </span>

                    </a>

                <?php endif; ?>


                <!-- SHOPPING CART -->

                <a
                    href="/froshgah/pages/cart.php"
                    class="navbar-cart"
                    aria-label="Shopping cart"
                    title="Shopping cart"
                >

                    <i class="fa-solid fa-cart-shopping"></i>

                    <span class="cart-label">Cart</span>

                </a>


                <!-- REGISTER BUTTON FOR GUESTS -->

                <?php if (!isLoggedIn()): ?>

                    <a
                        href="/froshgah/pages/register.php"
                        class="register-button"
                    >
                        <i class="fa-solid fa-user-plus"></i>
                        Register
                    </a>

                <?php endif; ?>


            </div>

        </div>


        <!-- =====================================
             SECONDARY NAVIGATION
        ====================================== -->

        <div class="navigation-bottom">

            <div class="navbar-container navigation-bottom-content">

                <nav class="nav-links" id="mainNavigation">

                  <a href="/froshgah/" class="<?php echo froshgahNavActive(['index.php', 'home.php']); ?>">
    Home
</a>

<a href="/froshgah/pages/products.php" class="<?php echo froshgahNavActive(['products.php', 'product-details.php']); ?>">
    Shop
</a>

<a href="/froshgah/pages/categories.php" class="<?php echo froshgahNavActive(['categories.php']); ?>">
    Categories
</a>

<a href="/froshgah/pages/about.php" class="<?php echo froshgahNavActive(['about.php']); ?>">
    About Us
</a>

<a href="/froshgah/pages/contact.php" class="<?php echo froshgahNavActive(['contact.php']); ?>">
    Contact
</a>
                

             

                </nav>


                <div class="navigation-tagline">
                    <i class="fa-solid fa-heart"></i>
                    Shopping made simpler
                </div>

            </div>

        </div>

    </div>

</header>


<!-- =========================================
     NAVBAR INTERACTIONS
========================================== -->

<script>

document.addEventListener('DOMContentLoaded', function () {

    const menuToggle = document.getElementById('mobileMenuToggle');
    const navigation = document.getElementById('mainNavigation');

    const accountTrigger = document.getElementById('accountTrigger');
    const accountMenu = document.getElementById('accountDropdownMenu');


    // MOBILE NAVIGATION

    if (menuToggle && navigation) {

        menuToggle.addEventListener('click', function () {

            const isOpen = navigation.classList.toggle('show');

            menuToggle.setAttribute(
                'aria-expanded',
                isOpen ? 'true' : 'false'
            );

            menuToggle.setAttribute(
                'aria-label',
                isOpen ? 'Close navigation menu' : 'Open navigation menu'
            );

            menuToggle.innerHTML = isOpen
                ? '<i class="fa-solid fa-xmark"></i>'
                : '<i class="fa-solid fa-bars"></i>';

        });

    }


    // ACCOUNT DROPDOWN

    if (accountTrigger && accountMenu) {

        accountTrigger.addEventListener('click', function (event) {

            event.stopPropagation();

            const isOpen = accountMenu.classList.toggle('show');

            accountTrigger.setAttribute(
                'aria-expanded',
                isOpen ? 'true' : 'false'
            );

        });


        document.addEventListener('click', function (event) {

            if (
                !accountMenu.contains(event.target) &&
                !accountTrigger.contains(event.target)
            ) {

                accountMenu.classList.remove('show');

                accountTrigger.setAttribute(
                    'aria-expanded',
                    'false'
                );

            }

        });

    }

});

</script>
