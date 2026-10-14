<?php
// ============================================================
// University Alumni Network - Private 1-to-1 Messaging System
// Secure Conversations, Autoscroll, Unread Tracking, Unsend & AJAX
// ============================================================
require_once __DIR__ . '/includes/functions.php';

require_login();

$currentUserId = current_user_id();
$baseUrl = get_base_url();

// ------------------------------------------------------------
// 1. AJAX & API ACTION HANDLERS (Return clean JSON without HTML)
// ------------------------------------------------------------

// A. Handle sending a new message (AJAX and Standard POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_message') {
    $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || !empty($_POST['ajax']);
    $convId = intval($_POST['conversation_id'] ?? 0);
    $messageText = trim($_POST['message'] ?? '');
    if ($convId > 0 && !empty($messageText) && $db_connected && $pdo) {
        try {
            // Security Authorization: verify current user is in this conversation
            $stmt = $pdo->prepare("SELECT user_one_id, user_two_id FROM conversations WHERE id = ? LIMIT 1");
            $stmt->execute([$convId]);
            $conv = $stmt->fetch();

            if (!$conv || ($conv['user_one_id'] != $currentUserId && $conv['user_two_id'] != $currentUserId)) {
                $errMsg = "Unauthorized conversation access.";
                if ($isAjax) {
                    header('Content-Type: application/json; charset=utf-8');
                    http_response_code(403);
                    echo json_encode(['success' => false, 'error' => $errMsg]);
                    exit;
                }
                $error = $errMsg;
            } else {
                $receiverId = ($conv['user_one_id'] == $currentUserId) ? $conv['user_two_id'] : $conv['user_one_id'];

                // Insert message with default is_unsent = 0
                $stmt = $pdo->prepare("
                    INSERT INTO messages (conversation_id, sender_id, receiver_id, message, is_read, is_unsent, sent_at)
                    VALUES (?, ?, ?, ?, 0, 0, NOW())
                ");
                $stmt->execute([$convId, $currentUserId, $receiverId, $messageText]);
                $newMsgId = (int)$pdo->lastInsertId();

                // Update conversation updated_at timestamp
                $pdo->prepare("UPDATE conversations SET updated_at = NOW() WHERE id = ?")->execute([$convId]);

                if ($isAjax) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode([
                        'success' => true,
                        'message' => [
                            'id' => $newMsgId,
                            'conversation_id' => $convId,
                            'sender_id' => $currentUserId,
                            'message' => $messageText,
                            'is_read' => 0,
                            'is_unsent' => 0,
                            'time' => date('h:i A'),
                            'is_me' => true
                        ]
                    ]);
                    exit;
                } else {
                    // PRG Pattern fallback
                    header("Location: " . $baseUrl . "messages.php?conversation_id=" . $convId);
                    exit;
                }
            }
        } catch (Exception $e) {
            $errMsg = "Failed to deliver message: " . $e->getMessage();
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => $errMsg]);
                exit;
            }
            $error = $errMsg;
        }
    } else {
        $errMsg = "Please enter a message.";
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => $errMsg]);
            exit;
        }
        $error = $errMsg;
    }
}

// B. Handle unsend message (Soft-delete with authorization check)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'unsend_message') {
    $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || !empty($_POST['ajax']);
    $msgId = intval($_POST['message_id'] ?? 0);

    if ($msgId > 0 && $db_connected && $pdo) {
        try {
            // Retrieve message and strictly verify ownership
            $stmt = $pdo->prepare("SELECT id, conversation_id, sender_id, receiver_id, is_unsent FROM messages WHERE id = ? LIMIT 1");
            $stmt->execute([$msgId]);
            $msg = $stmt->fetch();

            if (!$msg) {
                $errMsg = "Message not found.";
                if ($isAjax) {
                    header('Content-Type: application/json; charset=utf-8');
                    http_response_code(404);
                    echo json_encode(['success' => false, 'error' => $errMsg]);
                    exit;
                }
                $error = $errMsg;
            } elseif ((int)$msg['sender_id'] !== $currentUserId) {
                // Strict authorization: Cannot unsend messages sent by others
                $errMsg = "Unauthorized: You can only unsend messages you personally sent.";
                if ($isAjax) {
                    header('Content-Type: application/json; charset=utf-8');
                    http_response_code(403);
                    echo json_encode(['success' => false, 'error' => $errMsg]);
                    exit;
                }
                $error = $errMsg;
            } else {
                // Execute soft-delete if not already unsent
                if ((int)$msg['is_unsent'] !== 1) {
                    $updateStmt = $pdo->prepare("UPDATE messages SET is_unsent = 1, unsent_at = NOW() WHERE id = ? AND sender_id = ?");
                    $updateStmt->execute([$msgId, $currentUserId]);
                }

                if ($isAjax) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode([
                        'success' => true,
                        'message_id' => $msgId,
                        'conversation_id' => (int)$msg['conversation_id'],
                        'placeholder' => 'This message was unsent'
                    ]);
                    exit;
                } else {
                    header("Location: " . $baseUrl . "messages.php?conversation_id=" . (int)$msg['conversation_id']);
                    exit;
                }
            }
        } catch (Exception $e) {
            $errMsg = "Database error while unsending message: " . $e->getMessage();
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => $errMsg]);
                exit;
            }
            $error = $errMsg;
        }
    } else {
        $errMsg = "Invalid message identifier.";
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => $errMsg]);
            exit;
        }
        $error = $errMsg;
    }
}

