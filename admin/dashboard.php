<?php
require_once '../includes/auth.php';
require_role('admin');
header('Location: inventory.php');
exit;
?>