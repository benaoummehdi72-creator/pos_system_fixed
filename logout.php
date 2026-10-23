<?php
require_once 'includes/config.php';
require_once 'includes/helpers.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Admins just log out
if ($_SESSION['role'] !== 'cashier') {
    session_destroy();
    header('Location: index.php');
    exit;
}

$session_id = (int)($_SESSION['session_id'] ?? 0);

// Cashiers: the shift is only closed when "Close Register" is pressed.
// (Just opening this page does not end it, so the cashier can still go back to the POS.)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['close_register'])) {
    if ($session_id > 0) {
        close_session($conn, $session_id);
    }
    session_destroy();
    header('Location: index.php');
    exit;
}

// Read-only summary of the shift so far
$totals = get_session_totals($conn, $session_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logout - SuperMarket POS</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="logout-page">
    <div class="logout-box">
        <h2 class="heading">Shift Summary</h2>
        <p class="logout-text">Items sold: <strong><?= $totals['items'] ?></strong></p>
        <p class="logout-text">Total cash expected: <strong>$<?= number_format($totals['total'], 2, '.', '') ?></strong></p>
        <form method="post" action="logout.php">
            <button type="submit" name="close_register" value="1" class="btn">Close Register</button>
        </form>
        <p><a class="back-link" href="cashier/dashboard.php">&larr; Back to POS</a></p>
    </div>
</body>
</html>
