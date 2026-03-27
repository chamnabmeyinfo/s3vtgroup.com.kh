<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Clear customer session
unset($_SESSION['customer_id']);
unset($_SESSION['customer_email']);
unset($_SESSION['customer_name']);

header('Location: ' . (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php'));
exit;

