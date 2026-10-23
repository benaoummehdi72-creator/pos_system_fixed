<?php
require_once '../includes/auth.php';
require_role('admin');

// ---- Handle actions first, before any HTML is sent, so redirects always work ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Add cashier
    if (isset($_POST['add_cashier'])) {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        if ($username === '' || $password === '') {
            flash('Username and password are required.');
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            try {
                $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'cashier')");
                $stmt->bind_param("ss", $username, $hash);
                $stmt->execute();
                flash('Cashier added.', 'success');
            } catch (mysqli_sql_exception $e) {
                if ((int)$e->getCode() === 1062) {
                    flash('That username is already taken.');
                } else {
                    throw $e;
                }
            }
        }
        header("Location: cashiers.php");
        exit;
    }

    // Deactivate / reactivate: the account and its sales history are kept
    if (isset($_POST['set_status'])) {
        $id = (int)($_POST['cashier_id'] ?? 0);
        $status = ($_POST['set_status'] === 'inactive') ? 'inactive' : 'active';
        $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ? AND role = 'cashier'");
        $stmt->bind_param("si", $status, $id);
        $stmt->execute();
        if ($status === 'inactive') {
            // Don't leave a deactivated cashier's shift open
            close_stale_sessions($conn, $id);
            flash('Cashier deactivated. They can no longer log in; their sales history is kept.', 'success');
        } else {
            flash('Cashier reactivated.', 'success');
        }
        header("Location: cashiers.php");
        exit;
    }
}

$cashiers = $conn->query("SELECT id, username, status FROM users WHERE role = 'cashier' ORDER BY username");

include '../includes/header.php';
?>
<div class="cashiers-div">
<h2 class="heading">Add New Cashier</h2>
<?php render_flash(); ?>

<form method="post" class="inline-form">

    <input class="input" type="text" name="username" placeholder="Username" required>
    <input class="input" type="password" name="password" placeholder="Password" required>
    <button type="submit" name="add_cashier" class="btn">Add</button>
</form>
<h2 class="heading">Manage Cashiers</h2>
<table class="table" border="1">
    <tr><th>ID</th><th>Username</th><th>Status</th><th>Action</th></tr>
    <?php while ($c = $cashiers->fetch_assoc()): ?>
    <?php $is_active = ($c['status'] !== 'inactive'); ?>
    <tr class="<?= $is_active ? '' : 'archived-row' ?>">
        <td><?= $c['id'] ?></td>
        <td><?= htmlspecialchars($c['username']) ?></td>
        <td><span class="status-badge <?= $is_active ? 'status-active' : 'status-inactive' ?>"><?= $is_active ? 'Active' : 'Deactivated' ?></span></td>
        <td>
            <form method="post" class="inline-actions"
                  <?php if ($is_active): ?>onsubmit="return confirm('Deactivate this cashier? They will no longer be able to log in. Their sales history is kept.')"<?php endif; ?>>
                <input type="hidden" name="cashier_id" value="<?= $c['id'] ?>">
                <?php if ($is_active): ?>
                    <button type="submit" name="set_status" value="inactive" class="btn-cashier-delete">Deactivate</button>
                <?php else: ?>
                    <button type="submit" name="set_status" value="active" class="btn-cashier-delete">Reactivate</button>
                <?php endif; ?>
            </form>
        </td>
    </tr>
    <?php endwhile; ?>
</table>
    </div>
<?php include '../includes/footer.php'; ?>
