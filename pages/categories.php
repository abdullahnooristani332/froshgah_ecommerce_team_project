
<?php

require_once __DIR__ . '/../config/database.php';

$pageTitle = "Shop by Category - FroshGah";

$selectedCategory = null;
$selectedCategoryId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$categories = [];
$products = [];
$errorMessage = '';

try {
    // Get active categories and count their active products.
    $categoryQuery = "
        SELECT
            c.id,
            c.name,
            c.slug,
            c.description,
            c.image,
            COUNT(p.id) AS product_count
        FROM categories c
        LEFT JOIN products p
            ON p.category_id = c.id
            AND p.status = 'active'
        WHERE c.status = 'active'
        GROUP BY c.id, c.name, c.slug, c.description, c.image
        ORDER BY c.name ASC
    ";

    $categories = $pdo->query($categoryQuery)->fetchAll();

    // If a category was selected, retrieve its information.
    if ($selectedCategoryId) {
        $categoryStatement = $pdo->prepare("
            SELECT id, name, slug, description, image
            FROM categories
            WHERE id = ? AND status = 'active'
            LIMIT 1
        ");

        $categoryStatement->execute([$selectedCategoryId]);
        $selectedCategory = $categoryStatement->fetch();

        if ($selectedCategory) {
            // Get products belonging to the selected category.
            $productStatement = $pdo->prepare("
                SELECT
                    p.id,
                    p.name,
                    p.slug,
                    p.price,
                    p.stock,
                    pi.image_path
                FROM products p
                LEFT JOIN product_images pi
                    ON p.id = pi.product_id
                    AND pi.is_primary = 1
                WHERE p.category_id = ?
                    AND p.status = 'active'
                ORDER BY p.created_at DESC
                LIMIT 12
            ");

            $productStatement->execute([$selectedCategoryId]);
            $products = $productStatement->fetchAll();
        }
    }

} catch (PDOException $e) {
    error_log("Categories page error: " . $e->getMessage());
    $errorMessage = "We could not load categories right now. Please try again later.";
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

?>

<main class="store-page">

    <section class="category-hero">
        <div class="page-container category-hero-content">
            <div class="category-hero-text">
                <span class="eyebrow">DISCOVER FROSGAH</span>
                <h1>
                    Find exactly what
                    <span>you're looking for.</span>
                </h1>
                <p>
                    Explore our shopping categories and discover products
                    selected to make your everyday life better.
                </p>

                <a href="/froshgah/pages/products.php" class="page-btn page-btn-primary">
                    Explore All Products
                    <span aria-hidden="true">→</span>
                </a>
            </div>

            <div class="category-hero-visual">
                <div class="category-hero-circle"></div>
                <div class="category-hero-card">
                    <span class="hero-card-icon">🛍️</span>
                    <strong>One destination.</strong>
                    <span>Many possibilities.</span>
                </div>
                <div class="hero-floating-tag">Shop smarter</div>
            </div>
        </div>
    </section>

    <section class="category-section page-container">

        <div class="section-heading">
            <div>
                <span class="eyebrow">EXPLORE OUR STORE</span>
                <h2>Shop by category</h2>
                <p>Choose a category to discover its available products.</p>
            </div>

            <span class="category-total">
                <?php echo count($categories); ?>
                <?php echo count($categories) === 1 ? 'Category' : 'Categories'; ?>
            </span>
        </div>

        <?php if ($errorMessage): ?>
            <div class="page-alert page-alert-error">
                <?php echo htmlspecialchars($errorMessage); ?>
            </div>

        <?php elseif (empty($categories)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">🛍️</div>
                <h3>No categories available yet</h3>
                <p>Our categories will appear here when they are added to the store.</p>
            </div>

        <?php else: ?>

            <div class="category-grid">

                <?php foreach ($categories as $category): ?>

                    <?php
                    $categoryImage = !empty($category['image'])
                        ? '/froshgah/' . ltrim($category['image'], '/')
                        : '';

                    $isSelected = $selectedCategory &&
                        (int)$selectedCategory['id'] === (int)$category['id'];
                    ?>

                    <a
                        href="/froshgah/pages/categories.php?id=<?php echo (int)$category['id']; ?>#category-products"
                        class="category-card <?php echo $isSelected ? 'is-selected' : ''; ?>"
                    >

                        <div class="category-card-image">
                            <?php if ($categoryImage): ?>
                                <img
                                    src="<?php echo htmlspecialchars($categoryImage); ?>"
                                    alt="<?php echo htmlspecialchars($category['name']); ?>"
                                    loading="lazy"
                                    onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                >
                            <?php endif; ?>

                            <div
                                class="category-image-fallback"
                                <?php echo $categoryImage ? 'style="display:none"' : ''; ?>
                            >
                                <span>✦</span>
                            </div>

                            <span class="category-card-arrow">↗</span>
                        </div>

                        <div class="category-card-content">
                            <div>
                                <h3><?php echo htmlspecialchars($category['name']); ?></h3>

                                <p>
                                    <?php
                                    echo !empty($category['description'])
                                        ? htmlspecialchars($category['description'])
                                        : 'Explore products in this category.';
                                    ?>
                                </p>
                            </div>

                            <span class="category-product-count">
                                <?php echo (int)$category['product_count']; ?>
                                <?php echo (int)$category['product_count'] === 1 ? 'product' : 'products'; ?>
                            </span>
                        </div>

                    </a>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    </section>

    <?php if ($selectedCategory): ?>

        <section class="category-products-section" id="category-products">
            <div class="page-container">

                <div class="section-heading">
                    <div>
                        <span class="eyebrow">SELECTED CATEGORY</span>
                        <h2><?php echo htmlspecialchars($selectedCategory['name']); ?></h2>
                        <p>
                            <?php
                            echo !empty($selectedCategory['description'])
                                ? htmlspecialchars($selectedCategory['description'])
                                : 'Explore the available products in this category.';
                            ?>
                        </p>
                    </div>

                    <a href="/froshgah/pages/categories.php" class="page-btn page-btn-light">
                        View All Categories
                    </a>
                </div>

                <?php if (empty($products)): ?>

                    <div class="empty-state">
                        <div class="empty-state-icon">📦</div>
                        <h3>No products in this category yet</h3>
                        <p>Please explore another category or check back later.</p>
                        <a href="/froshgah/pages/products.php" class="page-btn page-btn-primary">
                            Browse All Products
                        </a>
                    </div>

                <?php else: ?>

                    <div class="category-product-grid">

                        <?php foreach ($products as $product): ?>

                            <?php
                            $productImage = !empty($product['image_path'])
                                ? '/froshgah/' . ltrim($product['image_path'], '/')
                                : '';
                            ?>

                            <article class="category-product-card">

                                <a
                                    href="/froshgah/pages/product-details.php?id=<?php echo (int)$product['id']; ?>"
                                    class="category-product-image"
                                >
                                    <?php if ($productImage): ?>
                                        <img
                                            src="<?php echo htmlspecialchars($productImage); ?>"
                                            alt="<?php echo htmlspecialchars($product['name']); ?>"
                                            loading="lazy"
                                        >
                                    <?php else: ?>
                                        <div class="product-image-placeholder">📦</div>
                                    <?php endif; ?>
                                </a>

                                <div class="category-product-info">
                                    <h3>
                                        <a href="/froshgah/pages/product-details.php?id=<?php echo (int)$product['id']; ?>">
                                            <?php echo htmlspecialchars($product['name']); ?>
                                        </a>
                                    </h3>

                                    <div class="category-product-bottom">
                                        <strong>
                                            ؋<?php echo number_format((float)$product['price'], 2); ?>
                                        </strong>

                                        <a
                                            href="/froshgah/pages/product-details.php?id=<?php echo (int)$product['id']; ?>"
                                            class="product-view-link"
                                        >
                                            View Product →
                                        </a>
                                    </div>
                                </div>

                            </article>

                        <?php endforeach; ?>

                    </div>

                    <div class="category-view-all">
                        <a href="/froshgah/pages/products.php" class="page-btn page-btn-primary">
                            Browse All Products
                        </a>
                    </div>

                <?php endif; ?>

            </div>
        </section>

    <?php elseif (!empty($categories)): ?>

        <section class="category-discovery-strip">
            <div class="page-container category-discovery-content">
                <div>
                    <span class="eyebrow">MORE TO DISCOVER</span>
                    <h2>Your next great find is waiting.</h2>
                    <p>Explore the complete FroshGah product collection.</p>
                </div>

                <a href="/froshgah/pages/products.php" class="page-btn page-btn-primary">
                    Shop All Products →
                </a>
            </div>
        </section>

    <?php endif; ?>

</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
