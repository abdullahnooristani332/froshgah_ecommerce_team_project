
<?php

require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . '/../config/database.php';

$pageTitle = 'Inventory Management - Froshgah';

$lowStockLimit = 5;

// Read the selected inventory filter.
$allowedFilters = ['all', 'low_stock', 'out_of_stock'];

$filter = $_GET['filter'] ?? 'all';

if (!in_array($filter, $allowedFilters, true)) {
    $filter = 'all';
}

// Read search.
$search = trim($_GET['search'] ?? '');

// Build product query.
$sql = "
    SELECT
        p.id,
        p.name,
        p.sku,
        p.stock,
        p.status,
        p.price,
        p.updated_at,
        (
            SELECT pi.image_path
            FROM product_images pi
            WHERE pi.product_id = p.id
            ORDER BY pi.is_primary DESC, pi.id ASC
            LIMIT 1
        ) AS image_path
    FROM products p
    WHERE 1 = 1
";

$params = [];

if ($filter === 'low_stock') {
    $sql .= " AND p.stock > 0 AND p.stock <= :low_stock_limit";
    $params[':low_stock_limit'] = $lowStockLimit;
}

if ($filter === 'out_of_stock') {
    $sql .= " AND p.stock = 0";
}

if ($search !== '') {
    $sql .= "
        AND (
            p.name LIKE :search_name
            OR p.sku LIKE :search_sku
        )
    ";

    $searchValue = '%' . $search . '%';

    $params[':search_name'] = $searchValue;
    $params[':search_sku'] = $searchValue;
}

$sql .= " ORDER BY p.stock ASC, p.name ASC";

$statement = $pdo->prepare($sql);
$statement->execute($params);
$products = $statement->fetchAll();

