
<?php

/**
 * Froshgah - Secure Product Creation and Image Upload
 */

require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . '/../config/database.php';

// Accept POST requests only.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /froshgah/admin/products.php');
    exit;
}

// Verify CSRF token.
if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    $_SESSION['product_error'] = 'Invalid form submission.';
    header('Location: /froshgah/admin/products.php');
    exit;
}

// Collect and validate input types.
$productId = filter_input(
    INPUT_POST,
    'product_id',
    FILTER_VALIDATE_INT
);

$productId = $productId !== false && $productId !== null
    ? (int) $productId
    : 0;

$name = is_string($_POST['name'] ?? null)
    ? trim($_POST['name'])
    : '';

$categoryId = filter_input(
    INPUT_POST,
    'category_id',
    FILTER_VALIDATE_INT
);

$sku = is_string($_POST['sku'] ?? null)
    ? trim($_POST['sku'])
    : '';

$description = is_string($_POST['description'] ?? null)
    ? trim($_POST['description'])
    : '';

$priceInput = is_string($_POST['price'] ?? null)
    ? trim($_POST['price'])
    : '';

$stockInput = is_string($_POST['stock'] ?? null)
    ? trim($_POST['stock'])
    : '';

$status = is_string($_POST['status'] ?? null)
    ? $_POST['status']
    : 'active';

$redirectUrl = $productId > 0
    ? '/froshgah/admin/products.php?edit=' . $productId
    : '/froshgah/admin/products.php';

// Reject invalid product IDs.
if ($productId < 0) {
    $_SESSION['product_error'] = 'Invalid product ID.';
    header('Location: /froshgah/admin/products.php');
    exit;
}

// Validate product information.
if (
    $name === ''
    || strlen($name) > 200
    || !$categoryId
    || $categoryId < 1
    || strlen($sku) > 100
    || strlen($description) > 10000
    || $priceInput === ''
    || $stockInput === ''
    || !is_numeric($priceInput)
    || !preg_match('/^\d+$/', $stockInput)
    || !in_array($status, ['active', 'inactive'], true)
) {
    $_SESSION['product_error'] =
        'Please enter valid product information.';

    header('Location: ' . $redirectUrl);
    exit;
}

// Validate price and stock.
$price = (float) $priceInput;
$stockValue = filter_var($stockInput, FILTER_VALIDATE_INT);

if (
    !is_finite($price)
    || $price < 0
    || $price > 9999999999.99
    || $stockValue === false
    || $stockValue < 0
    || $stockValue > 4294967295
) {
    $_SESSION['product_error'] =
        'Price or stock quantity is outside the allowed range.';

    header('Location: ' . $redirectUrl);
    exit;
}

$stock = (int) $stockValue;
$categoryId = (int) $categoryId;

$uploadDirectory = __DIR__ . '/../uploads/products/';
$imagePath = null;
$imageUploaded = false;