// C. Handle dynamic conversation retrieval (AJAX GET)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_conversation') {
    $convId = intval($_GET['conversation_id'] ?? 0);
    header('Content-Type: application/json; charset=utf-8');

    if ($convId > 0 && $db_connected && $pdo) {
        try {
            // Verify access and get conversation participant details
            $stmt = $pdo->prepare("
                SELECT c.*, 
                       u.id AS other_user_id, u.first_name, u.last_name, u.email,
                       p.profile_picture, p.current_job_title, p.current_company, p.degree_programme
                FROM conversations c
                JOIN users u ON u.id = CASE WHEN c.user_one_id = ? THEN c.user_two_id ELSE c.user_one_id END
                LEFT JOIN (
                    SELECT ap.user_id, ap.profile_picture, ap.current_job_title, ap.current_company, ap.degree_programme
                    FROM alumni_profiles ap
                    INNER JOIN (SELECT MAX(id) as max_id FROM alumni_profiles GROUP BY user_id) latest ON ap.id = latest.max_id
                ) p ON p.user_id = u.id
                WHERE c.id = ? AND (c.user_one_id = ? OR c.user_two_id = ?)
                GROUP BY c.id
                LIMIT 1
            ");
            $stmt->execute([$currentUserId, $convId, $currentUserId, $currentUserId]);
            $convData = $stmt->fetch();

            if (!$convData) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Conversation not found or access denied.']);
                exit;
            }

            // Mark unread messages as read
            $pdo->prepare("UPDATE messages SET is_read = 1 WHERE conversation_id = ? AND receiver_id = ?")
                ->execute([$convId, $currentUserId]);

            // Retrieve conversation messages
            $stmt = $pdo->prepare("
                SELECT m.*, u.first_name, u.last_name, p.profile_picture
                FROM messages m
                JOIN users u ON m.sender_id = u.id
                LEFT JOIN (
                    SELECT ap.user_id, ap.profile_picture
                    FROM alumni_profiles ap
                    INNER JOIN (SELECT MAX(id) as max_id FROM alumni_profiles GROUP BY user_id) latest ON ap.id = latest.max_id
                ) p ON p.user_id = u.id
                WHERE m.conversation_id = ?
                GROUP BY m.id
                ORDER BY m.id ASC
            ");
            $stmt->execute([$convId]);
            $rawMessages = $stmt->fetchAll();

            $formatted = [];
            foreach ($rawMessages as $m) {
                $formatted[] = [
                    'id' => (int)$m['id'],
                    'conversation_id' => (int)$m['conversation_id'],
                    'sender_id' => (int)$m['sender_id'],
                    'is_me' => ((int)$m['sender_id'] === $currentUserId),
                    'message' => $m['message'],
                    'is_read' => (int)$m['is_read'],
                    'is_unsent' => (int)($m['is_unsent'] ?? 0),
                    'sent_at' => $m['sent_at'],
                    'time' => date('h:i A', strtotime($m['sent_at']))
                ];
            }

            $subtitleParts = [];
            if (!empty($convData['current_job_title'])) {
                $subtitleParts[] = $convData['current_job_title'];
            } else {
                $subtitleParts[] = 'Alumnus';
            }
            if (!empty($convData['current_company'])) {
                $subtitleParts[] = $convData['current_company'];
            }

            echo json_encode([
                'success' => true,
                'conversation_id' => $convId,
                'user' => [
                    'id' => (int)$convData['other_user_id'],
                    'name' => $convData['first_name'] . ' ' . $convData['last_name'],
                    'avatar' => get_user_avatar_url($convData['profile_picture'] ?? 'default-avatar.svg'),
                    'subtitle' => implode(' • ', $subtitleParts),
                    'profile_url' => 'profile.php?id=' . (int)$convData['other_user_id']
                ],
                'messages' => $formatted
            ]);
            exit;
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Database error loading conversation.']);
            exit;
        }
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid conversation request.']);
        exit;
    }
}

// ------------------------------------------------------------
// 2. STANDARD FULL-PAGE RENDERING SETUP
// ------------------------------------------------------------
$pageTitle = 'Alumni Messages';
$currentPage = 'messages';
require_once __DIR__ . '/includes/header.php';

$activeConvId = isset($_GET['conversation_id']) ? intval($_GET['conversation_id']) : 0;
$targetUserId = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$error = $error ?? '';
$success = $success ?? '';

