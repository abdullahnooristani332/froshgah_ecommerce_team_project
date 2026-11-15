
<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/admin-auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /froshgah/admin/categories.php");
    exit;
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $_SESSION['category_error'] = "Invalid form submission.";
    header("Location: /froshgah/admin/categories.php");
    exit;
}

$categoryId = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);

if (!$categoryId || $categoryId < 1) {
    $_SESSION['category_error'] = "Invalid category.";
    header("Location: /froshgah/admin/categories.php");
    exit;
}

try {

    // Check whether products are assigned to this category.
    $productCountStatement = $pdo->prepare("
        SELECT COUNT(*)
        FROM products
        WHERE category_id = ?
    ");

    $productCountStatement->execute([$categoryId]);
    $productCount = (int) $productCountStatement->fetchColumn();

    if ($productCount > 0) {
        throw new Exception(
            "This category contains products and cannot be deleted."
        );
    }

    $deleteStatement = $pdo->prepare("
        DELETE FROM categories
        WHERE id = ?
    ");

    $deleteStatement->execute([$categoryId]);

    if ($deleteStatement->rowCount() === 0) {
        throw new Exception("Category not found.");
    }

    $_SESSION['category_message'] = "Category successfully deleted.";

} catch (Throwable $e) {

    error_log("Delete category error: " . $e->getMessage());

    $_SESSION['category_error'] =
        $e->getMessage() === "This category contains products and cannot be deleted."
        ? $e->getMessage()
        : "Could not delete the category.";

}

header("Location: /froshgah/admin/categories.php");
exit;
