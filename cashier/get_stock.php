<?php
session_start();
require_once '../includes/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'cashier') {
    http_response_code(403);
    echo json_encode(['error' => 'Not authorized']);
    exit;
}

// Current stock of every product that can be sold: { "<id>": <quantity>, ... }
$stock = [];
$result = $conn->query("SELECT id, quantity FROM products WHERE is_active = 1");
while ($row = $result->fetch_assoc()) {
    $stock[$row['id']] = (int)$row['quantity'];
}

echo json_encode($stock);
