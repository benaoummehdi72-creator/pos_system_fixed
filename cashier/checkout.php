<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/helpers.php';

header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in output
ini_set('log_errors', 1);

try {
    // Check authorization
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'cashier') {
        throw new Exception('Not authorized');
    }
    
    // Check cart
    if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
        throw new Exception('Cart is empty');
    }
    
    if (!isset($_SESSION['session_id'])) {
        throw new Exception('No active session');
    }
    
    $cart = $_SESSION['cart'];
    $cashier_id = $_SESSION['user_id'];
    $session_id = $_SESSION['session_id'];

    // The account must still be active and the shift still open
    if (!user_is_active($conn, $cashier_id)) {
        throw new Exception('Your account has been deactivated');
    }
    if (!session_is_open($conn, $session_id)) {
        throw new Exception('Your shift has ended. Please log out and log in again.');
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    $total_amount = 0;
    $cart_items = [];
    
    // Validate all items
    foreach ($cart as $product_id => $quantity) {
        $product_id = (int)$product_id;
        $quantity = (int)$quantity;
        
        $stmt = $conn->prepare("SELECT id, name, price, quantity, is_active FROM products WHERE id = ? FOR UPDATE");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $product = $stmt->get_result()->fetch_assoc();
        
        if (!$product) {
            throw new Exception("Product ID $product_id not found");
        }
        
        if (!$product['is_active']) {
            throw new Exception("{$product['name']} is no longer available");
        }

        if ($product['quantity'] < $quantity) {
            throw new Exception("Insufficient stock for {$product['name']} (have: {$product['quantity']}, need: $quantity)");
        }
        
        $subtotal = $product['price'] * $quantity;
        $total_amount += $subtotal;
        
        $cart_items[] = [
            'product_id' => $product_id,
            'quantity' => $quantity,
            'price' => $product['price']
        ];
    }
    
    // Create sale
    $stmt = $conn->prepare("INSERT INTO sales (cashier_id, session_id, sale_date, total_amount) VALUES (?, ?, NOW(), ?)");
    $stmt->bind_param("iid", $cashier_id, $session_id, $total_amount);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to create sale: " . $stmt->error);
    }
    
    $sale_id = $conn->insert_id;
    
    // Insert sale items and update stock
    foreach ($cart_items as $item) {
        // Insert sale item
        $stmt = $conn->prepare("INSERT INTO sale_items (sale_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiid", $sale_id, $item['product_id'], $item['quantity'], $item['price']);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to insert sale item: " . $stmt->error);
        }
        
        // Update stock
        $stmt = $conn->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ?");
        $stmt->bind_param("ii", $item['quantity'], $item['product_id']);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to update stock: " . $stmt->error);
        }
    }
    
    // Commit transaction
    $conn->commit();
    
    // Clear cart
    $_SESSION['cart'] = [];
    
    // Success!
    echo json_encode([
        'success' => true,
        'total' => number_format($total_amount, 2, '.', ''),
        'sale_id' => $sale_id
    ]);
    
} catch (Throwable $e) {
    // Undo any half-finished sale (harmless if no transaction is open)
    if (isset($conn)) {
        try {
            $conn->rollback();
        } catch (Throwable $ignored) {
            // connection already gone: nothing to roll back
        }
    }
    
    // Log the error
    error_log("Checkout error: " . $e->getMessage());
    
    // Return error
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}