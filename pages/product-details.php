
<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

// =====================================================
// 1. HELPER FUNCTIONS
// =====================================================

function productDetailsEscape($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}

function productDetailsImageUrl($path)
{
    $path = trim((string) $path);

    if ($path === '') {
        return '';
    }

    if (preg_match('~^https?://~i', $path)) {
        return $path;
    }

    return '/froshgah/' . ltrim($path, '/');
}


// =====================================================
// 2. GET AND VALIDATE PRODUCT ID
// =====================================================

$productId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$productId || $productId < 1) {
    http_response_code(404);
    exit('Invalid product ID.');
}


// =====================================================
// 3. FETCH PRODUCT INFORMATION
// =====================================================

$productStatement = $pdo->prepare("
    SELECT
        p.*,
        c.name AS category_name

    FROM products p

    INNER JOIN categories c
        ON c.id = p.category_id

    WHERE p.id = :product_id
      AND p.status = 'active'
      AND c.status = 'active'

    LIMIT 1
");

$productStatement->execute([
    'product_id' => $productId
]);

$product = $productStatement->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    http_response_code(404);
    exit('Product not found.');
}


// =====================================================
// 4. FETCH ALL PRODUCT IMAGES
// =====================================================

$imageStatement = $pdo->prepare("
    SELECT
        id,
        image_path,
        is_primary

    FROM product_images

    WHERE product_id = :product_id

    ORDER BY
        is_primary DESC,
        id ASC
");

$imageStatement->execute([
    'product_id' => $productId
]);

$productImages = $imageStatement->fetchAll(PDO::FETCH_ASSOC);

$mainImagePath = $productImages[0]['image_path'] ?? null;

$descriptionImagePath = $productImages[1]['image_path']
    ?? $mainImagePath;


// =====================================================
// 5. PRODUCT RATING SUMMARY
// =====================================================

$ratingStatement = $pdo->prepare("
    SELECT
        AVG(rating) AS average_rating,
        COUNT(*) AS total_reviews

    FROM product_reviews

    WHERE product_id = ?
      AND status = 'approved'
");

$ratingStatement->execute([$productId]);

$ratingSummary = $ratingStatement->fetch(PDO::FETCH_ASSOC);

$averageRating = $ratingSummary['average_rating'] !== null
    ? round((float) $ratingSummary['average_rating'], 1)
    : 0;

$totalReviews = (int) $ratingSummary['total_reviews'];

$roundedRating = (int) round($averageRating);


// =====================================================
// 6. FETCH APPROVED REVIEWS
// =====================================================

$reviewsStatement = $pdo->prepare("
    SELECT
        pr.rating,
        pr.review_title,
        pr.review_text,
        pr.created_at,
        u.name AS customer_name

    FROM product_reviews pr

    INNER JOIN users u
        ON u.id = pr.user_id

    WHERE pr.product_id = ?
      AND pr.status = 'approved'

    ORDER BY pr.created_at DESC
");

$reviewsStatement->execute([$productId]);

$approvedReviews = $reviewsStatement->fetchAll(PDO::FETCH_ASSOC);


// =====================================================
// 7. CUSTOMER REVIEW ELIGIBILITY
// =====================================================

$canReview = false;
$existingReview = null;

$isLoggedIn = isLoggedIn();

$isCustomer = $isLoggedIn
    && ($_SESSION['user_role'] ?? '') === 'customer';

if ($isCustomer) {

    // Check delivered purchases.

    $purchaseStatement = $pdo->prepare("
        SELECT oi.id

        FROM order_items oi

        INNER JOIN orders o
            ON o.id = oi.order_id

        WHERE o.user_id = ?
          AND oi.product_id = ?
          AND o.status = 'delivered'

        LIMIT 1
    ");

    $purchaseStatement->execute([
        (int) $_SESSION['user_id'],
        $productId
    ]);

    $canReview = (bool) $purchaseStatement->fetch();


    // Check existing review.

    $existingReviewStatement = $pdo->prepare("
        SELECT id, status

        FROM product_reviews

        WHERE user_id = ?
          AND product_id = ?

        LIMIT 1
    ");

    $existingReviewStatement->execute([
        (int) $_SESSION['user_id'],
        $productId
    ]);

    $existingReview = $existingReviewStatement->fetch(
        PDO::FETCH_ASSOC
    );
}


// =====================================================
// 8. FETCH RELATED PRODUCTS
// =====================================================

$relatedStatement = $pdo->prepare("
    SELECT
        p.id,
        p.name,
        p.price,
        p.stock,
        p.sku,

        (
            SELECT pi.image_path

            FROM product_images pi

            WHERE pi.product_id = p.id

            ORDER BY
                pi.is_primary DESC,
                pi.id ASC

            LIMIT 1

        ) AS image_path

    FROM products p

    WHERE p.category_id = :category_id
      AND p.id != :product_id
      AND p.status = 'active'

    ORDER BY p.created_at DESC, p.id DESC

    LIMIT 4
");

$relatedStatement->execute([
    'category_id' => (int) $product['category_id'],
    'product_id' => $productId
]);

$relatedProducts = $relatedStatement->fetchAll(PDO::FETCH_ASSOC);


// =====================================================
// 9. PRODUCT VARIABLES
// =====================================================

$productName = $product['name'];

$productPrice = (float) $product['price'];

$productStock = max(0, (int) $product['stock']);

$productDescription = trim(
    (string) ($product['description'] ?? '')
);

$pageTitle = $productName . ' - FroshGah';


// =====================================================
// 10. HEADER AND NAVIGATION
// =====================================================

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

?>

<main class="container pd-page">

    <!-- ==============================================
         BREADCRUMB
    =============================================== -->

    <nav class="pd-breadcrumb" aria-label="Breadcrumb">

        <a href="/froshgah/index.php" aria-label="Home">
            <span>⌂</span>
        </a>

        <span class="pd-breadcrumb-arrow">›</span>

        <a href="/froshgah/pages/products.php">
            Shop
        </a>

        <span class="pd-breadcrumb-arrow">›</span>

        <a href="/froshgah/pages/products.php?category=<?php
            echo (int) $product['category_id'];
        ?>">
            <?php echo productDetailsEscape($product['category_name']); ?>
        </a>

        <span class="pd-breadcrumb-arrow">›</span>

        <span class="pd-breadcrumb-current">
            <?php echo productDetailsEscape($productName); ?>
        </span>

    </nav>


    <!-- ==============================================
         MAIN PRODUCT SECTION
    =============================================== -->

    <section class="pd-product-layout">

        <!-- ==========================================
             LEFT: PRODUCT GALLERY
        =========================================== -->

        <div class="pd-gallery">

            <div class="pd-main-image">

                <?php if ($mainImagePath): ?>

                    <img
                        src="<?php echo productDetailsEscape(
                            productDetailsImageUrl($mainImagePath)
                        ); ?>"
                        alt="<?php echo productDetailsEscape($productName); ?>"
                        id="pdMainImage"
                    >

                <?php else: ?>

                    <div class="pd-image-placeholder">
                        <span>▧</span>
                        <p>No Product Image Available</p>
                    </div>

                <?php endif; ?>


                <!-- Wishlist visual indicator -->

                <div class="pd-image-label">
                    <?php echo productDetailsEscape(
                        $product['category_name']
                    ); ?>
                </div>

            </div>


            <!-- IMAGE THUMBNAILS -->

            <?php if (count($productImages) > 1): ?>

                <div class="pd-thumbnails">

                    <?php foreach ($productImages as $index => $image): ?>

                        <button
                            type="button"
                            class="pd-thumbnail <?php
                                echo $index === 0 ? 'active' : '';
                            ?>"
                            data-gallery-image="<?php
                                echo productDetailsEscape(
                                    productDetailsImageUrl(
                                        $image['image_path']
                                    )
                                );
                            ?>"
                            data-image-alt="<?php
                                echo productDetailsEscape(
                                    $productName . ' image ' . ($index + 1)
                                );
                            ?>"
                            aria-label="View product image <?php
                                echo $index + 1;
                            ?>"
                            aria-pressed="<?php
                                echo $index === 0 ? 'true' : 'false';
                            ?>"
                        >

                            <img
                                src="<?php echo productDetailsEscape(
                                    productDetailsImageUrl(
                                        $image['image_path']
                                    )
                                ); ?>"
                                alt=""
                            >

                        </button>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </div>


        <!-- ==========================================
             RIGHT: PRODUCT INFORMATION
        =========================================== -->

        <div class="pd-product-info">

            <span class="pd-category-badge">

                <?php echo productDetailsEscape(
                    $product['category_name']
                ); ?>

            </span>


            <h1 class="pd-product-title">

                <?php echo productDetailsEscape($productName); ?>

            </h1>


            <p class="pd-product-subtitle">

                <?php

                if ($productDescription !== '') {

                    echo productDetailsEscape(
                        mb_strimwidth(
                            $productDescription,
                            0,
                            130,
                            '...'
                        )
                    );

                } else {

                    echo 'Explore this product and discover its details.';

                }

                ?>

            </p>


            <!-- RATING -->

            <div class="pd-rating-row">

                <?php if ($totalReviews > 0): ?>

                    <div class="pd-stars" aria-label="<?php
                        echo $averageRating;
                    ?> out of 5 stars">

                        <?php for ($i = 1; $i <= 5; $i++): ?>

                            <span class="<?php
                                echo $i <= $roundedRating
                                    ? 'pd-star-filled'
                                    : 'pd-star-empty';
                            ?>">★</span>

                        <?php endfor; ?>

                    </div>

                    <span class="pd-rating-number">
                        <?php echo number_format($averageRating, 1); ?>
                    </span>

                    <a href="#pd-reviews" class="pd-review-count">
                        (<?php echo number_format($totalReviews); ?>
                        reviews)
                    </a>

                <?php else: ?>

                    <span class="pd-no-rating">
                        No customer reviews yet
                    </span>

                <?php endif; ?>

            </div>


            <!-- PRICE -->

            <div class="pd-price-section">

                <span class="pd-current-price">

                    $<?php echo number_format($productPrice, 2); ?>

                </span>

            </div>


            <!-- PRODUCT FEATURES / FACTS -->

            <div class="pd-product-features">

                <div class="pd-feature-item">

                    <span class="pd-feature-icon">✦</span>

                    <span>
                        Category:
                        <?php echo productDetailsEscape(
                            $product['category_name']
                        ); ?>
                    </span>

                </div>


                <div class="pd-feature-item">

                    <span class="pd-feature-icon">▤</span>

                    <span>
                        Product SKU:
                        <?php echo !empty($product['sku'])
                            ? productDetailsEscape($product['sku'])
                            : 'Not assigned'; ?>
                    </span>

                </div>


                <div class="pd-feature-item">

                    <span class="pd-feature-icon">✓</span>

                    <span>
                        <?php if ($productStock > 0): ?>

                            Available in stock

                        <?php else: ?>

                            Currently out of stock

                        <?php endif; ?>
                    </span>

                </div>


                <div class="pd-feature-item">

                    <span class="pd-feature-icon">♧</span>

                    <span>
                        Product ID:
                        #<?php echo (int) $product['id']; ?>
                    </span>

                </div>

            </div>


            <!-- ======================================
                 ADD TO CART
            ======================================= -->

            <?php if ($productStock > 0): ?>

                <form
                    action="/froshgah/actions/add-to-cart.php"
                    method="POST"
                    class="pd-cart-form"
                >

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?php echo productDetailsEscape(
                            generateCsrfToken()
                        ); ?>"
                    >

                    <input
                        type="hidden"
                        name="product_id"
                        value="<?php echo (int) $product['id']; ?>"
                    >


                    <div class="pd-quantity-control">

                        <button
                            type="button"
                            class="pd-quantity-button"
                            data-quantity-decrease
                            aria-label="Decrease quantity"
                        >
                            −
                        </button>

                        <input
                            type="number"
                            id="pdQuantity"
                            name="quantity"
                            value="1"
                            min="1"
                            max="<?php echo $productStock; ?>"
                            aria-label="Product quantity"
                            required
                        >

                        <button
                            type="button"
                            class="pd-quantity-button"
                            data-quantity-increase
                            aria-label="Increase quantity"
                        >
                            +
                        </button>

                    </div>


                    <button
                        type="submit"
                        class="pd-add-to-cart"
                    >

                        <span>🛒</span>

                        Add to Cart

                    </button>

                </form>

            <?php else: ?>

                <div class="pd-out-of-stock">

                    <span>Currently Unavailable</span>

                </div>

            <?php endif; ?>


            <!-- SHARE BUTTON -->

            <div class="pd-secondary-actions">

                <button
                    type="button"
                    class="pd-secondary-button"
                    data-share-product
                    data-share-text="<?php echo productDetailsEscape(
                        $productName
                    ); ?>"
                >

                    <span>↗</span>

                    Share Product

                </button>

            </div>


            <div class="pd-stock-note">

                <?php if ($productStock > 0): ?>

                    <span class="pd-stock-dot"></span>

                    <?php echo $productStock; ?> units currently available

                <?php else: ?>

                    <span class="pd-stock-dot pd-stock-dot-red"></span>

                    This product is out of stock.

                <?php endif; ?>

            </div>

        </div>

    </section>


    <!-- ==============================================
         PRODUCT INFORMATION TABS
    =============================================== -->

    <section class="pd-tabs-section">

        <!-- TAB NAVIGATION -->

        <div class="pd-tabs-navigation" role="tablist">

            <button
                type="button"
                class="pd-tab-button active"
                id="pd-tab-description"
                role="tab"
                aria-selected="true"
                aria-controls="pd-panel-description"
                data-tab="description"
            >

                <span>▤</span>

                Description

            </button>


            <button
                type="button"
                class="pd-tab-button"
                id="pd-tab-specifications"
                role="tab"
                aria-selected="false"
                aria-controls="pd-panel-specifications"
                data-tab="specifications"
            >

                <span>☷</span>

                Specifications

            </button>


            <button
                type="button"
                class="pd-tab-button"
                id="pd-tab-reviews"
                role="tab"
                aria-selected="false"
                aria-controls="pd-panel-reviews"
                data-tab="reviews"
            >

                <span>★</span>

                Reviews (<?php echo number_format($totalReviews); ?>)

            </button>

        </div>


        <!-- ==========================================
             DESCRIPTION TAB
        =========================================== -->

        <div
            class="pd-tab-panel"
            id="pd-panel-description"
            role="tabpanel"
            aria-labelledby="pd-tab-description"
            data-panel="description"
        >

            <div class="pd-description-layout">

                <div class="pd-description-image">

                    <?php if ($descriptionImagePath): ?>

                        <img
                            src="<?php echo productDetailsEscape(
                                productDetailsImageUrl(
                                    $descriptionImagePath
                                )
                            ); ?>"
                            alt="<?php echo productDetailsEscape(
                                $productName
                            ); ?>"
                        >

                    <?php else: ?>

                        <div class="pd-description-placeholder">
                            No Image Available
                        </div>

                    <?php endif; ?>

                </div>


                <div class="pd-description-content">

                    <h2>
                        About <?php echo productDetailsEscape($productName); ?>
                    </h2>


                    <?php if ($productDescription !== ''): ?>

                        <p>
                            <?php echo nl2br(
                                productDetailsEscape($productDescription)
                            ); ?>
                        </p>

                    <?php else: ?>

                        <p>
                            No detailed description has been provided
                            for this product yet.
                        </p>

                    <?php endif; ?>


                    <div class="pd-description-facts">

                        <div>
                            <span>Product</span>
                            <strong>
                                <?php echo productDetailsEscape(
                                    $productName
                                ); ?>
                            </strong>
                        </div>

                        <div>
                            <span>Category</span>
                            <strong>
                                <?php echo productDetailsEscape(
                                    $product['category_name']
                                ); ?>
                            </strong>
                        </div>

                        <div>
                            <span>Availability</span>
                            <strong>
                                <?php echo $productStock > 0
                                    ? 'In Stock'
                                    : 'Out of Stock'; ?>
                            </strong>
                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- ==========================================
             SPECIFICATIONS TAB
        =========================================== -->

        <div
            class="pd-tab-panel"
            id="pd-panel-specifications"
            role="tabpanel"
            aria-labelledby="pd-tab-specifications"
            data-panel="specifications"
            hidden
        >

            <div class="pd-specifications-content">

                <h2>Product Specifications</h2>

                <p>
                    General information about this product.
                </p>


                <div class="pd-specifications-table">

                    <div class="pd-spec-row">

                        <span>Product Name</span>

                        <strong>
                            <?php echo productDetailsEscape(
                                $productName
                            ); ?>
                        </strong>

                    </div>


                    <div class="pd-spec-row">

                        <span>Product ID</span>

                        <strong>
                            #<?php echo (int) $product['id']; ?>
                        </strong>

                    </div>


                    <div class="pd-spec-row">

                        <span>SKU</span>

                        <strong>
                            <?php echo !empty($product['sku'])
                                ? productDetailsEscape($product['sku'])
                                : 'Not assigned'; ?>
                        </strong>

                    </div>


                    <div class="pd-spec-row">

                        <span>Category</span>

                        <strong>
                            <?php echo productDetailsEscape(
                                $product['category_name']
                            ); ?>
                        </strong>

                    </div>


                    <div class="pd-spec-row">

                        <span>Price</span>

                        <strong>
                            $<?php echo number_format($productPrice, 2); ?>
                        </strong>

                    </div>


                    <div class="pd-spec-row">

                        <span>Stock Quantity</span>

                        <strong>
                            <?php echo $productStock; ?> units
                        </strong>

                    </div>


                    <div class="pd-spec-row">

                        <span>Availability</span>

                        <strong>
                            <?php echo $productStock > 0
                                ? 'Available'
                                : 'Out of Stock'; ?>
                        </strong>

                    </div>


                    <div class="pd-spec-row">

                        <span>Date Added</span>

                        <strong>
                            <?php

                            if (!empty($product['created_at'])) {

                                echo productDetailsEscape(
                                    date(
                                        'd M Y',
                                        strtotime($product['created_at'])
                                    )
                                );

                            } else {

                                echo 'Not available';

                            }

                            ?>
                        </strong>

                    </div>

                </div>

            </div>

        </div>


        <!-- ==========================================
             REVIEWS TAB
        =========================================== -->

        <div
            class="pd-tab-panel"
            id="pd-panel-reviews"
            role="tabpanel"
            aria-labelledby="pd-tab-reviews"
            data-panel="reviews"
            hidden
        >

            <div class="pd-reviews-content" id="pd-reviews">

                <!-- REVIEW SUMMARY -->

                <div class="pd-review-summary">

                    <div class="pd-review-summary-number">

                        <strong>
                            <?php echo number_format(
                                $averageRating,
                                1
                            ); ?>
                        </strong>

                        <div class="pd-stars">

                            <?php for ($i = 1; $i <= 5; $i++): ?>

                                <span class="<?php
                                    echo $i <= $roundedRating
                                        ? 'pd-star-filled'
                                        : 'pd-star-empty';
                                ?>">★</span>

                            <?php endfor; ?>

                        </div>

                        <span>
                            <?php echo $totalReviews; ?> verified approved
                            reviews
                        </span>

                    </div>

                </div>


                <!-- SUCCESS MESSAGE -->

                <?php if (!empty($_SESSION['review_success'])): ?>

                    <div class="pd-alert pd-alert-success">

                        <?php

                        echo productDetailsEscape(
                            $_SESSION['review_success']
                        );

                        unset($_SESSION['review_success']);

                        ?>

                    </div>

                <?php endif; ?>


                <!-- ERROR MESSAGE -->

                <?php if (!empty($_SESSION['review_error'])): ?>

                    <div class="pd-alert pd-alert-error">

                        <?php

                        echo productDetailsEscape(
                            $_SESSION['review_error']
                        );

                        unset($_SESSION['review_error']);

                        ?>

                    </div>

                <?php endif; ?>


                <!-- REVIEW SUBMISSION -->

                <div class="pd-review-form-container">

                    <?php if (!$isLoggedIn): ?>

                        <h3>Share Your Experience</h3>

                        <p>
                            Please
                            <a href="/froshgah/pages/login.php">
                                log in
                            </a>
                            to write a review after purchasing this product.
                        </p>


                    <?php elseif (!$isCustomer): ?>

                        <p>
                            Only customer accounts can submit product reviews.
                        </p>


                    <?php elseif ($existingReview): ?>

                        <h3>Your Review</h3>

                        <p>
                            You have already submitted a review for this product.
                        </p>

                        <span class="pd-review-status">

                            <?php echo productDetailsEscape(
                                ucfirst($existingReview['status'])
                            ); ?>

                        </span>


                    <?php elseif ($canReview): ?>

                        <h3>Write Your Review</h3>

                        <p>
                            Tell other customers about your experience.
                        </p>


                        <form
                            action="/froshgah/actions/submit-review.php"
                            method="POST"
                            class="pd-review-form"
                        >

                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?php echo productDetailsEscape(
                                    generateCsrfToken()
                                ); ?>"
                            >

                            <input
                                type="hidden"
                                name="product_id"
                                value="<?php echo (int) $product['id']; ?>"
                            >


                            <div class="pd-form-group">

                                <label for="pd-rating">
                                    Your Rating
                                </label>

                                <select
                                    name="rating"
                                    id="pd-rating"
                                    required
                                >

                                    <option value="">
                                        Select your rating
                                    </option>

                                    <option value="5">★★★★★ - Excellent</option>

                                    <option value="4">★★★★☆ - Very Good</option>

                                    <option value="3">★★★☆☆ - Good</option>

                                    <option value="2">★★☆☆☆ - Fair</option>

                                    <option value="1">★☆☆☆☆ - Poor</option>

                                </select>

                            </div>


                            <div class="pd-form-group">

                                <label for="pd-review-title">
                                    Review Title
                                </label>

                                <input
                                    type="text"
                                    name="review_title"
                                    id="pd-review-title"
                                    maxlength="150"
                                    placeholder="Write a short title"
                                    required
                                >

                            </div>


                            <div class="pd-form-group">

                                <label for="pd-review-text">
                                    Your Review
                                </label>

                                <textarea
                                    name="review_text"
                                    id="pd-review-text"
                                    rows="5"
                                    maxlength="5000"
                                    placeholder="Share your experience..."
                                    required
                                ></textarea>

                            </div>


                            <button
                                type="submit"
                                class="pd-review-submit"
                            >
                                Submit Review
                            </button>

                        </form>


                    <?php else: ?>

                        <h3>Write Your Review</h3>

                        <p>
                            You can review this product after receiving it.
                            Your order must have the delivered status.
                        </p>

                    <?php endif; ?>

                </div>


                <!-- APPROVED REVIEWS -->

                <div class="pd-customer-reviews">

                    <h3>Customer Feedback</h3>

                    <?php if (empty($approvedReviews)): ?>

                        <div class="pd-no-reviews">

                            <span>☆</span>

                            <h4>No Reviews Yet</h4>

                            <p>
                                There are no approved reviews for this product.
                            </p>

                        </div>

                    <?php else: ?>

                        <?php foreach ($approvedReviews as $review): ?>

                            <article class="pd-review-card">

                                <div class="pd-review-card-header">

                                    <div>

                                        <h4>
                                            <?php echo productDetailsEscape(
                                                $review['review_title']
                                            ); ?>
                                        </h4>

                                        <span class="pd-review-author">

                                            By
                                            <?php echo productDetailsEscape(
                                                $review['customer_name']
                                            ); ?>

                                        </span>

                                    </div>


                                    <div class="pd-review-card-rating">

                                        <?php for ($i = 1; $i <= 5; $i++): ?>

                                            <span class="<?php
                                                echo $i <= (int) $review['rating']
                                                    ? 'pd-star-filled'
                                                    : 'pd-star-empty';
                                            ?>">★</span>

                                        <?php endfor; ?>

                                    </div>

                                </div>


                                <p class="pd-review-text">

                                    <?php echo nl2br(
                                        productDetailsEscape(
                                            $review['review_text']
                                        )
                                    ); ?>

                                </p>


                                <span class="pd-review-date">

                                    <?php echo productDetailsEscape(
                                        date(
                                            'd M Y',
                                            strtotime($review['created_at'])
                                        )
                                    ); ?>

                                </span>

                            </article>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </div>

            </div>

        </div>

    </section>


    <!-- ==============================================
         RELATED PRODUCTS
    =============================================== -->

    <?php if (!empty($relatedProducts)): ?>

        <section class="pd-related-section">

            <div class="pd-section-heading">

                <span></span>

                <h2>Related Products</h2>

                <span></span>

            </div>


            <div class="pd-related-grid">

                <?php foreach ($relatedProducts as $related): ?>

                    <article class="pd-related-card">

                        <a
                            href="/froshgah/pages/product-details.php?id=<?php
                                echo (int) $related['id'];
                            ?>"
                            class="pd-related-image"
                        >

                            <?php if (!empty($related['image_path'])): ?>

                                <img
                                    src="<?php echo productDetailsEscape(
                                        productDetailsImageUrl(
                                            $related['image_path']
                                        )
                                    ); ?>"
                                    alt="<?php echo productDetailsEscape(
                                        $related['name']
                                    ); ?>"
                                    loading="lazy"
                                >

                            <?php else: ?>

                                <div class="pd-related-placeholder">
                                    No Image
                                </div>

                            <?php endif; ?>

                        </a>


                        <div class="pd-related-info">

                            <h3>

                                <a
                                    href="/froshgah/pages/product-details.php?id=<?php
                                        echo (int) $related['id'];
                                    ?>"
                                >

                                    <?php echo productDetailsEscape(
                                        $related['name']
                                    ); ?>

                                </a>

                            </h3>


                            <p class="pd-related-price">

                                $<?php echo number_format(
                                    (float) $related['price'],
                                    2
                                ); ?>

                            </p>


                            <?php if ((int) $related['stock'] > 0): ?>

                                <form
                                    action="/froshgah/actions/add-to-cart.php"
                                    method="POST"
                                >

                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?php echo productDetailsEscape(
                                            generateCsrfToken()
                                        ); ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="product_id"
                                        value="<?php echo (int) $related['id']; ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="quantity"
                                        value="1"
                                    >

                                    <button
                                        type="submit"
                                        class="pd-related-cart-button"
                                    >
                                        🛒 Add to Cart
                                    </button>

                                </form>

                            <?php else: ?>

                                <button
                                    type="button"
                                    class="pd-related-cart-button"
                                    disabled
                                >
                                    Out of Stock
                                </button>

                            <?php endif; ?>

                        </div>

                    </article>

                <?php endforeach; ?>

            </div>

        </section>

    <?php endif; ?>

