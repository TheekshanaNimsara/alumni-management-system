<?php
session_start();
require_once 'config/db.php';

// FOR TESTING: Hardcode a logged-in user ID if login isn't finished yet.
// Remove this once Dayasena finishes the login system!
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // Assuming 1 is the Admin ID
}

$current_user_id = $_SESSION['user_id'];
$chat_partner_id = isset($_GET['user']) ? intval($_GET['user']) : 0;
$chat_partner_name = "Select a conversation";

// Fetch the conversation if a partner is selected
$messages = [];
if ($chat_partner_id > 0) {
    // Get partner's name
    $name_stmt = $conn->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
    $name_stmt->bind_param("i", $chat_partner_id);
    $name_stmt->execute();
    $name_result = $name_stmt->get_result();
    if ($row = $name_result->fetch_assoc()) {
        $chat_partner_name = $row['first_name'] . " " . $row['last_name'];
    }
    $name_stmt->close();

    // Fetch message thread
    $msg_query = "
        SELECT message, sent_at, sender_id 
        FROM messages 
        WHERE (sender_id = ? AND receiver_id = ?) 
           OR (sender_id = ? AND receiver_id = ?)
        ORDER BY sent_at ASC
    ";
    $stmt = $conn->prepare($msg_query);
    $stmt->bind_param("iiii", $current_user_id, $chat_partner_id, $chat_partner_id, $current_user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Messages - Alumni Network</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="logo">
            <span class="logo-badge">AN</span>
            <span style="font-weight: bold; font-size: 1.2rem;">Alumni Network</span>
        </div>
        <div>
            <a href="index.php" style="color:white; text-decoration:none; margin-right:15px;">Dashboard</a>
            <a href="directory.php" style="color:white; text-decoration:none;">Directory</a>
        </div>
    </nav>

    <div class="container">
        <div class="chat-wrapper">
            <div class="chat-window">
                <div class="chat-header">
                    Chatting with: <?php echo htmlspecialchars($chat_partner_name); ?>
                </div>
                
                <div class="chat-messages">
                    <?php if (empty($messages) && $chat_partner_id > 0): ?>
                        <p style="text-align:center; color:#888;">No messages yet. Say hello!</p>
                    <?php elseif ($chat_partner_id == 0): ?>
                        <p style="text-align:center; color:#888;">Please select a user from the directory to message them.</p>
                    <?php else: ?>
                        <?php foreach ($messages as $msg): 
                            $is_sent = ($msg['sender_id'] == $current_user_id);
                            $bubble_class = $is_sent ? 'msg-sent' : 'msg-received';
                        ?>
                            <div class="msg-bubble <?php echo $bubble_class; ?>">
                                <?php echo htmlspecialchars($msg['message']); ?>
                                <span class="msg-time"><?php echo date('M d, H:i', strtotime($msg['sent_at'])); ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <?php if ($chat_partner_id > 0): ?>
                <form action="send_message.php" method="POST" class="chat-input-area">
                    <input type="hidden" name="receiver_id" value="<?php echo $chat_partner_id; ?>">
                    <input type="text" name="message" placeholder="Type your message..." required autocomplete="off">
                    <button type="submit" class="btn-primary" style="width: auto;">Send</button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>