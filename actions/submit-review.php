
<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

requireLogin();
// Only customer accounts can submit product reviews.
if (($_SESSION['user_role'] ?? '') !== 'customer') {

    http_response_code(403);

    exit('Only customer accounts can submit product reviews.');

}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /froshgah/');
    exit;
}

// Validate CSRF token.
if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $_SESSION['review_error'] = 'Invalid security token. Please try again.';
    header('Location: /froshgah/pages/products.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];
$productId = filter_var(
    $_POST['product_id'] ?? null,
    FILTER_VALIDATE_INT
);

$rating = filter_var(
    $_POST['rating'] ?? null,
    FILTER_VALIDATE_INT
);

$reviewTitle = trim($_POST['review_title'] ?? '');
$reviewText = trim($_POST['review_text'] ?? '');

// Validate product ID.
if (!$productId || $productId <= 0) {
    $_SESSION['review_error'] = 'Invalid product.';
    header('Location: /froshgah/pages/products.php');
    exit;
}

// Validate rating.
if ($rating === false || $rating < 1 || $rating > 5) {
    $_SESSION['review_error'] = 'Please select a rating between 1 and 5.';
    header('Location: /froshgah/pages/product-details.php?id=' . $productId);
    exit;
}

// Validate review title.
if ($reviewTitle === '' || strlen($reviewTitle) > 150) {
    $_SESSION['review_error'] = 'Review title is required and must not exceed 150 characters.';
    header('Location: /froshgah/pages/product-details.php?id=' . $productId);
    exit;
}

// Validate review description.
if ($reviewText === '' || strlen($reviewText) > 5000) {
    $_SESSION['review_error'] = 'Review description is required and must not exceed 5000 characters.';
    header('Location: /froshgah/pages/product-details.php?id=' . $productId);
    exit;
}

try {

    // Confirm that the product exists.
    $productStatement = $pdo->prepare("
        SELECT id
        FROM products
        WHERE id = ?
        LIMIT 1
    ");

    $productStatement->execute([$productId]);

    if (!$productStatement->fetch()) {
        $_SESSION['review_error'] = 'The selected product does not exist.';
        header('Location: /froshgah/pages/products.php');
        exit;
    }

    // Only customers with a delivered order containing this product
    // are allowed to submit a review.
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
        $userId,
        $productId
    ]);

    if (!$purchaseStatement->fetch()) {
        $_SESSION['review_error'] =
            'You can only review a product after receiving it through a delivered order.';

        header('Location: /froshgah/pages/product-details.php?id=' . $productId);
        exit;
    }

    // Prevent duplicate reviews.
    $duplicateStatement = $pdo->prepare("
        SELECT id
        FROM product_reviews
        WHERE user_id = ?
          AND product_id = ?
        LIMIT 1
    ");

    $duplicateStatement->execute([
        $userId,
        $productId
    ]);

    if ($duplicateStatement->fetch()) {
        $_SESSION['review_error'] =
            'You have already submitted a review for this product.';

        header('Location: /froshgah/pages/product-details.php?id=' . $productId);
        exit;
    }

    // Save the review as pending.
    $insertStatement = $pdo->prepare("
        INSERT INTO product_reviews (
            user_id,
            product_id,
            rating,
            review_title,
            review_text,
            status
        )
        VALUES (?, ?, ?, ?, ?, 'pending')
    ");

    $insertStatement->execute([
        $userId,
        $productId,
        $rating,
        $reviewTitle,
        $reviewText
    ]);

    $_SESSION['review_success'] =
        'Your review has been submitted and is waiting for administrator approval.';

    header('Location: /froshgah/pages/product-details.php?id=' . $productId);
    exit;

} catch (PDOException $e) {

    error_log('Review submission error: ' . $e->getMessage());

    $_SESSION['review_error'] =
        'Something went wrong while submitting your review. Please try again.';

    header('Location: /froshgah/pages/product-details.php?id=' . $productId);
    exit;
}
