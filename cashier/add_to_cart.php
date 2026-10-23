<?php
session_start();
require_once '../includes/config.php';

header('Content-Type: application/json');

$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;

if ($product_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid product']);
    exit;
}

// Check if product exists and has stock
$stmt = $conn->prepare("SELECT id, name, quantity, is_active FROM products WHERE id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product || !$product['is_active']) {
    echo json_encode(['success' => false, 'error' => 'This product is no longer available']);
    exit;
}

if ($product['quantity'] <= 0) {
    echo json_encode(['success' => false, 'error' => 'Out of stock']);
    exit;
}

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Check if adding would exceed stock
$current_qty = isset($_SESSION['cart'][$product_id]) ? $_SESSION['cart'][$product_id] : 0;
if ($current_qty + 1 > $product['quantity']) {
    echo json_encode(['success' => false, 'error' => 'Not enough stock']);
    exit;
}

// Add to cart
if (isset($_SESSION['cart'][$product_id])) {
    $_SESSION['cart'][$product_id]++;
} else {
    $_SESSION['cart'][$product_id] = 1;
}

echo json_encode(['success' => true, 'message' => 'Item added']);