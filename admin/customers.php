
<?php

require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . '/../config/database.php';

// Escape output safely.
function customerEscape($value)
{
    return htmlspecialchars(
        (string) ($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

$pageTitle = 'Customer Management - Froshgah';

// Search input.
$search = trim($_GET['search'] ?? '');

// Status message.
$message = $_SESSION['customer_message'] ?? '';
$error = $_SESSION['customer_error'] ?? '';

unset(
    $_SESSION['customer_message'],
    $_SESSION['customer_error']
);

// Build customer query.
$sql = "
    SELECT
        u.id,
        u.name,
        u.email,
        u.phone,
        u.status,
        u.created_at,
        COUNT(DISTINCT o.id) AS total_orders,
        COALESCE(SUM(
            CASE
                WHEN o.status <> 'cancelled'
                THEN o.total_amount
                ELSE 0
            END
        ), 0) AS total_spent
    FROM users u
    LEFT JOIN orders o ON o.user_id = u.id
    WHERE u.role = 'customer'
";

$params = [];

if ($search !== '') {
    $sql .= "
        AND (
            u.name LIKE :search_name
            OR u.email LIKE :search_email
            OR u.phone LIKE :search_phone
        )
    ";

    $searchValue = '%' . $search . '%';

    $params[':search_name'] = $searchValue;
    $params[':search_email'] = $searchValue;
    $params[':search_phone'] = $searchValue;
}

$sql .= "
    GROUP BY
        u.id,
        u.name,
        u.email,
        u.phone,
        u.status,
        u.created_at
    ORDER BY u.id DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$customers = $stmt->fetchAll();

// Customer counts.
$countStatement = $pdo->query("
    SELECT
        COUNT(*) AS total,
        SUM(status = 'active') AS active,
        SUM(status = 'inactive') AS inactive,
        SUM(status = 'blocked') AS blocked
    FROM users
    WHERE role = 'customer'
");

$counts = $countStatement->fetch();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

?>

<main class="container section admin-management-page">

    <div class="admin-page-heading">

        <div>
            <h1>Customer Management</h1>

            <p>
                View, search, and manage Froshgah customer accounts.
            </p>
        </div>

        <a href="/froshgah/admin/dashboard.php"
           class="admin-secondary-button">
            Back to Dashboard
        </a>

    </div>

    <?php if ($message): ?>

        <div class="alert alert-success">
            <?php echo customerEscape($message); ?>
        </div>

    <?php endif; ?>

    <?php if ($error): ?>

        <div class="alert alert-error">
            <?php echo customerEscape($error); ?>
        </div>

    <?php endif; ?>

    <!-- Customer statistics -->

    <div class="dashboard-stats">

        <div class="dashboard-stat-card">
            <h3>Total Customers</h3>
            <p>
                <?php echo (int) ($counts['total'] ?? 0); ?>
            </p>
        </div>

        <div class="dashboard-stat-card">
            <h3>Active Customers</h3>
            <p>
                <?php echo (int) ($counts['active'] ?? 0); ?>
            </p>
        </div>

        <div class="dashboard-stat-card">
            <h3>Inactive Customers</h3>
            <p>
                <?php echo (int) ($counts['inactive'] ?? 0); ?>
            </p>
        </div>

        <div class="dashboard-stat-card">
            <h3>Blocked Customers</h3>
            <p>
                <?php echo (int) ($counts['blocked'] ?? 0); ?>
            </p>
        </div>

    </div>

    <!-- Search customers -->

    <section class="admin-form-card">

        <h2>Search Customers</h2>

        <form method="GET"
              action="/froshgah/admin/customers.php"
              class="admin-search-form">

            <input
                type="search"
                name="search"
                placeholder="Search by name, email, or phone..."
                value="<?php echo customerEscape($search); ?>"
            >

            <button type="submit" class="admin-primary-button">
                Search
            </button>

            <a href="/froshgah/admin/customers.php"
               class="admin-secondary-button">
                Clear
            </a>

        </form>

    </section>

    <!-- Customers table -->

    <section class="admin-form-card">

        <h2>Customer Accounts</h2>

        <p>
            <?php echo count($customers); ?> customer(s) found.
        </p>

        <?php if (empty($customers)): ?>

            <p>No customers were found.</p>

        <?php else: ?>

            <div class="admin-table-wrapper">

                <table class="admin-table">

                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Customer</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Orders</th>
                            <th>Total Spent</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php foreach ($customers as $customer): ?>

                        <tr>

                            <td>
                                #<?php echo (int) $customer['id']; ?>
                            </td>

                            <td>
                                <?php echo customerEscape($customer['name']); ?>
                            </td>

                            <td>
                                <?php echo customerEscape($customer['email']); ?>
                            </td>

                            <td>
                                <?php
                                echo customerEscape(
                                    $customer['phone'] ?: 'Not provided'
                                );
                                ?>
                            </td>

                            <td>
                                <?php echo (int) $customer['total_orders']; ?>
                            </td>

                            <td>
                                $<?php
                                echo number_format(
                                    (float) $customer['total_spent'],
                                    2
                                );
                                ?>
                            </td>

                            <td>
                                <span class="customer-status status-<?php
                                    echo customerEscape($customer['status']);
                                ?>">
                                    <?php
                                    echo ucfirst(
                                        customerEscape($customer['status'])
                                    );
                                    ?>
                                </span>
                            </td>

                            <td>

                                <div class="admin-row-actions">

                                    <a
                                        class="admin-edit-link"
                                        href="/froshgah/admin/customer-details.php?id=<?php
                                            echo (int) $customer['id'];
                                        ?>"
                                    >
                                        View
                                    </a>

                                    <form
                                        method="POST"
                                        action="/froshgah/actions/update-customer-status.php"
                                        onsubmit="return confirm('Are you sure you want to change this customer account status?');"
                                    >

                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?php echo customerEscape(generateCsrfToken()); ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="customer_id"
                                            value="<?php echo (int) $customer['id']; ?>"
                                        >

                                        <select name="status" required>

                                            <option value="">
                                                Change Status
                                            </option>

                                            <option value="active">
                                                Active
                                            </option>

                                            <option value="inactive">
                                                Inactive
                                            </option>

                                            <option value="blocked">
                                                Blocked
                                            </option>

                                        </select>

                                        <button
                                            type="submit"
                                            class="admin-secondary-button"
                                        >
                                            Update
                                        </button>

                                    </form>

                                </div>

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
