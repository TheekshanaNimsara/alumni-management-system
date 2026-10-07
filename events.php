<?php
// ============================================================
// University Alumni Network - Campus & Alumni Events
// Interactive RSVP, Capacity Management, Open/Closed State & Moderation
// ============================================================
$pageTitle = 'Events';
$currentPage = 'events';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/functions.php';

$message = '';
$messageType = '';
$currentUserId = current_user_id();

// 1. Handle RSVP Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_rsvp') {
    if (!$isLoggedIn) {
        $message = "Please log in to RSVP for events.";
        $messageType = "danger";
    } elseif (!verify_csrf_token()) {
        $message = "Security token invalid. Please try again.";
        $messageType = "danger";
    } else {
        $eventId = intval($_POST['event_id'] ?? 0);
        if ($eventId > 0 && $db_connected && $pdo) {
            try {
                // Check event status & capacity
                $stmt = $pdo->prepare("
                    SELECT e.*, 
                           (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.id AND status = 'attending') AS attendee_count
                    FROM events e 
                    WHERE e.id = ? AND e.status = 'approved'
                ");
                $stmt->execute([$eventId]);
                $eventData = $stmt->fetch();

                if (!$eventData) {
                    $message = "Event not found or not currently active.";
                    $messageType = "danger";
                } else {
                    $isPast = (strtotime($eventData['event_date']) < strtotime(date('Y-m-d')));
                    $isFull = ($eventData['capacity'] > 0 && $eventData['attendee_count'] >= $eventData['capacity']);
                    $isClosed = ($eventData['is_closed'] == 1 || $isPast);

                    // Check existing registration
                    $stmt = $pdo->prepare("SELECT * FROM event_registrations WHERE event_id = ? AND user_id = ?");
                    $stmt->execute([$eventId, $currentUserId]);
                    $reg = $stmt->fetch();

                    if ($reg && $reg['status'] === 'attending') {
                        // Cancel RSVP
                        $stmt = $pdo->prepare("UPDATE event_registrations SET status = 'cancelled' WHERE event_id = ? AND user_id = ?");
                        $stmt->execute([$eventId, $currentUserId]);
                        $message = "Your RSVP has been cancelled for: " . htmlspecialchars($eventData['title']);
                        $messageType = "warning";
                    } else {
                        // Attempt RSVP
                        if ($isClosed) {
                            $message = "This event is closed or has concluded. Registrations are no longer accepted.";
                            $messageType = "danger";
                        } elseif ($isFull) {
                            $message = "This event has reached its maximum capacity.";
                            $messageType = "danger";
                        } else {
                            if ($reg) {
                                $stmt = $pdo->prepare("UPDATE event_registrations SET status = 'attending', registered_at = NOW() WHERE event_id = ? AND user_id = ?");
                                $stmt->execute([$eventId, $currentUserId]);
                            } else {
                                $stmt = $pdo->prepare("INSERT INTO event_registrations (event_id, user_id, status) VALUES (?, ?, 'attending')");
                                $stmt->execute([$eventId, $currentUserId]);
                            }
                            $message = "Registration confirmed! You are attending: " . htmlspecialchars($eventData['title']);
                            $messageType = "success";
                        }
                    }
                }
            } catch (Exception $e) {
                $message = "RSVP error: " . $e->getMessage();
                $messageType = "danger";
            }
        }
    }
}

// 2. Handle Event Proposal Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_event') {
    if (!$isLoggedIn) {
        $message = "Please log in to propose an event.";
        $messageType = "danger";
    } elseif (!verify_csrf_token()) {
        $message = "Security token invalid. Please try again.";
        $messageType = "danger";
    } else {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $event_date = trim($_POST['event_date'] ?? '');
        $event_time = trim($_POST['event_time'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $capacity = intval($_POST['capacity'] ?? 100);

        if (!empty($title) && !empty($event_date) && !empty($location)) {
            if ($db_connected && $pdo) {
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO events (title, description, event_date, event_time, location, capacity, organizer_id, status)
                        VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
                    ");
                    $stmt->execute([$title, $description, $event_date, $event_time, $location, $capacity, $currentUserId]);
                    $message = "Thank you! Your event proposal has been submitted for admin moderation.";
                    $messageType = "success";
                } catch (Exception $e) {
                    $message = "Submission error: " . $e->getMessage();
                    $messageType = "danger";
                }
            } else {
                $message = "Thank you! Your event proposal has been recorded.";
                $messageType = "success";
            }
        } else {
            $message = "Please complete all required fields.";
            $messageType = "danger";
        }
    }
}

