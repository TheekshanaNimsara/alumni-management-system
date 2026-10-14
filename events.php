<?php
// ============================================================
// University Alumni Network - Campus & Alumni Events
// Interactive RSVP, Capacity Management, Open/Closed State & Moderation
// ============================================================
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Events';
$currentPage = 'events';
$message = '';
$messageType = '';
$currentUserId = current_user_id();
$isLoggedIn = is_logged_in();

// Flash message retrieval from session
if (!empty($_SESSION['flash_message'])) {
    $message = $_SESSION['flash_message'];
    $messageType = $_SESSION['flash_type'] ?? 'info';
    unset($_SESSION['flash_message'], $_SESSION['flash_type']);
}

// 1. Handle RSVP Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_rsvp') {
    if (!$isLoggedIn) {
        $eventId = intval($_POST['event_id'] ?? 0);
        $redir = 'events.php' . ($eventId > 0 ? '#event-' . $eventId : '');
        header("Location: auth/login.php?redirect=" . urlencode($redir) . "&msg=auth_required");
        exit;
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
                    $_SESSION['flash_message'] = "Event not found or not currently active.";
                    $_SESSION['flash_type'] = "danger";
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
                        $_SESSION['flash_message'] = "Your RSVP has been cancelled for: " . htmlspecialchars($eventData['title']);
                        $_SESSION['flash_type'] = "warning";
                    } else {
                        // Attempt RSVP
                        if ($isClosed) {
                            $_SESSION['flash_message'] = "This event is closed or has concluded. Registrations are no longer accepted.";
                            $_SESSION['flash_type'] = "danger";
                        } elseif ($isFull) {
                            $_SESSION['flash_message'] = "This event has reached its maximum capacity.";
                            $_SESSION['flash_type'] = "danger";
                        } else {
                            if ($reg) {
                                $stmt = $pdo->prepare("UPDATE event_registrations SET status = 'attending', registered_at = NOW() WHERE event_id = ? AND user_id = ?");
                                $stmt->execute([$eventId, $currentUserId]);
                            } else {
                                $stmt = $pdo->prepare("INSERT INTO event_registrations (event_id, user_id, status) VALUES (?, ?, 'attending')");
                                $stmt->execute([$eventId, $currentUserId]);
                            }
                            $_SESSION['flash_message'] = "Registration confirmed! You are attending: " . htmlspecialchars($eventData['title']);
                            $_SESSION['flash_type'] = "success";
                        }
                    }
                }
                header("Location: events.php#event-" . $eventId);
                exit;
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
        header("Location: auth/login.php?redirect=" . urlencode("events.php#propose-event") . "&msg=auth_required");
        exit;
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
                    $eventStatus = is_admin() ? 'approved' : 'pending';
                    $stmt = $pdo->prepare("
                        INSERT INTO events (title, description, event_date, event_time, location, capacity, organizer_id, status)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$title, $description, $event_date, $event_time, $location, $capacity, $currentUserId, $eventStatus]);
                    $_SESSION['flash_message'] = is_admin() ? "Event created and published successfully!" : "Thank you! Your event proposal has been submitted for admin moderation.";
                    $_SESSION['flash_type'] = "success";
                    header("Location: events.php?tab=my#propose-event");
                    exit;
                } catch (Exception $e) {
                    $message = "Submission error: " . $e->getMessage();
                    $messageType = "danger";
                }
            } else {
                $_SESSION['flash_message'] = "Thank you! Your event proposal has been recorded.";
                $_SESSION['flash_type'] = "success";
                header("Location: events.php?tab=my#propose-event");
                exit;
            }
        } else {
            $message = "Please complete all required fields.";
            $messageType = "danger";
        }
    }
}

// Tab filter
$tabFilter = $_GET['tab'] ?? 'all';

