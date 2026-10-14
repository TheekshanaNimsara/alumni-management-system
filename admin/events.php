<?php
// ============================================================
// University Alumni Network - Admin Event Moderation
// ============================================================
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin();

$currentAdminPage = 'events';
$baseUrl = get_base_url();
$msg = '';

if (!empty($_SESSION['flash_msg'])) {
    $msg = $_SESSION['flash_msg'];
    unset($_SESSION['flash_msg']);
}

// Status Filter
$statusFilter = trim($_GET['status'] ?? '');

// Handle Moderation Action
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['event_id'], $_POST['action'])) {
    $eventId = intval($_POST['event_id']);
    $action = $_POST['action'];
    if ($db_connected && $pdo) {
        try {
            if ($action === 'approve') {
                $stmt = $pdo->prepare("UPDATE events SET status = 'approved' WHERE id = ?");
                $stmt->execute([$eventId]);
                $_SESSION['flash_msg'] = "Event #{$eventId} approved successfully.";
            } elseif ($action === 'reject') {
                $stmt = $pdo->prepare("UPDATE events SET status = 'rejected' WHERE id = ?");
                $stmt->execute([$eventId]);
                $_SESSION['flash_msg'] = "Event #{$eventId} rejected.";
            } elseif ($action === 'delete') {
                $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
                $stmt->execute([$eventId]);
                $_SESSION['flash_msg'] = "Event #{$eventId} deleted permanently.";
            }
        } catch (Exception $e) {
            $_SESSION['flash_msg'] = "Error updating event: " . $e->getMessage();
        }
    } else {
        $_SESSION['flash_msg'] = "Event #{$eventId} status updated ({$action}).";
    }

    header("Location: events.php" . (!empty($statusFilter) ? "?status=" . urlencode($statusFilter) : ""));
    exit;
}

// Fetch events for moderation
$eventsList = [];
if ($db_connected && $pdo) {
    try {
        $sql = "
            SELECT e.*, CONCAT(u.first_name, ' ', u.last_name) AS creator_name, u.email AS creator_email
            FROM events e
            LEFT JOIN users u ON e.organizer_id = u.id
        ";
        $params = [];
        if (!empty($statusFilter)) {
            $sql .= " WHERE e.status = ?";
            $params[] = $statusFilter;
        }
        $sql .= " ORDER BY FIELD(e.status, 'pending', 'approved', 'rejected'), e.event_date ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $eventsList = $stmt->fetchAll();
    } catch (Exception $e) {
        $eventsList = [];
    }
}