</main>


<!-- ==============================================
     JAVASCRIPT: GALLERY, QUANTITY, TABS AND SHARE
=============================================== -->

<script>

document.addEventListener('DOMContentLoaded', function () {

    // ---------------------------------------------
    // 1. PRODUCT IMAGE GALLERY
    // ---------------------------------------------

    const mainImage = document.getElementById('pdMainImage');

    const thumbnails = document.querySelectorAll(
        '.pd-thumbnail'
    );

    thumbnails.forEach(function (thumbnail) {

        thumbnail.addEventListener('click', function () {

            if (!mainImage) return;

            const newImage = this.dataset.galleryImage;
            const newAlt = this.dataset.imageAlt || '';

            mainImage.src = newImage;
            mainImage.alt = newAlt;

            thumbnails.forEach(function (item) {

                item.classList.remove('active');

                item.setAttribute('aria-pressed', 'false');

            });

            this.classList.add('active');

            this.setAttribute('aria-pressed', 'true');

        });

    });


    // ---------------------------------------------
    // 2. QUANTITY INCREASE AND DECREASE
    // ---------------------------------------------

    const quantityInput = document.getElementById('pdQuantity');

    const decreaseButton = document.querySelector(
        '[data-quantity-decrease]'
    );

    const increaseButton = document.querySelector(
        '[data-quantity-increase]'
    );

    if (quantityInput && decreaseButton && increaseButton) {

        decreaseButton.addEventListener('click', function () {

            const currentValue = parseInt(quantityInput.value, 10) || 1;

            const minimum = parseInt(quantityInput.min, 10) || 1;

            quantityInput.value = Math.max(
                minimum,
                currentValue - 1
            );

        });


        increaseButton.addEventListener('click', function () {

            const currentValue = parseInt(quantityInput.value, 10) || 1;

            const maximum = parseInt(quantityInput.max, 10);

            quantityInput.value = Math.min(
                maximum,
                currentValue + 1
            );

        });


        quantityInput.addEventListener('change', function () {

            const minimum = parseInt(this.min, 10) || 1;

            const maximum = parseInt(this.max, 10);

            let value = parseInt(this.value, 10);

            if (!Number.isFinite(value)) {
                value = minimum;
            }

            this.value = Math.max(
                minimum,
                Math.min(maximum, value)
            );

        });

    }


    // ---------------------------------------------
    // 3. PRODUCT TABS
    // ---------------------------------------------

    const tabButtons = document.querySelectorAll(
        '.pd-tab-button'
    );

    const tabPanels = document.querySelectorAll(
        '.pd-tab-panel'
    );

    tabButtons.forEach(function (button) {

        button.addEventListener('click', function () {

            const selectedTab = this.dataset.tab;

            tabButtons.forEach(function (tab) {

                tab.classList.remove('active');

                tab.setAttribute('aria-selected', 'false');

            });

            tabPanels.forEach(function (panel) {

                panel.hidden = true;

            });

            this.classList.add('active');

            this.setAttribute('aria-selected', 'true');

            const selectedPanel = document.querySelector(
                '[data-panel="' + selectedTab + '"]'
            );

            if (selectedPanel) {

                selectedPanel.hidden = false;

            }

        });

    });


    // ---------------------------------------------
    // 4. SHARE PRODUCT
    // ---------------------------------------------

    const shareButton = document.querySelector(
        '[data-share-product]'
    );

    if (shareButton) {

        shareButton.addEventListener('click', async function () {

            const shareData = {

                title: document.title,

                text: this.dataset.shareText,

                url: window.location.href

            };

            try {

                if (navigator.share) {

                    await navigator.share(shareData);

                } else if (navigator.clipboard) {

                    await navigator.clipboard.writeText(
                        window.location.href
                    );

                    const originalText = this.innerHTML;

                    this.textContent = '✓ Link Copied!';

                    setTimeout(() => {

                        this.innerHTML = originalText;

                    }, 2000);

                } else {

                    window.prompt(
                        'Copy this product link:',
                        window.location.href
                    );

                }

            } catch (error) {

                if (error.name !== 'AbortError') {

                    console.error('Sharing failed:', error);

                }

            }

        });

    }

});

</script>


<?php require_once __DIR__ . '/../includes/footer.php'; ?>
