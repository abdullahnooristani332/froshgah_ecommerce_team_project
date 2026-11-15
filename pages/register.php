
<?php

require_once __DIR__ . '/../includes/auth.php';

// Redirect logged-in users to the homepage.
if (isLoggedIn()) {
    header('Location: /froshgah/');
    exit;
}

$pageTitle = "Create Account - Froshgah";

$error = $_SESSION['register_error'] ?? '';
$oldName = $_SESSION['register_old_name'] ?? '';
$oldEmail = $_SESSION['register_old_email'] ?? '';
$oldPhone = $_SESSION['register_old_phone'] ?? '';

// Clear temporary messages and old values.
unset(
    $_SESSION['register_error'],
    $_SESSION['register_old_name'],
    $_SESSION['register_old_email'],
    $_SESSION['register_old_phone']
);

require_once __DIR__ . '/../includes/header.php';


?>

<link rel="stylesheet" href="/froshgah/assets/css/register.css">

<main class="frosh-register-page">

    <div class="frosh-register-card">

        <!-- LEFT IMAGE -->

        <div class="frosh-register-visual">

            <img
                src="/froshgah/assets/images/register-hero.png"
                alt="FroshGah - Shop More, Live Better"
                class="frosh-register-image"
            >

        </div>


        <!-- RIGHT REGISTRATION FORM -->

        <section class="frosh-register-content">

            <div class="frosh-register-inner">


                <!-- HEADING -->

                <div class="frosh-register-heading">

                    <h1>Create Account</h1>

                    <p>Join us today and start shopping!</p>

                </div>


                <!-- ERROR MESSAGE -->

                <?php if ($error): ?>

                    <div class="frosh-register-alert">

                        <?php echo htmlspecialchars($error); ?>

                    </div>

                <?php endif; ?>


                <!-- REGISTRATION FORM -->

                <form
                    action="/froshgah/actions/register-process.php"
                    method="POST"
                    class="frosh-register-form"
                >

                    <!-- CSRF TOKEN -->

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?php echo htmlspecialchars(generateCsrfToken()); ?>"
                    >


                    <!-- FULL NAME -->

                    <div class="frosh-register-field">

                        <label for="name" class="sr-only">
                            Full Name
                        </label>

                        <span class="frosh-register-icon">

                            <svg viewBox="0 0 24 24" fill="none">

                                <circle
                                    cx="12"
                                    cy="8"
                                    r="4"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                />

                                <path
                                    d="M4 21V19C4 16.8 5.8 15 8 15H16C18.2 15 20 16.8 20 19V21"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    stroke-linecap="round"
                                />

                            </svg>

                        </span>

                        <input
                            type="text"
                            id="name"
                            name="name"
                            placeholder="Full Name *"
                            value="<?php echo htmlspecialchars($oldName); ?>"
                            maxlength="150"
                            autocomplete="name"
                            required
                        >

                    </div>


                    <!-- EMAIL ADDRESS -->

                    <div class="frosh-register-field">

                        <label for="email" class="sr-only">
                            Email Address
                        </label>

                        <span class="frosh-register-icon">

                            <svg viewBox="0 0 24 24" fill="none">

                                <rect
                                    x="3"
                                    y="5"
                                    width="18"
                                    height="14"
                                    rx="2"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                />

                                <path
                                    d="M4 7L12 13L20 7"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />

                            </svg>

                        </span>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            placeholder="Email Address *"
                            value="<?php echo htmlspecialchars($oldEmail); ?>"
                            maxlength="150"
                            autocomplete="email"
                            required
                        >

                    </div>


                    <!-- PHONE NUMBER -->

                    <div class="frosh-register-field">

                        <label for="phone" class="sr-only">
                            Phone Number
                        </label>

                        <span class="frosh-register-icon">

                            <svg viewBox="0 0 24 24" fill="none">

                                <path
                                    d="M21 16.5V19C21 20.1 20.1 21 19 21C10.2 21 3 13.8 3 5C3 3.9 3.9 3 5 3H7.5C8.1 3 8.6 3.4 8.8 4L10 8C10.2 8.6 10 9.2 9.5 9.6L7.8 11C9 13.5 10.5 15 13 16.2L14.4 14.5C14.8 14 15.4 13.8 16 14L20 15.2C20.6 15.4 21 15.9 21 16.5Z"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />

                            </svg>

                        </span>

                        <input
                            type="tel"
                            id="phone"
                            name="phone"
                            placeholder="Phone Number"
                            value="<?php echo htmlspecialchars($oldPhone); ?>"
                            maxlength="30"
                            inputmode="numeric"
                            pattern="[0-9]{7,15}"
                            title="Enter 7 to 15 digits."
                            autocomplete="tel"
                        >

                    </div>


                    <!-- PASSWORD -->

                    <div class="frosh-register-field">

                        <label for="password" class="sr-only">
                            Password
                        </label>

                        <span class="frosh-register-icon">

                            <svg viewBox="0 0 24 24" fill="none">

                                <rect
                                    x="4"
                                    y="10"
                                    width="16"
                                    height="11"
                                    rx="2"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                />

                                <path
                                    d="M8 10V7C8 4.8 9.8 3 12 3C14.2 3 16 4.8 16 7V10"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                />

                                <circle
                                    cx="12"
                                    cy="15"
                                    r="1.5"
                                    fill="currentColor"
                                />

                            </svg>

                        </span>

                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Password *"
                            minlength="8"
                            maxlength="72"
                            autocomplete="new-password"
                            required
                        >

                    </div>


                    <!-- CONFIRM PASSWORD -->

                    <div class="frosh-register-field">

                        <label for="confirm_password" class="sr-only">
                            Confirm Password
                        </label>

                        <span class="frosh-register-icon">

                            <svg viewBox="0 0 24 24" fill="none">

                                <rect
                                    x="4"
                                    y="10"
                                    width="16"
                                    height="11"
                                    rx="2"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                />

                                <path
                                    d="M8 10V7C8 4.8 9.8 3 12 3C14.2 3 16 4.8 16 7V10"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                />

                                <circle
                                    cx="12"
                                    cy="15"
                                    r="1.5"
                                    fill="currentColor"
                                />

                            </svg>

                        </span>

                        <input
                            type="password"
                            id="confirm_password"
                            name="confirm_password"
                            placeholder="Confirm Password *"
                            minlength="8"
                            maxlength="72"
                            autocomplete="new-password"
                            required
                        >

                    </div>


                    <!-- CREATE ACCOUNT BUTTON -->

                    <button
                        type="submit"
                        class="frosh-register-button"
                    >

                        <svg viewBox="0 0 24 24" fill="none">

                            <circle
                                cx="10"
                                cy="7"
                                r="4"
                                stroke="currentColor"
                                stroke-width="1.8"
                            />

                            <path
                                d="M3 21V19C3 16.8 4.8 15 7 15H13"
                                stroke="currentColor"
                                stroke-width="1.8"
                                stroke-linecap="round"
                            />

                            <path
                                d="M19 14V22M15 18H23"
                                stroke="currentColor"
                                stroke-width="1.8"
                                stroke-linecap="round"
                            />

                        </svg>

                        <span>Create Account</span>

                    </button>

                </form>


                <!-- LOGIN LINK -->

                <p class="frosh-register-login">

                    Already have an account?

                    <a href="/froshgah/pages/login.php">
                        Login
                    </a>

                </p>


            </div>

        </section>

    </div>

</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
