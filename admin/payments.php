<?php

require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . '/../config/database.php';

$pageTitle = "Payment Management - Froshgah";

function paymentEscape($value)
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

// Read filters.
$statusFilter = $_GET['status'] ?? 'all';
$search = trim($_GET['search'] ?? '');

$allowedStatuses = [
    'all',
    'pending',
    'completed',
    'failed',
    'refunded'
];

if (!in_array($statusFilter, $allowedStatuses, true)) {
    $statusFilter = 'all';
}

// Pagination.
$perPage = 10;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

// Build conditions.
$where = [];
$params = [];

if ($statusFilter !== 'all') {
    $where[] = "p.status = :status";
    $params['status'] = $statusFilter;
}

if ($search !== '') {
    $where[] = "(
        o.order_number LIKE :search_order
        OR u.name LIKE :search_name
        OR u.email LIKE :search_email
        OR p.transaction_id LIKE :search_transaction
    )";

    $searchValue = '%' . $search . '%';

    $params['search_order'] = $searchValue;
    $params['search_name'] = $searchValue;
    $params['search_email'] = $searchValue;
    $params['search_transaction'] = $searchValue;
}

$whereSql = '';

if (!empty($where)) {
    $whereSql = ' WHERE ' . implode(' AND ', $where);
}

// Payment statistics.
$statsStatement = $pdo->query("
    SELECT
        COUNT(*) AS total_payments,
        COALESCE(SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END), 0) AS pending_payments,
        COALESCE(SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END), 0) AS completed_payments,
        COALESCE(SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END), 0) AS failed_payments,
        COALESCE(SUM(CASE WHEN status = 'refunded' THEN 1 ELSE 0 END), 0) AS refunded_payments,
        COALESCE(SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END), 0) AS collected_amount
    FROM payments
");

$stats = $statsStatement->fetch();

// Count filtered records.
$countSql = "
    SELECT COUNT(*)
    FROM payments p
    INNER JOIN orders o ON p.order_id = o.id
    INNER JOIN users u ON o.user_id = u.id
    $whereSql
";

$countStatement = $pdo->prepare($countSql);
$countStatement->execute($params);

$totalPayments = (int)$countStatement->fetchColumn();
$totalPages = max(1, (int)ceil($totalPayments / $perPage));

if ($page > $totalPages) {
    $page = $totalPages;
    $offset = ($page - 1) * $perPage;
}

// Fetch payment records.
$paymentsSql = "
    SELECT
        p.id,
        p.order_id,
        p.payment_method,
        p.transaction_id,
        p.amount,
        p.status,
        p.created_at,
        o.order_number,
        o.status AS order_status,
        u.name AS customer_name,
        u.email AS customer_email
    FROM payments p
    INNER JOIN orders o ON p.order_id = o.id
    INNER JOIN users u ON o.user_id = u.id
    $whereSql
    ORDER BY p.created_at DESC, p.id DESC
    LIMIT :limit OFFSET :offset
";

$paymentsStatement = $pdo->prepare($paymentsSql);

foreach ($params as $key => $value) {
    $paymentsStatement->bindValue(':' . $key, $value, PDO::PARAM_STR);
}

$paymentsStatement->bindValue(':limit', $perPage, PDO::PARAM_INT);
$paymentsStatement->bindValue(':offset', $offset, PDO::PARAM_INT);

$paymentsStatement->execute();

$payments = $paymentsStatement->fetchAll();

$csrfToken = generateCsrfToken();
$paymentMessage = $_SESSION['payment_message'] ?? null;
unset($_SESSION['payment_message']);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

?>

<main class="admin-payments-page">

    <div class="admin-payments-container">

        <div class="admin-payments-heading">
            <div>
                <h1>Payment Management</h1>
                <p>View and manage customer Cash on Delivery payments.</p>
            </div>

            <div class="admin-payments-method">
                Cash on Delivery
            </div>
        </div>
        <?php if ($paymentMessage): ?>

    <div class="payment-alert <?= paymentEscape($paymentMessage['type']) ?>">

        <?= paymentEscape($paymentMessage['text']) ?>

    </div>

