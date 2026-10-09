<?php
// ============================================================
// University Alumni Network - Private 1-to-1 Messaging System
// Secure Conversations, Autoscroll, Unread Tracking & XSS Safety
// ============================================================
$pageTitle = 'Alumni Messages';
$currentPage = 'messages';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/functions.php';

require_login();

$currentUserId = current_user_id();
$activeConvId = isset($_GET['conversation_id']) ? intval($_GET['conversation_id']) : 0;
$targetUserId = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$error = '';
$success = '';

// If a target user_id is provided, find or create the conversation
if ($targetUserId > 0 && $targetUserId !== $currentUserId && $db_connected && $pdo) {
    try {
        // Ensure user_one_id < user_two_id to avoid reverse duplicates
        $u1 = min($currentUserId, $targetUserId);
        $u2 = max($currentUserId, $targetUserId);

        $stmt = $pdo->prepare("SELECT id FROM conversations WHERE user_one_id = ? AND user_two_id = ? LIMIT 1");
        $stmt->execute([$u1, $u2]);
        $existing = $stmt->fetch();

        if ($existing) {
            $activeConvId = (int)$existing['id'];
        } else {
            // Verify target user exists
            $checkU = $pdo->prepare("SELECT id FROM users WHERE id = ? AND status = 'active' LIMIT 1");
            $checkU->execute([$targetUserId]);
            if ($checkU->fetch()) {
                $stmt = $pdo->prepare("INSERT INTO conversations (user_one_id, user_two_id) VALUES (?, ?)");
                $stmt->execute([$u1, $u2]);
                $activeConvId = (int)$pdo->lastInsertId();
            }
        }
    } catch (Exception $e) {
        $error = "Could not initialize conversation.";
    }
}

// Handle sending a new message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_message') {
    $convId = intval($_POST['conversation_id'] ?? 0);
    $messageText = trim($_POST['message'] ?? '');

        if ($convId > 0 && !empty($messageText) && $db_connected && $pdo) {
            try {
                // Security Authorization: verify current user is in this conversation
                $stmt = $pdo->prepare("SELECT user_one_id, user_two_id FROM conversations WHERE id = ? LIMIT 1");
                $stmt->execute([$convId]);
                $conv = $stmt->fetch();

                if (!$conv || ($conv['user_one_id'] != $currentUserId && $conv['user_two_id'] != $currentUserId)) {
                    $error = "Unauthorized conversation access.";
                } else {
                    $receiverId = ($conv['user_one_id'] == $currentUserId) ? $conv['user_two_id'] : $conv['user_one_id'];

                    // Insert message
                    $stmt = $pdo->prepare("
                        INSERT INTO messages (conversation_id, sender_id, receiver_id, message, is_read, sent_at)
                        VALUES (?, ?, ?, ?, 0, NOW())
                    ");
                    $stmt->execute([$convId, $currentUserId, $receiverId, $messageText]);

                    // Update conversation updated_at
                    $pdo->prepare("UPDATE conversations SET updated_at = NOW() WHERE id = ?")->execute([$convId]);

                    $activeConvId = $convId;
                    $success = "Message sent.";
                }
            } catch (Exception $e) {
                $error = "Failed to send message: " . $e->getMessage();
            }
        } else {
            $error = "Please enter a message.";
        }
}

// Fetch conversation list for current user
$conversations = [];
if ($db_connected && $pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT c.id, c.user_one_id, c.user_two_id, c.updated_at,
                   u.id AS other_user_id, u.first_name, u.last_name, u.role,
                   p.profile_picture, p.current_job_title, p.current_company,
                   (
                       SELECT m.message FROM messages m 
                       WHERE m.conversation_id = c.id 
                       ORDER BY m.id DESC LIMIT 1
                   ) AS last_message,
                   (
                       SELECT m.sent_at FROM messages m 
                       WHERE m.conversation_id = c.id 
                       ORDER BY m.id DESC LIMIT 1
                   ) AS last_message_time,
                   (
                       SELECT COUNT(*) FROM messages m 
                       WHERE m.conversation_id = c.id AND m.receiver_id = ? AND m.is_read = 0
                   ) AS unread_count
            FROM conversations c
            JOIN users u ON u.id = CASE WHEN c.user_one_id = ? THEN c.user_two_id ELSE c.user_one_id END
            LEFT JOIN alumni_profiles p ON p.user_id = u.id
            WHERE c.user_one_id = ? OR c.user_two_id = ?
            ORDER BY c.updated_at DESC
        ");
        $stmt->execute([$currentUserId, $currentUserId, $currentUserId, $currentUserId]);
        $conversations = $stmt->fetchAll();
    } catch (Exception $e) {
        $conversations = [];
    }
}

