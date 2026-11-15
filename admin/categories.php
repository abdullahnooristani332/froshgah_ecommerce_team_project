
<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/admin-auth.php';

$editCategory = null;

$editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);

if ($editId && $editId > 0) {

    $editStatement = $pdo->prepare("
        SELECT *
        FROM categories
        WHERE id = ?
        LIMIT 1
    ");

    $editStatement->execute([$editId]);
    $editCategory = $editStatement->fetch();

    if (!$editCategory) {
        header("Location: /froshgah/admin/categories.php");
        exit;
    }
}

// Get categories and their product counts.
$categories = $pdo->query("
    SELECT
        c.id,
        c.name,
        c.slug,
        c.description,
        c.status,
        c.created_at,
        COUNT(p.id) AS product_count
    FROM categories c
    LEFT JOIN products p ON p.category_id = c.id
    GROUP BY
        c.id,
        c.name,
        c.slug,
        c.description,
        c.status,
        c.created_at
    ORDER BY c.created_at DESC
")->fetchAll();

$successMessage = $_SESSION['category_message'] ?? '';
$errorMessage = $_SESSION['category_error'] ?? '';

unset($_SESSION['category_message'], $_SESSION['category_error']);

function adminCategoryEscape($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

?>

<div class="admin-management-page">

    <div class="admin-management-heading">
        <div>
            <h1>Category Management</h1>
            <p>Create and organize your product categories.</p>
        </div>
    </div>

    <?php if ($successMessage !== ''): ?>
        <div class="alert-message success">
            <?= adminCategoryEscape($successMessage); ?>
        </div>
    <?php endif; ?>

    <?php if ($errorMessage !== ''): ?>
        <div class="alert-message error">
            <?= adminCategoryEscape($errorMessage); ?>
        </div>
    <?php endif; ?>

    <section class="admin-form-card">

        <h2>
            <?= $editCategory ? 'Edit Category' : 'Add New Category'; ?>
        </h2>

        <form
            action="/froshgah/actions/save-category.php"
            method="POST"
            class="admin-category-form"
        >

            <input
                type="hidden"
                name="csrf_token"
                value="<?= adminCategoryEscape(generateCsrfToken()); ?>"
            >

            <input
                type="hidden"
                name="category_id"
                value="<?= (int) ($editCategory['id'] ?? 0); ?>"
            >

            <div class="form-group">
                <label for="category_name">Category Name</label>

                <input
                    type="text"
                    id="category_name"
                    name="name"
                    maxlength="150"
                    value="<?= adminCategoryEscape($editCategory['name'] ?? ''); ?>"
                    placeholder="Example: Electronics"
                    required
                >
            </div>

            <div class="form-group">
                <label for="category_description">Description</label>

                <textarea
                    id="category_description"
                    name="description"
                    rows="4"
                    placeholder="Describe this product category..."
                ><?= adminCategoryEscape($editCategory['description'] ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <label for="category_status">Status</label>

                <select id="category_status" name="status" required>

                    <option
                        value="active"
                        <?= ($editCategory['status'] ?? 'active') === 'active' ? 'selected' : ''; ?>
                    >
                        Active
                    </option>

                    <option
                        value="inactive"
                        <?= ($editCategory['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>
                    >
                        Inactive
                    </option>

                </select>
            </div>

            <div class="admin-form-actions">

                <button type="submit" class="admin-primary-button">
                    <?= $editCategory ? 'Update Category' : 'Add Category'; ?>
                </button>

                <?php if ($editCategory): ?>

                    <a
                        href="/froshgah/admin/categories.php"
                        class="admin-secondary-button"
                    >
                        Cancel Editing
                    </a>

                <?php endif; ?>

            </div>

        </form>

    </section>

    <section class="admin-list-section">

        <div class="admin-list-heading">
            <h2>All Categories</h2>
            <span><?= count($categories); ?> categories</span>
        </div>

        <?php if (empty($categories)): ?>

            <div class="orders-empty">
                <p>No categories have been created yet.</p>
            </div>

        <?php else: ?>

            <div class="orders-table-wrapper">

                <table class="orders-table">

                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Slug</th>
                            <th>Products</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($categories as $category): ?>

                            <tr>

                                <td>
                                    <strong>
                                        <?= adminCategoryEscape($category['name']); ?>
                                    </strong>

                                    <?php if (!empty($category['description'])): ?>
                                        <small class="admin-category-description">
                                            <?= adminCategoryEscape($category['description']); ?>
                                        </small>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?= adminCategoryEscape($category['slug']); ?>
                                </td>

                                <td>
                                    <?= (int) $category['product_count']; ?>
                                </td>

                                <td>
                                    <span class="order-status-badge <?= $category['status'] === 'active' ? 'status-delivered' : 'status-cancelled'; ?>">
                                        <?= ucfirst(adminCategoryEscape($category['status'])); ?>
                                    </span>
                                </td>

                                <td>
                                    <?= adminCategoryEscape(
                                        date('M d, Y', strtotime($category['created_at']))
                                    ); ?>
                                </td>

                                <td>

                                    <div class="admin-row-actions">

                                        <a
                                            href="?edit=<?= (int) $category['id']; ?>"
                                            class="admin-edit-link"
                                        >
                                            Edit
                                        </a>

                                        <form
                                            action="/froshgah/actions/delete-category.php"
                                            method="POST"
                                            onsubmit="return confirm('Delete this category? Categories containing products cannot be deleted.');"
                                        >

                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= adminCategoryEscape(generateCsrfToken()); ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="category_id"
                                                value="<?= (int) $category['id']; ?>"
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
