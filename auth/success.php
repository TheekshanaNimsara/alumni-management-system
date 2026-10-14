<?php

// Alumni Connect - Auth Success Router
// Dispatches to login-success.php or signup-success.php

require_once __DIR__ . '/../config/db.php';

$type = $_GET['type'] ?? $_SESSION['auth_success_type'] ?? 'login';

if ($type === 'signup' || $type === 'register') {
    require __DIR__ . '/signup-success.php';
} else {
    require __DIR__ . '/login-success.php';
}