// If no active conversation specified but conversations exist, default to the first one
if ($activeConvId === 0 && !empty($conversations)) {
    $activeConvId = (int)$conversations[0]['id'];
}

// Fetch active conversation details and messages (with strict authorization)
$activeConv = null;
$messagesList = [];

if ($activeConvId > 0 && $db_connected && $pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT c.*, 
                   u.id AS other_user_id, u.first_name, u.last_name, u.email,
                   p.profile_picture, p.current_job_title, p.current_company, p.degree_programme
            FROM conversations c
            JOIN users u ON u.id = CASE WHEN c.user_one_id = ? THEN c.user_two_id ELSE c.user_one_id END
            LEFT JOIN alumni_profiles p ON p.user_id = u.id
            WHERE c.id = ? AND (c.user_one_id = ? OR c.user_two_id = ?)
            LIMIT 1
        ");
        $stmt->execute([$currentUserId, $activeConvId, $currentUserId, $currentUserId]);
        $activeConv = $stmt->fetch();

        if ($activeConv) {
            // Mark unread messages in this conversation as read
            $stmt = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE conversation_id = ? AND receiver_id = ?");
            $stmt->execute([$activeConvId, $currentUserId]);

            // Fetch messages in chronological order
            $stmt = $pdo->prepare("
                SELECT m.*, u.first_name, u.last_name, p.profile_picture
                FROM messages m
                JOIN users u ON m.sender_id = u.id
                LEFT JOIN alumni_profiles p ON p.user_id = u.id
                WHERE m.conversation_id = ?
                ORDER BY m.id ASC
            ");
            $stmt->execute([$activeConvId]);
            $messagesList = $stmt->fetchAll();
        } else {
            $error = "Conversation not found or access denied.";
        }
    } catch (Exception $e) {
        $error = "Error loading conversation.";
    }
}
?>

