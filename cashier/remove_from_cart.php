<?php
session_start();

header('Content-Type: application/json');

$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;

if ($product_id > 0 && isset($_SESSION['cart'][$product_id])) {
    unset($_SESSION['cart'][$product_id]);
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Item not found in cart']);
}