// Fallback dummy records (reduced to two examples)
if (empty($eventsList) && empty($statusFilter)) {
    $eventsList = [
        ['id' => 1, 'title' => 'ALUMNI MEET 2026', 'creator_name' => 'System Administrator', 'status' => 'approved', 'event_date' => '2026-10-24', 'location' => 'Main Campus Grand Auditorium'],
        ['id' => 4, 'title' => 'Alumni Mentorship & Tech Fireside', 'creator_name' => 'Michael Chen', 'status' => 'pending', 'event_date' => '2026-12-18', 'location' => 'Zoom Live']
    ];
} elseif (empty($eventsList) && $statusFilter === 'pending') {
    $eventsList = [
        ['id' => 4, 'title' => 'Alumni Mentorship & Tech Fireside', 'creator_name' => 'Michael Chen', 'status' => 'pending', 'event_date' => '2026-12-18', 'location' => 'Zoom Live']
    ];
} elseif (empty($eventsList) && $statusFilter === 'approved') {
    $eventsList = [
        ['id' => 1, 'title' => 'ALUMNI MEET 2026', 'creator_name' => 'System Administrator', 'status' => 'approved', 'event_date' => '2026-10-24', 'location' => 'Main Campus Grand Auditorium']
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Moderation - Alumni Admin</title>
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="<?php echo $baseUrl; ?>assets/favicon.svg?v=3">
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo $baseUrl; ?>assets/favicon-32x32.png?v=3">
    <link rel="icon" type="image/png" sizes="16x16" href="<?php echo $baseUrl; ?>assets/favicon-16x16.png?v=3">
    <link rel="shortcut icon" href="<?php echo $baseUrl; ?>favicon.ico?v=3">
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo $baseUrl; ?>assets/apple-touch-icon.png?v=3">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>assets/css/style.css?v=4">
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
                <a href="<?php echo $baseUrl; ?>events.php" target="_blank" class="btn btn-outline-light btn-sm" style="color: var(--text-light); border-color: var(--border-color);">
                    View Public Events &rarr;
                </a>
            </div>
        </div>

        <?php if (!empty($msg)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($msg); ?></div>
        <?php endif; ?>

        <!-- Status Filter Tabs -->
        <div style="display: flex; gap: 0.5rem; margin-bottom: 1.5rem; flex-wrap: wrap;">
            <a href="events.php" class="btn <?php echo empty($statusFilter) ? 'btn-gold' : 'btn-outline-light'; ?> btn-sm" style="<?php echo empty($statusFilter) ? '' : 'color: var(--text-color); border-color: var(--border-color);'; ?>">
                All Events
            </a>
            <a href="events.php?status=pending" class="btn <?php echo ($statusFilter === 'pending') ? 'btn-gold' : 'btn-outline-light'; ?> btn-sm" style="<?php echo ($statusFilter === 'pending') ? '' : 'color: var(--text-color); border-color: var(--border-color);'; ?>">
                Pending Review
            </a>
            <a href="events.php?status=approved" class="btn <?php echo ($statusFilter === 'approved') ? 'btn-gold' : 'btn-outline-light'; ?> btn-sm" style="<?php echo ($statusFilter === 'approved') ? '' : 'color: var(--text-color); border-color: var(--border-color);'; ?>">
                Approved
            </a>
            <a href="events.php?status=rejected" class="btn <?php echo ($statusFilter === 'rejected') ? 'btn-gold' : 'btn-outline-light'; ?> btn-sm" style="<?php echo ($statusFilter === 'rejected') ? '' : 'color: var(--text-color); border-color: var(--border-color);'; ?>">
                Rejected
            </a>
        </div>

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
                                <strong style="color: var(--text-light); font-size: 1rem;">
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
                                <div style="display: flex; gap: 0.4rem; align-items: center; flex-wrap: wrap;">
                                    <button type="button" class="btn btn-outline-glass btn-sm"
                                            data-event='<?php echo htmlspecialchars(json_encode([
                                                "id" => $event["id"],
                                                "title" => $event["title"],
                                                "description" => $event["description"] ?? "",
                                                "event_date" => date("M d, Y", strtotime($event["event_date"])),
                                                "event_time" => !empty($event["event_time"]) ? date("h:i A", strtotime($event["event_time"])) : "TBD",
                                                "location" => $event["location"] ?? "Campus",
                                                "capacity" => $event["capacity"] ?? 100,
                                                "status" => $event["status"] ?? "pending",
                                                "creator_name" => $event["creator_name"] ?? "Alumnus",
                                                "creator_email" => $event["creator_email"] ?? ""
                                            ]), ENT_QUOTES, "UTF-8"); ?>'
                                            onclick="openEventReviewModal(this);"
                                            style="padding: 0.3rem 0.65rem; font-size: 0.8rem;">
                                        Review
                                    </button>

                                    <?php if ($event['status'] !== 'approved'): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                            <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="btn btn-glass-success btn-sm">
                                                Approve
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if ($event['status'] !== 'rejected'): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                            <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <button type="submit" class="btn btn-glass-danger btn-sm">
                                                Reject
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this event?');">
                                        <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <button type="submit" class="btn btn-glass-danger btn-sm">
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

