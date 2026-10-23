<?php
if (session_status() === PHP_SESSION_NONE) session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SuperMarket POS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<nav class="navbar">
    <div class="nav-brand">SuperMarket POS</div>
    <ul class="nav-links">
        <?php if (isset($_SESSION['user_id'])): ?>
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <li><a href="../admin/inventory.php">Inventory</a></li>
                <li><a href="../admin/sessions.php">Sessions</a></li>
                <li><a href="../admin/cashiers.php">Cashiers</a></li>
            <?php else: ?>
                <li><a href="../cashier/dashboard.php">POS</a></li>
            <?php endif; ?>
            <li><a href="../logout.php">Logout</a></li>
        <?php endif; ?>
    </ul>
</nav>
<div class="container">