// Fetch events based on tab filter
$events = [];
if ($db_connected && $pdo) {
    try {
        $userIdParam = $currentUserId ? $currentUserId : 0;
        if ($tabFilter === 'my' && $isLoggedIn) {
            $stmt = $pdo->prepare("
                SELECT e.*, 
                       CONCAT(u.first_name, ' ', u.last_name) AS organizer_name,
                       (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.id AND status = 'attending') AS attendee_count,
                       (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.id AND user_id = ? AND status = 'attending') AS is_user_attending
                FROM events e
                LEFT JOIN users u ON e.organizer_id = u.id
                WHERE e.organizer_id = ?
                ORDER BY e.created_at DESC
            ");
            $stmt->execute([$userIdParam, $userIdParam]);
            $events = $stmt->fetchAll();
        } else {
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
        }
    } catch (Exception $e) {
        $events = [];
    }
}

if (empty($events) && $tabFilter !== 'my') {
    $events = get_sample_events();
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- Cinematic Events Header -->
<section style="background: radial-gradient(circle at 50% 20%, rgba(232, 199, 126, 0.12) 0%, rgba(118, 45, 72, 0.75) 50%, #100810 100%); color: var(--text-light); padding: 4rem 0 3rem; border-bottom: 1px solid var(--border-color);">
    <div class="container text-center" style="text-align: center;">
        <span class="section-eyebrow">Alumni Gatherings &bull; Summits &bull; Reunions</span>
        <h1 class="section-title" style="color: var(--text-light); margin-bottom: 0.8rem;">University Events</h1>
        <p style="color: var(--text-secondary); max-width: 600px; margin: 0 auto;">
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
            <h2 style="color: var(--text-light); font-size: 1.6rem; font-weight: 800; margin: 0 0 0.3rem 0;">Upcoming Gatherings</h2>
            <p style="color: var(--text-muted); font-size: 0.95rem; margin: 0;">Scheduled calendar of university alumni events</p>
        </div>
        <div style="display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap;">
            <a href="index.php" class="btn btn-return-home btn-outline-light btn-sm">&larr; Return to Homepage</a>
            <?php if ($isLoggedIn): ?>
                <a href="#propose-event" class="btn btn-gold gold-glow btn-sm">
                    + Propose an Event
                </a>
            <?php else: ?>
                <a href="auth/login.php?redirect=<?php echo urlencode('events.php#propose-event'); ?>&msg=auth_required" class="btn btn-gold gold-glow btn-sm">
                    + Propose an Event
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Main Tab Selection (All Gatherings vs My Proposals) -->
    <div style="display: flex; gap: 0.8rem; margin-bottom: 2rem; border-bottom: 1px solid var(--border-color); padding-bottom: 1rem;">
        <a href="events.php?tab=all" 
           class="btn btn-sm <?php echo ($tabFilter !== 'my') ? 'btn-gold' : 'btn-outline-light'; ?>">
            All Gatherings
        </a>
        <?php if ($isLoggedIn): ?>
            <a href="events.php?tab=my" 
               class="btn btn-sm <?php echo ($tabFilter === 'my') ? 'btn-gold' : 'btn-outline-light'; ?>">
                My Proposals
            </a>
        <?php endif; ?>
    </div>

    <?php if (empty($events)): ?>
        <div style="text-align: center; padding: 3.5rem 1.5rem; background: var(--glass-bg); border-radius: var(--border-radius); border: 1px solid var(--glass-border); margin-bottom: 2rem;">
            <p style="color: var(--text-light); font-size: 1.1rem; font-weight: 700; margin-bottom: 0.5rem;">
                <?php echo ($tabFilter === 'my') ? 'No Event Proposals Found' : 'No Upcoming Events Scheduled'; ?>
            </p>
            <p style="color: var(--text-muted); font-size: 0.95rem; margin-bottom: 1.5rem;">
                <?php echo ($tabFilter === 'my') ? 'You have not submitted any event proposals yet. Propose your first gathering below!' : 'Check back later or propose an event below to gather fellow alumni.'; ?>
            </p>
            <a href="#propose-event" class="btn btn-gold gold-glow btn-sm">+ Propose an Event</a>
        </div>
    <?php else: ?>
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
                <div class="card card-dark event-card" id="event-<?php echo $event['id']; ?>" style="display: flex; flex-direction: column;">
                    
                    <div class="event-image-box" style="position: relative;">
                        <img src="assets/images/event-placeholder.svg" alt="Event Banner" class="event-image">
                        <div class="event-artwork-overlay"></div>
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

                        <!-- Status / Moderation Badge for Proposals -->
                        <?php if (!empty($event['status']) && $event['status'] !== 'approved'): ?>
                            <span style="position: absolute; top: 12px; right: 12px; font-size: 0.75rem; font-weight: 700; padding: 0.3rem 0.65rem; border-radius: 4px; background: <?php echo ($event['status'] === 'rejected' ? 'rgba(239, 68, 68, 0.9)' : 'rgba(245, 158, 11, 0.9)'); ?>; color: #fff; box-shadow: 0 2px 8px rgba(0,0,0,0.5);">
                                <?php echo ($event['status'] === 'rejected') ? '&#10006; Rejected' : '&#9203; Pending Approval'; ?>
                            </span>
                        <?php elseif ($tabFilter === 'my'): ?>
                            <span style="position: absolute; top: 12px; right: 12px; font-size: 0.75rem; font-weight: 700; padding: 0.3rem 0.65rem; border-radius: 4px; background: rgba(16, 185, 129, 0.9); color: #fff; box-shadow: 0 2px 8px rgba(0,0,0,0.5);">
                                &#10003; Approved
                            </span>
                        <?php endif; ?>
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
                        <div style="font-size: 0.82rem; color: var(--text-secondary); margin-bottom: 1rem; padding: 0.5rem 0.8rem; background: rgba(0,0,0,0.3); border-radius: 4px; display: flex; justify-content: space-between;">
                            <span>Attendees: <strong style="color: var(--accent-light);"><?php echo $attendees; ?> registered</strong></span>
                            <span>Capacity: <?php echo $capacity; ?></span>
                        </div>

                        <!-- Card Actions -->
                        <div style="padding-top: 1rem; border-top: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; gap: 0.5rem;">
                            <span style="font-size: 0.8rem; color: var(--text-secondary);">
                                By: <strong style="color: var(--accent-light);"><?php echo htmlspecialchars(!empty($event['organizer_name']) ? $event['organizer_name'] : 'Alumni Relations'); ?></strong>
                            </span>

                            <div style="display: flex; gap: 0.4rem;">
                                <!-- Report Event Link -->
                                <button type="button" class="btn btn-outline-light btn-sm" style="font-size: 0.72rem; padding: 0.25rem 0.45rem; border-color: var(--border-color);" 
                                        onclick="openReportModal(<?php echo $event['id']; ?>, 'event', '<?php echo htmlspecialchars(addslashes($event['title'])); ?>');" title="Report Event">
                                    &#9873;
                                </button>

                                <!-- Event Details Modal Trigger -->
                                <button type="button" class="btn btn-outline-glass btn-sm" 
                                        data-event='<?php echo htmlspecialchars(json_encode([
                                            "id" => $event["id"],
                                            "title" => $event["title"],
                                            "description" => $event["description"] ?? "",
                                            "date" => date("d M Y", strtotime($event["event_date"])),
                                            "time" => date("h:i A", strtotime($event["event_time"])),
                                            "location" => $event["location"],
                                            "capacity" => $capacity,
                                            "attendees" => $attendees,
                                            "organizer" => !empty($event["organizer_name"]) ? $event["organizer_name"] : "Alumni Relations",
                                            "status" => $event["status"] ?? "approved",
                                            "is_attending" => $isUserAttending,
                                            "is_closed" => ($isClosed || $isFull)
                                        ]), ENT_QUOTES, "UTF-8"); ?>'
                                        onclick="openEventDetailsModalWithData(this);"
                                        style="font-size: 0.78rem;">
                                    Details &rarr;
                                </button>

                                <!-- RSVP Form Button -->
                                <?php if ($isLoggedIn): ?>
                                    <form method="POST" action="events.php" style="display: inline;">
                                        <input type="hidden" name="action" value="toggle_rsvp">
                                        <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                        
                                        <?php if ($isUserAttending): ?>
                                            <button type="submit" class="btn btn-glass-success btn-sm" style="font-size: 0.78rem;" title="Click to Cancel RSVP">
                                                &#10003; Attending (Cancel)
                                            </button>
                                        <?php elseif ($isClosed || $isFull): ?>
                                            <button type="button" class="btn btn-sm" disabled style="font-size: 0.78rem;">
                                                Closed
                                            </button>
                                        <?php else: ?>
                                            <button type="submit" class="btn btn-gold btn-sm gold-glow" style="font-size: 0.78rem;">
                                                RSVP Now
                                            </button>
                                        <?php endif; ?>
                                    </form>
                                <?php else: ?>
                                    <a href="auth/login.php?redirect=<?php echo urlencode('events.php#event-' . $event['id']); ?>&msg=auth_required" class="btn btn-outline-gold btn-sm" style="font-size: 0.78rem;">
                                        Login to RSVP
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>

                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Propose Event Section -->
    <div id="propose-event" class="card" style="margin-top: 5rem; padding: 2.5rem; border-top: 4px solid var(--accent-color);">
        <h3 style="color: var(--text-light); font-size: 1.5rem; margin-bottom: 0.5rem; font-weight: 800;">
            Host or Propose an Alumni Event
        </h3>
        <p style="color: var(--text-muted); margin-bottom: 2rem; font-size: 0.95rem;">
            Whether it is an industry keynote, an informal batch reunion, or a campus technical hackathon, submit your proposal for university endorsement.
        </p>

        <?php if ($isLoggedIn): ?>
            <form method="POST" action="events.php#propose-event">
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
                    <a href="auth/login.php?redirect=<?php echo urlencode('events.php#propose-event'); ?>&msg=auth_required" class="btn btn-primary btn-sm">Login to Your Account</a>
                    <a href="auth/register.php" class="btn btn-gold btn-sm">Join the Network</a>
                </div>
            </div>
        <?php endif; ?>
    </div>

