<?php
session_start();
require_once '../includes/config.php';

header('Content-Type: application/json');

$cart = $_SESSION['cart'] ?? [];
$result = [];

if (!empty($cart)) {
    $ids = array_map('intval', array_keys($cart));
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    // One query for the whole cart (archived products are ignored)
    $stmt = $conn->prepare("SELECT id, name, price, quantity FROM products WHERE is_active = 1 AND id IN ($placeholders)");
    $stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
    $stmt->execute();

    $products = [];
    foreach ($stmt->get_result() as $row) {
        $products[(int)$row['id']] = $row;
    }

    foreach ($cart as $product_id => $quantity) {
        $product_id = (int)$product_id;
        if (isset($products[$product_id])) {
            $p = $products[$product_id];
            $result[$product_id] = [
                'name' => $p['name'],
                'price' => $p['price'],
                'qty' => $quantity,
                'stock' => $p['quantity']
            ];
        } else {
            // The product was archived after it was added: drop it from the cart
            unset($_SESSION['cart'][$product_id]);
        }
    }
}

echo json_encode($result);