<?php endif; ?>

        <!-- Payment statistics -->

        <section class="payment-statistics">

            <div class="payment-stat-card">
                <span>Total Payments</span>
                <strong>
                    <?= number_format((int)$stats['total_payments']) ?>
                </strong>
            </div>

            <div class="payment-stat-card pending">
                <span>Pending Payments</span>
                <strong>
                    <?= number_format((int)$stats['pending_payments']) ?>
                </strong>
            </div>

            <div class="payment-stat-card completed">
                <span>Completed Payments</span>
                <strong>
                    <?= number_format((int)$stats['completed_payments']) ?>
                </strong>
            </div>

            <div class="payment-stat-card failed">
                <span>Failed Payments</span>
                <strong>
                    <?= number_format((int)$stats['failed_payments']) ?>
                </strong>
            </div>

            <div class="payment-stat-card refunded">
                <span>Refunded Payments</span>
                <strong>
                    <?= number_format((int)$stats['refunded_payments']) ?>
                </strong>
            </div>

            <div class="payment-stat-card collected">
                <span>Cash Collected</span>
                <strong>
                    <?= number_format((float)$stats['collected_amount'], 2) ?>
                </strong>
            </div>

        </section>

        <!-- Search and filters -->

        <section class="payment-filters">

            <form method="GET" action="">

                <div class="payment-search-field">
                    <label for="payment-search">Search payments</label>

                    <input
                        type="text"
                        id="payment-search"
                        name="search"
                        placeholder="Order number, customer, email..."
                        value="<?= paymentEscape($search) ?>"
                    >
                </div>

                <div class="payment-filter-field">
                    <label for="payment-status">Payment status</label>

                    <select id="payment-status" name="status">

                        <option value="all"
                            <?= $statusFilter === 'all' ? 'selected' : '' ?>>
                            All Payments
                        </option>

                        <option value="pending"
                            <?= $statusFilter === 'pending' ? 'selected' : '' ?>>
                            Pending
                        </option>

                        <option value="completed"
                            <?= $statusFilter === 'completed' ? 'selected' : '' ?>>
                            Completed
                        </option>

                        <option value="failed"
                            <?= $statusFilter === 'failed' ? 'selected' : '' ?>>
                            Failed
                        </option>

                        <option value="refunded"
                            <?= $statusFilter === 'refunded' ? 'selected' : '' ?>>
                            Refunded
                        </option>

                    </select>
                </div>

                <button type="submit" class="payment-filter-button">
                    Apply Filters
                </button>

                <a href="/froshgah/admin/payments.php"
                   class="payment-reset-button">
                    Reset
                </a>

            </form>

        </section>

        <!-- Payments table -->

        <section class="payment-table-section">

            <div class="payment-table-heading">
                <h2>Payment Records</h2>

                <span>
                    <?= number_format($totalPayments) ?> records found
                </span>
            </div>

            <div class="payment-table-wrapper">

                <table class="payment-table">

                    <thead>
                        <tr>
                            <th>Payment ID</th>
                            <th>Order Number</th>
                            <th>Customer</th>
                            <th>Method</th>
                            <th>Amount</th>
                            <th>Payment Status</th>
                            <th>Order Status</th>
                            <th>Date</th>
                            <th>Update Status</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php if (empty($payments)): ?>

                            <tr>
                                <td colspan="9" class="payment-empty">
                                    No payment records were found.
                                </td>
                            </tr>

                        <?php else: ?>

                            <?php foreach ($payments as $payment): ?>

                                <tr>

                                    <td>
                                        #<?= (int)$payment['id'] ?>
                                    </td>

                                    <td>
                                        <strong>
                                            <?= paymentEscape($payment['order_number']) ?>
                                        </strong>
                                    </td>

                                    <td>
                                        <div class="payment-customer">
                                            <strong>
                                                <?= paymentEscape($payment['customer_name']) ?>
                                            </strong>

                                            <small>
                                                <?= paymentEscape($payment['customer_email']) ?>
                                            </small>
                                        </div>
                                    </td>

                                    <td>
                                        <?= paymentEscape(
                                            ucwords(str_replace('_', ' ', $payment['payment_method']))
                                        ) ?>
                                    </td>

                                    <td>
                                        <strong>
                                            <?= number_format((float)$payment['amount'], 2) ?>
                                        </strong>
                                    </td>

                                    <td>
                                        <span class="payment-status-badge <?= paymentEscape($payment['status']) ?>">
                                            <?= paymentEscape(ucfirst($payment['status'])) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?= paymentEscape(
                                            ucfirst(str_replace('_', ' ', $payment['order_status']))
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= paymentEscape(
                                            date('d M Y, h:i A', strtotime($payment['created_at']))
                                        ) ?>
                                    </td>

                                    <td>

                                        <form
                                            method="POST"
                                            action="/froshgah/actions/update-payment-status.php"
                                            class="payment-update-form"
                                        >

                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= paymentEscape($csrfToken) ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="payment_id"
                                                value="<?= (int)$payment['id'] ?>"
                                            >

                                            <select name="status" required>

                                                <option value="pending"
                                                    <?= $payment['status'] === 'pending' ? 'selected' : '' ?>>
                                                    Pending
                                                </option>

                                                <option value="completed"
                                                    <?= $payment['status'] === 'completed' ? 'selected' : '' ?>>
                                                    Completed
                                                </option>

                                                <option value="failed"
                                                    <?= $payment['status'] === 'failed' ? 'selected' : '' ?>>
                                                    Failed
                                                </option>

                                                <option value="refunded"
                                                    <?= $payment['status'] === 'refunded' ? 'selected' : '' ?>>
                                                    Refunded
                                                </option>

                                            </select>

                                            <button type="submit">
                                                Update
                                            </button>

                                        </form>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

            <!-- Pagination -->

            <?php if ($totalPages > 1): ?>

                <div class="payment-pagination">

                    <?php if ($page > 1): ?>

                        <a href="?<?= http_build_query([
                            'search' => $search,
                            'status' => $statusFilter,
                            'page' => $page - 1
                        ]) ?>">
                            Previous
                        </a>

                    <?php endif; ?>

                    <span>
                        Page <?= $page ?> of <?= $totalPages ?>
                    </span>

                    <?php if ($page < $totalPages): ?>

                        <a href="?<?= http_build_query([
                            'search' => $search,
                            'status' => $statusFilter,
                            'page' => $page + 1
                        ]) ?>">
                            Next
                        </a>

                    <?php endif; ?>

                </div>

            <?php endif; ?>

        </section>

        <div class="payment-management-note">
            <strong>Important:</strong>
            Mark a COD payment as Completed only after the cash has actually
            been received. Mark it as Refunded only after the money has been
            returned to the customer.
        </div>

    </div>

</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>