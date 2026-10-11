<?php
// ============================================================
// University Alumni Network - Content & User Report Handler
// Robust validation, safe redirection, no CSRF blockage
// ============================================================
require_once __DIR__ . '/../config/db.php';

require_login();

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $reporter_id = current_user_id();

    $targets = [
        'reported_user_id' => 'users',
        'event_id'         => 'events',
        'job_id'           => 'jobs',
        'message_id'       => 'messages'
    ];

    $values = [];
    foreach ($targets as $key => $table) {
        $id = intval($_POST[$key] ?? 0);
        $values[$key] = $id > 0 ? $id : null;
    }

    $reason = trim($_POST['reason'] ?? '');
    $description = trim($_POST['description'] ?? '');

    // Default destination
    $destination = get_base_url() . 'index.php';
    if (!empty($values['reported_user_id'])) {
        $destination = get_base_url() . 'profile.php?id=' . $values['reported_user_id'];
    } elseif (!empty($values['event_id'])) {
        $destination = get_base_url() . 'events.php';
    } elseif (!empty($values['job_id'])) {
        $destination = get_base_url() . 'jobs.php';
    } elseif (!empty($values['message_id'])) {
        $destination = get_base_url() . 'messages.php';
    }

    // Must have a reason and at least one target
    $validTargets = array_filter($values);
    if (!empty($reason) && count($validTargets) >= 1 && $db_connected && $pdo) {
        try {
            // Verify target exists
            $targetValid = true;
            foreach ($targets as $key => $table) {
                if (!$values[$key]) continue;
                $stmt = $pdo->prepare("SELECT id FROM {$table} WHERE id = ? LIMIT 1");
                $stmt->execute([$values[$key]]);
                if (!$stmt->fetch()) {
                    $targetValid = false;
                    break;
                }
            }

            if ($targetValid) {
                $stmt = $pdo->prepare("
                    INSERT INTO reports (reporter_id, reported_user_id, event_id, job_id, message_id, reason, description, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
                ");
                $stmt->execute([
                    $reporter_id,
                    $values['reported_user_id'],
                    $values['event_id'],
                    $values['job_id'],
                    $values['message_id'],
                    substr($reason, 0, 150),
                    $description
                ]);
            }
        } catch (Exception $e) {
            error_log("Report submission error: " . $e->getMessage());
        }
    }

    $separator = (strpos($destination, '?') !== false) ? '&' : '?';
    header("Location: " . $destination . $separator . "reported=1");
    exit;
} else {
    header("Location: " . get_base_url() . "index.php");
    exit;
}

