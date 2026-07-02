<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/init.php';

// Customer must be logged in to perform any cart/order action
if (!isset($_SESSION['customer_logged_in']) || $_SESSION['customer_logged_in'] !== true) {
    http_response_code(403);
    header('Location: ../customer/login.php');
    exit();
}

$customerId = (int) ($_SESSION['customer_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../customer/index.php');
    exit();
}

if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
    flash('danger', 'Security token expired. Please try again.');
    header('Location: ../customer/cart.php');
    exit();
}

$action = $_POST['action'] ?? '';

try {
    // ─── Cart: Add item ───────────────────────────────────────────────────────
    if ($action === 'cart_add') {
        $productId = (int) ($_POST['product_id'] ?? 0);
        $quantity  = max(1, (int) ($_POST['quantity'] ?? 1));

        if ($productId <= 0) {
            flash('danger', 'Invalid product selected.');
            header('Location: ../customer/shop.php');
            exit();
        }

        // Verify product exists and has stock
        $stmt = $database->prepare(
            "SELECT id, stock_quantity FROM products WHERE id = :id AND status = 'Active' LIMIT 1"
        );
        $stmt->execute(['id' => $productId]);
        $product = $stmt->fetch();

        if (!$product) {
            flash('danger', 'Product not available.');
            header('Location: ../customer/shop.php');
            exit();
        }

        if ((int) $product['stock_quantity'] < $quantity) {
            flash('danger', 'Insufficient stock.');
            header('Location: ../customer/shop.php');
            exit();
        }

        // Upsert cart item
        $stmt = $database->prepare(
            "INSERT INTO cart_items (customer_id, product_id, quantity)
             VALUES (:cid, :pid, :qty)
             ON DUPLICATE KEY UPDATE quantity = quantity + :qty2"
        );
        $stmt->execute([
            'cid'  => $customerId,
            'pid'  => $productId,
            'qty'  => $quantity,
            'qty2' => $quantity,
        ]);

        flash('success', 'Item added to cart.');
        header('Location: ../customer/cart.php');
        exit();
    }

    // ─── Cart: Update quantity ────────────────────────────────────────────────
    if ($action === 'cart_update') {
        $cartItemId = (int) ($_POST['cart_item_id'] ?? 0);
        $quantity   = max(1, (int) ($_POST['quantity'] ?? 1));

        $stmt = $database->prepare(
            "UPDATE cart_items SET quantity = :qty
             WHERE id = :id AND customer_id = :cid"
        );
        $stmt->execute(['qty' => $quantity, 'id' => $cartItemId, 'cid' => $customerId]);

        flash('success', 'Cart updated.');
        header('Location: ../customer/cart.php');
        exit();
    }

    // ─── Cart: Remove item ────────────────────────────────────────────────────
    if ($action === 'cart_remove') {
        $cartItemId = (int) ($_POST['cart_item_id'] ?? 0);

        $stmt = $database->prepare(
            "DELETE FROM cart_items WHERE id = :id AND customer_id = :cid"
        );
        $stmt->execute(['id' => $cartItemId, 'cid' => $customerId]);

        flash('success', 'Item removed from cart.');
        header('Location: ../customer/cart.php');
        exit();
    }

    // ─── Place order ──────────────────────────────────────────────────────────
    if ($action === 'place_order') {
        // Load cart items
        $stmt = $database->prepare(
            "SELECT ci.id AS cart_id, ci.product_id, ci.quantity,
                    p.name, p.price, p.stock_quantity
             FROM cart_items ci
             JOIN products p ON p.id = ci.product_id
             WHERE ci.customer_id = :cid
               AND p.status = 'Active'
               AND p.stock_quantity >= ci.quantity"
        );
        $stmt->execute(['cid' => $customerId]);
        $cartItems = $stmt->fetchAll();

        if (empty($cartItems)) {
            flash('danger', 'Your cart is empty or items are out of stock.');
            header('Location: ../customer/cart.php');
            exit();
        }

        $database->beginTransaction();

        $totalAmount = 0.0;
        foreach ($cartItems as $item) {
            $totalAmount += (float) $item['price'] * (int) $item['quantity'];
        }

        // Generate unique transaction ID
        $txnId = 'ORD-' . date('YmdHis') . '-' . random_int(100, 999);

        // Create the order
        $orderStmt = $database->prepare(
            "INSERT INTO orders (customer_id, txn_id, customer_name, total_amount, order_status, shipping_status)
             VALUES (:cid, :txn_id, :name, :total, 'Pending', 'Processing')"
        );
        $customerName = $_SESSION['customer_name'] ?? 'Customer';
        $orderStmt->execute([
            'cid'    => $customerId,
            'txn_id' => $txnId,
            'name'   => $customerName,
            'total'  => $totalAmount,
        ]);
        $orderId = (int) $database->lastInsertId();

        // Insert order items and decrement stock
        $itemStmt  = $database->prepare(
            "INSERT INTO order_items (order_id, product_id, product_name, quantity, unit_price)
             VALUES (:oid, :pid, :pname, :qty, :price)"
        );
        $stockStmt = $database->prepare(
            "UPDATE products SET stock_quantity = stock_quantity - :qty,
             status = IF(stock_quantity - :qty2 <= 0, 'Out of Stock', status)
             WHERE id = :pid"
        );

        foreach ($cartItems as $item) {
            $itemStmt->execute([
                'oid'   => $orderId,
                'pid'   => $item['product_id'],
                'pname' => $item['name'],
                'qty'   => $item['quantity'],
                'price' => $item['price'],
            ]);
            $stockStmt->execute([
                'qty'  => $item['quantity'],
                'qty2' => $item['quantity'],
                'pid'  => $item['product_id'],
            ]);
        }

        // Clear the cart
        $database->prepare("DELETE FROM cart_items WHERE customer_id = :cid")
            ->execute(['cid' => $customerId]);

        $database->commit();

        // Notify customer: Order Received
        notify_customer(
            $database,
            $customerId,
            'order_received',
            "Your order {$txnId} has been received and is awaiting confirmation.",
            ['order_id' => $orderId, 'txn_id' => $txnId]
        );

        // Notify all admins: New Order
        notify_admins(
            $database,
            'new_order',
            "New order {$txnId} placed by {$customerName} — " . money($totalAmount),
            ['order_id' => $orderId, 'txn_id' => $txnId]
        );

        flash('success', "Order placed successfully! Your order ID is {$txnId}.");
        header('Location: ../customer/orders.php');
        exit();
    }

    // ─── Mark notification as read ────────────────────────────────────────────
    if ($action === 'mark_notification_read') {
        $notifId = (int) ($_POST['notification_id'] ?? 0);

        $stmt = $database->prepare(
            "UPDATE notifications SET is_read = 1
             WHERE id = :id AND recipient_type = 'customer' AND recipient_id = :cid"
        );
        $stmt->execute(['id' => $notifId, 'cid' => $customerId]);

        header('Location: ../customer/notifications.php');
        exit();
    }

    // ─── Mark all notifications as read ──────────────────────────────────────
    if ($action === 'mark_all_read') {
        $stmt = $database->prepare(
            "UPDATE notifications SET is_read = 1
             WHERE recipient_type = 'customer' AND recipient_id = :cid"
        );
        $stmt->execute(['cid' => $customerId]);

        flash('success', 'All notifications marked as read.');
        header('Location: ../customer/notifications.php');
        exit();
    }

} catch (PDOException $e) {
    if (isset($database) && $database->inTransaction()) {
        $database->rollBack();
    }
    flash('danger', 'An error occurred. Please try again.');
    header('Location: ../customer/cart.php');
    exit();
}

// Fallback
header('Location: ../customer/index.php');
exit();
