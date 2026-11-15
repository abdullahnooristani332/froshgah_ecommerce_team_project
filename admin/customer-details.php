
<?php

require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . '/../config/database.php';

function customerDetailsEscape($value)
{
    return htmlspecialchars(
        (string) ($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

// Validate customer ID.
$customerId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$customerId || $customerId < 1) {
    http_response_code(400);
    exit('Invalid customer ID.');
}

// Retrieve customer.
$customerStatement = $pdo->prepare("
    SELECT
        id,
        name,
        email,
        phone,
        role,
        status,
        created_at
    FROM users
    WHERE id = :id
      AND role = 'customer'
    LIMIT 1
");

$customerStatement->execute([
    ':id' => $customerId
]);

$customer = $customerStatement->fetch();

if (!$customer) {
    http_response_code(404);
    exit('Customer not found.');
}

// Retrieve customer's orders.
$orderStatement = $pdo->prepare("
    SELECT
        id,
        order_number,
        total_amount,
        status,
        created_at
    FROM orders
    WHERE user_id = :user_id
    ORDER BY id DESC
");

$orderStatement->execute([
    ':user_id' => $customerId
]);

$orders = $orderStatement->fetchAll();

// Calculate total spent excluding cancelled orders.
$totalStatement = $pdo->prepare("
    SELECT
        COUNT(*) AS total_orders,
        COALESCE(
            SUM(
                CASE
                    WHEN status <> 'cancelled'
                    THEN total_amount
                    ELSE 0
                END
            ),
            0
        ) AS total_spent
    FROM orders
    WHERE user_id = :user_id
");

$totalStatement->execute([
    ':user_id' => $customerId
]);

$summary = $totalStatement->fetch();

$pageTitle = 'Customer Details - Froshgah';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

?>

<main class="container section admin-management-page">

    <div class="admin-page-heading">

        <div>
            <h1>Customer Details</h1>
            <p>
                Customer account information and purchase history.
            </p>
        </div>

        <a href="/froshgah/admin/customers.php"
           class="admin-secondary-button">
            Back to Customers
        </a>

    </div>

    <section class="admin-form-card">

        <h2>Personal Information</h2>

        <div class="customer-details-grid">

            <div>
                <strong>Customer ID</strong>
                <p>#<?php echo (int) $customer['id']; ?></p>
            </div>

            <div>
                <strong>Full Name</strong>
                <p><?php echo customerDetailsEscape($customer['name']); ?></p>
            </div>

            <div>
                <strong>Email Address</strong>
                <p><?php echo customerDetailsEscape($customer['email']); ?></p>
            </div>

            <div>
                <strong>Phone Number</strong>
                <p>
                    <?php
                    echo customerDetailsEscape(
                        $customer['phone'] ?: 'Not provided'
                    );
                    ?>
                </p>
            </div>

            <div>
                <strong>Account Role</strong>
                <p><?php echo customerDetailsEscape($customer['role']); ?></p>
            </div>

            <div>
                <strong>Account Status</strong>
                <p><?php echo ucfirst(customerDetailsEscape($customer['status'])); ?></p>
            </div>

            <div>
                <strong>Registration Date</strong>
                <p><?php echo customerDetailsEscape($customer['created_at']); ?></p>
            </div>

        </div>

    </section>

    <section class="dashboard-stats">

        <div class="dashboard-stat-card">
            <h3>Total Orders</h3>
            <p><?php echo (int) $summary['total_orders']; ?></p>
        </div>

        <div class="dashboard-stat-card">
            <h3>Total Spent</h3>
            <p>
                $<?php
                echo number_format(
                    (float) $summary['total_spent'],
                    2
                );
                ?>
            </p>
        </div>

    </section>

    <section class="admin-form-card">

        <h2>Customer Order History</h2>

        <?php if (empty($orders)): ?>

            <p>This customer has not placed any orders yet.</p>

        <?php else: ?>

            <div class="admin-table-wrapper">

                <table class="admin-table">

                    <thead>
                        <tr>
                            <th>Order Number</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Order Date</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php foreach ($orders as $order): ?>

                        <tr>

                            <td>
                                <?php echo customerDetailsEscape($order['order_number']); ?>
                            </td>

                            <td>
                                $<?php
                                echo number_format(
                                    (float) $order['total_amount'],
                                    2
                                );
                                ?>
                            </td>

                            <td>
                                <?php echo ucfirst(customerDetailsEscape($order['status'])); ?>
                            </td>

                            <td>
                                <?php echo customerDetailsEscape($order['created_at']); ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php endif; ?>

    </section>

</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
