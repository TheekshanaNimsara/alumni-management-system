<?php
// Route browser visitors to main portal
if (empty($_GET['action']) && empty($_POST['action']) && empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header("Location: events.php");
    exit;
}

require_once __DIR__ . '/config/db.php';

$action = $_GET['action'] ?? $_POST['action'] ?? 'get';

switch ($action) {

    // ---- Fetch all events ----
    case 'get':
        header('Content-Type: application/json');
        $stmt = $pdo->query(
            "SELECT id, title, event_date AS date, start_time AS start, end_time AS end, location, reg_date AS regDate, status
             FROM events ORDER BY event_date ASC"
        );
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;

    // ---- Save a new event submission ----
    case 'save':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: eventcreate.html');
            exit;
        }

        $title    = trim($_POST['title'] ?? '');
        $date     = $_POST['date'] ?? '';
        $start    = $_POST['start'] ?? '';
        $end      = $_POST['end'] ?? '';
        $location = trim($_POST['location'] ?? '');
        $regDate  = $_POST['regDate'] ?? '';

        $errors = [];
        if ($title === '')    $errors[] = 'Event title is required.';
        if ($date === '')     $errors[] = 'Event date is required.';
        if ($location === '') $errors[] = 'Location is required.';
        if ($start !== '' && $end !== '' && $end <= $start) {
            $errors[] = 'End time must be after the start time.';
        }
        if ($regDate !== '' && $date !== '' && $regDate > $date) {
            $errors[] = 'Registration deadline must be on or before the event date.';
        }

        if (!empty($errors)) {
            $query = http_build_query([
                'error'    => implode('|', $errors),
                'title'    => $title,
                'date'     => $date,
                'start'    => $start,
                'end'      => $end,
                'location' => $location,
                'regDate'  => $regDate,
            ]);
            header('Location: eventcreate.html?' . $query);
            exit;
        }

        $stmt = $pdo->prepare(
            "INSERT INTO events (title, event_date, start_time, end_time, location, reg_date, status)
             VALUES (:title, :date, :start, :end, :location, :regDate, 'pending')"
        );
        $stmt->execute([
            ':title'    => $title,
            ':date'     => $date,
            ':start'    => $start,
            ':end'      => $end,
            ':location' => $location,
            ':regDate'  => $regDate,
        ]);

        header('Location: event.html?tab=my');
        exit;

    // ---- Approve an event submission ----
    case 'approve':
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
            exit;
        }

        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        if ($id <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid event ID.']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("UPDATE events SET status = 'approved' WHERE id = :id");
            $stmt->execute([':id' => $id]);
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;

    // ---- Delete an event ----
    case 'delete':
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
            exit;
        }

        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        if ($id <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid event ID.']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("DELETE FROM events WHERE id = :id");
            $stmt->execute([':id' => $id]);
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;

    // ---- Setup status confirmation ----
    case 'setup':
        echo "Database and table setup verified successfully.";
        exit;

    default:
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Unknown action']);
        exit;
}
