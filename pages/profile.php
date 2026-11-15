
<?php

require_once __DIR__ . '/../includes/auth.php';
requireLogin();

require_once __DIR__ . '/../config/database.php';

$pageTitle = "My Profile - Froshgah";

$userId = (int) $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT id, name, email, phone, role, status, created_at
    FROM users
    WHERE id = :id
    LIMIT 1
");

$stmt->execute([':id' => $userId]);
$user = $stmt->fetch();

if (!$user) {
    http_response_code(404);
    exit('User account not found.');
}

$stmt = $pdo->prepare("
    SELECT
        COUNT(*) AS total_orders,
        COALESCE(SUM(
            CASE WHEN status = 'delivered'
            THEN total_amount ELSE 0 END
        ), 0) AS completed_spending
    FROM orders
    WHERE user_id = :user_id
");

$stmt->execute([':user_id' => $userId]);
$stats = $stmt->fetch();

$error = $_SESSION['profile_error'] ?? '';
$success = $_SESSION['profile_success'] ?? '';

unset($_SESSION['profile_error'], $_SESSION['profile_success']);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

?>

<main class="container section">

    <div class="profile-page">

        <div class="profile-heading">
            <h1>My Profile</h1>
            <p>Manage your personal information and account.</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <div class="profile-stats">

            <div class="profile-stat">
                <h3><?php echo (int) $stats['total_orders']; ?></h3>
                <p>Total Orders</p>
            </div>

            <div class="profile-stat">
                <h3>
                    <?php echo number_format((float) $stats['completed_spending'], 2); ?>
                </h3>
                <p>Delivered Order Value</p>
            </div>

        </div>

        <div class="profile-card">

            <h2>Personal Information</h2>

            <form action="/froshgah/actions/update-profile.php"
                  method="POST"
                  class="auth-form">

                <input type="hidden" name="csrf_token"
                       value="<?php echo htmlspecialchars(generateCsrfToken()); ?>">

                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text"
                           id="name"
                           name="name"
                           maxlength="150"
                           value="<?php echo htmlspecialchars($user['name']); ?>"
                           required>
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email"
                           id="email"
                           value="<?php echo htmlspecialchars($user['email']); ?>"
                           disabled>
                    <small>Email changes are not available in this step.</small>
                </div>

                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel"
                           id="phone"
                           name="phone"
                           maxlength="30"
                           value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                </div>

                <button type="submit" class="btn auth-btn">
                    Save Changes
                </button>

            </form>

        </div>

        <div class="profile-card">

            <h2>Change Password</h2>

            <form action="/froshgah/actions/change-password.php"
                  method="POST"
                  class="auth-form">

                <input type="hidden" name="csrf_token"
                       value="<?php echo htmlspecialchars(generateCsrfToken()); ?>">

                <div class="form-group">
                    <label for="current_password">Current Password</label>
                    <input type="password"
                           id="current_password"
                           name="current_password"
                           required>
                </div>

                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password"
                           id="new_password"
                           name="new_password"
                           minlength="8"
                           maxlength="72"
                           required>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password"
                           id="confirm_password"
                           name="confirm_password"
                           minlength="8"
                           maxlength="72"
                           required>
                </div>

                <button type="submit" class="btn auth-btn">
                    Change Password
                </button>

            </form>

        </div>

        <div class="profile-links">
            <a href="/froshgah/pages/my-orders.php">
                View My Orders
            </a>
        </div>

    </div>

</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
