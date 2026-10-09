<?php
// ============================================================
// University Alumni Network - Conversation Alias / Router
// ============================================================
require_once __DIR__ . '/config/db.php';

require_login();

$convId = isset($_GET['id']) ? intval($_GET['id']) : (isset($_GET['conversation_id']) ? intval($_GET['conversation_id']) : 0);
$userId = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

if ($convId > 0) {
    header("Location: messages.php?conversation_id=" . $convId);
    exit;
} elseif ($userId > 0) {
    header("Location: messages.php?user_id=" . $userId);
    exit;
} else {
    header("Location: messages.php");
    exit;
}
