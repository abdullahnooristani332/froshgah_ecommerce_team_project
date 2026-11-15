
<?php

require_once __DIR__ . '/../includes/auth.php';

// Redirect logged-in users to homepage.
if (isLoggedIn()) {
    header('Location: /froshgah/');
    exit;
}

$pageTitle = "Login - Froshgah";

$error = $_SESSION['login_error'] ?? '';
$success = $_SESSION['success_message'] ?? '';

unset(
    $_SESSION['login_error'],
    $_SESSION['success_message']
);

require_once __DIR__ . '/../includes/header.php';

?>

<main class="frosh-login-page">

    <div class="frosh-login-card">

        <!-- LEFT SIDE: ILLUSTRATION -->
        <section class="frosh-login-visual">

            <img
                src="/froshgah/assets/images/login-illustration.png"
                alt="Froshgah online shopping illustration"
                class="frosh-login-image"
            >

        </section>


        <!-- RIGHT SIDE: LOGIN FORM -->
        <section class="frosh-login-content">

            <div class="frosh-login-inner">

                <div class="frosh-login-heading">

                    <h1>Welcome Back!</h1>

                    <p>
                        Sign in to your FroshGah account
                    </p>

                </div>


                <?php if ($error): ?>

                    <div class="frosh-login-alert frosh-login-error">
                        <?php echo htmlspecialchars($error); ?>
                    </div>

                <?php endif; ?>


                <?php if ($success): ?>

                    <div class="frosh-login-alert frosh-login-success">
                        <?php echo htmlspecialchars($success); ?>
                    </div>

                <?php endif; ?>


                <form
                    action="/froshgah/actions/login-process.php"
                    method="POST"
                    class="frosh-login-form"
                >

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?php echo htmlspecialchars(generateCsrfToken()); ?>"
                    >


                    <!-- EMAIL -->
                    <div class="frosh-login-field">

                        <label for="email">Email Address</label>

                        <div class="frosh-login-input-wrapper">

                            <span class="frosh-login-icon">
                                <svg viewBox="0 0 24 24" fill="none">
                                    <rect x="3" y="5" width="18" height="14" rx="2"
                                        stroke="currentColor" stroke-width="2"/>
                                    <path d="M3 7L12 13L21 7"
                                        stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round"/>
                                </svg>
                            </span>

                            <input
                                type="email"
                                id="email"
                                name="email"
                                placeholder="Email Address"
                                maxlength="150"
                                autocomplete="email"
                                required
                            >

                        </div>

                    </div>


                    <!-- PASSWORD -->
                    <div class="frosh-login-field">

                        <label for="password">Password</label>

                        <div class="frosh-login-input-wrapper">

                            <span class="frosh-login-icon">
                                <svg viewBox="0 0 24 24" fill="none">
                                    <rect x="4" y="10" width="16" height="11"
                                        rx="2" stroke="currentColor"
                                        stroke-width="2"/>
                                    <path d="M8 10V7a4 4 0 0 1 8 0v3"
                                        stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round"/>
                                    <path d="M12 14v3"
                                        stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round"/>
                                </svg>
                            </span>

                            <input
                                type="password"
                                id="password"
                                name="password"
                                placeholder="Password"
                                autocomplete="current-password"
                                required
                            >

                            <button
                                type="button"
                                class="frosh-password-toggle"
                                id="togglePassword"
                                aria-label="Show password"
                            >
                                <svg viewBox="0 0 24 24" fill="none">
                                    <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"
                                        stroke="currentColor"
                                        stroke-width="2"/>
                                    <circle cx="12" cy="12" r="3"
                                        stroke="currentColor"
                                        stroke-width="2"/>
                                </svg>
                            </button>

                        </div>

                    </div>


                    <!-- REMEMBER ME -->
                    <div class="frosh-login-options">

                        <label class="frosh-remember-label">

                            <input
                                type="checkbox"
                                name="remember_me"
                                value="1"
                            >

                            <span>Remember Me</span>

                        </label>

                        <a
                            href="#"
                            class="frosh-forgot-link"
                            onclick="return false;"
                        >
                            Forgot Password?
                        </a>

                    </div>


                    <!-- LOGIN BUTTON -->
                    <button
                        type="submit"
                        class="frosh-login-button"
                    >

                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M10 17l5-5-5-5"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"/>
                            <path d="M15 12H3"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"/>
                            <path d="M12 3h6a3 3 0 0 1 3 3v12a3 3 0 0 1-3 3h-6"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"/>
                        </svg>

                        <span>Login</span>

                    </button>

                </form>


                <!-- REGISTER LINK -->
                <p class="frosh-register-text">

                    Don't have an account?

                    <a href="/froshgah/pages/register.php">
                        Register Now
                    </a>

                </p>

            </div>

        </section>

    </div>

</main>


<script>
document.addEventListener('DOMContentLoaded', function () {

    const passwordInput = document.getElementById('password');
    const toggleButton = document.getElementById('togglePassword');

    if (passwordInput && toggleButton) {

        toggleButton.addEventListener('click', function () {

            const isPassword = passwordInput.type === 'password';

            passwordInput.type = isPassword ? 'text' : 'password';

            toggleButton.setAttribute(
                'aria-label',
                isPassword ? 'Hide password' : 'Show password'
            );

        });

    }

});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
