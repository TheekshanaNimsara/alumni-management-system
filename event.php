<?php
$DB_HOST = 'localhost';
$DB_NAME = 'alumni_events';
$DB_USER = 'root';
$DB_PASS = '';

try {
    // 1. Connect to MySQL server and ensure database exists
    $pdo = new PDO("mysql:host=$DB_HOST;charset=utf8mb4", $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `alumni_events`");
    $pdo->exec("USE `alumni_events`");

    // 2. Ensure events table exists
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS events (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            event_date DATE NOT NULL,
            start_time TIME NOT NULL,
            end_time TIME NOT NULL,
            location VARCHAR(255) NOT NULL,
            reg_date DATE NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // Ensure status column exists if table existed previously
    $stmt = $pdo->query("SHOW COLUMNS FROM events LIKE 'status'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE events ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'pending'");
        $pdo->exec("UPDATE events SET status = 'approved'");
    }

    // Seed sample data if empty
    $count = $pdo->query("SELECT COUNT(*) FROM events")->fetchColumn();
    if ($count == 0) {
        $pdo->exec("
            INSERT INTO events (title, event_date, start_time, end_time, location, reg_date, status) VALUES
            ('Annual Alumni Tech & Innovation Symposium 2026', '2026-11-15', '10:00:00', '16:00:00', 'Main Campus Auditorium', '2026-11-10', 'approved'),
            ('Career Mentorship & Leadership Workshop', '2026-10-25', '14:00:00', '17:00:00', 'Online (Zoom)', '2026-10-20', 'approved'),
            ('Spring Alumni Gala & Networking Dinner', '2026-09-01', '18:00:00', '22:00:00', 'Grand Ballroom, City Hotel', '2026-08-25', 'approved')
        ");
    }
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}

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
