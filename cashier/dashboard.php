<?php
require_once '../includes/auth.php';
require_role('cashier');
include '../includes/header.php';

$products = $conn->query("SELECT * FROM products WHERE is_active = 1 ORDER BY name");
?>

<div class="pos-controls">
    <div class="search-box">
        <input type="text" id="product-search" placeholder="Enter Search Term" autocomplete="off">
    </div>
    <div class="cart-summary">
        Items: <strong id="cart-count">0</strong>
    </div>
</div>

<div class="pos-container">
    <div class="product-grid">
        <?php while ($p = $products->fetch_assoc()): ?>
            <div class="product-card <?= $p['quantity'] <= 0 ? 'out-of-stock' : '' ?>"
                 data-id="<?= $p['id'] ?>"
                 data-name="<?= htmlspecialchars($p['name']) ?>"
                 data-price="<?= $p['price'] ?>">
                <h3 class="heading"><?= htmlspecialchars($p['name']) ?></h3>
                <p class="price">$<?= number_format($p['price'], 2, '.', '') ?></p>
                <p class="stock">Stock: <?= $p['quantity'] ?></p>
                <?php if ($p['quantity'] > 0): ?>
                    <button class="add-to-cart btn">Add to Cart</button>
                <?php else: ?>
                    <button class="btn" disabled>Out of Stock</button>
                <?php endif; ?>
            </div>
        <?php endwhile; ?>
    </div>

    <div class="cart-panel">
        <h2 class="heading">Cart</h2>
        <div id="cart-items"></div>
        <div class="total-container">
        <div class="cart-total">Total: $<span id="cart-total">0.00</span></div>
                </div>
        <button id="checkout-btn" class="btn checkout">Checkout</button>
        <div id="message"></div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>