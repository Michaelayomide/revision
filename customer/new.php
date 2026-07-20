if ($action === 'place_order') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        flash('danger', 'Security token expired. Please try again.');
        redirect_to('checkout.php');
    }

    $customerId = (int)$_SESSION['customer_id'];
    $shippingPhone = trim($_POST['shipping_phone'] ?? '');
    $shippingAddress = trim($_POST['shipping_address'] ?? '');
    $paymentMethod = trim($_POST['payment_method'] ?? 'Cash on Delivery');

    if ($shippingPhone === '' || $shippingAddress === '') {
        flash('danger', 'Please provide a valid shipping address and phone number.');
        redirect_to('checkout.php');
    }

    try {
        // Fetch cart items to calculate the real total and verify inventory quantities
        $stmt = $database->prepare(
            "SELECT ci.product_id, ci.quantity, p.price, p.stock_quantity, p.name 
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

        // Initialize ACID Safe Transaction Engine block
        $database->beginTransaction();

        $subtotal = 0.0;
        foreach ($cartItems as $item) {
            // Check stock values dynamically before mutating tables
            if ((int)$item['quantity'] > (int)$item['stock_quantity']) {
                throw new Exception("Insufficient stock for item: " . $item['name']);
            }
            $subtotal += (float)$item['price'] * (int)$item['quantity'];
        }
        
        $shippingCost = 15.00;
        $totalAmount = $subtotal + $shippingCost;

        // 1. Insert global Order payload metadata
        $orderStmt = $database->prepare(
            "INSERT INTO orders (customer_id, shipping_phone, shipping_address, payment_method, total_amount, status, created_at) 
             VALUES (:customer_id, :phone, :address, :payment, :total, 'Pending', NOW())"
        );
        $orderStmt->execute([
            'customer_id' => $customerId,
            'phone' => $shippingPhone,
            'address' => $shippingAddress,
            'payment' => $paymentMethod,
            'total' => $totalAmount
        ]);
        
        $orderId = (int)$database->lastInsertId();

        // 2. Map line items and deduct matching inventory pools
        $itemStmt = $database->prepare(
            "INSERT INTO order_items (order_id, product_id, quantity, price) 
             VALUES (:order_id, :product_id, :quantity, :price)"
        );
        $stockUpdateStmt = $database->prepare(
            "UPDATE products 
             SET stock_quantity = stock_quantity - :qty, 
                 status = CASE WHEN stock_quantity - :qty2 <= 0 THEN 'Out of Stock' ELSE status END
             WHERE id = :pid"
        );

        foreach ($cartItems as $item) {
            $itemStmt->execute([
                'order_id' => $orderId,
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'price' => $item['price']
            ]);

            $stockUpdateStmt->execute([
                'qty' => $item['quantity'],
                'qty2' => $item['quantity'],
                'pid' => $item['product_id']
            ]);
        }

        // 3. Clear user cart allocations upon success
        $clearCart = $database->prepare("DELETE FROM cart_items WHERE customer_id = :cid");
        $clearCart->execute(['cid' => $customerId]);

        // Everything succeeded; commit changes to disk
        $database->commit();

        // Pass the Order ID forward to the Success Page context
        $_SESSION['last_order_id'] = $orderId;
        redirect_to('order_success.php');

    } catch (Exception $e) {
        // Rollback on any failure to prevent data corruption
        if ($database->inTransaction()) {
            $database->rollBack();
        }
        flash('danger', 'Order execution error: ' . $e->getMessage());
        redirect_to('checkout.php');
    }
}