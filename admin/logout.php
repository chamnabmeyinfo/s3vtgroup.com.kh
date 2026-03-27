<?php
require_once __DIR__ . '/../bootstrap/app.php';

session_destroy();
header('Location: ' . url('admin/login.php'));
exit;

