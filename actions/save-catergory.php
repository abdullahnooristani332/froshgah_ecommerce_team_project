
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

$categoryId = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT) ?: 0;
$name = trim($_POST['name'] ?? '');
$description = trim($_POST['description'] ?? '');
$status = $_POST['status'] ?? 'active';

$redirectUrl = $categoryId > 0
    ? "/froshgah/admin/categories.php?edit=" . $categoryId
    : "/froshgah/admin/categories.php";

if (
    $name === '' ||
    strlen($name) > 150 ||
    !in_array($status, ['active', 'inactive'], true)
) {
    $_SESSION['category_error'] = "Please enter valid category information.";
    header("Location: " . $redirectUrl);
    exit;
}

// Generate a unique category slug.
$baseSlug = strtolower(trim($name));
$baseSlug = preg_replace('/[^a-z0-9]+/', '-', $baseSlug);
$baseSlug = trim($baseSlug, '-');

if ($baseSlug === '') {
    $baseSlug = 'category';
}

$slug = $baseSlug;
$counter = 1;

while (true) {

    $sql = "
        SELECT id
        FROM categories
        WHERE slug = ?
    ";

    $params = [$slug];

    if ($categoryId > 0) {
        $sql .= " AND id != ?";
        $params[] = $categoryId;
    }

    $checkStatement = $pdo->prepare($sql . " LIMIT 1");
    $checkStatement->execute($params);

    if (!$checkStatement->fetch()) {
        break;
    }

    $counter++;
    $slug = $baseSlug . '-' . $counter;
}

try {

    if ($categoryId > 0) {

        $updateStatement = $pdo->prepare("
            UPDATE categories
            SET
                name = ?,
                slug = ?,
                description = ?,
                status = ?
            WHERE id = ?
        ");

        $updateStatement->execute([
            $name,
            $slug,
            $description,
            $status,
            $categoryId
        ]);

        $_SESSION['category_message'] = "Category successfully updated.";

    } else {

        $insertStatement = $pdo->prepare("
            INSERT INTO categories (
                name,
                slug,
                description,
                status
            )
            VALUES (?, ?, ?, ?)
        ");

        $insertStatement->execute([
            $name,
            $slug,
            $description,
            $status
        ]);

        $_SESSION['category_message'] = "Category successfully created.";
    }

} catch (Throwable $e) {

    error_log("Save category error: " . $e->getMessage());

    $_SESSION['category_error'] =
        "Could not save the category. Please check its information.";

}

header("Location: /froshgah/admin/categories.php");
exit;
