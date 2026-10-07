<?php
// ============================================================
// University Alumni Network - Events
// ============================================================
$pageTitle = 'Events';
$currentPage = 'events';
require_once __DIR__ . '/includes/header.php';

$message = '';
$messageType = '';

// Handle Event Submission by logged-in users
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_event') {
    if (!$isLoggedIn) {
        $message = "Please log in to propose an event.";
        $messageType = "danger";
    } else {
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $event_date = trim($_POST['event_date']);
        $event_time = trim($_POST['event_time']);
        $location = trim($_POST['location']);

        if (!empty($title) && !empty($event_date) && !empty($location)) {
            if ($db_connected && $pdo) {
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO events (title, description, event_date, event_time, location, organizer_id, status)
                        VALUES (?, ?, ?, ?, ?, 'pending')
                    ");
                    $stmt->execute([$title, $description, $event_date, $event_time, $location, $_SESSION['user_id']]);
                    $message = "Thank you! Your event submission has been sent for admin moderation.";
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

// Fetch approved events
$events = [];
if ($db_connected && $pdo) {
    try {
        $stmt = $pdo->query("
            SELECT e.*, CONCAT(u.first_name, ' ', u.last_name) AS organizer_name
            FROM events e
            LEFT JOIN users u ON e.organizer_id = u.id
            WHERE e.status = 'approved'
            ORDER BY e.event_date ASC
        ");
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
            <h2 style="color: var(--primary-color); font-size: 1.6rem; font-weight: 800;">Upcoming Gatherings</h2>
            <p style="color: var(--text-muted); font-size: 0.95rem;">Scheduled calendar of university alumni events</p>
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
            <div class="card card-dark event-card">
                <div class="event-image-box">
                    <img src="assets/images/event-placeholder.svg" alt="Event Banner" class="event-image">
                    <span class="event-date-badge">
                        <?php echo date('d M Y', strtotime($event['event_date'])); ?>
                    </span>
                </div>
                <div class="event-body">
                    <div class="event-meta">
                        <span>&#128338; <?php echo date('h:i A', strtotime($event['event_time'])); ?></span>
                        <span>&bull;</span>
                        <span>&#128205; <?php echo htmlspecialchars($event['location']); ?></span>
                    </div>
                    <h3 class="event-title"><?php echo htmlspecialchars($event['title']); ?></h3>
                    <p class="event-desc"><?php echo htmlspecialchars($event['description']); ?></p>
                    
                    <div style="margin-top: auto; padding-top: 1rem; border-top: 1px solid rgba(212, 175, 55, 0.25); display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-size: 0.82rem; color: rgba(248, 244, 234, 0.75);">
                            Organized by: <strong style="color: var(--accent-color);"><?php echo htmlspecialchars(!empty($event['organizer_name']) ? $event['organizer_name'] : 'Alumni Relations'); ?></strong>
                        </span>
                        <button type="button" class="btn btn-outline-gold btn-sm" onclick="alert('Registration confirmed for: <?php echo addslashes($event['title']); ?>');">
                            RSVP Now
                        </button>
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
            Have an initiative, technical workshop, or regional reunion in mind? Submit details for review by the Alumni Directorate.
        </p>

        <?php if ($isLoggedIn): ?>
            <form method="POST">
                <input type="hidden" name="action" value="create_event">
                <div class="grid grid-2">
                    <div class="form-group">
                        <label for="title">Event Title *</label>
                        <input type="text" id="title" name="title" class="form-control" placeholder="e.g. 2018 Batch Reunion" required>
                    </div>
                    <div class="form-group">
                        <label for="location">Location / Venue *</label>
                        <input type="text" id="location" name="location" class="form-control" placeholder="e.g. Campus Quad or Zoom Link" required>
                    </div>
                </div>

                <div class="grid grid-2">
                    <div class="form-group">
                        <label for="event_date">Date *</label>
                        <input type="date" id="event_date" name="event_date" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="event_time">Time *</label>
                        <input type="time" id="event_time" name="event_time" class="form-control" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="description">Event Description &amp; Agenda *</label>
                    <textarea id="description" name="description" class="form-control" rows="4" placeholder="Detail the objectives, guest speakers, and schedule..." required></textarea>
                </div>

                <button type="submit" class="btn btn-gold gold-glow">Submit Proposal for Review</button>
            </form>
        <?php else: ?>
            <div style="background: var(--bg-color); padding: 2rem; border-radius: var(--border-radius); text-align: center;">
                <p style="color: var(--text-main); margin-bottom: 1rem; font-weight: 600;">
                    You must be signed in to submit an alumni event.
                </p>
                <a href="auth/login.php" class="btn btn-primary btn-sm">Log In to Propose Event</a>
                <a href="auth/register.php" class="btn btn-outline-gold btn-sm" style="margin-left: 0.5rem;">Join Network</a>
            </div>
        <?php endif; ?>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
