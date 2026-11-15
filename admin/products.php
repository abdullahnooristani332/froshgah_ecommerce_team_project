
<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/admin-auth.php';

$editProduct = null;
$editImage = null;

// Load a product for editing if an ID is supplied.
$editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);

if ($editId && $editId > 0) {

    $editStatement = $pdo->prepare("
        SELECT *
        FROM products
        WHERE id = ?
        LIMIT 1
    ");

    $editStatement->execute([$editId]);
    $editProduct = $editStatement->fetch();

    if (!$editProduct) {
        header("Location: /froshgah/admin/products.php");
        exit;
    }

    $imageStatement = $pdo->prepare("
        SELECT image_path
        FROM product_images
        WHERE product_id = ?
        ORDER BY is_primary DESC, id ASC
        LIMIT 1
    ");

    $imageStatement->execute([$editId]);
    $editImage = $imageStatement->fetchColumn();
}

// Get all categories for the product form.
$categories = $pdo->query("
    SELECT id, name
    FROM categories
    ORDER BY name ASC
")->fetchAll();

// Search products.
$search = trim($_GET['search'] ?? '');

$sql = "
    SELECT
        p.id,
        p.name,
        p.sku,
        p.price,
        p.stock,
        p.status,
        c.name AS category_name,
        (
            SELECT pi.image_path
            FROM product_images pi
            WHERE pi.product_id = p.id
            ORDER BY pi.is_primary DESC, pi.id ASC
            LIMIT 1
        ) AS image_path
    FROM products p
    LEFT JOIN categories c ON c.id = p.category_id
";

$params = [];

if ($search !== '') {
    $sql .= "
        WHERE p.name LIKE ?
           OR p.sku LIKE ?
    ";

    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

$sql .= " ORDER BY p.created_at DESC";

$listStatement = $pdo->prepare($sql);
$listStatement->execute($params);
$products = $listStatement->fetchAll();

$successMessage = $_SESSION['product_message'] ?? '';
$errorMessage = $_SESSION['product_error'] ?? '';

unset($_SESSION['product_message'], $_SESSION['product_error']);

function adminProductEscape($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

?>

<div class="admin-management-page">

    <div class="admin-management-heading">
        <div>
            <h1>Product Management</h1>
            <p>Add, edit, and manage your store products.</p>
        </div>
    </div>

    <?php if ($successMessage !== ''): ?>
        <div class="alert-message success">
            <?= adminProductEscape($successMessage); ?>
        </div>
    <?php endif; ?>

    <?php if ($errorMessage !== ''): ?>
        <div class="alert-message error">
            <?= adminProductEscape($errorMessage); ?>
        </div>
    <?php endif; ?>

    <section class="admin-form-card">

        <h2>
            <?= $editProduct ? 'Edit Product' : 'Add New Product'; ?>
        </h2>

        <?php if (empty($categories)): ?>

            <p>
                You must create a category before adding products.
                <a href="/froshgah/admin/categories.php">Create a category</a>
            </p>

        <?php else: ?>

            <form
                action="/froshgah/actions/save-product.php"
                method="POST"
                enctype="multipart/form-data"
                class="admin-product-form"
            >

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= adminProductEscape(generateCsrfToken()); ?>"
                >

                <input
                    type="hidden"
                    name="product_id"
                    value="<?= (int) ($editProduct['id'] ?? 0); ?>"
                >

                <div class="admin-form-grid">

                    <div class="form-group">
                        <label for="product_name">Product Name</label>
                        <input
                            type="text"
                            id="product_name"
                            name="name"
                            maxlength="200"
                            value="<?= adminProductEscape($editProduct['name'] ?? ''); ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="category_id">Category</label>

                        <select id="category_id" name="category_id" required>
                            <option value="">Select Category</option>

                            <?php foreach ($categories as $category): ?>

                                <option
                                    value="<?= (int) $category['id']; ?>"
                                    <?= (int) ($editProduct['category_id'] ?? 0) === (int) $category['id'] ? 'selected' : ''; ?>
                                >
                                    <?= adminProductEscape($category['name']); ?>
                                </option>

                            <?php endforeach; ?>

                        </select>
                    </div>

                    <div class="form-group">
                        <label for="sku">SKU</label>
                        <input
                            type="text"
                            id="sku"
                            name="sku"
                            maxlength="100"
                            value="<?= adminProductEscape($editProduct['sku'] ?? ''); ?>"
                            placeholder="Example: FR-LAP-001"
                        >
                    </div>

                    <div class="form-group">
                        <label for="price">Price</label>
                        <input
                            type="number"
                            id="price"
                            name="price"
                            min="0"
                            step="0.01"
                            value="<?= adminProductEscape($editProduct['price'] ?? ''); ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="stock">Stock Quantity</label>
                        <input
                            type="number"
                            id="stock"
                            name="stock"
                            min="0"
                            step="1"
                            value="<?= adminProductEscape($editProduct['stock'] ?? '0'); ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="status">Product Status</label>

                        <select id="status" name="status" required>

                            <option
                                value="active"
                                <?= ($editProduct['status'] ?? 'active') === 'active' ? 'selected' : ''; ?>
                            >
                                Active
                            </option>

                            <option
                                value="inactive"
                                <?= ($editProduct['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>
                            >
                                Inactive
                            </option>

                        </select>
                    </div>

                    <div class="form-group full-width">
                        <label for="description">Description</label>
                        <textarea
                            id="description"
                            name="description"
                            rows="5"
                        ><?= adminProductEscape($editProduct['description'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-group full-width">
                        <label for="product_image">Product Image</label>

                        <input
                            type="file"
                            id="product_image"
                            name="product_image"
                            accept="image/jpeg,image/png,image/webp"
                        >

                        <small>
                            Allowed: JPG, PNG, WEBP. Maximum size: 3 MB.
                            Leave empty when editing to keep the existing image.
                        </small>

                        <?php if ($editImage): ?>
                            <div class="current-product-image">
                                <p>Current Image:</p>
                                <img
                                    src="/froshgah/<?= adminProductEscape($editImage); ?>"
                                    alt="Current product"
                                >
                            </div>
                        <?php endif; ?>

                    </div>

                </div>

                <div class="admin-form-actions">

                    <button type="submit" class="admin-primary-button">
                        <?= $editProduct ? 'Update Product' : 'Add Product'; ?>
                    </button>

                    <?php if ($editProduct): ?>
                        <a
                            href="/froshgah/admin/products.php"
                            class="admin-secondary-button"
                        >
                            Cancel Editing
                        </a>
                    <?php endif; ?>

                </div>

            </form>

        <?php endif; ?>

    </section>

    <section class="admin-list-section">

        <div class="admin-list-heading">
            <h2>All Products</h2>

            <form method="GET" class="admin-search-form">
                <input
                    type="search"
                    name="search"
                    placeholder="Search by product name or SKU"
                    value="<?= adminProductEscape($search); ?>"
                >
                <button type="submit">Search</button>
            </form>
        </div>

        <?php if (empty($products)): ?>

            <div class="orders-empty">
                <p>No products found.</p>
            </div>

        <?php else: ?>

            <div class="orders-table-wrapper">

                <table class="orders-table admin-products-table">

                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Product</th>
                            <th>Category</th>
                            <th>SKU</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($products as $product): ?>

                            <tr>

                                <td>
                                    <?php if ($product['image_path']): ?>
                                        <img
                                            class="admin-product-thumbnail"
                                            src="/froshgah/<?= adminProductEscape($product['image_path']); ?>"
                                            alt="<?= adminProductEscape($product['name']); ?>"
                                        >
                                    <?php else: ?>
                                        <span>No image</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?= adminProductEscape($product['name']); ?>
                                </td>

                                <td>
                                    <?= adminProductEscape($product['category_name'] ?? 'Uncategorized'); ?>
                                </td>

                                <td>
                                    <?= adminProductEscape($product['sku'] ?? '-'); ?>
                                </td>

                                <td>
                                    $<?= number_format((float) $product['price'], 2); ?>
                                </td>

                                <td>
                                    <?= (int) $product['stock']; ?>
                                </td>

                                <td>
                                    <span class="order-status-badge status-<?= $product['status'] === 'active' ? 'delivered' : ($product['status'] === 'inactive' ? 'cancelled' : 'pending'); ?>">
                                        <?= ucfirst(adminProductEscape($product['status'])); ?>
                                    </span>
                                </td>

                                <td>
                                    <div class="admin-row-actions">

                                        <a
                                            href="?edit=<?= (int) $product['id']; ?>"
                                            class="admin-edit-link"
                                        >
                                            Edit
                                        </a>

                                        <form
                                            action="/froshgah/actions/delete-product.php"
                                            method="POST"
                                            onsubmit="return confirm('Are you sure you want to delete this product?');"
                                        >

                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= adminProductEscape(generateCsrfToken()); ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="product_id"
                                                value="<?= (int) $product['id']; ?>"
                                            >

                                            <button
                                                type="submit"
                                                class="admin-delete-button"
                                            >
                                                Delete
                                            </button>

                                        </form>

                                    </div>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php endif; ?>

    </section>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
