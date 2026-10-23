<?php
/**
 * Small helpers shared by several pages.
 * Included by includes/auth.php, login.php, logout.php and cashier/checkout.php.
 */

/* ---------------------------------------------------------------
 * Flash messages: one-time notices that survive a redirect
 * ------------------------------------------------------------- */
function flash($message, $type = 'error') {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

function render_flash() {
    if (empty($_SESSION['flash'])) return;
    $f = $_SESSION['flash'];
    unset($_SESSION['flash']);
    $type = ($f['type'] === 'success') ? 'success' : 'error';
    echo '<div class="flash ' . $type . '">' . htmlspecialchars($f['message']) . '</div>';
}

/* ---------------------------------------------------------------
 * Accounts
 * ------------------------------------------------------------- */
function user_is_active($conn, $user_id) {
    $user_id = (int)$user_id;
    $stmt = $conn->prepare("SELECT status FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row && $row['status'] !== 'inactive';
}

/* ---------------------------------------------------------------
 * Cashier shifts (rows in the `sessions` table)
 * ------------------------------------------------------------- */

/** Items sold and money taken so far in one shift. */
function get_session_totals($conn, $session_id) {
    $session_id = (int)$session_id;
    $stmt = $conn->prepare(
        "SELECT COALESCE(SUM(total_amount), 0) AS total,
                COALESCE((SELECT SUM(si.quantity)
                          FROM sale_items si
                          JOIN sales sa ON sa.id = si.sale_id
                          WHERE sa.session_id = ?), 0) AS items
         FROM sales
         WHERE session_id = ?"
    );
    $stmt->bind_param("ii", $session_id, $session_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return ['total' => (float)$row['total'], 'items' => (int)$row['items']];
}

/** True while the shift exists and has not been closed. */
function session_is_open($conn, $session_id) {
    $session_id = (int)$session_id;
    $stmt = $conn->prepare("SELECT 1 FROM sessions WHERE id = ? AND end_time IS NULL");
    $stmt->bind_param("i", $session_id);
    $stmt->execute();
    return (bool)$stmt->get_result()->fetch_row();
}

/**
 * Close a shift and store its totals.
 * $end_time = null means "now"; otherwise pass a 'Y-m-d H:i:s' string.
 * Does nothing if the shift is already closed.
 */
function close_session($conn, $session_id, $end_time = null) {
    $session_id = (int)$session_id;
    $totals = get_session_totals($conn, $session_id);
    $stmt = $conn->prepare(
        "UPDATE sessions
         SET end_time = COALESCE(?, NOW()), total_amount = ?, total_items = ?
         WHERE id = ? AND end_time IS NULL"
    );
    $stmt->bind_param("sdii", $end_time, $totals['total'], $totals['items'], $session_id);
    $stmt->execute();
}

/**
 * Close a shift whose cashier never logged out (browser closed, power cut...).
 * The end time is the last thing the cashier did: their last sale, or the login time.
 */
function close_session_at_last_activity($conn, $session_id) {
    $session_id = (int)$session_id;
    $stmt = $conn->prepare(
        "SELECT COALESCE((SELECT MAX(sale_date) FROM sales WHERE session_id = ?), start_time) AS last_activity
         FROM sessions
         WHERE id = ? AND end_time IS NULL"
    );
    $stmt->bind_param("ii", $session_id, $session_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if ($row) {
        close_session($conn, $session_id, $row['last_activity']);
    }
}

/** Close every shift this cashier left open. */
function close_stale_sessions($conn, $cashier_id) {
    $cashier_id = (int)$cashier_id;
    $stmt = $conn->prepare("SELECT id FROM sessions WHERE cashier_id = ? AND end_time IS NULL");
    $stmt->bind_param("i", $cashier_id);
    $stmt->execute();
    $ids = array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), 'id');
    foreach ($ids as $id) {
        close_session_at_last_activity($conn, $id);
    }
}
