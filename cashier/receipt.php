<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_role('cashier');

$sale_id = isset($_GET['sale_id']) ? (int)$_GET['sale_id'] : 0;
if ($sale_id <= 0) die("Invalid sale ID.");

// Get sale
$stmt = $conn->prepare("SELECT s.*, u.username FROM sales s JOIN users u ON s.cashier_id = u.id WHERE s.id = ?");
$stmt->bind_param("i", $sale_id);
$stmt->execute();
$sale = $stmt->get_result()->fetch_assoc();
if (!$sale) die("Sale not found.");

// Get items
$stmt = $conn->prepare("SELECT si.*, p.name FROM sale_items si JOIN products p ON si.product_id = p.id WHERE si.sale_id = ?");
$stmt->bind_param("i", $sale_id);
$stmt->execute();
$items = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Receipt #<?= $sale_id ?></title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            width: 80mm; margin: 0 auto; padding: 5mm;
            font-size: 12px;
        }
        .center { text-align: center; }
        .divider { border-top: 1px dashed #000; margin: 5px 0; }
        .item { display: flex; justify-content: space-between; }
        .total { font-weight: bold; font-size: 14px; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="center">
        <h3>SuperMarket POS</h3>
        Receipt #<?= $sale_id ?><br>
        <?= date('d/m/Y H:i', strtotime($sale['sale_date'])) ?><br>
        Cashier: <?= htmlspecialchars($sale['username']) ?>
    </div>
    <div class="divider"></div>
    <?php while ($item = $items->fetch_assoc()): ?>
        <div class="item">
            <span><?= htmlspecialchars($item['name']) ?> x<?= $item['quantity'] ?></span>
            <span>$<?= number_format($item['price'] * $item['quantity'], 2) ?></span>
        </div>
    <?php endwhile; ?>
    <div class="divider"></div>
    <div class="item total">
        <span>TOTAL</span>
        <span>$<?= number_format($sale['total_amount'], 2) ?></span>
    </div>
    <div class="center" style="margin-top:10px;">Thank you!</div>
    <div class="divider"></div>
    <button class="no-print" onclick="window.print()">Print</button>
    <button class="no-print" onclick="window.close()">Close</button>
</body>
</html>