<?php
require_once '../includes/auth.php';
require_role('admin');

// ---- Handle actions first, before any HTML is sent, so redirects always work ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Restock (+/-); stock may not go below zero
    if (isset($_POST['restock_id'])) {
        $id = (int)$_POST['restock_id'];
        $change = (int)($_POST['change'] ?? 0);
        $stmt = $conn->prepare("UPDATE products SET quantity = quantity + ? WHERE id = ? AND quantity + ? >= 0");
        $stmt->bind_param("iii", $change, $id, $change);
        $stmt->execute();
        if ($stmt->affected_rows === 0 && $change !== 0) {
            flash('Stock cannot go below zero.');
        }
        header("Location: inventory.php");
        exit;
    }

    // Add product
    if (isset($_POST['add_product'])) {
        $name = trim($_POST['name'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $qty = (int)($_POST['quantity'] ?? 0);
        if ($name === '' || $price < 0 || $qty < 0) {
            flash('Please enter a product name and a price and quantity that are not negative.');
        } else {
            $stmt = $conn->prepare("INSERT INTO products (name, price, quantity) VALUES (?, ?, ?)");
            $stmt->bind_param("sdi", $name, $price, $qty);
            $stmt->execute();
            flash('Product added.', 'success');
        }
        header("Location: inventory.php");
        exit;
    }

    // Archive / restore: hides a product from the POS but keeps its sales history
    if (isset($_POST['archive_id']) || isset($_POST['unarchive_id'])) {
        $restoring = isset($_POST['unarchive_id']);
        $id = (int)($restoring ? $_POST['unarchive_id'] : $_POST['archive_id']);
        $active = $restoring ? 1 : 0;
        $stmt = $conn->prepare("UPDATE products SET is_active = ? WHERE id = ?");
        $stmt->bind_param("ii", $active, $id);
        $stmt->execute();
        flash($restoring ? 'Product restored to the POS.' : 'Product archived. It no longer appears in the POS.', 'success');
        header("Location: inventory.php");
        exit;
    }

    // Permanent delete: only allowed for products that were never sold
    if (isset($_POST['delete_id'])) {
        $id = (int)$_POST['delete_id'];
        $stmt = $conn->prepare("DELETE FROM products WHERE id = ? AND NOT EXISTS (SELECT 1 FROM sale_items WHERE product_id = ?)");
        $stmt->bind_param("ii", $id, $id);
        $stmt->execute();
        if ($stmt->affected_rows === 0) {
            flash('This product has sales history, so it can only be archived.');
        } else {
            flash('Product deleted.', 'success');
        }
        header("Location: inventory.php");
        exit;
    }
}

$products = $conn->query("
    SELECT p.*, (SELECT COUNT(*) FROM sale_items si WHERE si.product_id = p.id) AS sold_count
    FROM products p
    ORDER BY p.is_active DESC, p.name
");

include '../includes/header.php';
?>
<div>
<h2 class="heading">Inventory Management</h2>
</div>
<?php render_flash(); ?>
<!-- Add Product Form -->
<div><form method="post" class="inline-form">
    <h3 class="heading">Add New Product</h3>
    <input class="input" type="text" name="name" placeholder="Product name" required>
    <input class="input" type="number" step="0.01" min="0" name="price" placeholder="Price" required>
    <input class="input" type="number" min="0" name="quantity" placeholder="Quantity" required>
    <button type="submit" name="add_product" class="add-btn">Add</button>
</form>
</div>
<!-- Product List -->
<div>
<table class="table" border="1">
    <tr>
        <th>ID</th><th>Name</th><th>Price</th><th>Stock</th><th>Status</th><th>Restock</th><th>Action</th>
    </tr>
    <?php while ($p = $products->fetch_assoc()): ?>
    <tr class="<?= $p['is_active'] ? '' : 'archived-row' ?>">
        <td><?= $p['id'] ?></td>
        <td><?= htmlspecialchars($p['name']) ?></td>
        <td>$<?= number_format($p['price'], 2, '.', '') ?></td>
        <td><?= $p['quantity'] ?></td>
        <td><span class="status-badge <?= $p['is_active'] ? 'status-active' : 'status-inactive' ?>"><?= $p['is_active'] ? 'Active' : 'Archived' ?></span></td>
        <td>
            <form method="post" style="display:flex; gap:4px;">
                <input type="hidden" name="restock_id" value="<?= $p['id'] ?>">
                <input type="number" name="change" placeholder="+/-" required style="width:60px;">
                <button type="submit" class="btn">Update</button>
            </form>
        </td>
        <td>
            <div class="inline-actions">
                <?php if ($p['is_active']): ?>
                <form method="post" onsubmit="return confirm('Archive this product? It will disappear from the POS, but its sales history is kept.')">
                    <input type="hidden" name="archive_id" value="<?= $p['id'] ?>">
                    <button type="submit" class="btn-danger">Archive</button>
                </form>
                <?php else: ?>
                <form method="post">
                    <input type="hidden" name="unarchive_id" value="<?= $p['id'] ?>">
                    <button type="submit" class="btn-danger">Restore</button>
                </form>
                <?php endif; ?>
                <?php if ((int)$p['sold_count'] === 0): ?>
                <form method="post" onsubmit="return confirm('Permanently delete this product? This cannot be undone.')">
                    <input type="hidden" name="delete_id" value="<?= $p['id'] ?>">
                    <button type="submit" class="btn-danger">Delete</button>
                </form>
                <?php endif; ?>
            </div>
        </td>
    </tr>
    <?php endwhile; ?>
</table>
    </div>
<?php include '../includes/footer.php'; ?>