<div class="container" style="padding-top: 2.5rem; padding-bottom: 4rem;">
    
    <div style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div>
            <span class="section-eyebrow">Direct Alumni Communications</span>
            <h1 class="section-title" style="color: var(--primary-color); font-size: 2rem; margin-bottom: 0.3rem;">
                Alumni Messages
            </h1>
            <p style="color: var(--text-muted); font-size: 0.95rem;">
                Private, secure 1-to-1 dialogue with fellow graduates, researchers, and mentors.
            </p>
        </div>
        <div>
            <a href="directory.php" class="btn btn-outline-gold btn-sm">
                + Start New Message from Directory
            </a>
        </div>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <!-- Messaging Workspace: Two-column layout -->
    <div class="card" style="padding: 0; overflow: hidden; display: flex; min-height: 600px; box-shadow: 0 10px 30px rgba(18, 3, 5, 0.08); border-top: 3px solid var(--accent-color);">

        <!-- Left Column: Conversations Sidebar -->
        <div style="width: 320px; border-right: 1px solid var(--border-color); background: var(--bg-color); display: flex; flex-direction: column;">
            
            <div style="padding: 1.2rem; border-bottom: 1px solid var(--border-color); background: var(--surface-color);">
                <input type="text" id="convSearchInput" placeholder="Filter conversations..." class="form-control" style="font-size: 0.88rem; padding: 0.5rem 0.8rem;" onkeyup="filterConversations();">
            </div>

            <div id="convListContainer" style="overflow-y: auto; flex: 1;">
                <?php if (empty($conversations)): ?>
                    <div style="padding: 2.5rem 1rem; text-align: center; color: var(--text-muted); font-size: 0.9rem;">
                        <p style="font-size: 2rem; margin-bottom: 0.5rem;">&#128172;</p>
                        No active conversations yet.<br>
                        <a href="directory.php" style="color: var(--primary-color); font-weight: 700;">Find alumni</a> to start messaging.
                    </div>
                <?php else: ?>
                    <?php foreach ($conversations as $c): ?>
                        <?php 
                            $isActive = ($activeConvId === (int)$c['id']);
                            $otherAvatar = get_user_avatar_url($c['profile_picture'] ?? 'default-avatar.svg');
                            $unread = (int)($c['unread_count'] ?? 0);
                        ?>
                        <a href="messages.php?conversation_id=<?php echo $c['id']; ?>" class="conv-item" 
                           style="display: flex; align-items: center; gap: 0.9rem; padding: 1rem 1.2rem; text-decoration: none; border-bottom: 1px solid rgba(0,0,0,0.05); transition: background 0.2s; <?php echo $isActive ? 'background: rgba(212, 175, 55, 0.15); border-left: 4px solid var(--accent-color);' : 'background: transparent;'; ?>"
                           onmouseover="if (!this.style.borderLeft) this.style.background='rgba(0,0,0,0.03)';"
                           onmouseout="if (!this.style.borderLeft) this.style.background='transparent';">
                            
                            <img src="<?php echo $otherAvatar; ?>" alt="Avatar" style="width: 44px; height: 44px; border-radius: 50%; object-fit: cover; border: 2px solid var(--accent-color); flex-shrink: 0;">
                            
                            <div style="flex: 1; min-width: 0;">
                                <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 0.2rem;">
                                    <strong class="conv-name" style="color: var(--primary-color); font-size: 0.95rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                        <?php echo htmlspecialchars($c['first_name'] . ' ' . $c['last_name']); ?>
                                    </strong>
                                    <?php if (!empty($c['last_message_time'])): ?>
                                        <span style="font-size: 0.75rem; color: var(--text-muted); flex-shrink: 0;">
                                            <?php echo time_ago($c['last_message_time']); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <p style="margin: 0; font-size: 0.83rem; color: var(--text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                        <?php echo htmlspecialchars($c['last_message'] ?? 'Started a conversation'); ?>
                                    </p>
                                    <?php if ($unread > 0): ?>
                                        <span class="badge" style="background: var(--accent-color); color: var(--primary-color); font-size: 0.72rem; padding: 0.2rem 0.5rem; border-radius: 10px; margin-left: 0.5rem;">
                                            <?php echo $unread; ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        </div>

        <!-- Right Column: Conversation Window -->
        <div style="flex: 1; display: flex; flex-direction: column; background: var(--surface-color);">
            
            <?php if ($activeConv): ?>
                
                <!-- Chat Window Header -->
                <div style="padding: 1rem 1.5rem; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: var(--surface-elevated);">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <img src="<?php echo get_user_avatar_url($activeConv['profile_picture'] ?? 'default-avatar.svg'); ?>" 
                             alt="Avatar" style="width: 48px; height: 48px; border-radius: 50%; object-fit: cover; border: 2px solid var(--accent-color);">
                        <div>
                            <h3 style="margin: 0; font-size: 1.1rem; color: var(--text-light); font-weight: 800;">
                                <?php echo htmlspecialchars($activeConv['first_name'] . ' ' . $activeConv['last_name']); ?>
                            </h3>
                            <p style="margin: 0; font-size: 0.82rem; color: var(--text-muted);">
                                <?php echo htmlspecialchars($activeConv['current_job_title'] ?? 'Alumnus'); ?>
                                <?php if (!empty($activeConv['current_company'])): ?>
                                    &bull; <?php echo htmlspecialchars($activeConv['current_company']); ?>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>

                    <div style="display: flex; gap: 0.6rem;">
                        <a href="profile.php?id=<?php echo $activeConv['other_user_id']; ?>" class="btn btn-outline-gold btn-sm">
                            View Profile
                        </a>
                        <button type="button" class="btn btn-outline-light btn-sm" style="color: var(--text-muted); border-color: var(--border-color);" 
                                onclick="openReportModal(<?php echo $activeConv['other_user_id']; ?>, 'message', '<?php echo htmlspecialchars(addslashes($activeConv['first_name'] . ' ' . $activeConv['last_name'])); ?>');">
                            &#9873; Report
                        </button>
                    </div>
                </div>

                <!-- Message History Container (Autoscrolled) -->
                <div id="messageContainer" style="flex: 1; overflow-y: auto; padding: 1.5rem; background: #0E0204; display: flex; flex-direction: column; gap: 1rem; max-height: 480px;">
                    <?php if (empty($messagesList)): ?>
                        <div style="text-align: center; color: var(--text-muted); margin: auto; font-size: 0.92rem;">
                            <p>No messages yet in this conversation.</p>
                            <p style="font-size: 0.85rem;">Say hello to start the dialogue!</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($messagesList as $msg): ?>
                            <?php 
                                $isMe = ($msg['sender_id'] == $currentUserId);
                            ?>
                            <div style="display: flex; flex-direction: column; align-items: <?php echo $isMe ? 'flex-end' : 'flex-start'; ?>;">
                                <div style="max-width: 70%; padding: 0.8rem 1.1rem; border-radius: 12px; font-size: 0.95rem; line-height: 1.5; word-wrap: break-word; box-shadow: 0 4px 12px rgba(0,0,0,0.3); <?php echo $isMe ? 'background: #4A1116; color: var(--text-light); border: 1px solid var(--border-gold); border-bottom-right-radius: 2px;' : 'background: #24070A; color: var(--text-color); border: 1px solid var(--border-color); border-bottom-left-radius: 2px;'; ?>">
                                    <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                                </div>
                                <span style="font-size: 0.72rem; color: var(--text-muted); margin-top: 0.3rem; padding: 0 0.3rem;">
                                    <?php echo date('h:i A', strtotime($msg['sent_at'])); ?> 
                                    <?php if ($isMe): ?>
                                        &bull; <?php echo ($msg['is_read'] == 1) ? '<span style="color: var(--accent-color);">&#10003;&#10003; Read</span>' : 'Sent'; ?>
                                    <?php endif; ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Message Input Footer -->
                <div style="padding: 1.2rem; border-top: 1px solid var(--border-color); background: var(--surface-elevated);">
                    <form method="POST" action="messages.php?conversation_id=<?php echo $activeConvId; ?>" style="display: flex; gap: 0.8rem; align-items: center;">
                        <input type="hidden" name="action" value="send_message">
                        <input type="hidden" name="conversation_id" value="<?php echo $activeConvId; ?>">
                        
                        <input type="text" name="message" id="messageInput" class="form-control" 
                                placeholder="Type a message to <?php echo htmlspecialchars($activeConv['first_name']); ?>..." 
                                autocomplete="off" required style="flex: 1;">
                        
                        <button type="submit" class="btn btn-gold gold-glow" style="padding: 0.65rem 1.5rem; flex-shrink: 0;">
                            Send &#10148;
                        </button>
                    </form>
                </div>

            <?php else: ?>
                
                <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; color: var(--text-muted); padding: 3rem; text-align: center;">
                    <div style="font-size: 3rem; margin-bottom: 1rem; color: var(--accent-color);">&#128172;</div>
                    <h3 style="color: var(--text-light); font-weight: 700; margin-bottom: 0.5rem;">Select or Start a Conversation</h3>
                    <p style="max-width: 400px; font-size: 0.92rem; line-height: 1.6;">
                        Choose an existing discussion from the left panel, or connect with batchmates directly through the Alumni Directory.
                    </p>
                    <a href="directory.php" class="btn btn-gold btn-sm" style="margin-top: 1rem;">Browse Alumni Directory</a>
                </div>

            <?php endif; ?>

        </div>

    </div>

