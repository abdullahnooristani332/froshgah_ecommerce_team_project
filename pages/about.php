
<?php

$pageTitle = "About Us - FroshGah";

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

?>

<main class="store-page">

    <section class="about-hero">
        <div class="page-container about-hero-content">

            <div class="about-hero-text">
                <span class="eyebrow">GET TO KNOW US</span>

                <h1>
                    Shopping made
                    <span>simpler and better.</span>
                </h1>

                <p>
                    Welcome to FroshGah, an online shopping destination
                    designed to make discovering and ordering products
                    a more convenient experience for customers in Afghanistan.
                </p>

                <a href="/froshgah/pages/products.php" class="page-btn page-btn-primary">
                    Start Shopping →
                </a>
            </div>

            <div class="about-hero-art">
                <div class="about-art-circle"></div>

                <div class="about-art-card about-art-card-main">
                    <span class="about-art-icon">🛍️</span>
                    <strong>Welcome to FroshGah</strong>
                    <small>Your shopping destination</small>
                </div>

                <div class="about-art-card about-art-card-small">
                    <span>✓</span>
                    <strong>Simple shopping</strong>
                </div>
            </div>

        </div>
    </section>

    <section class="about-story page-container">

        <div class="about-story-label">
            <span class="eyebrow">OUR STORY</span>
            <h2>A better way to discover products.</h2>
        </div>

        <div class="about-story-content">
            <p>
                FroshGah is an online shopping platform created to bring
                product discovery, ordering, and order management together
                in one convenient place.
            </p>

            <p>
                We believe that shopping online should feel straightforward.
                Customers should be able to explore products, learn about
                what they are buying, add items to their cart, and place
                orders without unnecessary complications.
            </p>

            <p>
                Our platform is designed around a simple idea: make the
                shopping journey easier to understand and easier to use.
                From browsing the catalog to receiving order updates,
                FroshGah aims to provide a clear and organized experience.
            </p>
        </div>

    </section>

    <section class="about-values-section">
        <div class="page-container">

            <div class="section-heading section-heading-centered">
                <div>
                    <span class="eyebrow">WHAT MATTERS TO US</span>
                    <h2>Built around a better experience.</h2>
                    <p>Our platform focuses on practical values that matter throughout the shopping journey.</p>
                </div>
            </div>

            <div class="about-values-grid">

                <article class="about-value-card">
                    <div class="about-value-icon">✦</div>
                    <h3>Convenience</h3>
                    <p>
                        Explore products and place orders through one
                        organized online shopping experience.
                    </p>
                </article>

                <article class="about-value-card">
                    <div class="about-value-icon">♡</div>
                    <h3>Customer Focus</h3>
                    <p>
                        We aim to make product discovery, account access,
                        and order management easy to understand.
                    </p>
                </article>

                <article class="about-value-card">
                    <div class="about-value-icon">✓</div>
                    <h3>Clarity</h3>
                    <p>
                        Clear product information and order status updates
                        help customers follow their purchases.
                    </p>
                </article>

                <article class="about-value-card">
                    <div class="about-value-icon">⌂</div>
                    <h3>Local Identity</h3>
                    <p>
                        FroshGah is designed with Afghan shoppers and
                        their online shopping experience in mind.
                    </p>
                </article>

            </div>

        </div>
    </section>

    <section class="about-how-section page-container">

        <div class="section-heading">
            <div>
                <span class="eyebrow">HOW IT WORKS</span>
                <h2>Shopping in three simple steps.</h2>
                <p>A straightforward journey from discovery to order.</p>
            </div>
        </div>

        <div class="about-steps">

            <div class="about-step">
                <span class="about-step-number">01</span>
                <div>
                    <h3>Discover</h3>
                    <p>Browse categories and explore the available products.</p>
                </div>
            </div>

            <div class="about-step">
                <span class="about-step-number">02</span>
                <div>
                    <h3>Choose</h3>
                    <p>Add the products you want to your shopping cart.</p>
                </div>
            </div>

            <div class="about-step">
                <span class="about-step-number">03</span>
                <div>
                    <h3>Order</h3>
                    <p>Complete checkout and follow your order status.</p>
                </div>
            </div>

        </div>

    </section>

    <section class="about-cta">
        <div class="page-container about-cta-content">
            <div>
                <span class="eyebrow">YOUR NEXT SHOPPING EXPERIENCE</span>
                <h2>Ready to explore FroshGah?</h2>
                <p>Discover the products available in our online store.</p>
            </div>

            <a href="/froshgah/pages/products.php" class="page-btn page-btn-white">
                Explore Products →
            </a>
        </div>
    </section>

</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
