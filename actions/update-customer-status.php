
<?php

require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . '/../config/database.php';

// Only accept POST requests.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /froshgah/admin/customers.php');
    exit;
}

// Verify CSRF token.
if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    exit('Invalid form submission. Please refresh and try again.');
}

// Validate customer ID.
$customerId = filter_var(
    $_POST['customer_id'] ?? null,
    FILTER_VALIDATE_INT
);

// Allowed customer statuses.
$allowedStatuses = [
    'active',
    'inactive',
    'blocked'
];

$newStatus = $_POST['status'] ?? '';

if (!$customerId || $customerId < 1) {
    $_SESSION['customer_error'] = 'Invalid customer account.';
    header('Location: /froshgah/admin/customers.php');
    exit;
}

if (!in_array($newStatus, $allowedStatuses, true)) {
    $_SESSION['customer_error'] = 'Invalid account status.';
    header('Location: /froshgah/admin/customers.php');
    exit;
}

try {

    // Only update customer accounts.
    // Administrator accounts cannot be modified here.
    $statement = $pdo->prepare("
        UPDATE users
        SET status = :status
        WHERE id = :id
          AND role = 'customer'
    ");

    $statement->execute([
        ':status' => $newStatus,
        ':id' => $customerId
    ]);

    if ($statement->rowCount() === 0) {

        // Check whether the customer exists and already has this status.
        $check = $pdo->prepare("
            SELECT id
            FROM users
            WHERE id = :id
              AND role = 'customer'
              AND status = :status
        ");

        $check->execute([
            ':id' => $customerId,
            ':status' => $newStatus
        ]);

        if (!$check->fetch()) {
            $_SESSION['customer_error'] =
                'Customer account was not found.';
        } else {
            $_SESSION['customer_message'] =
                'The customer already has this status.';
        }

    } else {

        $_SESSION['customer_message'] =
            'Customer account status updated successfully.';

    }

} catch (PDOException $e) {

    error_log(
        'Froshgah customer status error: ' . $e->getMessage()
    );

    $_SESSION['customer_error'] =
        'Could not update the customer account. Please try again.';

}

header('Location: /froshgah/admin/customers.php');
exit;
