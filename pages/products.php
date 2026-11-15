
<?php

require_once __DIR__ . '/../config/database.php';

// ======================================================
// 1. CONFIGURATION
// ======================================================

$productsPerPage = 9;
$currencySymbol = '$';

// ======================================================
// 2. HELPER FUNCTIONS
// ======================================================

function fgEscape($value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}

function fgProductUrl(array $changes = []): string
{
    $query = $_GET;

    foreach ($changes as $key => $value) {
        if ($value === null || $value === '') {
            unset($query[$key]);
        } else {
            $query[$key] = $value;
        }
    }

    if (!array_key_exists('page', $changes)) {
        $query['page'] = 1;
    }

    $url = '/froshgah/pages/products.php';

    return $url . (
        !empty($query)
            ? '?' . http_build_query($query)
            : ''
    );
}

// ======================================================
// 3. READ FILTER VALUES
// ======================================================

$search = trim((string) ($_GET['search'] ?? ''));

$categoryId = filter_var(
    $_GET['category'] ?? '',
    FILTER_VALIDATE_INT
);

if (
    $categoryId === false ||
    $categoryId === null ||
    $categoryId < 1
) {
    $categoryId = null;
}

$minPriceInput = trim((string) ($_GET['min_price'] ?? ''));

$maxPriceInput = trim((string) ($_GET['max_price'] ?? ''));

$minPrice = null;
$maxPrice = null;

$filterError = '';

// Minimum price validation

if ($minPriceInput !== '') {

    if (
        !is_numeric($minPriceInput) ||
        (float) $minPriceInput < 0
    ) {
        $filterError = 'Please enter a valid minimum price.';
    } else {
        $minPrice = (float) $minPriceInput;
    }
}

// Maximum price validation

if ($maxPriceInput !== '') {

    if (
        !is_numeric($maxPriceInput) ||
        (float) $maxPriceInput < 0
    ) {
        $filterError = 'Please enter a valid maximum price.';
    } else {
        $maxPrice = (float) $maxPriceInput;
    }
}

// Check price range

if (
    $minPrice !== null &&
    $maxPrice !== null &&
    $minPrice > $maxPrice
) {
    $filterError = 'Minimum price cannot exceed maximum price.';
}

// ======================================================
// 4. SORTING
// ======================================================

$sortOptions = [

    'newest' => 'Newest First',

    'oldest' => 'Oldest First',

    'price_low' => 'Price: Low to High',

    'price_high' => 'Price: High to Low',

    'name_asc' => 'Name: A to Z'

];

$sort = (string) ($_GET['sort'] ?? 'newest');

if (!array_key_exists($sort, $sortOptions)) {
    $sort = 'newest';
}

$sortSql = [

    'newest' => 'p.created_at DESC, p.id DESC',

    'oldest' => 'p.created_at ASC, p.id ASC',

    'price_low' => 'p.price ASC, p.id DESC',

    'price_high' => 'p.price DESC, p.id DESC',

    'name_asc' => 'p.name ASC, p.id DESC'

];

$orderBy = $sortSql[$sort];

// ======================================================
// 5. PAGINATION
// ======================================================

$page = filter_var(
    $_GET['page'] ?? 1,
    FILTER_VALIDATE_INT
);

if ($page === false || $page === null || $page < 1) {
    $page = 1;
}

// ======================================================
// 6. LOAD CATEGORIES
// ======================================================

// Also retrieve the number of products in each category.

