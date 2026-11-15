
<?php

/**
 * Create a notification for one user.
 */
function createNotification(
    PDO $pdo,
    int $userId,
    string $title,
    string $message,
    string $type = 'general',
    ?int $orderId = null
): bool {

    if (
        $userId < 1 ||
        trim($title) === '' ||
        trim($message) === ''
    ) {
        return false;
    }

    $statement = $pdo->prepare("
        INSERT INTO notifications (
            user_id,
            order_id,
            title,
            message,
            type,
            is_read
        )
        VALUES (?, ?, ?, ?, ?, 0)
    ");

    return $statement->execute([
        $userId,
        $orderId,
        trim($title),
        trim($message),
        $type
    ]);
}


/**
 * Notify every active administrator about a new order.
 *
 * This function is intended to run inside the
 * order-placement database transaction.
 */
function notifyAdminsAboutNewOrder(
    PDO $pdo,
    int $orderId,
    string $orderNumber,
    string $customerName,
    float $orderTotal
): int {

    $adminStatement = $pdo->prepare("
        SELECT id
        FROM users
        WHERE role = 'admin'
          AND status = 'active'
    ");

    $adminStatement->execute();

    $admins = $adminStatement->fetchAll(PDO::FETCH_COLUMN);

    $notificationTitle = 'New customer order received!';

    $notificationMessage =
        $customerName .
        ' has placed a new order. ' .
        'Order number: ' . $orderNumber . '. ' .
        'Order total: ' . number_format($orderTotal, 2) .
        '. Please review and process this order.';

    $insertStatement = $pdo->prepare("
        INSERT INTO notifications (
            user_id,
            order_id,
            title,
            message,
            type,
            is_read
        )
        VALUES (?, ?, ?, ?, 'new_order', 0)
    ");

    $createdCount = 0;

    foreach ($admins as $adminId) {

        $insertStatement->execute([
            (int) $adminId,
            $orderId,
            $notificationTitle,
            $notificationMessage
        ]);

        $createdCount++;
    }

    return $createdCount;
}
