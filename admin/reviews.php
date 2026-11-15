
<?php

require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . '/../config/database.php';

$pageTitle = 'Manage Reviews - Froshgah';


// ==========================================
// REVIEW STATUS FILTER
// ==========================================

$allowedStatuses = [
    'all',
    'pending',
    'approved',
    'rejected'
];

$filterStatus = $_GET['status'] ?? 'all';

if (!in_array($filterStatus, $allowedStatuses, true)) {
    $filterStatus = 'all';
}


// ==========================================
// RETRIEVE REVIEWS
// ==========================================

$sql = "
    SELECT
        pr.id,
        pr.user_id,
        pr.product_id,
        pr.rating,
        pr.review_title,
        pr.review_text,
        pr.status,
        pr.created_at,
        pr.updated_at,

        u.name AS customer_name,
        u.email AS customer_email,

        p.name AS product_name

    FROM product_reviews pr

    INNER JOIN users u
        ON u.id = pr.user_id

    INNER JOIN products p
        ON p.id = pr.product_id
";

if ($filterStatus !== 'all') {

    $sql .= "
        WHERE pr.status = :status
    ";

}

$sql .= "
    ORDER BY
        CASE
            WHEN pr.status = 'pending' THEN 1
            WHEN pr.status = 'approved' THEN 2
            WHEN pr.status = 'rejected' THEN 3
        END,
        pr.created_at DESC
";

$reviewStatement = $pdo->prepare($sql);

if ($filterStatus !== 'all') {

    $reviewStatement->bindValue(
        ':status',
        $filterStatus,
        PDO::PARAM_STR
    );

}

$reviewStatement->execute();

$reviews = $reviewStatement->fetchAll();


// ==========================================
// REVIEW COUNTS
// ==========================================