// Inventory statistics.
$statsStatement = $pdo->prepare("
    SELECT
        COUNT(*) AS total_products,

        COALESCE(SUM(
            CASE
                WHEN stock > 0 AND stock <= ?
                THEN 1 ELSE 0
            END
        ), 0) AS low_stock_products,

        COALESCE(SUM(
            CASE
                WHEN stock = 0
                THEN 1 ELSE 0
            END
        ), 0) AS out_of_stock_products,

        COALESCE(SUM(stock), 0) AS total_units

    FROM products
");

$statsStatement->execute([$lowStockLimit]);
$stats = $statsStatement->fetch();

// Flash messages.
$message = $_SESSION['inventory_message'] ?? '';
$error = $_SESSION['inventory_error'] ?? '';

unset(
    $_SESSION['inventory_message'],
    $_SESSION['inventory_error']
);

function inventoryEscape($value): string
{
    return htmlspecialchars(
        (string)($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

?>

<main class="container section inventory-page">

    <div class="inventory-heading">

        <div>
            <h1>Inventory Management</h1>

            <p>
                Monitor product quantities and manage available stock.
            </p>
        </div>

        <a
            href="/froshgah/admin/products.php"
            class="admin-secondary-button"
        >
            Manage Products
        </a>

    </div>

    <?php if ($message !== ''): ?>

        <div class="inventory-alert inventory-success">
            <?php echo inventoryEscape($message); ?>
        </div>

    <?php endif; ?>

    <?php if ($error !== ''): ?>

        <div class="inventory-alert inventory-error">
            <?php echo inventoryEscape($error); ?>
        </div>

    <?php endif; ?>

    <!-- INVENTORY STATISTICS -->

    <section class="inventory-statistics">

        <article class="inventory-stat-card">

            <span>Total Products</span>

            <strong>
                <?php echo (int)$stats['total_products']; ?>
            </strong>

        </article>

        <article class="inventory-stat-card low-stock-stat">

            <span>Low Stock Products</span>

            <strong>
                <?php echo (int)$stats['low_stock_products']; ?>
            </strong>

            <small>1 to <?php echo $lowStockLimit; ?> units</small>

        </article>

        <article class="inventory-stat-card out-stock-stat">

            <span>Out of Stock</span>

            <strong>
                <?php echo (int)$stats['out_of_stock_products']; ?>
            </strong>

        </article>

        <article class="inventory-stat-card">

            <span>Total Available Units</span>

            <strong>
                <?php echo number_format((int)$stats['total_units']); ?>
            </strong>

        </article>

    </section>

    <!-- SEARCH AND FILTER -->

    <section class="inventory-toolbar">

        <form
            method="GET"
            action="/froshgah/admin/inventory.php"
            class="inventory-search-form"
        >

            <input
                type="search"
                name="search"
                placeholder="Search product name or SKU..."
                value="<?php echo inventoryEscape($search); ?>"
            >

            <select name="filter">

                <option
                    value="all"
                    <?php echo $filter === 'all' ? 'selected' : ''; ?>
                >
                    All Products
                </option>

                <option
                    value="low_stock"
                    <?php echo $filter === 'low_stock' ? 'selected' : ''; ?>
                >
                    Low Stock
                </option>

                <option
                    value="out_of_stock"
                    <?php echo $filter === 'out_of_stock' ? 'selected' : ''; ?>
                >
                    Out of Stock
                </option>

            </select>

            <button type="submit" class="inventory-search-button">
                Search
            </button>

            <a
                href="/froshgah/admin/inventory.php"
                class="inventory-clear-button"
            >
                Clear
            </a>

        </form>

    </section>

    <!-- INVENTORY TABLE -->

    <section class="inventory-table-section">

        <div class="inventory-table-heading">

            <h2>Product Stock</h2>

            <span>
                <?php echo count($products); ?> product(s) found
            </span>

        </div>

        <?php if (empty($products)): ?>

            <div class="inventory-empty">
                <h3>No products found</h3>
                <p>Try changing your search or inventory filter.</p>
            </div>

        <?php else: ?>

            <div class="admin-table-wrapper">

                <table class="admin-table inventory-table">

                    <thead>

                        <tr>
                            <th>Product</th>
                            <th>SKU</th>
                            <th>Price</th>
                            <th>Current Stock</th>
                            <th>Status</th>
                            <th>Update Stock</th>
                        </tr>

                    </thead>

                    <tbody>

                        <?php foreach ($products as $product): ?>

                            <tr>

                                <td>

                                    <div class="inventory-product">

                                        <?php if (!empty($product['image_path'])): ?>

                                            <img
                                                src="/froshgah/<?php
                                                    echo inventoryEscape(
                                                        ltrim($product['image_path'], '/')
                                                    );
                                                ?>"
                                                alt="<?php
                                                    echo inventoryEscape($product['name']);
                                                ?>"
                                            >

                                        <?php else: ?>

                                            <div class="inventory-no-image">
                                                No Image
                                            </div>

                                        <?php endif; ?>

                                        <strong>
                                            <?php
                                            echo inventoryEscape($product['name']);
                                            ?>
                                        </strong>

                                    </div>

                                </td>

                                <td>
                                    <?php
                                    echo inventoryEscape(
                                        $product['sku'] ?: 'N/A'
                                    );
                                    ?>
                                </td>

                                <td>
                                    $<?php
                                    echo number_format(
                                        (float)$product['price'],
                                        2
                                    );
                                    ?>
                                </td>

                                <td>

                                    <strong class="inventory-quantity">
                                        <?php echo (int)$product['stock']; ?>
                                    </strong>

                                </td>

                                <td>

                                    <?php if ($product['status'] === 'inactive'): ?>

                                        <span class="inventory-status status-inactive">
                                            Inactive
                                        </span>

                                    <?php elseif ((int)$product['stock'] === 0): ?>

                                        <span class="inventory-status status-out">
                                            Out of Stock
                                        </span>

                                    <?php elseif ((int)$product['stock'] <= $lowStockLimit): ?>

                                        <span class="inventory-status status-low">
                                            Low Stock
                                        </span>

                                    <?php else: ?>

                                        <span class="inventory-status status-good">
                                            In Stock
                                        </span>

                                    <?php endif; ?>

                                </td>

                                <td>

                                    <form
                                        method="POST"
                                        action="/froshgah/actions/update-stock.php"
                                        class="inventory-update-form"
                                    >

                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?php
                                                echo inventoryEscape(
                                                    generateCsrfToken()
                                                );
                                            ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="product_id"
                                            value="<?php echo (int)$product['id']; ?>"
                                        >

                                        <input
                                            type="number"
                                            name="stock"
                                            min="0"
                                            step="1"
                                            required
                                            value="<?php echo (int)$product['stock']; ?>"
                                            aria-label="New stock quantity"
                                        >

                                        <button type="submit">
                                            Update
                                        </button>

                                    </form>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php endif; ?>

    </section>

    <div class="inventory-information">

        <strong>Inventory rule:</strong>

        <p>
            Products with 1–5 units are considered low stock.
            Products with zero units are considered out of stock.
            Inactive products remain inactive when their stock is updated.
        </p>

    </div>

</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
