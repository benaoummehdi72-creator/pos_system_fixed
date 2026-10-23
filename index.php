<?php
session_start();

// If already logged in, redirect to appropriate dashboard
if (isset($_SESSION['user_id'])) {
    $redirect = ($_SESSION['role'] === 'admin') ? 'admin/dashboard.php' : 'cashier/dashboard.php';
    header("Location: $redirect");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SuperMarket POS</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background: #ecf0f1;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .login-container {
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
        }
        .login-container h2 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 30px;
        }
        .login-container input {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            box-sizing: border-box;
        }
        .login-container button {
            width: 100%;
            padding: 12px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            margin-top: 10px;
        }
        .login-container button:hover {
            background: #2980b9;
        }
        .error {
            color: #e74c3c;
            text-align: center;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>SuperMarket POS</h2>
        <?php if (($_GET['error'] ?? '') === '2'): ?>
            <p class="error">This account has been deactivated. Please contact an administrator.</p>
        <?php elseif (isset($_GET['error'])): ?>
            <p class="error">Invalid username or password</p>
        <?php endif; ?>
        <form action="login.php" method="POST">
            <input type="text" name="username" placeholder="Username" required autocomplete="off">
            <input type="password" name="password" placeholder="Password" required>
            <button style="background:#2c3e50;"class="login-button"type="submit">Login</button>
        </form>
    </div>
</body>
</html>