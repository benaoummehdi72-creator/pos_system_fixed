<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

function require_login() {
    global $conn;

    if (!isset($_SESSION['user_id'])) {
        header('Location: ../index.php');
        exit;
    }

    // A deactivated account loses access immediately, even if it is still logged in
    if (!user_is_active($conn, $_SESSION['user_id'])) {
        session_destroy();
        header('Location: ../index.php?error=2');
        exit;
    }
}

function require_role($role) {
    require_login();
    if ($_SESSION['role'] !== $role) {
        header('Location: ../index.php');
        exit;
    }
}
?>
