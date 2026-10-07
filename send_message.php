<?php
// ============================================================
// University Alumni Network - Direct Send Message Bridge
// Fully integrated with PDO and Conversations table
// ============================================================
require_once __DIR__ . '/config/db.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sender_id = current_user_id();
    $receiver_id = intval($_POST['receiver_id'] ?? 0);
    $message = trim($_POST['message'] ?? '');

    if (!empty($message) && $receiver_id > 0 && $receiver_id !== $sender_id && $db_connected && $pdo) {
        try {
            $u1 = min($sender_id, $receiver_id);
            $u2 = max($sender_id, $receiver_id);

            // Find or create conversation
            $stmt = $pdo->prepare("SELECT id FROM conversations WHERE user_one_id = ? AND user_two_id = ? LIMIT 1");
            $stmt->execute([$u1, $u2]);
            $conv = $stmt->fetch();

            if ($conv) {
                $convId = (int)$conv['id'];
            } else {
                $stmt = $pdo->prepare("INSERT INTO conversations (user_one_id, user_two_id) VALUES (?, ?)");
                $stmt->execute([$u1, $u2]);
                $convId = (int)$pdo->lastInsertId();
            }

            // Insert message
            $stmt = $pdo->prepare("INSERT INTO messages (conversation_id, sender_id, receiver_id, message, is_read, sent_at) VALUES (?, ?, ?, ?, 0, NOW())");
            $stmt->execute([$convId, $sender_id, $receiver_id, $message]);

            $pdo->prepare("UPDATE conversations SET updated_at = NOW() WHERE id = ?")->execute([$convId]);

            header("Location: messages.php?conversation_id=" . $convId);
            exit;
        } catch (Exception $e) {
            header("Location: messages.php?user_id=" . $receiver_id);
            exit;
        }
    }

    header("Location: messages.php" . ($receiver_id > 0 ? "?user_id=" . $receiver_id : ""));
    exit;
} else {
    header("Location: messages.php");
    exit;
}