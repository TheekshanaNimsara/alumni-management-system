<?php
// Route browser visitors to main portal
if (empty($_GET['action']) && empty($_POST['action']) && empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header("Location: jobs.php");
    exit;
}

require_once __DIR__ . '/config/db.php';
    // Migrate older schemas
    try { $pdo->exec("ALTER TABLE jobs ADD COLUMN contact_email VARCHAR(200) AFTER description"); } catch (PDOException $e) {}
    try { $pdo->exec("ALTER TABLE jobs ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'pending' AFTER contact_email"); } catch (PDOException $e) {}


// ── Helpers ────────────────────────────────────────────────────────────────
function isAjax(): bool {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) ||
           (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
}

// ── Route ──────────────────────────────────────────────────────────────────
$action = $_GET['action'] ?? $_POST['action'] ?? 'get';

switch ($action) {

    // ── GET: return jobs as JSON ───────────────────────────────────────────
    case 'get':
        header('Content-Type: application/json');
        $statusFilter = $_GET['status'] ?? 'approved';
        if ($statusFilter === 'all') {
            $stmt = $pdo->query("SELECT * FROM jobs ORDER BY id DESC");
        } else {
            $stmt = $pdo->prepare("SELECT * FROM jobs WHERE status = :status ORDER BY id DESC");
            $stmt->execute([':status' => $statusFilter]);
        }
        echo json_encode($stmt->fetchAll());
        exit;

    // ── CREATE: insert a new job posting ──────────────────────────────────
    case 'create':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'POST required.']);
            exit;
        }

        // Accept both jobcreate.html field names AND generic names
        $title         = trim($_POST['jobTitle']       ?? $_POST['title']   ?? '');
        $company       = trim($_POST['companyName']    ?? $_POST['company'] ?? '');
        $location      = trim($_POST['location']       ?? '');
        $type          = trim($_POST['jobType']        ?? $_POST['type']    ?? '');
        $description   = trim($_POST['description']    ?? '');
        $contact_email = trim($_POST['contactEmail']   ?? '');

        $errors = [];
        if ($title === '')   $errors[] = 'Job title is required.';
        if ($company === '') $errors[] = 'Company name is required.';
        if ($type === '')    $errors[] = 'Job type is required.';

        if (!empty($errors)) {
            $msg = implode(' ', $errors);
            if (isAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => $msg]);
            } else {
                header('Location: jobcreate.html?error=' . urlencode($msg));
            }
            exit;
        }

        try {
            $stmt = $pdo->prepare("
                INSERT INTO jobs (title, company, location, type, description, contact_email, status)
                VALUES (:title, :company, :location, :type, :description, :contact_email, 'pending')
            ");
            $stmt->execute([
                ':title'         => $title,
                ':company'       => $company,
                ':location'      => $location,
                ':type'          => $type,
                ':description'   => $description,
                ':contact_email' => $contact_email,
            ]);

            if (isAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true]);
            } else {
                header('Location: jobs.html?success=1');
            }
        } catch (PDOException $e) {
            if (isAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            } else {
                header('Location: jobcreate.html?error=' . urlencode($e->getMessage()));
            }
        }
        exit;

    // ── APPROVE: set status = approved ────────────────────────────────────
    case 'approve':
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'POST required.']);
            exit;
        }

        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid job ID.']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("UPDATE jobs SET status = 'approved' WHERE id = :id");
            $stmt->execute([':id' => $id]);
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;

    // ── DELETE: remove a job by id ─────────────────────────────────────────
    case 'delete':
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'POST required.']);
            exit;
        }

        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid job ID.']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("DELETE FROM jobs WHERE id = :id");
            $stmt->execute([':id' => $id]);
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;

    // ── SETUP: verify DB / table (plain text) ─────────────────────────────
    case 'setup':
        echo 'Database and jobs table verified/created successfully.';
        exit;

    // ── Unknown action ────────────────────────────────────────────────────
    default:
        header('Content-Type: application/json');
        echo json_encode(['error' => "Unknown action: $action"]);
        exit;
}