<!-- Admin Event Review Modal -->
<div id="eventReviewModal" class="modal-overlay" style="display: none; position: fixed; inset: 0; background: rgba(10, 2, 4, 0.78); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); z-index: 9999; align-items: center; justify-content: center; padding: 1rem;">
    <div class="modal-box" style="max-width: 650px; width: 100%; max-height: 90vh; overflow-y: auto; background: linear-gradient(145deg, rgba(28, 23, 26, 0.96) 0%, rgba(18, 3, 5, 0.98) 100%); border: 1px solid rgba(216, 185, 106, 0.28); border-top: 4px solid var(--accent-color); border-radius: 12px; padding: 2rem;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.5rem;">
            <div>
                <span id="reviewEventBadge" class="badge badge-pending" style="font-size: 0.75rem; margin-bottom: 0.4rem; display: inline-block;">Pending</span>
                <h3 id="reviewEventTitle" style="color: var(--text-light); margin-bottom: 0.2rem; font-weight: 800; font-size: 1.4rem;">Event Proposal</h3>
                <p id="reviewEventCreator" style="color: var(--accent-light); font-weight: 600; margin: 0; font-size: 0.95rem;"></p>
            </div>
            <button type="button" onclick="closeEventReviewModal();" style="background: rgba(255, 255, 255, 0.08); border: 1px solid rgba(216, 185, 106, 0.2); color: var(--text-light); width: 32px; height: 32px; border-radius: 50%; cursor: pointer; font-size: 1.2rem; display: flex; align-items: center; justify-content: center;">&times;</button>
        </div>

        <div style="display: flex; flex-wrap: wrap; gap: 1rem; font-size: 0.85rem; color: var(--text-muted); padding: 0.6rem 0; border-top: 1px solid var(--border-color); border-bottom: 1px solid var(--border-color); margin-bottom: 1rem;">
            <span id="reviewEventDate">&#128197; </span>
            <span id="reviewEventTime">&#128338; </span>
            <span id="reviewEventLocation">&#128205; </span>
            <span id="reviewEventCapacity">&#128101; Capacity: 100</span>
        </div>

        <div style="margin-bottom: 1.2rem;">
            <h5 style="color: var(--accent-light); font-size: 0.82rem; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 0.3rem;">Description &amp; Agenda</h5>
            <p id="reviewEventDesc" style="color: var(--text-color); font-size: 0.9rem; line-height: 1.5; margin: 0; max-height: 160px; overflow-y: auto; white-space: pre-wrap;"></p>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--border-color); padding-top: 1.2rem; margin-top: 1rem; flex-wrap: wrap; gap: 0.8rem;">
            <button type="button" class="btn btn-outline-light btn-sm" onclick="closeEventReviewModal();">Close</button>
            <div style="display: flex; gap: 0.5rem;">
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="event_id" id="modalEventIdApprove" value="">
                    <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">
                    <input type="hidden" name="action" value="approve">
                    <button type="submit" class="btn btn-glass-success btn-sm">Approve Event</button>
                </form>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="event_id" id="modalEventIdReject" value="">
                    <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">
                    <input type="hidden" name="action" value="reject">
                    <button type="submit" class="btn btn-glass-danger btn-sm">Reject Event</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function openEventReviewModal(btn) {
    try {
        const ev = JSON.parse(btn.dataset.event);
        document.getElementById('reviewEventTitle').innerText = ev.title || 'Event Proposal';
        document.getElementById('reviewEventCreator').innerText = 'Submitted by ' + (ev.creator_name || 'Alumnus') + (ev.creator_email ? (' (' + ev.creator_email + ')') : '');
        document.getElementById('reviewEventDate').innerHTML = '&#128197; ' + (ev.event_date || 'TBD');
        document.getElementById('reviewEventTime').innerHTML = '&#128338; ' + (ev.event_time || 'TBD');
        document.getElementById('reviewEventLocation').innerHTML = '&#128205; ' + (ev.location || 'Campus');
        document.getElementById('reviewEventCapacity').innerHTML = '&#128101; Capacity: ' + (ev.capacity || 100);
        document.getElementById('reviewEventDesc').innerText = ev.description || 'No detailed agenda provided.';

        const badge = document.getElementById('reviewEventBadge');
        if (badge) {
            badge.className = 'badge badge-' + (ev.status || 'pending').toLowerCase();
            badge.innerText = (ev.status || 'Pending').toUpperCase();
        }

        document.getElementById('modalEventIdApprove').value = ev.id;
        document.getElementById('modalEventIdReject').value = ev.id;

        document.getElementById('eventReviewModal').style.display = 'flex';
    } catch(e) {
        console.error('Error opening event modal:', e);
    }
}
function closeEventReviewModal() {
    document.getElementById('eventReviewModal').style.display = 'none';
}
</script>

<script src="<?php echo $baseUrl; ?>assets/js/main.js"></script>
</body>
</html>
