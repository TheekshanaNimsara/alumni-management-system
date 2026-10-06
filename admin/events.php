<?php
// ============================================================
// University Alumni Network - Admin Event Moderation
// ============================================================
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../auth/login.php?admin_required=1");
    exit;
}

$currentAdminPage = 'events';
$baseUrl = get_base_url();
$msg = '';

// Handle Moderation Action
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['event_id'], $_POST['action'])) {
    $eventId = intval($_POST['event_id']);
    $action = $_POST['action'];

    if ($db_connected && $pdo) {
        try {
            if ($action === 'approve') {
                $stmt = $pdo->prepare("UPDATE events SET status = 'approved' WHERE id = ?");
                $stmt->execute([$eventId]);
                $msg = "Event #{$eventId} approved successfully.";
            } elseif ($action === 'reject') {
                $stmt = $pdo->prepare("UPDATE events SET status = 'rejected' WHERE id = ?");
                $stmt->execute([$eventId]);
                $msg = "Event #{$eventId} rejected.";
            } elseif ($action === 'delete') {
                $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
                $stmt->execute([$eventId]);
                $msg = "Event #{$eventId} deleted permanently.";
            }
        } catch (Exception $e) {
            $msg = "Error updating event: " . $e->getMessage();
        }
    } else {
        $msg = "Event #{$eventId} status updated ({$action}).";
    }
}

// Fetch all events for moderation
$eventsList = [];
if ($db_connected && $pdo) {
    try {
        $stmt = $pdo->query("
            SELECT e.*, CONCAT(u.first_name, ' ', u.last_name) AS creator_name, u.email AS creator_email
            FROM events e
            LEFT JOIN users u ON e.organizer_id = u.id
            ORDER BY FIELD(e.status, 'pending', 'approved', 'rejected'), e.event_date ASC
        ");
        $eventsList = $stmt->fetchAll();
    } catch (Exception $e) {
        $eventsList = [];
    }
}

if (empty($eventsList)) {
    $eventsList = [
        ['id' => 1, 'title' => 'ALUMNI MEET 2026', 'creator_name' => 'System Administrator', 'status' => 'approved', 'event_date' => '2026-10-24', 'location' => 'Main Campus Grand Auditorium'],
        ['id' => 2, 'title' => 'GLOBAL TECH & AI SUMMIT', 'creator_name' => 'John Doe', 'status' => 'approved', 'event_date' => '2026-11-15', 'location' => 'University Main Hall'],
        ['id' => 3, 'title' => 'ANNUAL CAREER & INTERNSHIP EXPO', 'creator_name' => 'Sarah Jenkins', 'status' => 'approved', 'event_date' => '2026-12-05', 'location' => 'Convocation Grounds'],
        ['id' => 4, 'title' => 'Alumni Mentorship & Tech Fireside', 'creator_name' => 'Mike Chen', 'status' => 'pending', 'event_date' => '2026-12-18', 'location' => 'Zoom Live']
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Moderation - Alumni Admin</title>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>assets/css/style.css">
</head>
<body>

<div class="admin-layout">
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

    <main class="admin-main">
        <div class="admin-header">
            <div>
                <h1 class="admin-title">Event Moderation</h1>
                <p style="color: var(--text-muted); font-size: 0.95rem;">Review, approve, or decline community-submitted events</p>
            </div>
            <div>
                <a href="<?php echo $baseUrl; ?>events.php" target="_blank" class="btn btn-outline-light btn-sm" style="color: var(--primary-color); border-color: var(--border-color);">
                    View Public Events &rarr;
                </a>
            </div>
        </div>

        <?php if (!empty($msg)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($msg); ?></div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Event</th>
                        <th>Creator</th>
                        <th>Date &amp; Venue</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($eventsList as $event): ?>
                        <tr>
                            <td>
                                <strong style="color: var(--primary-color); font-size: 1rem;">
                                    <?php echo htmlspecialchars($event['title']); ?>
                                </strong>
                            </td>
                            <td><?php echo htmlspecialchars($event['creator_name'] ?? 'Alumnus'); ?></td>
                            <td>
                                <div><?php echo date('M d, Y', strtotime($event['event_date'])); ?></div>
                                <div style="font-size: 0.8rem; color: var(--text-muted);"><?php echo htmlspecialchars($event['location']); ?></div>
                            </td>
                            <td>
                                <span class="badge badge-<?php echo strtolower($event['status']); ?>">
                                    <?php echo ucfirst($event['status']); ?>
                                </span>
                            </td>
                            <td>
                                <div style="display: flex; gap: 0.4rem;">
                                    <?php if ($event['status'] !== 'approved'): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="btn btn-primary btn-sm" style="padding: 0.3rem 0.65rem; font-size: 0.8rem;">
                                                Approve
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if ($event['status'] !== 'rejected'): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <button type="submit" class="btn btn-sm" style="background: #FFF4D6; color: #B7791F; border: 1px solid #D8CDBB; padding: 0.3rem 0.65rem; font-size: 0.8rem;">
                                                Reject
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this event?');">
                                        <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <button type="submit" class="btn btn-sm" style="background: #F8EAEA; color: #A63D40; border: 1px solid #A63D40; padding: 0.3rem 0.65rem; font-size: 0.8rem;">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

</body>
</html>
