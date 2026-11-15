
<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

requireLogin();

$pageTitle = 'My Reviews - Froshgah';

$userId = (int) $_SESSION['user_id'];

$reviewStatement = $pdo->prepare("
    SELECT
        pr.id,
        pr.product_id,
        pr.rating,
        pr.review_title,
        pr.review_text,
        pr.status,
        pr.created_at,
        p.name AS product_name

    FROM product_reviews pr

    INNER JOIN products p
        ON p.id = pr.product_id

    WHERE pr.user_id = ?

    ORDER BY pr.created_at DESC
");

$reviewStatement->execute([$userId]);

$reviews = $reviewStatement->fetchAll();

require_once __DIR__ . '/../includes/header.php';

?>

<?php require_once __DIR__ . '/../includes/navbar.php'; ?>

<main class="container my-reviews-page">

    <div class="page-heading">

        <h1>My Reviews</h1>

        <p>
            View the reviews and ratings you have submitted
            for products in Froshgah.
        </p>

    </div>

    <?php if (empty($reviews)): ?>

        <div class="empty-reviews">

            <h2>You have not submitted any reviews yet.</h2>

            <p>
                After receiving a product, you can submit a rating
                and share your experience.
            </p>

            <a href="/froshgah/pages/products.php" class="btn">
                Browse Products
            </a>

        </div>

    <?php else: ?>

        <div class="reviews-list">

            <?php foreach ($reviews as $review): ?>

                <article class="customer-review-card">

                    <div class="customer-review-header">

                        <div>

                            <h3>
                                <?php echo htmlspecialchars($review['review_title']); ?>
                            </h3>

                            <p class="review-product-name">

                                Product:

                                <a href="/froshgah/pages/product-details.php?id=<?php
                                    echo (int) $review['product_id'];
                                ?>">

                                    <?php echo htmlspecialchars($review['product_name']); ?>

                                </a>

                            </p>

                        </div>

                        <span class="review-status review-status-<?php
                            echo htmlspecialchars($review['status']);
                        ?>">

                            <?php echo ucfirst(htmlspecialchars($review['status'])); ?>

                        </span>

                    </div>

                    <div class="review-rating">

                        <?php for ($i = 1; $i <= 5; $i++): ?>

                            <span class="<?php echo $i <= (int) $review['rating'] ? 'star-filled' : 'star-empty'; ?>">
                                ★
                            </span>

                        <?php endfor; ?>

                        <span class="rating-number">
                            <?php echo (int) $review['rating']; ?>/5
                        </span>

                    </div>

                    <p class="customer-review-text">
                        <?php echo nl2br(htmlspecialchars($review['review_text'])); ?>
                    </p>

                    <p class="review-date">

                        Submitted on:

                        <?php
                            echo htmlspecialchars(
                                date('d M Y, h:i A', strtotime($review['created_at']))
                            );
                        ?>

                    </p>

                    <?php if ($review['status'] === 'pending'): ?>

                        <p class="review-notice">
                            Your review is waiting for administrator approval.
                        </p>

                    <?php elseif ($review['status'] === 'rejected'): ?>

                        <p class="review-notice review-rejected-notice">
                            Your review was not approved and is not visible publicly.
                        </p>

                    <?php elseif ($review['status'] === 'approved'): ?>

                        <p class="review-notice review-approved-notice">
                            Your review has been approved and is visible on the product page.
                        </p>

                    <?php endif; ?>

                </article>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