// Fetch approved events with live attendee counts and user RSVP status
$events = [];
if ($db_connected && $pdo) {
    try {
        $userIdParam = $currentUserId ? $currentUserId : 0;
        $stmt = $pdo->prepare("
            SELECT e.*, 
                   CONCAT(u.first_name, ' ', u.last_name) AS organizer_name,
                   (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.id AND status = 'attending') AS attendee_count,
                   (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.id AND user_id = ? AND status = 'attending') AS is_user_attending
            FROM events e
            LEFT JOIN users u ON e.organizer_id = u.id
            WHERE e.status = 'approved'
            ORDER BY e.event_date ASC
        ");
        $stmt->execute([$userIdParam]);
        $events = $stmt->fetchAll();
    } catch (Exception $e) {
        $events = [];
    }
}

if (empty($events)) {
    $events = get_sample_events();
}
?>

<!-- Cinematic Events Header -->
<section style="background: var(--primary-color); color: var(--text-light); padding: 4rem 0 3rem; border-bottom: 2px solid var(--accent-color);">
    <div class="container text-center" style="text-align: center;">
        <span class="section-eyebrow">Alumni Gatherings &bull; Summits &bull; Reunions</span>
        <h1 class="section-title" style="color: var(--text-light); margin-bottom: 0.8rem;">University Events</h1>
        <p style="color: rgba(248, 244, 234, 0.75); max-width: 600px; margin: 0 auto;">
            Participate in prestigious conferences, technical workshops, and heartwarming alumni reunions.
        </p>
    </div>
</section>

<div class="container" style="padding-top: 3rem; padding-bottom: 5rem;">

    <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $messageType; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <!-- Action Header / Proposal Trigger -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2.5rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h2 style="color: var(--primary-color); font-size: 1.6rem; font-weight: 800; margin: 0 0 0.3rem 0;">Upcoming Gatherings</h2>
            <p style="color: var(--text-muted); font-size: 0.95rem; margin: 0;">Scheduled calendar of university alumni events</p>
        </div>
        <div>
            <a href="#propose-event" class="btn btn-gold gold-glow btn-sm">
                + Propose an Event
            </a>
        </div>
    </div>

    <!-- Events Grid (Cinematic Dark Cards) -->
    <div class="grid grid-3">
        <?php foreach ($events as $event): ?>
            <?php
                $attendees = intval($event['attendee_count'] ?? 0);
                $capacity = intval($event['capacity'] ?? 100);
                $isUserAttending = !empty($event['is_user_attending']);
                $isPast = (strtotime($event['event_date']) < strtotime(date('Y-m-d')));
                $isClosed = (!empty($event['is_closed']) || $isPast);
                $isFull = ($capacity > 0 && $attendees >= $capacity);
            ?>
            <div class="card card-dark event-card" style="display: flex; flex-direction: column;">
                
                <div class="event-image-box" style="position: relative;">
                    <img src="assets/images/event-placeholder.svg" alt="Event Banner" class="event-image">
                    
                    <span class="event-date-badge">
                        <?php echo date('d M Y', strtotime($event['event_date'])); ?>
                    </span>

                    <!-- Open / Closed Badge -->
                    <span style="position: absolute; top: 12px; left: 12px; font-size: 0.75rem; font-weight: 700; padding: 0.3rem 0.65rem; border-radius: 4px; <?php echo ($isClosed || $isFull) ? 'background: rgba(166, 61, 64, 0.9); color: #fff;' : 'background: rgba(63, 125, 90, 0.9); color: #fff;'; ?>">
                        <?php 
                            if ($isPast) echo "Past Event";
                            elseif ($isClosed) echo "Closed";
                            elseif ($isFull) echo "Capacity Full";
                            else echo "&#9679; Open for RSVP";
                        ?>
                    </span>
                </div>

                <div class="event-body" style="flex: 1; display: flex; flex-direction: column;">
                    
                    <div class="event-meta" style="margin-bottom: 0.6rem;">
                        <span>&#128338; <?php echo date('h:i A', strtotime($event['event_time'])); ?></span>
                        <span>&bull;</span>
                        <span>&#128205; <?php echo htmlspecialchars($event['location']); ?></span>
                    </div>

                    <h3 class="event-title" style="margin-bottom: 0.6rem;"><?php echo htmlspecialchars($event['title']); ?></h3>
                    <p class="event-desc" style="flex: 1; margin-bottom: 1rem;"><?php echo htmlspecialchars($event['description']); ?></p>
                    
                    <!-- Attendance Progress -->
                    <div style="font-size: 0.82rem; color: rgba(248, 244, 234, 0.8); margin-bottom: 1rem; padding: 0.5rem 0.8rem; background: rgba(0,0,0,0.25); border-radius: 4px; display: flex; justify-content: space-between;">
                        <span>Attendees: <strong style="color: var(--accent-light);"><?php echo $attendees; ?> registered</strong></span>
                        <span>Capacity: <?php echo $capacity; ?></span>
                    </div>

                    <!-- Card Actions -->
                    <div style="padding-top: 1rem; border-top: 1px solid rgba(212, 175, 55, 0.25); display: flex; justify-content: space-between; align-items: center; gap: 0.5rem;">
                        <span style="font-size: 0.8rem; color: rgba(248, 244, 234, 0.7);">
                            By: <strong style="color: var(--accent-color);"><?php echo htmlspecialchars(!empty($event['organizer_name']) ? $event['organizer_name'] : 'Alumni Relations'); ?></strong>
                        </span>

                        <div style="display: flex; gap: 0.4rem;">
                            <!-- Report Event Link -->
                            <button type="button" class="btn btn-outline-light btn-sm" style="font-size: 0.72rem; padding: 0.25rem 0.45rem; border-color: rgba(255,255,255,0.2);" 
                                    onclick="openReportModal(<?php echo $event['id']; ?>, 'event', '<?php echo htmlspecialchars(addslashes($event['title'])); ?>');" title="Report Event">
                                &#9873;
                            </button>

                            <!-- RSVP Form Button -->
                            <?php if ($isLoggedIn): ?>
                                <form method="POST" action="events.php" style="display: inline;">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="action" value="toggle_rsvp">
                                    <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                    
                                    <?php if ($isUserAttending): ?>
                                        <button type="submit" class="btn btn-sm" style="background: var(--success-color); color: #fff; font-size: 0.78rem; border: 1px solid var(--accent-color);" title="Click to Cancel RSVP">
                                            &#10003; Attending (Cancel)
                                        </button>
                                    <?php elseif ($isClosed || $isFull): ?>
                                        <button type="button" class="btn btn-sm btn-disabled" disabled style="font-size: 0.78rem;">
                                            Closed
                                        </button>
                                    <?php else: ?>
                                        <button type="submit" class="btn btn-gold btn-sm gold-glow" style="font-size: 0.78rem;">
                                            RSVP Now
                                        </button>
                                    <?php endif; ?>
                                </form>
                            <?php else: ?>
                                <a href="auth/login.php" class="btn btn-outline-gold btn-sm" style="font-size: 0.78rem;">
                                    Login to RSVP
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Propose Event Section -->
    <div id="propose-event" class="card" style="margin-top: 5rem; padding: 2.5rem; border-top: 4px solid var(--accent-color);">
        <h3 style="color: var(--primary-color); font-size: 1.5rem; margin-bottom: 0.5rem; font-weight: 800;">
            Host or Propose an Alumni Event
        </h3>
        <p style="color: var(--text-muted); margin-bottom: 2rem; font-size: 0.95rem;">
            Whether it is an industry keynote, an informal batch reunion, or a campus technical hackathon, submit your proposal for university endorsement.
        </p>

        <?php if ($isLoggedIn): ?>
            <form method="POST" action="events.php#propose-event">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="create_event">
                
                <div class="grid grid-2" style="margin-bottom: 1.2rem; gap: 1.5rem;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="title">Event Title *</label>
                        <input type="text" id="title" name="title" class="form-control" placeholder="e.g. AI & Distributed Systems Summit 2026" required>
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="location">Location / Venue *</label>
                        <input type="text" id="location" name="location" class="form-control" placeholder="e.g. Grand Auditorium, or Zoom Webinar link" required>
                    </div>
                </div>

                <div class="grid grid-3" style="margin-bottom: 1.2rem; gap: 1.5rem;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="event_date">Date of Event *</label>
                        <input type="date" id="event_date" name="event_date" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="event_time">Start Time *</label>
                        <input type="time" id="event_time" name="event_time" class="form-control" required>
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="capacity">Expected Capacity</label>
                        <input type="number" id="capacity" name="capacity" class="form-control" value="100" min="5" max="5000">
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label for="description">Event Description & Agenda</label>
                    <textarea id="description" name="description" class="form-control" rows="4" placeholder="Detail the objectives, guest speakers, target batch/graduates, and agenda..."></textarea>
                </div>

                <button type="submit" class="btn btn-gold gold-glow">
                    Submit Event for Review
                </button>
            </form>
        <?php else: ?>
            <div style="background: var(--bg-color); border: 1px dashed var(--border-color); padding: 2rem; border-radius: var(--border-radius); text-align: center;">
                <p style="color: var(--text-color); margin-bottom: 1rem; font-weight: 600;">
                    Please sign in with your verified alumni account to submit event proposals.
                </p>
                <div style="display: flex; gap: 1rem; justify-content: center;">
                    <a href="auth/login.php" class="btn btn-primary btn-sm">Login to Your Account</a>
                    <a href="auth/register.php" class="btn btn-gold btn-sm">Join the Network</a>
                </div>
            </div>
        <?php endif; ?>
    </div>

</div>

<!-- Modal for reporting event -->
<div id="reportModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: #ffffff; width: 90%; max-width: 480px; border-radius: var(--border-radius); padding: 2rem; border-top: 4px solid var(--accent-color); box-shadow: 0 15px 40px rgba(0,0,0,0.5);">
        <h3 style="color: var(--primary-color); margin-bottom: 0.5rem; font-weight: 800;">Report Event</h3>
        <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1.2rem;">
            Report misleading information or unauthorized alumni gatherings.
        </p>

        <form method="POST" action="actions/report.php">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="event_id" id="report_event_id" value="">
            
            <div class="form-group">
                <label for="report_reason">Reason for Report *</label>
                <select name="reason" id="report_reason" class="form-control" required>
                    <option value="">Select a reason...</option>
                    <option value="Misleading Date / Venue">Misleading Date / Venue</option>
                    <option value="Commercial Solicitation without Authorization">Commercial Solicitation without Authorization</option>
                    <option value="Duplicate or Cancelled Event">Duplicate or Cancelled Event</option>
                    <option value="Other Violation">Other Violation</option>
                </select>
            </div>

            <div class="form-group">
                <label for="report_desc">Details</label>
                <textarea name="description" id="report_desc" rows="3" class="form-control" placeholder="Provide additional context..."></textarea>
            </div>

            <div style="display: flex; gap: 0.8rem; justify-content: flex-end; margin-top: 1.5rem;">
                <button type="button" class="btn btn-outline-light" style="color: var(--text-color);" onclick="closeReportModal();">Cancel</button>
                <button type="submit" class="btn btn-gold">Submit Report</button>
            </div>
        </form>
    </div>
</div>

<script>
function openReportModal(eventId, type, name) {
    document.getElementById('report_event_id').value = eventId;
    document.getElementById('reportModal').style.display = 'flex';
}
function closeReportModal() {
    document.getElementById('reportModal').style.display = 'none';
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
