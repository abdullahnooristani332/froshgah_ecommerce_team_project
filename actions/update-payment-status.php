    <?php

require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . '/../config/database.php';

function redirectToPayments()
{
    header('Location: /froshgah/admin/payments.php');
    exit;
}

// Only POST requests are accepted.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}

// Verify CSRF token.
$csrfToken = $_POST['csrf_token'] ?? '';

if (!verifyCsrfToken($csrfToken)) {
    $_SESSION['payment_message'] = [
        'type' => 'error',
        'text' => 'Security verification failed. Please try again.'
    ];

    redirectToPayments();
}

// Validate payment ID.
$paymentId = filter_var(
    $_POST['payment_id'] ?? null,
    FILTER_VALIDATE_INT
);

if (!$paymentId || $paymentId < 1) {
    $_SESSION['payment_message'] = [
        'type' => 'error',
        'text' => 'Invalid payment record.'
    ];

    redirectToPayments();
}

// Validate requested status.
$newStatus = $_POST['status'] ?? '';

$allowedStatuses = [
    'pending',
    'completed',
    'failed',
    'refunded'
];

if (!in_array($newStatus, $allowedStatuses, true)) {
    $_SESSION['payment_message'] = [
        'type' => 'error',
        'text' => 'Invalid payment status.'
    ];

    redirectToPayments();
}

try {

    $pdo->beginTransaction();

    // Lock the payment row while updating it.
    $paymentStatement = $pdo->prepare("
        SELECT id, status
        FROM payments
        WHERE id = ?
        LIMIT 1
        FOR UPDATE
    ");

    $paymentStatement->execute([$paymentId]);

    $payment = $paymentStatement->fetch();

    if (!$payment) {
        $pdo->rollBack();

        $_SESSION['payment_message'] = [
            'type' => 'error',
            'text' => 'Payment record not found.'
        ];

        redirectToPayments();
    }

    // Avoid unnecessary database updates.
    if ($payment['status'] === $newStatus) {

        $pdo->commit();

        $_SESSION['payment_message'] = [
            'type' => 'success',
            'text' => 'The payment already has this status.'
        ];

        redirectToPayments();
    }

    // Update payment status.
    $updateStatement = $pdo->prepare("
        UPDATE payments
        SET status = ?
        WHERE id = ?
    ");

    $updateStatement->execute([
        $newStatus,
        $paymentId
    ]);

    $pdo->commit();

    $_SESSION['payment_message'] = [
        'type' => 'success',
        'text' => 'Payment status successfully updated to ' .
                  ucfirst($newStatus) . '.'
    ];

    redirectToPayments();

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log(
        'Payment status update error: ' . $e->getMessage()
    );

    $_SESSION['payment_message'] = [
        'type' => 'error',
        'text' => 'Unable to update the payment. Please try again.'
    ];

    redirectToPayments();
}