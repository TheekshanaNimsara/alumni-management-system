<?php
/**
 * Unified Jobs backend.
 *
 * Supported actions (via $_GET['action'] or $_POST['action']):
 *   get     – Returns all jobs as JSON.
 *   create  – Inserts a new job (status = pending by default).
 *               jobTitle, companyName, location, jobType, description, contactEmail
 *             (also accepts generic: title, company, type for AJAX callers)
 *   approve – Approves a job by id (POST field: id). Returns JSON.
 *   delete  – Deletes a job by id (POST field: id). Returns JSON.
 *   setup   – Creates the DB / table (returns plain text).
 *
 * create redirects to jobs.html on success when called as a regular form POST.
 * When called via XMLHttpRequest / fetch (X-Requested-With header) it returns JSON.
 */

$DB_HOST = 'localhost';
$DB_NAME = 'alumni_jobs';
$DB_USER = 'root';
$DB_PASS = '';

// ── DB Connection ──────────────────────────────────────────────────────────
try {
    $pdo = new PDO("mysql:host=$DB_HOST;charset=utf8mb4", $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$DB_NAME`");
    $pdo->exec("USE `$DB_NAME`");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS jobs (
            id            INT AUTO_INCREMENT PRIMARY KEY,
            title         VARCHAR(150) NOT NULL,
            company       VARCHAR(150) NOT NULL,
            location      VARCHAR(150),
            type          VARCHAR(50)  NOT NULL,
            description   TEXT,
            contact_email VARCHAR(200),
            status        VARCHAR(20)  NOT NULL DEFAULT 'pending',
            posted_at     DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    // Migrate older schemas
    try { $pdo->exec("ALTER TABLE jobs ADD COLUMN contact_email VARCHAR(200) AFTER description"); } catch (PDOException $e) {}
    try { $pdo->exec("ALTER TABLE jobs ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'pending' AFTER contact_email"); } catch (PDOException $e) {}
} catch (PDOException $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    die(json_encode(['success' => false, 'error' => 'DB connection failed: ' . $e->getMessage()]));
}

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
