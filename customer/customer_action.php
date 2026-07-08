<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/init.php';

// Protect: customer must be logged in
if (!isset($_SESSION['customer_logged_in']) || $_SESSION['customer_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_to('shop.php');
}

if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
    flash('danger', 'Security token expired. Please try again.');
    redirect_to('shop.php');
}

$action     = $_POST['action'] ?? '';
$customerId = (int) $_SESSION['customer_id'];

// ═══════════════════════════════════════════════════════════════
// ACTION: Add product to cart
// ═══════════════════════════════════════════════════════════════
if ($action === 'cart_add') {
    $productId = (int) ($_POST['product_id'] ?? 0);
    $quantity  = max(1, (int) ($_POST['quantity'] ?? 1));

    if ($productId <= 0) {
        flash('danger', 'Invalid product.');
        redirect_to('shop.php');
    }

    // Fetch product & verify stock
    $stmt = $database->prepare("SELECT id, name, stock_quantity, status FROM products WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $productId]);
    $product = $stmt->fetch();

    if (!$product || $product['status'] !== 'Active' || (int) $product['stock_quantity'] <= 0) {
        flash('danger', 'Product is unavailable or out of stock.');
        redirect_to('shop.php');
    }

    // Check if already in cart
    $existingStmt = $database->prepare("SELECT id, quantity FROM cart_items WHERE customer_id = :cid AND product_id = :pid LIMIT 1");
    $existingStmt->execute(['cid' => $customerId, 'pid' => $productId]);
    $existing = $existingStmt->fetch();

    if ($existing) {
        $newQty = (int) $existing['quantity'] + $quantity;
        // Cap at stock
        $newQty = min($newQty, (int) $product['stock_quantity']);
        $upd = $database->prepare("UPDATE cart_items SET quantity = :qty WHERE id = :id");
        $upd->execute(['qty' => $newQty, 'id' => $existing['id']]);
        flash('success', e($product['name']) . ' quantity updated in your cart.');
    } else {
        // Cap at stock
        $quantity = min($quantity, (int) $product['stock_quantity']);
        $ins = $database->prepare("INSERT INTO cart_items (customer_id, product_id, quantity) VALUES (:cid, :pid, :qty)");
        $ins->execute(['cid' => $customerId, 'pid' => $productId, 'qty' => $quantity]);
        flash('success', e($product['name']) . ' added to your cart!');
    }

    redirect_to('shop.php');
}

// ═══════════════════════════════════════════════════════════════
// ACTION: Update cart item quantity
// ═══════════════════════════════════════════════════════════════
if ($action === 'cart_update') {
    $cartItemId = (int) ($_POST['cart_item_id'] ?? 0);
    $quantity   = max(1, (int) ($_POST['quantity'] ?? 1));

    if ($cartItemId <= 0) {
        flash('danger', 'Invalid cart item.');
        redirect_to('cart.php');
    }

    // Verify ownership + fetch stock
    $stmt = $database->prepare(
        "SELECT ci.id, p.stock_quantity, p.name 
         FROM cart_items ci 
         JOIN products p ON ci.product_id = p.id 
         WHERE ci.id = :id AND ci.customer_id = :cid LIMIT 1"
    );
    $stmt->execute(['id' => $cartItemId, 'cid' => $customerId]);
    $item = $stmt->fetch();

    if (!$item) {
        flash('danger', 'Cart item not found.');
        redirect_to('cart.php');
    }

    // Cap at available stock
    $quantity = min($quantity, (int) $item['stock_quantity']);
    $quantity = max(1, $quantity);

    $upd = $database->prepare("UPDATE cart_items SET quantity = :qty WHERE id = :id AND customer_id = :cid");
    $upd->execute(['qty' => $quantity, 'id' => $cartItemId, 'cid' => $customerId]);

    flash('success', 'Cart updated.');
    redirect_to('cart.php');
}

// ═══════════════════════════════════════════════════════════════
// ACTION: Remove cart item
// ═══════════════════════════════════════════════════════════════
if ($action === 'cart_remove') {
    $cartItemId = (int) ($_POST['cart_item_id'] ?? 0);

    if ($cartItemId > 0) {
        $del = $database->prepare("DELETE FROM cart_items WHERE id = :id AND customer_id = :cid");
        $del->execute(['id' => $cartItemId, 'cid' => $customerId]);
        flash('success', 'Item removed from cart.');
    }

    redirect_to('cart.php');
}

// ═══════════════════════════════════════════════════════════════
// ACTION: Place order
// ═══════════════════════════════════════════════════════════════
if ($action === 'place_order') {

    $shippingPhone   = trim($_POST['shipping_phone'] ?? '');
    $shippingAddress = trim($_POST['shipping_address'] ?? '');
    $paymentMethod   = trim($_POST['payment_method'] ?? 'Cash on Delivery');
    $idempotencyKey  = trim($_POST['order_token'] ?? '');

    // Validate allowed payment methods
    $allowedPayments = ['Cash on Delivery', 'Bank Transfer'];
    if (!in_array($paymentMethod, $allowedPayments, true)) {
        $paymentMethod = 'Cash on Delivery';
    }

    // Basic validation
    if ($shippingPhone === '' || $shippingAddress === '') {
        flash('danger', 'Please provide a valid shipping address and phone number.');
        redirect_to('checkout.php');
    }

    if (strlen($shippingPhone) < 7) {
        flash('danger', 'Phone number appears to be too short. Please check and try again.');
        redirect_to('checkout.php');
    }

    // Idempotency: prevent duplicate order on page refresh
    if ($idempotencyKey !== '') {
        $dupChk = $database->prepare("SELECT id FROM orders WHERE idempotency_key = :key LIMIT 1");
        $dupChk->execute(['key' => $idempotencyKey]);
        $existingOrder = $dupChk->fetch();
        if ($existingOrder) {
            // Order was already placed — redirect to success page
            $_SESSION['last_order_id'] = (int) $existingOrder['id'];
            unset($_SESSION['order_token']);
            redirect_to('order_success.php');
        }
    }

    try {
        // Fetch cart items with live stock & price
        $stmt = $database->prepare(
            "SELECT ci.id AS cart_item_id, ci.product_id, ci.quantity, 
                    p.name, p.price, p.stock_quantity
             FROM cart_items ci
             JOIN products p ON ci.product_id = p.id
             WHERE ci.customer_id = :cid"
        );
        $stmt->execute(['cid' => $customerId]);
        $cartItems = $stmt->fetchAll();

        if (empty($cartItems)) {
            flash('danger', 'Your cart is empty.');
            redirect_to('cart.php');
        }

        // Begin transaction
        $database->beginTransaction();

        // Validate stock levels inside transaction (row-level lock not needed for this scale)
        $subtotal = 0.0;
        foreach ($cartItems as $item) {
            if ((int) $item['quantity'] > (int) $item['stock_quantity']) {
                throw new \RuntimeException(
                    "Sorry, only {$item['stock_quantity']} unit(s) of \"{$item['name']}\" are in stock."
                );
            }
            $subtotal += (float) $item['price'] * (int) $item['quantity'];
        }

        $shippingCost = 15.00;
        $totalAmount  = $subtotal + $shippingCost;

        // Generate unique transaction ID  e.g.  ORD-A3F91B2C
        $txnId = 'ORD-' . strtoupper(substr(bin2hex(random_bytes(8)), 0, 8));

        $customerName = $_SESSION['customer_name'] ?? 'Customer';

        // 1. Insert order record (with ALL required columns)
        $orderStmt = $database->prepare(
            "INSERT INTO orders 
                (customer_id, customer_name, shipping_phone, shipping_address, payment_method,
                 txn_id, total_amount, order_status, shipping_status, idempotency_key, created_at)
             VALUES 
                (:customer_id, :customer_name, :phone, :address, :payment,
                 :txn_id, :total, 'Pending', 'Processing', :idem_key, NOW())"
        );
        $orderStmt->execute([
            'customer_id'   => $customerId,
            'customer_name' => $customerName,
            'phone'         => $shippingPhone,
            'address'       => $shippingAddress,
            'payment'       => $paymentMethod,
            'txn_id'        => $txnId,
            'total'         => $totalAmount,
            'idem_key'      => $idempotencyKey ?: null,
        ]);

        $orderId = (int) $database->lastInsertId();

        // 2. Insert order items + update stock
        $itemStmt = $database->prepare(
            "INSERT INTO order_items (order_id, product_id, product_name, quantity, unit_price)
             VALUES (:order_id, :product_id, :product_name, :quantity, :unit_price)"
        );
        $stockStmt = $database->prepare(
            "UPDATE products 
             SET stock_quantity = stock_quantity - :qty,
                 status = CASE WHEN (stock_quantity - :qty2) <= 0 THEN 'Out of Stock' ELSE status END
             WHERE id = :pid AND stock_quantity >= :qty3"
        );

        foreach ($cartItems as $item) {
            $itemStmt->execute([
                'order_id'     => $orderId,
                'product_id'   => $item['product_id'],
                'product_name' => $item['name'],
                'quantity'     => $item['quantity'],
                'unit_price'   => $item['price'],
            ]);

            $rowsUpdated = $stockStmt->execute([
                'qty'  => $item['quantity'],
                'qty2' => $item['quantity'],
                'pid'  => $item['product_id'],
                'qty3' => $item['quantity'],
            ]);

            // If 0 rows updated, stock was insufficient (race condition guard)
            if ($stockStmt->rowCount() === 0) {
                throw new \RuntimeException("Stock was sold out while placing your order for \"{$item['name']}\". Please update your cart.");
            }
        }

        // 3. Clear the customer's cart
        $database->prepare("DELETE FROM cart_items WHERE customer_id = :cid")->execute(['cid' => $customerId]);

        // 4. Commit everything
        $database->commit();

        // 5. Notifications — after commit so they are non-critical
        $notifMsg = "New order #{$txnId} placed by {$customerName} — Total: " . money($totalAmount) . " via {$paymentMethod}.";
        $extraData = [
            'order_id'       => $orderId,
            'txn_id'         => $txnId,
            'customer_name'  => $customerName,
            'total_amount'   => $totalAmount,
            'payment_method' => $paymentMethod,
            'order_date'     => date('Y-m-d H:i:s'),
        ];

        notify_admins($database, 'new_order', $notifMsg, $extraData);
        notify_customer($database, $customerId, 'order_placed',
            "Your order #{$txnId} has been placed successfully! Total: " . money($totalAmount) . ". We'll update you as it progresses.",
            $extraData
        );

        // 6. Store order ID for success page + clear idempotency token from session
        $_SESSION['last_order_id'] = $orderId;
        unset($_SESSION['order_token']);

        redirect_to('order_success.php');

    } catch (\Exception $e) {
        if ($database->inTransaction()) {
            $database->rollBack();
        }
        flash('danger', $e->getMessage());
        redirect_to('checkout.php');
    }
}

// Fallback: unknown action
redirect_to('shop.php');