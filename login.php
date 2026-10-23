<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        header('Location: index.php?error=1');
        exit;
    }

    $stmt = $conn->prepare("SELECT id, username, password, role, status FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        if (password_verify($password, $user['password'])) {
            // Deactivated accounts cannot log in. This is checked after the password,
            // so the message does not reveal which usernames exist.
            if ($user['status'] === 'inactive') {
                header('Location: index.php?error=2');
                exit;
            }

            // Clear any existing session data
            session_destroy();
            session_start();

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            if ($user['role'] === 'cashier') {
                // A shift left open (browser closed, power cut...) is closed first,
                // at the time of the cashier's last activity
                close_stale_sessions($conn, (int)$user['id']);

                // Create a new session for cashier
                $stmt = $conn->prepare("INSERT INTO sessions (cashier_id, start_time) VALUES (?, NOW())");
                $stmt->bind_param("i", $user['id']);
                $stmt->execute();
                $_SESSION['session_id'] = $conn->insert_id;
                $_SESSION['cart'] = []; // Initialize empty cart

                header('Location: cashier/dashboard.php');
                exit;
            } else {
                // Admin login
                header('Location: admin/dashboard.php');
                exit;
            }
        }
    }

    // Login failed
    header('Location: index.php?error=1');
    exit;
}

// If not POST request, redirect to login
header('Location: index.php');
exit;