</div>

<!-- Modal for Event Details -->
<div id="eventDetailsModal" class="modal-overlay" style="display: none; position: fixed; inset: 0; background: rgba(10, 2, 4, 0.78); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); z-index: 9999; align-items: center; justify-content: center; padding: 1rem;">
    <div class="modal-box" style="max-width: 620px; width: 100%; max-height: 90vh; overflow-y: auto; background: linear-gradient(145deg, rgba(28, 23, 26, 0.96) 0%, rgba(18, 3, 5, 0.98) 100%); border: 1px solid rgba(232, 199, 126, 0.22); border-top: 4px solid var(--accent-color); border-radius: 12px; padding: 2rem;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.5rem;">
            <div>
                <span id="evDetailBadge" class="badge badge-approved" style="font-size: 0.75rem; margin-bottom: 0.4rem; display: inline-block;">Approved</span>
                <h3 id="evDetailTitle" style="color: var(--text-light); margin-bottom: 0.2rem; font-weight: 800; font-size: 1.4rem;">Event Specifications</h3>
                <p id="evDetailOrganizer" style="color: var(--accent-light); font-weight: 600; margin: 0; font-size: 0.95rem;"></p>
            </div>
            <button type="button" onclick="closeEventDetailsModal();" style="background: rgba(255, 255, 255, 0.08); border: 1px solid rgba(232, 199, 126, 0.22); color: var(--text-light); width: 32px; height: 32px; border-radius: 50%; cursor: pointer; font-size: 1.2rem; display: flex; align-items: center; justify-content: center;">&times;</button>
        </div>

        <div style="display: flex; flex-wrap: wrap; gap: 1rem; font-size: 0.85rem; color: var(--text-muted); padding: 0.6rem 0; border-top: 1px solid var(--border-color); border-bottom: 1px solid var(--border-color); margin-bottom: 1rem;">
            <span id="evDetailDate">&#128197; </span>
            <span id="evDetailTime">&#128338; </span>
            <span id="evDetailLocation">&#128205; </span>
        </div>

        <div style="font-size: 0.84rem; color: var(--text-secondary); margin-bottom: 1.2rem; padding: 0.5rem 0.8rem; background: rgba(0,0,0,0.3); border-radius: 4px; display: flex; justify-content: space-between;">
            <span id="evDetailAttendees">Attendees: 0 registered</span>
            <span id="evDetailCapacity">Capacity: 100</span>
        </div>

        <div style="margin-bottom: 1.5rem;">
            <h5 style="color: var(--accent-light); font-size: 0.82rem; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 0.4rem;">Event Description &amp; Agenda</h5>
            <p id="evDetailDesc" style="color: var(--text-color); font-size: 0.92rem; line-height: 1.6; margin: 0; max-height: 200px; overflow-y: auto; white-space: pre-wrap;"></p>
        </div>

        <div id="evModalActionArea" style="display: flex; justify-content: flex-end; gap: 0.8rem; border-top: 1px solid var(--border-color); padding-top: 1.2rem;">
            <button type="button" class="btn btn-outline-light btn-sm" onclick="closeEventDetailsModal();">Close</button>
            <?php if ($isLoggedIn): ?>
                <form id="evModalRsvpForm" method="POST" action="events.php" style="display: inline;">
                    <input type="hidden" name="action" value="toggle_rsvp">
                    <input type="hidden" name="event_id" id="evModalEventId" value="">
                    <button type="submit" id="evModalRsvpBtn" class="btn btn-gold btn-sm gold-glow">RSVP Now</button>
                </form>
            <?php else: ?>
                <a id="evModalLoginBtn" href="auth/login.php" class="btn btn-gold btn-sm">Login to RSVP</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal for reporting event -->