$categoryStatement = $pdo->query("

    SELECT

        c.id,

        c.name,

        COUNT(p.id) AS product_count

    FROM categories c

    LEFT JOIN products p

        ON p.category_id = c.id

        AND p.status IN ('active', 'out_of_stock')

    WHERE c.status = 'active'

    GROUP BY c.id, c.name

    ORDER BY c.name ASC

");

$categories = $categoryStatement->fetchAll(PDO::FETCH_ASSOC);

// ======================================================
// 7. BUILD FILTER CONDITIONS
// ======================================================

$whereConditions = [

    "p.status IN ('active', 'out_of_stock')",

    "c.status = 'active'"

];

$params = [];

// Search filter

if ($search !== '') {

    $whereConditions[] = "

        (

            p.name LIKE :search_name

            OR p.description LIKE :search_description

            OR p.sku LIKE :search_sku

        )

    ";

    $searchValue = '%' . $search . '%';

    $params['search_name'] = $searchValue;

    $params['search_description'] = $searchValue;

    $params['search_sku'] = $searchValue;

}

// Category filter

if ($categoryId !== null) {

    $whereConditions[] = 'p.category_id = :category_id';

    $params['category_id'] = $categoryId;

}

// Minimum price

if ($minPrice !== null) {

    $whereConditions[] = 'p.price >= :min_price';

    $params['min_price'] = $minPrice;

}

// Maximum price

if ($maxPrice !== null) {

    $whereConditions[] = 'p.price <= :max_price';

    $params['max_price'] = $maxPrice;

}

$whereSql = implode(' AND ', $whereConditions);

// ======================================================
// 8. COUNT PRODUCTS
// ======================================================

$countSql = "

    SELECT COUNT(*)

    FROM products p

    INNER JOIN categories c

        ON p.category_id = c.id

    WHERE $whereSql

";

$countStatement = $pdo->prepare($countSql);

foreach ($params as $key => $value) {

    $type = is_int($value)
        ? PDO::PARAM_INT
        : PDO::PARAM_STR;

    $countStatement->bindValue(
        ':' . $key,
        $value,
        $type
    );

}

$countStatement->execute();

$totalProducts = (int) $countStatement->fetchColumn();

$totalPages = max(
    1,
    (int) ceil($totalProducts / $productsPerPage)
);

// Keep page inside valid range

if ($page > $totalPages) {
    $page = $totalPages;
}

$offset = ($page - 1) * $productsPerPage;

// ======================================================
// 9. FETCH PRODUCTS
// ======================================================

$productSql = "

    SELECT

        p.id,

        p.name,

        p.sku,

        p.description,

        p.price,

        p.stock,

        p.status,

        p.created_at,

        c.name AS category_name,

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

    INNER JOIN categories c

        ON p.category_id = c.id

    WHERE $whereSql

    ORDER BY $orderBy

    LIMIT :limit OFFSET :offset

";

$productStatement = $pdo->prepare($productSql);

foreach ($params as $key => $value) {

    $type = is_int($value)
        ? PDO::PARAM_INT
        : PDO::PARAM_STR;

    $productStatement->bindValue(
        ':' . $key,
        $value,
        $type
    );

}

$productStatement->bindValue(
    ':limit',
    $productsPerPage,
    PDO::PARAM_INT
);

$productStatement->bindValue(
    ':offset',
    $offset,
    PDO::PARAM_INT
);

$productStatement->execute();

$products = $productStatement->fetchAll(PDO::FETCH_ASSOC);

// ======================================================
// 10. RESULTS INFORMATION
// ======================================================

$startItem = $totalProducts > 0
    ? $offset + 1
    : 0;

$endItem = min(
    $offset + count($products),
    $totalProducts
);

// ======================================================
// 11. PRICE RANGE OPTIONS
// ======================================================

$priceRanges = [

    [
        'label' => 'Under $25',
        'min' => null,
        'max' => 25
    ],

    [
        'label' => '$25 – $50',
        'min' => 25,
        'max' => 50
    ],

    [
        'label' => '$50 – $100',
        'min' => 50,
        'max' => 100
    ],

    [
        'label' => '$100 – $200',
        'min' => 100,
        'max' => 200
    ],

    [
        'label' => 'Over $200',
        'min' => 200,
        'max' => null
    ]

];

// ======================================================
// 12. PAGE HEADER
// ======================================================

$pageTitle = 'Shop Products | FroshGah';

require_once __DIR__ . '/../includes/header.php';

require_once __DIR__ . '/../includes/navbar.php';

?>

<main class="fg-shop-page">

    <!-- ============================================= -->
    <!-- HERO BANNER -->
    <!-- ============================================= -->

    <section class="fg-shop-hero">

        <div class="fg-hero-content">

            <span class="fg-hero-label">
                DISCOVER SOMETHING NEW
            </span>

            <h1>
                Shop Our
                <span>Products</span>
            </h1>

            <p>
                Discover a world of quality products
                at the best prices.
            </p>

            <div class="fg-hero-line"></div>

        </div>

        <div class="fg-hero-art" aria-hidden="true">

            <div class="fg-art-circle"></div>

            <div class="fg-art-bag fg-art-bag-blue">
                <span></span>
            </div>

            <div class="fg-art-bag fg-art-bag-orange">
                <span></span>
            </div>

            <div class="fg-art-cart">
                🛒
            </div>

            <div class="fg-art-headphones">
                🎧
            </div>

            <div class="fg-art-sparkle fg-sparkle-one">✦</div>

            <div class="fg-art-sparkle fg-sparkle-two">✦</div>

        </div>

    </section>

    <!-- ============================================= -->
    <!-- BREADCRUMB -->
    <!-- ============================================= -->

    <nav class="fg-breadcrumb" aria-label="Breadcrumb">

        <a href="/froshgah/index.php">
            <span>⌂</span> Home
        </a>

        <span class="fg-breadcrumb-separator">›</span>

        <span class="fg-breadcrumb-current">
            Shop
        </span>

    </nav>

    <!-- ============================================= -->
    <!-- SHOP LAYOUT -->
    <!-- ============================================= -->

    <form

        action="/froshgah/pages/products.php"

        method="GET"

        id="fgShopFilterForm"

        class="fg-shop-form"

    >

        <input
            type="hidden"
            name="category"
            value="<?php echo fgEscape($categoryId ?? ''); ?>"
        >

        <div class="fg-shop-layout">

            <!-- ===================================== -->
            <!-- LEFT SIDEBAR -->
            <!-- ===================================== -->

            <aside class="fg-filter-sidebar">

                <div class="fg-filter-header">

                    <h2>
                        <span class="fg-filter-heading-icon">▦</span>
                        Filters
                    </h2>

                    <a href="/froshgah/pages/products.php">
                        Reset
                    </a>

                </div>

                <!-- CATEGORIES -->

                <div class="fg-filter-group">

                    <h3>
                        <span>▦</span>
                        Categories
                    </h3>

                    <div class="fg-category-list">

                        <a

                            href="<?php echo fgEscape(
                                fgProductUrl(['category' => null])
                            ); ?>"

                            class="fg-category-link <?php echo $categoryId === null ? 'active' : ''; ?>"

                        >

                            <span class="fg-category-name">
                                All Categories
                            </span>

                            <span class="fg-category-count">
                                <?php echo $totalProducts; ?>
                            </span>

                        </a>

                        <?php foreach ($categories as $category): ?>

                            <a

                                href="<?php echo fgEscape(
                                    fgProductUrl([
                                        'category' => (int) $category['id']
                                    ])
                                ); ?>"

                                class="fg-category-link <?php echo $categoryId === (int) $category['id'] ? 'active' : ''; ?>"

                            >

                                <span class="fg-category-name">

                                    <?php echo fgEscape(
                                        $category['name']
                                    ); ?>

                                </span>

                                <span class="fg-category-count">

                                    <?php echo (int) $category['product_count']; ?>

                                </span>

                            </a>

                        <?php endforeach; ?>

                    </div>

                </div>

                <!-- PRICE RANGE -->

                <div class="fg-filter-group">

                    <h3>
                        <span>◆</span>
                        Price Range
                    </h3>

                    <div class="fg-price-range-list">

                        <?php foreach ($priceRanges as $range): ?>

                            <?php

                            $rangeActive = (
                                $minPrice === $range['min'] &&
                                $maxPrice === $range['max']
                            );

                            ?>

                            <a

                                href="<?php echo fgEscape(
                                    fgProductUrl([

                                        'min_price' => $range['min'],

                                        'max_price' => $range['max']

                                    ])
                                ); ?>"

                                class="fg-price-range-link <?php echo $rangeActive ? 'active' : ''; ?>"

                            >

                                <span class="fg-custom-radio"></span>

                                <span>
                                    <?php echo fgEscape($range['label']); ?>
                                </span>

                            </a>

                        <?php endforeach; ?>

                    </div>

                </div>

                <!-- CUSTOM PRICE -->

                <div class="fg-filter-group">

                    <h3>
                        <span>⌁</span>
                        Custom Price
                    </h3>

                    <div class="fg-custom-price-fields">

                        <div>

                            <label for="min_price">
                                Minimum
                            </label>

                            <input

                                type="number"

                                id="min_price"

                                name="min_price"

                                min="0"

                                step="0.01"

                                placeholder="0.00"

                                value="<?php echo fgEscape($minPriceInput); ?>"

                            >

                        </div>

                        <div>

                            <label for="max_price">
                                Maximum
                            </label>

                            <input

                                type="number"

                                id="max_price"

                                name="max_price"

                                min="0"

                                step="0.01"

                                placeholder="999.00"

                                value="<?php echo fgEscape($maxPriceInput); ?>"

                            >

                        </div>

                    </div>

                </div>

                <!-- FILTER ACTIONS -->

                <div class="fg-filter-actions">

                    <button
                        type="submit"
                        class="fg-apply-filter-button"
                    >

                        <span>⌕</span>
                        Apply Filters

                    </button>

                    <a
                        href="/froshgah/pages/products.php"
                        class="fg-clear-filter-button"
                    >
                        Clear All Filters
                    </a>

                </div>

            </aside>

            <!-- ===================================== -->
            <!-- RIGHT PRODUCT CONTENT -->
            <!-- ===================================== -->

            <section class="fg-products-content">

                <!-- TOOLBAR -->

                <div class="fg-products-toolbar">

                    <div class="fg-toolbar-information">

                        <h2>
                            Explore Products
                        </h2>

                        <p>

                            Showing

                            <strong>
                                <?php echo $startItem; ?>–<?php echo $endItem; ?>
                            </strong>

                            of

                            <strong>
                                <?php echo number_format($totalProducts); ?>
                            </strong>

                            products

                        </p>

                    </div>

                    <div class="fg-toolbar-controls">

                        <!-- SEARCH -->

                        <div class="fg-shop-search">

                            <input

                                type="search"

                                name="search"

                                id="fgProductSearch"

                                placeholder="Search products..."

                                value="<?php echo fgEscape($search); ?>"

                            >

                            <button
                                type="submit"
                                aria-label="Search products"
                            >
                                ⌕
                            </button>

                        </div>

                        <!-- SORT -->

                        <div class="fg-sort-control">

                            <label for="fgSort">
                                Sort:
                            </label>

                            <select name="sort" id="fgSort">

                                <?php foreach ($sortOptions as $value => $label): ?>

                                    <option

                                        value="<?php echo fgEscape($value); ?>"

                                        <?php echo $sort === $value ? 'selected' : ''; ?>

                                    >

                                        <?php echo fgEscape($label); ?>

                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>

                        <!-- GRID / LIST -->

                        <div class="fg-view-switcher">

                            <button

                                type="button"

                                class="fg-view-button active"

                                data-view-toggle="grid"

                                aria-label="Grid view"

                                aria-pressed="true"

                            >
                                ▦

                            </button>

                            <button

                                type="button"

                                class="fg-view-button"

                                data-view-toggle="list"

                                aria-label="List view"

                                aria-pressed="false"

                            >
                                ☷

                            </button>

                        </div>

                    </div>

                </div>

                <!-- VALIDATION ERROR -->

                <?php if ($filterError !== ''): ?>

                    <div class="fg-filter-error">

                        <?php echo fgEscape($filterError); ?>

                    </div>

                <?php endif; ?>

                <!-- ================================= -->
                <!-- PRODUCTS -->
                <!-- ================================= -->

                <?php if (empty($products)): ?>

                    <div class="fg-empty-products">

                        <div class="fg-empty-icon">
                            🛍️
                        </div>

                        <h2>
                            No Products Found
                        </h2>

                        <p>
                            We couldn't find products matching
                            your search or selected filters.
                        </p>

                        <a href="/froshgah/pages/products.php">

                            Explore All Products

                        </a>

                    </div>

                <?php else: ?>

                    <div

                        class="fg-product-grid"

                        id="fgProductGrid"

                        data-view="grid"

                    >

                        <?php foreach ($products as $product): ?>

                            <?php

                            $productUrl = '/froshgah/pages/product-details.php?id='
                                . (int) $product['id'];

                            $imagePath = trim(
                                (string) ($product['image_path'] ?? '')
                            );

                            $imageUrl = '';

                            if ($imagePath !== '') {

                                if (
                                    preg_match(
                                        '~^https?://~i',
                                        $imagePath
                                    )
                                ) {

                                    $imageUrl = $imagePath;

                                } else {

                                    $imageUrl = '/froshgah/' .
                                        ltrim($imagePath, '/');

                                }

                            }

                            $inStock = (
                                (int) $product['stock'] > 0 &&
                                $product['status'] !== 'out_of_stock'
                            );

                            $description = trim(
                                (string) ($product['description'] ?? '')
                            );

                            if ($description === '') {

                                $description = 'Discover this product at FroshGah.';

                            } else {

                                $description = mb_strimwidth(
                                    $description,
                                    0,
                                    95,
                                    '...'
                                );

                            }

                            ?>

                            <article class="fg-product-card">

                                <!-- PRODUCT IMAGE -->

                                <div class="fg-product-image-container">

                                    <a

                                        href="<?php echo fgEscape($productUrl); ?>"

                                        class="fg-product-image-link"

                                    >

                                        <?php if ($imageUrl !== ''): ?>

                                            <img

                                                src="<?php echo fgEscape($imageUrl); ?>"

                                                alt="<?php echo fgEscape($product['name']); ?>"

                                                class="fg-product-image"

                                                loading="lazy"

                                                decoding="async"

                                            >

                                        <?php else: ?>

                                            <div class="fg-product-placeholder">

                                                <span>▧</span>

                                                <small>
                                                    No Image Available
                                                </small>

                                            </div>

                                        <?php endif; ?>

                                    </a>

                                    <?php if (!$inStock): ?>

                                        <span class="fg-product-stock-badge">

                                            Out of Stock

                                        </span>

                                    <?php endif; ?>

                                </div>

                                <!-- PRODUCT INFORMATION -->

                                <div class="fg-product-information">

                                    <span class="fg-product-category">

                                        <?php echo fgEscape(
                                            $product['category_name']
                                        ); ?>

                                    </span>

                                    <h3>

                                        <a href="<?php echo fgEscape($productUrl); ?>">

                                            <?php echo fgEscape(
                                                $product['name']
                                            ); ?>

                                        </a>

                                    </h3>

                                    <p class="fg-product-description">

                                        <?php echo fgEscape($description); ?>

                                    </p>

                                    <div class="fg-product-bottom">

                                        <div class="fg-product-price">

                                            <strong>

                                                <?php

                                                echo fgEscape($currencySymbol) .

                                                    number_format(
                                                        (float) $product['price'],
                                                        2
                                                    );

                                                ?>

                                            </strong>

                                        </div>

                                        <div class="fg-product-stock">

                                            <?php if ($inStock): ?>

                                                <span class="fg-stock-dot"></span>

                                                <span>
                                                    In Stock
                                                </span>

                                            <?php else: ?>

                                                <span class="fg-stock-dot fg-stock-dot-out"></span>

                                                <span>
                                                    Out of Stock
                                                </span>

                                            <?php endif; ?>

                                        </div>

                                    </div>

                                    <!-- PRODUCT ACTION -->

                                    <a

                                        href="<?php echo fgEscape($productUrl); ?>"

                                        class="fg-product-button"

                                    >

                                        View Details

                                        <span>→</span>

                                    </a>

                                </div>

                            </article>

                        <?php endforeach; ?>

                    </div>

                <?php endif; ?>

            </section>

        </div>

    </form>

    <!-- ============================================= -->
    <!-- PAGINATION -->
    <!-- ============================================= -->

    <?php if ($totalPages > 1): ?>

        <nav

            class="fg-pagination"

            aria-label="Product pagination"

        >

            <?php if ($page > 1): ?>

                <a

                    href="<?php echo fgEscape(
                        fgProductUrl(['page' => $page - 1])
                    ); ?>"

                    class="fg-pagination-arrow"

                    aria-label="Previous page"

                >
                    ‹
                </a>

            <?php else: ?>

                <span class="fg-pagination-arrow disabled">
                    ‹
                </span>

            <?php endif; ?>

            <?php

            $startPage = max(1, $page - 2);

            $endPage = min($totalPages, $page + 2);

            ?>

            <?php if ($startPage > 1): ?>

                <a
                    href="<?php echo fgEscape(
                        fgProductUrl(['page' => 1])
                    ); ?>"
                    class="fg-pagination-number"
                >
                    1
                </a>

                <?php if ($startPage > 2): ?>

                    <span class="fg-pagination-dots">
                        ...
                    </span>

                <?php endif; ?>

            <?php endif; ?>

            <?php for (
                $pageNumber = $startPage;
                $pageNumber <= $endPage;
                $pageNumber++
            ): ?>

                <?php if ($pageNumber === $page): ?>

                    <span class="fg-pagination-number active">

                        <?php echo $pageNumber; ?>

                    </span>

                <?php else: ?>

                    <a

                        href="<?php echo fgEscape(
                            fgProductUrl(['page' => $pageNumber])
                        ); ?>"

                        class="fg-pagination-number"

                    >

                        <?php echo $pageNumber; ?>

                    </a>

                <?php endif; ?>

            <?php endfor; ?>

            <?php if ($endPage < $totalPages): ?>

                <?php if ($endPage < $totalPages - 1): ?>

                    <span class="fg-pagination-dots">
                        ...
                    </span>

                <?php endif; ?>

                <a

                    href="<?php echo fgEscape(
                        fgProductUrl(['page' => $totalPages])
                    ); ?>"

                    class="fg-pagination-number"

                >

                    <?php echo $totalPages; ?>

                </a>

            <?php endif; ?>

            <?php if ($page < $totalPages): ?>

                <a

                    href="<?php echo fgEscape(
                        fgProductUrl(['page' => $page + 1])
                    ); ?>"

                    class="fg-pagination-arrow"

                    aria-label="Next page"

                >
                    ›
                </a>

            <?php else: ?>

                <span class="fg-pagination-arrow disabled">
                    ›
                </span>

            <?php endif; ?>

        </nav>

    <?php endif; ?>

</main>

<!-- ============================================= -->
<!-- JAVASCRIPT -->
<!-- ============================================= -->

<script>

document.addEventListener('DOMContentLoaded', function () {

    const form = document.getElementById('fgShopFilterForm');

    const sortSelect = document.getElementById('fgSort');

    const productGrid = document.getElementById('fgProductGrid');

    const viewButtons = document.querySelectorAll('[data-view-toggle]');

    // Automatically submit when sorting changes.

    if (sortSelect && form) {

        sortSelect.addEventListener('change', function () {

            form.requestSubmit();

        });

    }

    // Grid and list view switching.

    viewButtons.forEach(function (button) {

        button.addEventListener('click', function () {

            const selectedView = this.dataset.viewToggle;

            if (!productGrid) {
                return;
            }

            productGrid.dataset.view = selectedView;

            viewButtons.forEach(function (item) {

                const active = item === button;

                item.classList.toggle('active', active);

                item.setAttribute(
                    'aria-pressed',
                    active ? 'true' : 'false'
                );

            });

        });

    });

});

</script>

<?php

require_once __DIR__ . '/../includes/footer.php';

?>
