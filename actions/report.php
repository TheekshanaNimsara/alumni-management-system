<?php
// ============================================================
// University Alumni Network - Content & User Report Handler
// ============================================================
require_once __DIR__ . '/../config/db.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reporter_id = current_user_id();
    $reported_user_id = !empty($_POST['reported_user_id']) ? intval($_POST['reported_user_id']) : null;
    $event_id = !empty($_POST['event_id']) ? intval($_POST['event_id']) : null;
    $job_id = !empty($_POST['job_id']) ? intval($_POST['job_id']) : null;
    $message_id = !empty($_POST['message_id']) ? intval($_POST['message_id']) : null;
    $reason = trim($_POST['reason'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (!empty($reason)) {
        if ($db_connected && $pdo) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO reports (reporter_id, reported_user_id, event_id, job_id, message_id, reason, description, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
                ");
                $stmt->execute([$reporter_id, $reported_user_id, $event_id, $job_id, $message_id, $reason, $description]);
            } catch (Exception $e) {
                // Graceful fallback
            }
        }
    }

    // Redirect back to referring page or homepage
    $referer = $_SERVER['HTTP_REFERER'] ?? '../index.php';
    $glue = (strpos($referer, '?') !== false) ? '&' : '?';
    header("Location: " . $referer . $glue . "reported=1");
    exit;
} else {
    header("Location: ../index.php");
    exit;
}