<div id="reportModal" class="modal-overlay">
    <div class="modal-box">
        <h3 style="color: var(--text-light); margin-bottom: 0.5rem; font-weight: 800;">Report Event</h3>
        <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1.2rem;">
            Report misleading information or unauthorized alumni gatherings.
        </p>

        <form method="POST" action="actions/report.php">
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
                <button type="button" class="btn btn-outline-light" onclick="closeReportModal();">Cancel</button>
                <button type="submit" class="btn btn-gold">Submit Report</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEventDetailsModalWithData(btn) {
    try {
        const ev = JSON.parse(btn.dataset.event);
        document.getElementById('evDetailTitle').innerText = ev.title || 'Event Details';
        document.getElementById('evDetailOrganizer').innerText = 'Hosted by ' + (ev.organizer || 'Alumni Relations');
        document.getElementById('evDetailDate').innerHTML = '&#128197; ' + (ev.date || 'TBD');
        document.getElementById('evDetailTime').innerHTML = '&#128338; ' + (ev.time || 'TBD');
        document.getElementById('evDetailLocation').innerHTML = '&#128205; ' + (ev.location || 'Campus');
        document.getElementById('evDetailAttendees').innerText = 'Attendees: ' + (ev.attendees || 0) + ' registered';
        document.getElementById('evDetailCapacity').innerText = 'Capacity: ' + (ev.capacity || '100');
        document.getElementById('evDetailDesc').innerText = ev.description || 'No detailed agenda provided.';

        const badge = document.getElementById('evDetailBadge');
        if (badge) {
            badge.className = 'badge badge-' + (ev.status || 'approved').toLowerCase();
            badge.innerText = (ev.status || 'Approved').toUpperCase();
        }

        const rsvpForm = document.getElementById('evModalRsvpForm');
        const evIdInput = document.getElementById('evModalEventId');
        const rsvpBtn = document.getElementById('evModalRsvpBtn');
        const loginBtn = document.getElementById('evModalLoginBtn');

        if (evIdInput) evIdInput.value = ev.id;
        if (loginBtn) loginBtn.href = 'auth/login.php?redirect=' + encodeURIComponent('events.php#event-' + ev.id) + '&msg=auth_required';

        if (rsvpBtn) {
            if (ev.is_attending) {
                rsvpBtn.className = 'btn btn-sm';
                rsvpBtn.style.background = 'var(--success-color)';
                rsvpBtn.style.color = '#fff';
                rsvpBtn.innerText = '✓ Attending (Cancel)';
            } else if (ev.is_closed) {
                rsvpBtn.className = 'btn btn-sm btn-disabled';
                rsvpBtn.disabled = true;
                rsvpBtn.innerText = 'Registration Closed';
            } else {
                rsvpBtn.className = 'btn btn-gold btn-sm gold-glow';
                rsvpBtn.disabled = false;
                rsvpBtn.innerText = 'RSVP Now';
            }
        }

        document.getElementById('eventDetailsModal').style.display = 'flex';
    } catch(e) {
        console.error('Error opening event modal:', e);
    }
}
function closeEventDetailsModal() {
    document.getElementById('eventDetailsModal').style.display = 'none';
}

function openReportModal(eventId, type, name) {
    document.getElementById('report_event_id').value = eventId;
    document.getElementById('reportModal').style.display = 'flex';
}
function closeReportModal() {
    document.getElementById('reportModal').style.display = 'none';
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
