<?php
require_once '../includes/auth.php';
require_role('admin');

// Close a shift the cashier never closed (ends at the cashier's last activity)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['close_session_id'])) {
    close_session_at_last_activity($conn, (int)$_POST['close_session_id']);
    flash('Shift closed.', 'success');
    header('Location: sessions.php');
    exit;
}

// Shifts that are still open show live totals; closed shifts use the stored totals
$sessions = $conn->query("
    SELECT s.id, u.username, s.start_time, s.end_time,
        CASE WHEN s.end_time IS NULL
             THEN COALESCE((SELECT SUM(sa.total_amount) FROM sales sa WHERE sa.session_id = s.id), 0)
             ELSE s.total_amount END AS total_amount,
        CASE WHEN s.end_time IS NULL
             THEN COALESCE((SELECT SUM(si.quantity) FROM sale_items si JOIN sales sa ON sa.id = si.sale_id WHERE sa.session_id = s.id), 0)
             ELSE s.total_items END AS total_items
    FROM sessions s
    JOIN users u ON s.cashier_id = u.id
    ORDER BY s.start_time DESC
");

include '../includes/header.php';
?>
<div class="sessions-div">
<div>
<h2 class="heading">All Cashier Sessions</h2>
</div>
<?php render_flash(); ?>
<div>
  <table class="table" border="1">
    <tr>
        <th>Cashier</th><th>Start</th><th>End</th><th>Items Sold</th><th>Total ($)</th><th>Action</th>
    </tr>
    <?php while ($row = $sessions->fetch_assoc()): ?>
    <tr>
        <td><?= htmlspecialchars($row['username']) ?></td>
        <td><?= $row['start_time'] ?></td>
        <td><?= $row['end_time'] ?? 'Active' ?></td>
        <td><?= $row['total_items'] ?></td>
        <td>$<?= number_format($row['total_amount'], 2, '.', '') ?></td>
        <td>
            <?php if ($row['end_time'] === null): ?>
            <form method="post" class="inline-actions" onsubmit="return confirm('Close this shift? Only do this if the cashier has left without logging out.')">
                <input type="hidden" name="close_session_id" value="<?= $row['id'] ?>">
                <button type="submit" class="btn-cashier-delete">Close shift</button>
            </form>
            <?php endif; ?>
        </td>
    </tr>
    <?php endwhile; ?>
  </table>
  </div>
  </div>
<?php include '../includes/footer.php'; ?>