</div>

<!-- Modal for reporting message/user -->
<div id="reportModal" class="modal-overlay">
    <div class="modal-box">
        <h3 style="color: var(--text-light); margin-bottom: 0.5rem; font-weight: 800;">Report Message or User</h3>
        <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1.2rem;">
            Reports are confidentially sent to the platform administrators for moderation.
        </p>

        <form method="POST" action="actions/report.php">
            <input type="hidden" name="reported_user_id" id="report_user_id" value="">
            
            <div class="form-group">
                <label for="report_reason">Reason for Report *</label>
                <select name="reason" id="report_reason" class="form-control" required>
                    <option value="">Select a reason...</option>
                    <option value="Spam / Commercial Solicitation">Spam / Commercial Solicitation</option>
                    <option value="Inappropriate Language or Harassment">Inappropriate Language or Harassment</option>
                    <option value="Fraud / Suspicious Activity">Fraud / Suspicious Activity</option>
                    <option value="Other Policy Violation">Other Policy Violation</option>
                </select>
            </div>

            <div class="form-group">
                <label for="report_desc">Details</label>
                <textarea name="description" id="report_desc" rows="3" class="form-control" placeholder="Describe the issue..."></textarea>
            </div>

            <div style="display: flex; gap: 0.8rem; justify-content: flex-end; margin-top: 1.5rem;">
                <button type="button" class="btn btn-outline-light" onclick="closeReportModal();">Cancel</button>
                <button type="submit" class="btn btn-gold">Submit Report</button>
            </div>
        </form>
    </div>
</div>

<script>
// Requirement 24: Message Autoscroll
document.addEventListener('DOMContentLoaded', function() {
    const messageContainer = document.getElementById('messageContainer');
    if (messageContainer) {
        messageContainer.scrollTop = messageContainer.scrollHeight;
    }
    const msgInput = document.getElementById('messageInput');
    if (msgInput) {
        msgInput.focus();
    }
});

// Client-side conversation filtering
function filterConversations() {
    const input = document.getElementById('convSearchInput').value.toLowerCase();
    const items = document.querySelectorAll('.conv-item');
    items.forEach(function(item) {
        const name = item.querySelector('.conv-name').innerText.toLowerCase();
        if (name.includes(input)) {
            item.style.display = 'flex';
        } else {
            item.style.display = 'none';
        }
    });
}

function openReportModal(userId, type, name) {
    document.getElementById('report_user_id').value = userId;
    document.getElementById('reportModal').style.display = 'flex';
}
function closeReportModal() {
    document.getElementById('reportModal').style.display = 'none';
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
