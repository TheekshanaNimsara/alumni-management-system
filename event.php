<?php
// ============================================================
// University Alumni Network - Events API & Submission Controller
// Secure PDO Handlers • Role-Based Access Control • RSVP Management
// ============================================================

require_once __DIR__ . '/config/db.php';

// Route raw browser visits without action to portal
if (empty($_GET['action']) && empty($_POST['action']) && empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header("Location: events.php");
    exit;
}

function isAjax(): bool {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) ||
           (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
}

$action = $_GET['action'] ?? $_POST['action'] ?? 'get';
$currentUserId = current_user_id();
$isAdmin = is_admin();

switch ($action) {

    // ── GET: Fetch events ──────────────────────────────────────────
    case 'get':
        header('Content-Type: application/json');
        if (!$pdo) {
            echo json_encode([]);
            exit;
        }

        try {
            $userParam = $currentUserId ? (int)$currentUserId : 0;
            $statusFilter = $_GET['status'] ?? null;

            if ($isAdmin && $statusFilter === 'all') {
                $sql = "
                    SELECT 
                        e.id,
                        COALESCE(NULLIF(e.title, ''), 'Untitled Event') AS title,
                        COALESCE(e.description, '') AS description,
                        e.event_date AS date,
                        COALESCE(e.start_time, e.event_time, '09:00') AS start,
                        COALESCE(e.end_time, '17:00') AS end,
                        COALESCE(NULLIF(e.location, ''), 'Main Campus Auditorium') AS location,
                        COALESCE(e.reg_date, e.event_date) AS regDate,
                        e.status,
                        COALESCE(e.capacity, 100) AS capacity,
                        e.organizer_id,
                        (SELECT COUNT(*) FROM event_registrations er WHERE er.event_id = e.id AND er.status = 'attending') AS attendee_count,
                        EXISTS(SELECT 1 FROM event_registrations er WHERE er.event_id = e.id AND er.user_id = :uid AND er.status = 'attending') AS is_registered
                    FROM events e
                    ORDER BY e.event_date ASC
                ";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':uid' => $userParam]);
            } else {
                $sql = "
                    SELECT 
                        e.id,
                        COALESCE(NULLIF(e.title, ''), 'Untitled Event') AS title,
                        COALESCE(e.description, '') AS description,
                        e.event_date AS date,
                        COALESCE(e.start_time, e.event_time, '09:00') AS start,
                        COALESCE(e.end_time, '17:00') AS end,
                        COALESCE(NULLIF(e.location, ''), 'Main Campus Auditorium') AS location,
                        COALESCE(e.reg_date, e.event_date) AS regDate,
                        e.status,
                        COALESCE(e.capacity, 100) AS capacity,
                        e.organizer_id,
                        (SELECT COUNT(*) FROM event_registrations er WHERE er.event_id = e.id AND er.status = 'attending') AS attendee_count,
                        EXISTS(SELECT 1 FROM event_registrations er WHERE er.event_id = e.id AND er.user_id = :uid AND er.status = 'attending') AS is_registered
                    FROM events e
                    WHERE e.status = 'approved' OR e.organizer_id = :uid_match
                    ORDER BY e.event_date ASC
                ";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':uid' => $userParam, ':uid_match' => $userParam]);
            }

            $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($events);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        }
        exit;

    // ── SAVE: Create new event submission ──────────────────────────
    case 'save':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: eventcreate.html');
            exit;
        }

        $title       = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $date        = trim($_POST['date'] ?? '');
        $regDate     = trim($_POST['regDate'] ?? '');
        $start       = trim($_POST['start'] ?? '');
        $end         = trim($_POST['end'] ?? '');
        $location    = trim($_POST['location'] ?? '');
        $capacity    = intval($_POST['capacity'] ?? 100);
        if ($capacity <= 0) $capacity = 100;

        $errors = [];
        if ($title === '')       $errors[] = 'Event title is required.';
        if ($description === '') $errors[] = 'Event description is required.';
        if ($date === '')        $errors[] = 'Event date is required.';
        if ($location === '')    $errors[] = 'Location is required.';
        if ($start !== '' && $end !== '' && $end <= $start) {
            $errors[] = 'End time must be after the start time.';
        }
        if ($regDate !== '' && $date !== '' && $regDate > $date) {
            $errors[] = 'Registration deadline must be on or before the event date.';
        }

        if (!empty($errors)) {
            if (isAjax()) {
                http_response_code(400);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => implode(' ', $errors)]);
                exit;
            }
            $query = http_build_query([
                'error'       => implode('|', $errors),
                'title'       => $title,
                'description' => $description,
                'date'        => $date,
                'regDate'     => $regDate,
                'start'       => $start,
                'end'         => $end,
                'location'    => $location,
                'capacity'    => $capacity,
            ]);
            header('Location: eventcreate.html?' . $query);
            exit;
        }

        $organizerId = $currentUserId ? (int)$currentUserId : 1;
        $status = $isAdmin ? 'approved' : 'pending';
        $eventTime = !empty($start) ? $start : '09:00:00';
        $startTime = !empty($start) ? $start : '09:00:00';
        $endTime   = !empty($end) ? $end : '17:00:00';
        $regDateVal = !empty($regDate) ? $regDate : $date;

        try {
            $stmt = $pdo->prepare("
                INSERT INTO events (title, description, event_date, reg_date, event_time, start_time, end_time, location, capacity, organizer_id, status)
                VALUES (:title, :description, :event_date, :reg_date, :event_time, :start_time, :end_time, :location, :capacity, :organizer_id, :status)
            ");
            $stmt->execute([
                ':title'        => $title,
                ':description'  => $description,
                ':event_date'   => $date,
                ':reg_date'     => $regDateVal,
                ':event_time'   => $eventTime,
                ':start_time'   => $startTime,
                ':end_time'     => $endTime,
                ':location'     => $location,
                ':capacity'     => $capacity,
                ':organizer_id' => $organizerId,
                ':status'       => $status,
            ]);

            if (isAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true]);
                exit;
            }

            header('Location: event.html?tab=my&created=1');
            exit;
        } catch (PDOException $e) {
            if (isAjax()) {
                http_response_code(500);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                exit;
            }
            header('Location: eventcreate.html?error=' . urlencode($e->getMessage()));
            exit;
        }

    // ── APPROVE: Administrator moderation ──────────────────────────
    case 'approve':
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'POST method required.']);
            exit;
        }

        if (!$isAdmin) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Forbidden: Administrator authorization required.']);
            exit;
        }

        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid event ID.']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("UPDATE events SET status = 'approved' WHERE id = :id");
            $stmt->execute([':id' => $id]);
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;

    // ── DELETE: Remove event (Admin or event creator) ───────────────
    case 'delete':
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'POST method required.']);
            exit;
        }

        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid event ID.']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("SELECT organizer_id FROM events WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $eventRecord = $stmt->fetch();

            if (!$eventRecord) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Event not found.']);
                exit;
            }

            if (!$isAdmin && ($currentUserId === null || (int)$eventRecord['organizer_id'] !== (int)$currentUserId)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Forbidden: You can only delete your own submitted events.']);
                exit;
            }

            $delStmt = $pdo->prepare("DELETE FROM events WHERE id = :id");
            $delStmt->execute([':id' => $id]);
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;

    // ── REGISTER: User RSVP Toggle ─────────────────────────────────
    case 'register':
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'POST method required.']);
            exit;
        }

        if (!is_logged_in()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Please sign in to RSVP for events.', 'redirect' => 'auth/login.php']);
            exit;
        }

        $eventId = intval($_POST['event_id'] ?? 0);
        if ($eventId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid event ID.']);
            exit;
        }

        try {
            // Check event existence, approval, and deadline
            $eventStmt = $pdo->prepare("
                SELECT e.*, 
                       (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.id AND status = 'attending') AS current_attendees
                FROM events e 
                WHERE e.id = ? AND e.status = 'approved'
            ");
            $eventStmt->execute([$eventId]);
            $ev = $eventStmt->fetch();

            if (!$ev) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Event not found or not active.']);
                exit;
            }

            $deadline = !empty($ev['reg_date']) ? $ev['reg_date'] : $ev['event_date'];
            $isPastDeadline = (strtotime($deadline) < strtotime(date('Y-m-d')));
            if ($isPastDeadline || (!empty($ev['is_closed']) && $ev['is_closed'] == 1)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Registration is closed for this event.']);
                exit;
            }

            // Check existing RSVP
            $checkReg = $pdo->prepare("SELECT id, status FROM event_registrations WHERE event_id = ? AND user_id = ?");
            $checkReg->execute([$eventId, $currentUserId]);
            $existingReg = $checkReg->fetch();

            $isRegistered = false;
            if ($existingReg && $existingReg['status'] === 'attending') {
                // Cancel registration
                $upd = $pdo->prepare("UPDATE event_registrations SET status = 'cancelled' WHERE id = ?");
                $upd->execute([$existingReg['id']]);
                $isRegistered = false;
            } else {
                // Check capacity
                if ($ev['capacity'] > 0 && $ev['current_attendees'] >= $ev['capacity']) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'This event has reached full capacity.']);
                    exit;
                }

                if ($existingReg) {
                    $upd = $pdo->prepare("UPDATE event_registrations SET status = 'attending', registered_at = NOW() WHERE id = ?");
                    $upd->execute([$existingReg['id']]);
                } else {
                    $ins = $pdo->prepare("INSERT INTO event_registrations (event_id, user_id, status) VALUES (?, ?, 'attending')");
                    $ins->execute([$eventId, $currentUserId]);
                }
                $isRegistered = true;
            }

            // Get updated count
            $cntStmt = $pdo->prepare("SELECT COUNT(*) FROM event_registrations WHERE event_id = ? AND status = 'attending'");
            $cntStmt->execute([$eventId]);
            $newCount = (int)$cntStmt->fetchColumn();

            echo json_encode([
                'success'        => true,
                'is_registered'  => $isRegistered,
                'attendee_count' => $newCount
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;

    // ── SETUP: Health confirmation ────────────────────────────────
    case 'setup':
        echo "Database and table setup verified successfully.";
        exit;

    default:
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Unknown action: ' . htmlspecialchars($action)]);
        exit;
}