// If a target user_id is provided, find or create the conversation
if ($targetUserId > 0 && $targetUserId !== $currentUserId && $db_connected && $pdo) {
    try {
        $u1 = min($currentUserId, $targetUserId);
        $u2 = max($currentUserId, $targetUserId);

        $stmt = $pdo->prepare("SELECT id FROM conversations WHERE user_one_id = ? AND user_two_id = ? LIMIT 1");
        $stmt->execute([$u1, $u2]);
        $existing = $stmt->fetch();

        if ($existing) {
            $activeConvId = (int)$existing['id'];
        } else {
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

// Fetch conversation list for current user
$conversations = [];
if ($db_connected && $pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT c.id, c.user_one_id, c.user_two_id, c.updated_at,
                   u.id AS other_user_id, u.first_name, u.last_name, u.role,
                   p.profile_picture, p.current_job_title, p.current_company,
                   (
                       SELECT CASE 
                           WHEN m.is_unsent = 1 THEN 'This message was unsent' 
                           ELSE m.message 
                       END
                       FROM messages m 
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
                       WHERE m.conversation_id = c.id AND m.receiver_id = ? AND m.is_read = 0 AND m.is_unsent = 0
                   ) AS unread_count
            FROM conversations c
            JOIN users u ON u.id = CASE WHEN c.user_one_id = ? THEN c.user_two_id ELSE c.user_one_id END
            LEFT JOIN (
                SELECT ap.user_id, ap.profile_picture, ap.current_job_title, ap.current_company
                FROM alumni_profiles ap
                INNER JOIN (SELECT MAX(id) as max_id FROM alumni_profiles GROUP BY user_id) latest ON ap.id = latest.max_id
            ) p ON p.user_id = u.id
            WHERE c.user_one_id = ? OR c.user_two_id = ?
            GROUP BY c.id
            ORDER BY c.updated_at DESC
        ");
        $stmt->execute([$currentUserId, $currentUserId, $currentUserId, $currentUserId]);
        $conversations = $stmt->fetchAll();
    } catch (Exception $e) {
        $conversations = [];
    }
}

// Default to the first conversation if none specified
if ($activeConvId === 0 && !empty($conversations)) {
    $activeConvId = (int)$conversations[0]['id'];
}

// Fetch active conversation details and messages for initial HTML load
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
            LEFT JOIN (
                SELECT ap.user_id, ap.profile_picture, ap.current_job_title, ap.current_company, ap.degree_programme
                FROM alumni_profiles ap
                INNER JOIN (SELECT MAX(id) as max_id FROM alumni_profiles GROUP BY user_id) latest ON ap.id = latest.max_id
            ) p ON p.user_id = u.id
            WHERE c.id = ? AND (c.user_one_id = ? OR c.user_two_id = ?)
            GROUP BY c.id
            LIMIT 1
        ");
        $stmt->execute([$currentUserId, $activeConvId, $currentUserId, $currentUserId]);
        $activeConv = $stmt->fetch();

        if ($activeConv) {
            // Mark unread as read
            $stmt = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE conversation_id = ? AND receiver_id = ?");
            $stmt->execute([$activeConvId, $currentUserId]);

            // Fetch messages in chronological order
            $stmt = $pdo->prepare("
                SELECT m.*, u.first_name, u.last_name, p.profile_picture
                FROM messages m
                JOIN users u ON m.sender_id = u.id
                LEFT JOIN (
                    SELECT ap.user_id, ap.profile_picture
                    FROM alumni_profiles ap
                    INNER JOIN (SELECT MAX(id) as max_id FROM alumni_profiles GROUP BY user_id) latest ON ap.id = latest.max_id
                ) p ON p.user_id = u.id
                WHERE m.conversation_id = ?
                GROUP BY m.id
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

<style>
/* Discrete Unsend Control & Inline Composer Loader */
.btn-msg-unsend {
    background: rgba(232, 199, 126, 0.12);
    border: 1px solid rgba(232, 199, 126, 0.25);
    color: #F5DDA0;
    font-size: 0.70rem;
    padding: 0.12rem 0.45rem;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    line-height: 1.2;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
}
.btn-msg-unsend:hover {
    background: rgba(239, 68, 68, 0.25);
    border-color: rgba(239, 68, 68, 0.45);
    color: #FCA5A5;
    transform: translateY(-1px);
}
.composer-spinner {
    display: inline-block;
    width: 14px;
    height: 14px;
    border: 2px solid rgba(255, 255, 255, 0.35);
    border-top-color: #FFFFFF;
    border-radius: 50%;
    animation: composerSpin 0.75s linear infinite;
    vertical-align: middle;
    margin-right: 0.4rem;
}
@keyframes composerSpin {
    to { transform: rotate(360deg); }
}
.conv-skeleton-spinner {
    width: 32px;
    height: 32px;
    border: 3px solid rgba(232, 199, 126, 0.2);
    border-top-color: #E8C77E;
    border-radius: 50%;
    animation: convSpin 0.8s linear infinite;
}
@keyframes convSpin {
    to { transform: rotate(360deg); }
}
.msg-bubble {
    word-wrap: break-word;
    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
    transition: all 0.25s ease;
}
.msg-bubble-unsent {
    background: rgba(28, 23, 26, 0.6) !important;
    color: var(--text-muted) !important;
    border: 1px dashed rgba(232, 199, 126, 0.25) !important;
}
</style>

<div class="container" style="padding-top: 2.5rem; padding-bottom: 4rem;">
    
    <div style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div>
            <span class="section-eyebrow">Direct Alumni Communications</span>
            <h1 class="section-title" style="color: var(--text-light); font-size: 2rem; margin-bottom: 0.3rem;">
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
        <div class="alert alert-danger" id="globalErrorAlert"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <!-- Messaging Workspace: Two-column layout -->
    <div class="card" style="padding: 0; overflow: hidden; display: flex; min-height: 600px; box-shadow: var(--card-shadow); border-top: 3px solid var(--accent-color);">

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
                        <a href="directory.php" style="color: var(--accent-light); font-weight: 700;">Find alumni</a> to start messaging.
                    </div>
                <?php else: ?>
                    <?php foreach ($conversations as $c): ?>
                        <?php 
                            $isActive = ($activeConvId === (int)$c['id']);
                            $otherAvatar = get_user_avatar_url($c['profile_picture'] ?? 'default-avatar.svg');
                            $unread = (int)($c['unread_count'] ?? 0);
                        ?>
                        <a href="messages.php?conversation_id=<?php echo $c['id']; ?>" 
                           class="conv-item <?php echo $isActive ? 'active' : ''; ?>" 
                           data-conv-id="<?php echo $c['id']; ?>"
                           data-no-loader="true"
                           style="display: flex; align-items: center; gap: 0.9rem; padding: 1rem 1.2rem; text-decoration: none; border-bottom: 1px solid var(--border-color); transition: background 0.2s; <?php echo $isActive ? 'background: rgba(232, 199, 126, 0.15); border-left: 4px solid var(--accent-color);' : 'background: transparent;'; ?>"
                           onmouseover="if (!this.classList.contains('active')) this.style.background='rgba(232, 199, 126, 0.08)';"
                           onmouseout="if (!this.classList.contains('active')) this.style.background='transparent';">
                            
                            <img src="<?php echo $otherAvatar; ?>" alt="Avatar" style="width: 44px; height: 44px; border-radius: 50%; object-fit: cover; border: 2px solid var(--accent-color); flex-shrink: 0;">
                            
                            <div style="flex: 1; min-width: 0;">
                                <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 0.2rem;">
                                    <strong class="conv-name" style="color: var(--text-light); font-size: 0.95rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                        <?php echo htmlspecialchars($c['first_name'] . ' ' . $c['last_name']); ?>
                                    </strong>
                                    <?php if (!empty($c['last_message_time'])): ?>
                                        <span class="conv-time" style="font-size: 0.75rem; color: var(--text-muted); flex-shrink: 0;">
                                            <?php echo time_ago($c['last_message_time']); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <p class="conv-preview" style="margin: 0; font-size: 0.83rem; color: var(--text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                        <?php echo htmlspecialchars($c['last_message'] ?? 'Started a conversation'); ?>
                                    </p>
                                    <?php if ($unread > 0): ?>
                                        <span class="badge" style="background: var(--accent-color); color: #FFFFFF; font-weight: 700; font-size: 0.72rem; padding: 0.2rem 0.5rem; border-radius: 10px; margin-left: 0.5rem;">
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
        <div id="chatPaneContainer" style="flex: 1; display: flex; flex-direction: column; background: var(--surface-color);">
            
            <?php if ($activeConv): ?>
                
                <!-- Chat Window Header -->
                <div id="chatWindowHeader" style="padding: 1rem 1.5rem; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: var(--surface-elevated);">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <img id="chatHeaderAvatar" src="<?php echo get_user_avatar_url($activeConv['profile_picture'] ?? 'default-avatar.svg'); ?>" 
                             alt="Avatar" style="width: 48px; height: 48px; border-radius: 50%; object-fit: cover; border: 2px solid var(--accent-color);">
                        <div>
                            <h3 id="chatHeaderName" style="margin: 0; font-size: 1.1rem; color: var(--text-light); font-weight: 800;">
                                <?php echo htmlspecialchars($activeConv['first_name'] . ' ' . $activeConv['last_name']); ?>
                            </h3>
                            <p id="chatHeaderSubtitle" style="margin: 0; font-size: 0.82rem; color: var(--text-muted);">
                                <?php echo htmlspecialchars($activeConv['current_job_title'] ?? 'Alumnus'); ?>
                                <?php if (!empty($activeConv['current_company'])): ?>
                                    &bull; <?php echo htmlspecialchars($activeConv['current_company']); ?>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>

                    <div style="display: flex; gap: 0.6rem;">
                        <a id="chatHeaderProfileLink" href="profile.php?id=<?php echo $activeConv['other_user_id']; ?>" class="btn btn-outline-gold btn-sm">
                            View Profile
                        </a>
                        <button type="button" id="chatHeaderReportBtn" class="btn btn-outline-light btn-sm" style="color: var(--text-muted); border-color: var(--border-color);" 
                                onclick="openReportModal(<?php echo $activeConv['other_user_id']; ?>, 'message', '<?php echo htmlspecialchars(addslashes($activeConv['first_name'] . ' ' . $activeConv['last_name'])); ?>');">
                            &#9873; Report
                        </button>
                    </div>
                </div>

                <!-- Message History Container (Autoscrolled) -->
                <div id="messageContainer" style="flex: 1; overflow-y: auto; padding: 1.5rem; background: var(--surface-dark, #120E11); display: flex; flex-direction: column; gap: 1rem; max-height: 480px;">
                    <?php if (empty($messagesList)): ?>
                        <div id="emptyChatPlaceholder" style="text-align: center; color: var(--text-muted); margin: auto; font-size: 0.92rem;">
                            <p>No messages yet in this conversation.</p>
                            <p style="font-size: 0.85rem;">Say hello to start the dialogue!</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($messagesList as $msg): ?>
                            <?php 
                                $isMe = ($msg['sender_id'] == $currentUserId);
                                $isUnsent = !empty($msg['is_unsent']);
                            ?>
                            <div id="msg-row-<?php echo $msg['id']; ?>" class="msg-row <?php echo $isMe ? 'msg-row-me' : 'msg-row-other'; ?>" style="display: flex; flex-direction: column; align-items: <?php echo $isMe ? 'flex-end' : 'flex-start'; ?>;">
                                <?php if ($isUnsent): ?>
                                    <div class="msg-bubble msg-bubble-unsent" style="max-width: 70%; padding: 0.75rem 1.1rem; border-radius: 12px; font-size: 0.92rem; line-height: 1.5; background: rgba(28, 23, 26, 0.6); color: var(--text-muted); border: 1px dashed rgba(232, 199, 126, 0.25); border-bottom-<?php echo $isMe ? 'right' : 'left'; ?>-radius: 2px;">
                                        <em style="font-style: italic; opacity: 0.85;">This message was unsent</em>
                                    </div>
                                    <div class="msg-meta-actions" style="margin-top: 0.3rem; padding: 0 0.3rem;">
                                        <span class="msg-time" style="font-size: 0.72rem; color: var(--text-muted);">
                                            <?php echo date('h:i A', strtotime($msg['sent_at'])); ?> &bull; Unsent
                                        </span>
                                    </div>
                                <?php else: ?>
                                    <div class="msg-bubble <?php echo $isMe ? 'msg-bubble-me' : 'msg-bubble-other'; ?>" style="max-width: 70%; padding: 0.8rem 1.1rem; border-radius: 12px; font-size: 0.95rem; line-height: 1.5; word-wrap: break-word; box-shadow: 0 4px 12px rgba(0,0,0,0.3); <?php echo $isMe ? 'background: linear-gradient(135deg, #762D48, #451629); color: #F6EDF1; border: 1px solid rgba(232, 199, 126, 0.35); border-bottom-right-radius: 2px;' : 'background: #1D1620; color: var(--text-color); border: 1px solid var(--border-color); border-bottom-left-radius: 2px;'; ?>">
                                        <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                                    </div>
                                    <div class="msg-meta-actions" style="display: flex; align-items: center; gap: 0.5rem; margin-top: 0.3rem; padding: 0 0.3rem;">
                                        <span class="msg-time" style="font-size: 0.72rem; color: var(--text-muted);">
                                            <?php echo date('h:i A', strtotime($msg['sent_at'])); ?> 
                                            <?php if ($isMe): ?>
                                                &bull; <?php echo ($msg['is_read'] == 1) ? '<span style="color: var(--accent-light); font-weight: 700;">&#10003;&#10003; Read</span>' : 'Sent'; ?>
                                            <?php endif; ?>
                                        </span>
                                        <?php if ($isMe): ?>
                                            <button type="button" class="btn-msg-unsend" onclick="unsendMessage(<?php echo (int)$msg['id']; ?>);" title="Unsend message" aria-label="Unsend message">
                                                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: -1px; margin-right: 2px;"><path d="M3 6h18"></path><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                                Unsend
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Message Input Footer -->
                <div style="padding: 1.2rem; border-top: 1px solid var(--border-color); background: var(--surface-elevated);">
                    <div id="composerErrorAlert" style="display: none; padding: 0.55rem 0.9rem; margin-bottom: 0.75rem; border-radius: 8px; font-size: 0.85rem; background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.35); color: #FCA5A5;"></div>
                    
                    <form method="POST" action="messages.php?conversation_id=<?php echo $activeConvId; ?>" id="messageSendForm" style="display: flex; gap: 0.8rem; align-items: center;">
                        <input type="hidden" name="action" value="send_message">
                        <input type="hidden" name="conversation_id" value="<?php echo $activeConvId; ?>">
                        
                        <input type="text" name="message" id="messageInput" class="form-control" 
                                placeholder="Type a message to <?php echo htmlspecialchars($activeConv['first_name']); ?>..." 
                                autocomplete="off" required style="flex: 1;">
                        
                        <button type="submit" id="btnSendMessage" class="btn btn-gold gold-glow" style="padding: 0.65rem 1.5rem; flex-shrink: 0;">
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
// State management
let currentActiveConvId = <?php echo (int)$activeConvId; ?>;
const currentUserId = <?php echo (int)$currentUserId; ?>;
let isSending = false;
let isLoadingConv = false;

// Initial Focus & Scroll
document.addEventListener('DOMContentLoaded', function() {
    scrollChatToBottom();
    const msgInput = document.getElementById('messageInput');
    if (msgInput) msgInput.focus();
});

function scrollChatToBottom() {
    const messageContainer = document.getElementById('messageContainer');
    if (messageContainer) {
        messageContainer.scrollTop = messageContainer.scrollHeight;
    }
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function escapeJs(text) {
    if (!text) return '';
    return text.replace(/\\/g, '\\\\').replace(/'/g, "\\'").replace(/"/g, '\\"');
}

function showComposerError(msg) {
    const errEl = document.getElementById('composerErrorAlert');
    if (errEl) {
        errEl.textContent = msg;
        errEl.style.display = 'block';
        clearTimeout(window._composerErrTimer);
        window._composerErrTimer = setTimeout(hideComposerError, 5000);
    }
}

function hideComposerError() {
    const errEl = document.getElementById('composerErrorAlert');
    if (errEl) errEl.style.display = 'none';
}

function updateSidebarLastMessage(text) {
    const activeItem = document.querySelector(`.conv-item[data-conv-id="${currentActiveConvId}"]`);
    if (activeItem) {
        const preview = activeItem.querySelector('.conv-preview');
        if (preview) preview.textContent = text;
        const timeEl = activeItem.querySelector('.conv-time');
        if (timeEl) timeEl.textContent = 'Just now';
    }
}

// ------------------------------------------------------------
// 1. INLINE MESSAGE SENDING (NO FULL-PAGE RELOAD & NO GLOBAL LOADER)
// ------------------------------------------------------------
const sendForm = document.getElementById('messageSendForm');
const sendBtn = document.getElementById('btnSendMessage');
const msgInput = document.getElementById('messageInput');

if (sendForm && sendBtn && msgInput) {
    sendForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const text = msgInput.value.trim();
        if (!text || isSending) return;

        isSending = true;
        const originalBtnHtml = sendBtn.innerHTML;
        sendBtn.disabled = true;
        sendBtn.innerHTML = '<span class="composer-spinner"></span> Sending...';
        hideComposerError();

        const formData = new FormData(sendForm);
        formData.append('ajax', '1');

        fetch(sendForm.getAttribute('action') || window.location.href, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        })
        .then(res => {
            if (!res.ok) throw new Error('HTTP ' + res.status);
            return res.json();
        })
        .then(data => {
            if (data && data.success && data.message) {
                msgInput.value = '';
                appendMessageToChat(data.message);
                updateSidebarLastMessage(data.message.message);
                scrollChatToBottom();
            } else {
                showComposerError(data && data.error ? data.error : 'Failed to deliver message.');
            }
        })
        .catch(err => {
            showComposerError('Network or server error delivering message. Please try again.');
        })
        .finally(() => {
            sendBtn.disabled = false;
            sendBtn.innerHTML = originalBtnHtml;
            isSending = false;
            msgInput.focus();
        });
    });
}

function appendMessageToChat(msg) {
    const container = document.getElementById('messageContainer');
    if (!container) return;

    // Remove empty placeholder if present
    const emptyPlaceholder = document.getElementById('emptyChatPlaceholder');
    if (emptyPlaceholder) emptyPlaceholder.remove();

    const row = document.createElement('div');
    row.id = `msg-row-${msg.id}`;
    row.className = 'msg-row msg-row-me';
    row.style.cssText = 'display: flex; flex-direction: column; align-items: flex-end;';

    row.innerHTML = `
        <div class="msg-bubble msg-bubble-me" style="max-width: 70%; padding: 0.8rem 1.1rem; border-radius: 12px; font-size: 0.95rem; line-height: 1.5; word-wrap: break-word; box-shadow: 0 4px 12px rgba(0,0,0,0.3); background: linear-gradient(135deg, #762D48, #451629); color: #F6EDF1; border: 1px solid rgba(232, 199, 126, 0.35); border-bottom-right-radius: 2px;">
            ${escapeHtml(msg.message).replace(/\n/g, '<br>')}
        </div>
        <div class="msg-meta-actions" style="display: flex; align-items: center; gap: 0.5rem; margin-top: 0.3rem; padding: 0 0.3rem;">
            <span class="msg-time" style="font-size: 0.72rem; color: var(--text-muted);">
                ${escapeHtml(msg.time || 'Just now')} &bull; Sent
            </span>
            <button type="button" class="btn-msg-unsend" onclick="unsendMessage(${msg.id});" title="Unsend message" aria-label="Unsend message">
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: -1px; margin-right: 2px;"><path d="M3 6h18"></path><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                Unsend
            </button>
        </div>
    `;

    container.appendChild(row);
}

// ------------------------------------------------------------
// 2. SECURE MESSAGE UNSEND (SOFT DELETE WITH CLIENT-SIDE REPLACEMENT)
// ------------------------------------------------------------
function unsendMessage(msgId) {
    if (!msgId) return;
    if (!confirm('Unsend this message for everyone in this conversation?')) {
        return;
    }

    const formData = new FormData();
    formData.append('action', 'unsend_message');
    formData.append('message_id', msgId);
    formData.append('ajax', '1');

    fetch('messages.php', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    })
    .then(res => {
        if (!res.ok) throw new Error('HTTP ' + res.status);
        return res.json();
    })
    .then(data => {
        if (data && data.success) {
            const row = document.getElementById(`msg-row-${msgId}`);
            if (row) {
                const bubble = row.querySelector('.msg-bubble');
                if (bubble) {
                    bubble.className = 'msg-bubble msg-bubble-unsent';
                    bubble.style.cssText = 'max-width: 70%; padding: 0.75rem 1.1rem; border-radius: 12px; font-size: 0.92rem; line-height: 1.5; background: rgba(28, 23, 26, 0.6); color: var(--text-muted); border: 1px dashed rgba(232, 199, 126, 0.25); border-bottom-right-radius: 2px;';
                    bubble.innerHTML = '<em style="font-style: italic; opacity: 0.85;">This message was unsent</em>';
                }
                const meta = row.querySelector('.msg-meta-actions');
                if (meta) {
                    const timeEl = meta.querySelector('.msg-time');
                    const timeText = timeEl ? timeEl.textContent.split('•')[0].trim() : 'Sent';
                    meta.innerHTML = `<span class="msg-time" style="font-size: 0.72rem; color: var(--text-muted);">${escapeHtml(timeText)} &bull; Unsent</span>`;
                }
            }
            updateSidebarLastMessage('This message was unsent');
        } else {
            alert(data && data.error ? data.error : 'Could not unsend message.');
        }
    })
    .catch(err => {
        alert('Network or server error attempting to unsend message.');
    });
}

// ------------------------------------------------------------
// 3. CONVERSATION SWITCHING VIA AJAX (NO FULL-PAGE RELOAD & SKELETON LOADER)
// ------------------------------------------------------------
document.querySelectorAll('.conv-item').forEach(function(item) {
    item.addEventListener('click', function(e) {
        if (e.ctrlKey || e.metaKey || e.button !== 0) return;
        e.preventDefault();
        const convId = parseInt(this.getAttribute('data-conv-id'), 10);
        if (!convId || convId === currentActiveConvId) return;
        loadConversation(convId);
    });
});

function loadConversation(convId) {
    if (!convId || isLoadingConv) return;
    isLoadingConv = true;
    currentActiveConvId = convId;

    // Update active sidebar selection
    document.querySelectorAll('.conv-item').forEach(el => {
        const id = parseInt(el.getAttribute('data-conv-id'), 10);
        if (id === convId) {
            el.classList.add('active');
            el.style.background = 'rgba(232, 199, 126, 0.15)';
            el.style.borderLeft = '4px solid var(--accent-color)';
            const unreadBadge = el.querySelector('.badge');
            if (unreadBadge) unreadBadge.remove();
        } else {
            el.classList.remove('active');
            el.style.background = 'transparent';
            el.style.borderLeft = 'none';
        }
    });

    // Clear previous conversation immediately & show skeleton loader
    const messageContainer = document.getElementById('messageContainer');
    if (messageContainer) {
        messageContainer.innerHTML = `
            <div id="convLoadingState" style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; margin: auto; padding: 3rem 1rem; color: var(--text-muted);">
                <div class="conv-skeleton-spinner"></div>
                <p style="margin-top: 1rem; font-size: 0.9rem;">Loading discussion...</p>
            </div>
        `;
    }

    fetch(`messages.php?action=get_conversation&conversation_id=${convId}&ajax=1`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(res => {
        if (!res.ok) throw new Error('HTTP ' + res.status);
        return res.json();
    })
    .then(data => {
        if (data && data.success) {
            // Update Header Information
            const avatar = document.getElementById('chatHeaderAvatar');
            const name = document.getElementById('chatHeaderName');
            const sub = document.getElementById('chatHeaderSubtitle');
            const profLink = document.getElementById('chatHeaderProfileLink');
            const reportBtn = document.getElementById('chatHeaderReportBtn');

            if (avatar) avatar.src = data.user.avatar;
            if (name) name.textContent = data.user.name;
            if (sub) sub.textContent = data.user.subtitle;
            if (profLink) profLink.href = data.user.profile_url;
            if (reportBtn) {
                reportBtn.setAttribute('onclick', `openReportModal(${data.user.id}, 'message', '${escapeJs(data.user.name)}');`);
            }

            // Update Form Action & Hidden Input
            const hiddenConv = document.querySelector('#messageSendForm input[name="conversation_id"]');
            if (hiddenConv) hiddenConv.value = convId;
            const form = document.getElementById('messageSendForm');
            if (form) form.action = `messages.php?conversation_id=${convId}`;
            if (msgInput) msgInput.placeholder = `Type a message to ${data.user.name.split(' ')[0]}...`;

            // Update Browser URL cleanly
            try {
                window.history.pushState(null, '', `messages.php?conversation_id=${convId}`);
            } catch(e) {}

            // Render Messages
            renderConversationMessages(data.messages);
            scrollChatToBottom();
            if (msgInput) msgInput.focus();
        } else {
            showConversationLoadError(data && data.error ? data.error : 'Could not load conversation.', convId);
        }
    })
    .catch(err => {
        showConversationLoadError('Network error loading discussion. Please check your connection.', convId);
    })
    .finally(() => {
        isLoadingConv = false;
    });
}

function renderConversationMessages(messages) {
    const container = document.getElementById('messageContainer');
    if (!container) return;

    if (!messages || messages.length === 0) {
        container.innerHTML = `
            <div id="emptyChatPlaceholder" style="text-align: center; color: var(--text-muted); margin: auto; font-size: 0.92rem;">
                <p>No messages yet in this conversation.</p>
                <p style="font-size: 0.85rem;">Say hello to start the dialogue!</p>
            </div>
        `;
        return;
    }

    let html = '';
    messages.forEach(msg => {
        const isMe = (msg.is_me === true || msg.sender_id === currentUserId);
        const isUnsent = (msg.is_unsent === 1 || msg.is_unsent === '1');

        if (isUnsent) {
            html += `
                <div id="msg-row-${msg.id}" class="msg-row ${isMe ? 'msg-row-me' : 'msg-row-other'}" style="display: flex; flex-direction: column; align-items: ${isMe ? 'flex-end' : 'flex-start'};">
                    <div class="msg-bubble msg-bubble-unsent" style="max-width: 70%; padding: 0.75rem 1.1rem; border-radius: 12px; font-size: 0.92rem; line-height: 1.5; background: rgba(28, 23, 26, 0.6); color: var(--text-muted); border: 1px dashed rgba(232, 199, 126, 0.25); border-bottom-${isMe ? 'right' : 'left'}-radius: 2px;">
                        <em style="font-style: italic; opacity: 0.85;">This message was unsent</em>
                    </div>
                    <div class="msg-meta-actions" style="margin-top: 0.3rem; padding: 0 0.3rem;">
                        <span class="msg-time" style="font-size: 0.72rem; color: var(--text-muted);">
                            ${escapeHtml(msg.time)} &bull; Unsent
                        </span>
                    </div>
                </div>
            `;
        } else {
            html += `
                <div id="msg-row-${msg.id}" class="msg-row ${isMe ? 'msg-row-me' : 'msg-row-other'}" style="display: flex; flex-direction: column; align-items: ${isMe ? 'flex-end' : 'flex-start'};">
                    <div class="msg-bubble ${isMe ? 'msg-bubble-me' : 'msg-bubble-other'}" style="max-width: 70%; padding: 0.8rem 1.1rem; border-radius: 12px; font-size: 0.95rem; line-height: 1.5; word-wrap: break-word; box-shadow: 0 4px 12px rgba(0,0,0,0.3); ${isMe ? 'background: linear-gradient(135deg, #762D48, #451629); color: #F6EDF1; border: 1px solid rgba(232, 199, 126, 0.35); border-bottom-right-radius: 2px;' : 'background: #1D1620; color: var(--text-color); border: 1px solid var(--border-color); border-bottom-left-radius: 2px;'}`">
                        ${escapeHtml(msg.message).replace(/\n/g, '<br>')}
                    </div>
                    <div class="msg-meta-actions" style="display: flex; align-items: center; gap: 0.5rem; margin-top: 0.3rem; padding: 0 0.3rem;">
                        <span class="msg-time" style="font-size: 0.72rem; color: var(--text-muted);">
                            ${escapeHtml(msg.time)} 
                            ${isMe ? `&bull; ${msg.is_read == 1 ? '<span style="color: var(--accent-light); font-weight: 700;">&#10003;&#10003; Read</span>' : 'Sent'}` : ''}
                        </span>
                        ${isMe ? `
                            <button type="button" class="btn-msg-unsend" onclick="unsendMessage(${msg.id});" title="Unsend message" aria-label="Unsend message">
                                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: -1px; margin-right: 2px;"><path d="M3 6h18"></path><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                Unsend
                            </button>
                        ` : ''}
                    </div>
                </div>
            `;
        }
    });

    container.innerHTML = html;
}

function showConversationLoadError(errText, convId) {
    const container = document.getElementById('messageContainer');
    if (!container) return;
    container.innerHTML = `
        <div style="text-align: center; color: var(--text-muted); margin: auto; padding: 2rem 1rem;">
            <p style="font-size: 2rem; margin-bottom: 0.5rem; color: #EF4444;">&#9888;</p>
            <h4 style="color: var(--text-light); margin-bottom: 0.3rem;">Could not load discussion</h4>
            <p style="font-size: 0.88rem; max-width: 320px; margin: 0 auto 1.2rem;">${escapeHtml(errText)}</p>
            <button type="button" class="btn btn-outline-light btn-sm" onclick="loadConversation(${convId});">
                Retry Loading
            </button>
        </div>
    `;
}

// ------------------------------------------------------------
// 4. CLIENT-SIDE SEARCH & REPORT MODAL
// ------------------------------------------------------------
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
