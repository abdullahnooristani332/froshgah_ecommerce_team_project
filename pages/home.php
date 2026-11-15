
<?php

// =============================================
// 1. EXISTING AUTHENTICATION AND DATABASE
// =============================================

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

$pageTitle = 'FroshGah - Shop More, Live Better';


// =============================================
// 2. LOAD ACTIVE CATEGORIES
// =============================================

$categoryStatement = $pdo->query("
    SELECT id, name
    FROM categories
    WHERE status = 'active'
    ORDER BY name ASC
    LIMIT 8
");

$homeCategories = $categoryStatement->fetchAll();


// =============================================
// 3. LOAD TRENDING PRODUCTS
// SAME DATABASE LOGIC AS PRODUCTS PAGE
// =============================================

$productStatement = $pdo->query("
    SELECT
        p.id,
        p.name,
        p.sku,
        p.description,
        p.price,
        p.stock,
        p.status,
        p.created_at,
        c.name AS category_name,

        (
            SELECT pi.image_path
            FROM product_images pi
            WHERE pi.product_id = p.id
            ORDER BY pi.is_primary DESC, pi.id ASC
            LIMIT 1
        ) AS image_path

    FROM products p

    INNER JOIN categories c
        ON p.category_id = c.id

    WHERE
        p.status IN ('active', 'out_of_stock')
        AND c.status = 'active'

    ORDER BY p.created_at DESC, p.id DESC

    LIMIT 5
");

$homeProducts = $productStatement->fetchAll();


// =============================================
// 4. INCLUDE EXISTING HEADER AND NAVBAR
// =============================================

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

?>

<link rel="stylesheet" href="/froshgah/assets/css/home.css">


<main class="frosh-home">


    <!-- =====================================
         HERO SECTION
    ====================================== -->

    <section class="frosh-hero">

        <div class="frosh-container frosh-hero-grid">

            <div class="frosh-hero-content">

                <span class="frosh-eyebrow">
                    YOUR ONE-STOP ONLINE STORE
                </span>

                <h1>
                    Shop Smarter,
                    <span>Live Better</span>
                </h1>

                <p>
                    Discover a world of amazing products
                    with unbeatable prices and fast delivery.
                    Everything you need, all in one place.
                </p>

                <a
                    href="/froshgah/pages/products.php"
                    class="frosh-primary-btn"
                >
                    Shop Now <span>→</span>
                </a>

            </div>


            <!-- MAIN COMMERCIAL HERO IMAGE -->

            <div class="frosh-hero-image">

                <img
                    src="/froshgah/assets/images/home-hero.png"
                    alt="FroshGah Online Shopping"
                    onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                >

                <div class="frosh-commercial-placeholder">

                    <span>IMAGE PLACEHOLDER</span>

                    <strong>MAIN HERO BANNER</strong>

                    <small>
                        Add home-hero.png here
                    </small>

                    <small>
                        Recommended: 750 × 550 px
                    </small>

                </div>

            </div>

        </div>

    </section>



    <!-- =====================================
         SHOP BY CATEGORY
    ====================================== -->

    <section class="frosh-section frosh-categories-section">

        <div class="frosh-container">

            <div class="frosh-section-heading">

                <h2>Shop by Category</h2>

                <a href="/froshgah/pages/products.php">
                    View All →
                </a>

            </div>


            <div class="frosh-category-grid">

                <?php foreach ($homeCategories as $category): ?>

                    <a
                        href="/froshgah/pages/products.php?category=<?php echo (int)$category['id']; ?>"
                        class="frosh-category-card"
                    >

                        <div class="frosh-category-image">

                            <div class="frosh-category-placeholder">
                            <img src="/froshgah/assets/images/<?php echo (int)$category['id']; ?>.png" alt="">
                               

                            </div>

                        </div>

                        <h3>
                            <?php echo htmlspecialchars($category['name']); ?>
                        </h3>

                    </a>

                <?php endforeach; ?>

            </div>


            <?php if (empty($homeCategories)): ?>

                <div class="frosh-empty-message">
                    No categories available yet.
                </div>

            <?php endif; ?>

        </div>

    </section>



    <!-- =====================================
         SHOPPING BENEFITS
    ====================================== -->

    <section class="frosh-benefits-section">

        <div class="frosh-container">

            <div class="frosh-benefits-grid">


                <div class="frosh-benefit">

                    <span class="frosh-benefit-icon">🚚</span>

                    <div>
                        <h3>Fast & Reliable Delivery</h3>
                        <p>Get your orders quickly</p>
                    </div>

                </div>


                <div class="frosh-benefit">

                    <span class="frosh-benefit-icon">🛡️</span>

                    <div>
                        <h3>Secure Payments</h3>
                        <p>Shop with confidence</p>
                    </div>

                </div>


                <div class="frosh-benefit">

                    <span class="frosh-benefit-icon">🎧</span>

                    <div>
                        <h3>24/7 Customer Support</h3>
                        <p>We're here to help</p>
                    </div>

                </div>


                <div class="frosh-benefit">

                    <span class="frosh-benefit-icon">📦</span>

                    <div>
                        <h3>Easy Returns</h3>
                        <p>Hassle-free returns</p>
                    </div>

                </div>


            </div>

        </div>

    </section>



    <!-- =====================================
         PROMOTIONAL BANNERS
    ====================================== -->

    <section class="frosh-section frosh-promotions-section">

        <div class="frosh-container">

            <div class="frosh-promo-grid">


                <!-- PROMOTION ONE -->

                <div class="frosh-promo-card frosh-promo-blue">

                    <div class="frosh-promo-content">

                        <span>LIMITED TIME OFFERS</span>

                        <h2>
                            Big Deals,<br>
                            Bigger Savings!
                        </h2>

                        <p>
                            Discover amazing deals
                            on your favorite products.
                        </p>

                        <a href="/froshgah/pages/products.php">
                            Shop the Deals →
                        </a>

                    </div>


                    <div class="frosh-promo-image">

                        <img
                            src="/froshgah/assets/images/promo-deals.png"
                            alt="Special shopping deals"
                            onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                        >

                        <div class="frosh-promo-placeholder">
                            PROMOTION IMAGE 1
                        </div>

                    </div>

                </div>



                <!-- PROMOTION TWO -->

                <div class="frosh-promo-card frosh-promo-orange">

                    <div class="frosh-promo-content">

                        <span>NEW ARRIVALS</span>

                        <h2>
                            Upgrade Your<br>
                            Lifestyle
                        </h2>

                        <p>
                            Premium quality,
                            better living.
                        </p>

                        <a href="/froshgah/pages/products.php">
                            Explore More →
                        </a>

                    </div>


                    <div class="frosh-promo-image">

                        <img
                            src="/froshgah/assets/images/promo-lifestyle.png"
                            alt="Lifestyle products"
                            onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                        >

                        <div class="frosh-promo-placeholder">
                            PROMOTION IMAGE 2
                        </div>

                    </div>

                </div>


            </div>

        </div>

    </section>



    <!-- =====================================
         TRENDING PRODUCTS
         REAL DATABASE PRODUCTS
    ====================================== -->

    <section class="frosh-section frosh-products-section">

        <div class="frosh-container">

            <div class="frosh-section-heading">

                <h2>Trending Products</h2>

                <a href="/froshgah/pages/products.php">
                    View All Products →
                </a>

            </div>


            <?php if (!empty($homeProducts)): ?>

                <div class="frosh-products-grid">

                    <?php foreach ($homeProducts as $product): ?>

                        <article class="frosh-product-card">


                            <!-- PRODUCT IMAGE -->

                            <a
                                href="/froshgah/pages/product-details.php?id=<?php echo (int)$product['id']; ?>"
                                class="frosh-product-image"
                            >

                                <?php if (!empty($product['image_path'])): ?>

                                    <img
                                        src="/froshgah/<?php echo htmlspecialchars(ltrim($product['image_path'], '/')); ?>"
                                        alt="<?php echo htmlspecialchars($product['name']); ?>"
                                        loading="lazy"
                                    >

                                <?php else: ?>

                                    <div class="frosh-product-placeholder">
                                        PRODUCT IMAGE
                                    </div>

                                <?php endif; ?>

                            </a>



                            <!-- PRODUCT INFORMATION -->

                            <div class="frosh-product-info">

                                <span class="frosh-product-category">

                                    <?php echo htmlspecialchars($product['category_name']); ?>

                                </span>


                                <h3>

                                    <a href="/froshgah/pages/product-details.php?id=<?php echo (int)$product['id']; ?>">

                                        <?php echo htmlspecialchars($product['name']); ?>

                                    </a>

                                </h3>


                                <p class="frosh-product-description">

                                    <?php

                                    $description = trim(
                                        $product['description'] ?? ''
                                    );

                                    if ($description === '') {

                                        echo 'Discover this amazing product.';

                                    } else {

                                        echo htmlspecialchars(
                                            mb_strimwidth(
                                                $description,
                                                0,
                                                75,
                                                '...'
                                            )
                                        );

                                    }

                                    ?>

                                </p>


                                <div class="frosh-product-price">

                                    <?php echo number_format((float)$product['price'], 2); ?>

                                </div>


                                <div class="frosh-product-stock">

                                    <?php if ((int)$product['stock'] > 0): ?>

                                        <span class="frosh-in-stock">
                                            In Stock
                                        </span>

                                    <?php else: ?>

                                        <span class="frosh-out-stock">
                                            Out of Stock
                                        </span>

                                    <?php endif; ?>

                                </div>


                                <a
                                    href="/froshgah/pages/product-details.php?id=<?php echo (int)$product['id']; ?>"
                                    class="frosh-product-button"
                                >
                                    View Product
                                </a>

                            </div>

                        </article>

                    <?php endforeach; ?>

                </div>


            <?php else: ?>

                <div class="frosh-empty-products">

                    <div class="frosh-empty-icon">🛍️</div>

                    <h3>No Products Available Yet</h3>

                    <p>
                        Products added to your database
                        will automatically appear here.
                    </p>

                    <a href="/froshgah/pages/products.php">
                        Explore Products
                    </a>

                </div>

            <?php endif; ?>

        </div>

    </section>



    <!-- =====================================
         NEWSLETTER PROMOTION
    ====================================== -->

    <section class="frosh-newsletter-section">

        <div class="frosh-container">

            <div class="frosh-newsletter">

                <div class="frosh-newsletter-icon">
                    ✉
                </div>

                <div class="frosh-newsletter-text">

                    <h2>Stay Updated!</h2>

                    <p>
                        Discover new arrivals,
                        exciting deals and exclusive offers.
                    </p>

                </div>

                <a
                    href="/froshgah/pages/products.php"
                    class="frosh-newsletter-button"
                >
                    Explore Products →
                </a>

            </div>

        </div>

    </section>


</main>


<?php require_once __DIR__ . '/../includes/footer.php'; ?>
