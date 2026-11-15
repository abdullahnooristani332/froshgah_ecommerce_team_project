
<?php

require_once __DIR__ . '/../includes/auth.php';

$pageTitle = "Contact Us - FroshGah";

$successMessage = $_SESSION['contact_success'] ?? '';
$errorMessage = $_SESSION['contact_error'] ?? '';

unset($_SESSION['contact_success'], $_SESSION['contact_error']);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

?>

<main class="store-page">

    <section class="contact-hero">
        <div class="page-container">
            <span class="eyebrow">WE'RE HERE TO HELP</span>
            <h1>Let's start a <span>conversation.</span></h1>
            <p>
                Have a question about an order or our products?
                Send us a message and we'll be glad to hear from you.
            </p>
        </div>
    </section>

    <section class="contact-main page-container">

        <div class="contact-information">

            <span class="eyebrow">GET IN TOUCH</span>
            <h2>How can we help?</h2>

            <p class="contact-intro">
                Whether you need help with an order, want to ask a question,
                or have feedback about FroshGah, you can contact us using
                the form on this page.
            </p>

            <div class="contact-info-card">
                <div class="contact-info-icon">✉</div>
                <div>
                    <h3>Email</h3>
                    <p>Use the contact form to send us your message.</p>
                </div>
            </div>

            <div class="contact-info-card">
                <div class="contact-info-icon">🛍️</div>
                <div>
                    <h3>Order Support</h3>
                    <p>Include your order number when asking about an existing order.</p>
                </div>
            </div>

            <div class="contact-info-card">
                <div class="contact-info-icon">⌂</div>
                <div>
                    <h3>FroshGah</h3>
                    <p>Online shopping platform for customers in Afghanistan.</p>
                </div>
            </div>

            <div class="contact-note">
                <strong>A quick tip</strong>
                <p>
                    For order-related questions, include the order number
                    in your message so we can identify the order more easily.
                </p>
            </div>

        </div>

        <div class="contact-form-card">

            <div class="contact-form-heading">
                <span class="eyebrow">SEND A MESSAGE</span>
                <h2>We'd love to hear from you.</h2>
                <p>Complete the fields below to send your message.</p>
            </div>

            <?php if ($successMessage): ?>
                <div class="page-alert page-alert-success">
                    <?php echo htmlspecialchars($successMessage); ?>
                </div>
            <?php endif; ?>

            <?php if ($errorMessage): ?>
                <div class="page-alert page-alert-error">
                    <?php echo htmlspecialchars($errorMessage); ?>
                </div>
            <?php endif; ?>

            <form
                action="/froshgah/actions/contact-message.php"
                method="POST"
                class="contact-form"
            >

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?php echo htmlspecialchars(generateCsrfToken()); ?>"
                >

                <!-- Honeypot field to catch simple automated spam. -->
                <div class="contact-honeypot" aria-hidden="true">
                    <label for="website">Leave this field empty</label>
                    <input
                        type="text"
                        id="website"
                        name="website"
                        tabindex="-1"
                        autocomplete="off"
                    >
                </div>

                <div class="contact-form-row">

                    <div class="form-field">
                        <label for="contact-name">Your name *</label>
                        <input
                            type="text"
                            id="contact-name"
                            name="name"
                            placeholder="Enter your full name"
                            maxlength="100"
                            autocomplete="name"
                            required
                        >
                    </div>

                    <div class="form-field">
                        <label for="contact-email">Email address *</label>
                        <input
                            type="email"
                            id="contact-email"
                            name="email"
                            placeholder="you@example.com"
                            maxlength="150"
                            autocomplete="email"
                            required
                        >
                    </div>

                </div>

                <div class="contact-form-row">

                    <div class="form-field">
                        <label for="contact-phone">Phone number</label>
                        <input
                            type="tel"
                            id="contact-phone"
                            name="phone"
                            placeholder="Your phone number"
                            maxlength="30"
                            autocomplete="tel"
                        >
                    </div>

                    <div class="form-field">
                        <label for="contact-subject">Subject *</label>
                        <input
                            type="text"
                            id="contact-subject"
                            name="subject"
                            placeholder="What is your message about?"
                            maxlength="150"
                            required
                        >
                    </div>

                </div>

                <div class="form-field">
                    <label for="contact-message">Your message *</label>
                    <textarea
                        id="contact-message"
                        name="message"
                        rows="6"
                        maxlength="5000"
                        placeholder="Write your message here..."
                        required
                    ></textarea>
                    <small>Please keep your message under 5,000 characters.</small>
                </div>

                <button type="submit" class="page-btn page-btn-primary contact-submit">
                    Send Message
                    <span aria-hidden="true">→</span>
                </button>

                <p class="contact-form-footnote">
                    Your message will be saved for the FroshGah team to review.
                </p>

            </form>

        </div>

    </section>

</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