try {

    // Confirm that the selected category exists.
    $categoryStatement = $pdo->prepare("
        SELECT id
        FROM categories
        WHERE id = ?
        LIMIT 1
    ");

    $categoryStatement->execute([$categoryId]);

    if (!$categoryStatement->fetch()) {
        throw new RuntimeException(
            'The selected category does not exist.'
        );
    }

    // Generate a URL-friendly product slug.
    $baseSlug = strtolower(trim($name));
    $baseSlug = preg_replace('/[^a-z0-9]+/', '-', $baseSlug);
    $baseSlug = trim($baseSlug, '-');

    if ($baseSlug === '') {
        $baseSlug = 'product';
    }

    $slug = $baseSlug;
    $counter = 1;

    // Find an available slug.
    while (true) {

        $slugSql = "
            SELECT id
            FROM products
            WHERE slug = ?
        ";

        $slugParams = [$slug];

        if ($productId > 0) {
            $slugSql .= " AND id != ?";
            $slugParams[] = $productId;
        }

        $slugStatement = $pdo->prepare(
            $slugSql . " LIMIT 1"
        );

        $slugStatement->execute($slugParams);

        if (!$slugStatement->fetch()) {
            break;
        }

        $counter++;
        $slug = $baseSlug . '-' . $counter;
    }

    // Process an optional product image.
    if (
        isset($_FILES['product_image'])
        && is_array($_FILES['product_image'])
        && ($_FILES['product_image']['error'] ?? UPLOAD_ERR_NO_FILE)
            !== UPLOAD_ERR_NO_FILE
    ) {

        $file = $_FILES['product_image'];

        // Validate the uploaded file structure.
        if (
            !isset(
                $file['name'],
                $file['tmp_name'],
                $file['error'],
                $file['size']
            )
            || !is_string($file['tmp_name'])
            || !is_int($file['error'])
            || !is_numeric($file['size'])
        ) {
            throw new RuntimeException('Invalid uploaded file.');
        }

        // Check upload status.
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Image upload failed.');
        }

        // Confirm this is an actual HTTP uploaded file.
        if (!is_uploaded_file($file['tmp_name'])) {
            throw new RuntimeException('Invalid image upload.');
        }

        // Validate file size.
        $fileSize = (int) $file['size'];

        if ($fileSize < 1 || $fileSize > 3 * 1024 * 1024) {
            throw new RuntimeException(
                'Image must be larger than 0 bytes and must not exceed 3 MB.'
            );
        }

        // Detect the actual MIME type.
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);

        // Only these image formats are permitted.
        $allowedImages = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp'
        ];

        if (!isset($allowedImages[$mimeType])) {
            throw new RuntimeException(
                'Only JPG, PNG, and WEBP images are allowed.'
            );
        }

        // Check that PHP can recognize the image.
        $imageInformation = @getimagesize($file['tmp_name']);

        if (
            $imageInformation === false
            || !isset($imageInformation['mime'])
            || $imageInformation['mime'] !== $mimeType
        ) {
            throw new RuntimeException(
                'The uploaded file is not a valid image.'
            );
        }

        // Create the upload directory if necessary.
        if (!is_dir($uploadDirectory)) {
            if (!mkdir($uploadDirectory, 0755, true)) {
                throw new RuntimeException(
                    'Image upload directory could not be created.'
                );
            }
        }

        if (!is_writable($uploadDirectory)) {
            throw new RuntimeException(
                'Image upload directory is not writable.'
            );
        }

        // Generate an unpredictable filename.
        $fileName = bin2hex(random_bytes(16))
            . '.'
            . $allowedImages[$mimeType];

        $destination = $uploadDirectory . $fileName;

        // Move only a verified uploaded file.
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new RuntimeException(
                'Could not save the uploaded image.'
            );
        }

        $imagePath = 'uploads/products/' . $fileName;
        $imageUploaded = true;
    }

    // Determine the final product status.
    $finalStatus = $stock === 0
        ? 'out_of_stock'
        : $status;

    // Begin the database transaction.
    $pdo->beginTransaction();

    if ($productId > 0) {

        // Verify that the product exists.
        $checkStatement = $pdo->prepare("
            SELECT id
            FROM products
            WHERE id = ?
            LIMIT 1
            FOR UPDATE
        ");

        $checkStatement->execute([$productId]);

        if (!$checkStatement->fetch()) {
            throw new RuntimeException('Product not found.');
        }

        // Update existing product.
        $updateStatement = $pdo->prepare("
            UPDATE products
            SET
                category_id = ?,
                name = ?,
                slug = ?,
                sku = ?,
                description = ?,
                price = ?,
                stock = ?,
                status = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");

        $updateStatement->execute([
            $categoryId,
            $name,
            $slug,
            $sku !== '' ? $sku : null,
            $description,
            $price,
            $stock,
            $finalStatus,
            $productId
        ]);

        $savedProductId = $productId;

    } else {

        // Insert new product.
        $insertStatement = $pdo->prepare("
            INSERT INTO products (
                category_id,
                name,
                slug,
                sku,
                description,
                price,
                stock,
                status
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $insertStatement->execute([
            $categoryId,
            $name,
            $slug,
            $sku !== '' ? $sku : null,
            $description,
            $price,
            $stock,
            $finalStatus
        ]);

        $savedProductId = (int) $pdo->lastInsertId();
    }

    // Save the new primary image if one was uploaded.
    if ($imageUploaded && $imagePath !== null) {

        $removePrimaryStatement = $pdo->prepare("
            UPDATE product_images
            SET is_primary = 0
            WHERE product_id = ?
        ");

        $removePrimaryStatement->execute([$savedProductId]);

        $imageStatement = $pdo->prepare("
            INSERT INTO product_images (
                product_id,
                image_path,
                is_primary
            )
            VALUES (?, ?, 1)
        ");

        $imageStatement->execute([
            $savedProductId,
            $imagePath
        ]);
    }

    // Commit the database changes.
    $pdo->commit();

    $_SESSION['product_message'] = $productId > 0
        ? 'Product successfully updated.'
        : 'Product successfully added.';

} catch (Throwable $e) {

    // Roll back database changes.
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // Delete the newly uploaded file if saving failed.
    if ($imageUploaded && $imagePath !== null) {

        $savedFile = __DIR__ . '/../' . $imagePath;

        if (is_file($savedFile)) {
            @unlink($savedFile);
        }
    }

    error_log(
        'Froshgah save product error: ' . $e->getMessage()
    );

    // Show a safe message without exposing technical details.
    if ($e instanceof RuntimeException) {
        $_SESSION['product_error'] = $e->getMessage();
    } elseif ($e instanceof PDOException && $e->getCode() === '23000') {
        $_SESSION['product_error'] =
            'The SKU or product information already exists.';
    } else {
        $_SESSION['product_error'] =
            'Could not save the product. Please check the information and try again.';
    }
}

header('Location: /froshgah/admin/products.php');
exit;