$countStatement = $pdo->query("
    SELECT
        COUNT(*) AS total_reviews,

        SUM(
            CASE WHEN status = 'pending'
            THEN 1 ELSE 0 END
        ) AS pending_reviews,

        SUM(
            CASE WHEN status = 'approved'
            THEN 1 ELSE 0 END
        ) AS approved_reviews,

        SUM(
            CASE WHEN status = 'rejected'
            THEN 1 ELSE 0 END
        ) AS rejected_reviews

    FROM product_reviews
");

$reviewCounts = $countStatement->fetch();

$totalReviews = (int) ($reviewCounts['total_reviews'] ?? 0);
$pendingReviews = (int) ($reviewCounts['pending_reviews'] ?? 0);
$approvedReviews = (int) ($reviewCounts['approved_reviews'] ?? 0);
$rejectedReviews = (int) ($reviewCounts['rejected_reviews'] ?? 0);


require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

?>

<main class="container admin-reviews-page">

    <!-- ==================================
         PAGE HEADING
    =================================== -->

    <div class="admin-reviews-heading">

        <div>

            <h1>Manage Product Reviews</h1>

            <p>
                Review customer feedback and manage publication status.
            </p>

        </div>

    </div>


    <!-- ==================================
         SUCCESS AND ERROR MESSAGES
    =================================== -->

    <?php if (!empty($_SESSION['review_admin_success'])): ?>

        <div class="admin-review-alert admin-review-alert-success">

            <?php

            echo htmlspecialchars($_SESSION['review_admin_success']);

            unset($_SESSION['review_admin_success']);

            ?>

        </div>

    <?php endif; ?>


    <?php if (!empty($_SESSION['review_admin_error'])): ?>

        <div class="admin-review-alert admin-review-alert-error">

            <?php

            echo htmlspecialchars($_SESSION['review_admin_error']);

            unset($_SESSION['review_admin_error']);

            ?>

        </div>

    <?php endif; ?>


    <!-- ==================================
         REVIEW STATISTICS
    =================================== -->

    <section class="admin-review-stats">

        <div class="admin-review-stat-card">

            <span>Total Reviews</span>

            <strong>
                <?php echo $totalReviews; ?>
            </strong>

        </div>


        <div class="admin-review-stat-card pending-stat">

            <span>Pending Reviews</span>

            <strong>
                <?php echo $pendingReviews; ?>
            </strong>

        </div>


        <div class="admin-review-stat-card approved-stat">

            <span>Approved Reviews</span>

            <strong>
                <?php echo $approvedReviews; ?>
            </strong>

        </div>


        <div class="admin-review-stat-card rejected-stat">

            <span>Rejected Reviews</span>

            <strong>
                <?php echo $rejectedReviews; ?>
            </strong>

        </div>

    </section>


    <!-- ==================================
         FILTER REVIEWS
    =================================== -->

    <section class="admin-review-filter">

        <h2>Filter Reviews</h2>

        <div class="admin-review-filter-links">

            <a
                href="/froshgah/admin/reviews.php?status=all"
                class="<?php echo $filterStatus === 'all' ? 'active' : ''; ?>"
            >
                All Reviews
            </a>

            <a
                href="/froshgah/admin/reviews.php?status=pending"
                class="<?php echo $filterStatus === 'pending' ? 'active' : ''; ?>"
            >
                Pending
            </a>

            <a
                href="/froshgah/admin/reviews.php?status=approved"
                class="<?php echo $filterStatus === 'approved' ? 'active' : ''; ?>"
            >
                Approved
            </a>

            <a
                href="/froshgah/admin/reviews.php?status=rejected"
                class="<?php echo $filterStatus === 'rejected' ? 'active' : ''; ?>"
            >
                Rejected
            </a>

        </div>

    </section>


    <!-- ==================================
         REVIEWS LIST
    =================================== -->

    <section class="admin-reviews-list">

        <div class="admin-reviews-list-heading">

            <h2>Customer Reviews</h2>

            <span>
                <?php echo count($reviews); ?> review(s) displayed
            </span>

        </div>


        <?php if (empty($reviews)): ?>

            <div class="admin-no-reviews">

                <h3>No Reviews Found</h3>

                <p>
                    There are no reviews available for this filter.
                </p>

            </div>

        <?php else: ?>

            <?php foreach ($reviews as $review): ?>

                <article class="admin-review-card">

                    <!-- Review Header -->

                    <div class="admin-review-card-header">

                        <div>

                            <h3>
                                <?php
                                    echo htmlspecialchars($review['review_title']);
                                ?>
                            </h3>

                            <p class="admin-review-product">

                                Product:

                                <strong>
                                    <?php
                                        echo htmlspecialchars($review['product_name']);
                                    ?>
                                </strong>

                            </p>

                        </div>


                        <span class="review-status review-status-<?php
                            echo htmlspecialchars($review['status']);
                        ?>">

                            <?php
                                echo ucfirst(htmlspecialchars($review['status']));
                            ?>

                        </span>

                    </div>


                    <!-- Rating -->

                    <div class="admin-review-rating">

                        <?php

                        for ($i = 1; $i <= 5; $i++) {

                            echo $i <= (int) $review['rating']
                                ? '★'
                                : '☆';

                        }

                        ?>

                        <span>
                            <?php echo (int) $review['rating']; ?>/5
                        </span>

                    </div>


                    <!-- Customer Information -->

                    <div class="admin-review-customer">

                        <p>

                            <strong>Customer:</strong>

                            <?php
                                echo htmlspecialchars($review['customer_name']);
                            ?>

                        </p>

                        <p>

                            <strong>Email:</strong>

                            <?php
                                echo htmlspecialchars($review['customer_email']);
                            ?>

                        </p>

                        <p>

                            <strong>Submitted:</strong>

                            <?php

                            echo htmlspecialchars(
                                date(
                                    'd M Y, h:i A',
                                    strtotime($review['created_at'])
                                )
                            );

                            ?>

                        </p>

                    </div>


                    <!-- Review Description -->

                    <div class="admin-review-description">

                        <h4>Customer Feedback</h4>

                        <p>

                            <?php

                            echo nl2br(
                                htmlspecialchars($review['review_text'])
                            );

                            ?>

                        </p>

                    </div>


                    <!-- Administrator Actions -->

                    <div class="admin-review-actions">

                        <?php if ($review['status'] !== 'approved'): ?>

                            <form
                                action="/froshgah/actions/update-review-status.php"
                                method="POST"
                            >

                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?php
                                        echo htmlspecialchars(generateCsrfToken());
                                    ?>"
                                >

                                <input
                                    type="hidden"
                                    name="review_id"
                                    value="<?php echo (int) $review['id']; ?>"
                                >

                                <input
                                    type="hidden"
                                    name="status"
                                    value="approved"
                                >

                                <button
                                    type="submit"
                                    class="admin-review-approve-button"
                                >
                                    Approve Review
                                </button>

                            </form>

                        <?php endif; ?>


                        <?php if ($review['status'] !== 'rejected'): ?>

                            <form
                                action="/froshgah/actions/update-review-status.php"
                                method="POST"
                                onsubmit="return confirm('Are you sure you want to reject this review?');"
                            >

                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?php
                                        echo htmlspecialchars(generateCsrfToken());
                                    ?>"
                                >

                                <input
                                    type="hidden"
                                    name="review_id"
                                    value="<?php echo (int) $review['id']; ?>"
                                >

                                <input
                                    type="hidden"
                                    name="status"
                                    value="rejected"
                                >

                                <button
                                    type="submit"
                                    class="admin-review-reject-button"
                                >
                                    Reject Review
                                </button>

                            </form>

                        <?php endif; ?>

                    </div>

                </article>

            <?php endforeach; ?>

        <?php endif; ?>

    </section>

</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
