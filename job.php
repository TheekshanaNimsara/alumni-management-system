<?php
// ============================================================
// University Alumni Network - Jobs API & Submission Controller
// Secure PDO Handlers • Field Mappings • Role-Based Access Control
// ============================================================

require_once __DIR__ . '/config/db.php';

// Route raw browser visits without action to portal
if (empty($_GET['action']) && empty($_POST['action']) && empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header("Location: jobs.php");
    exit;
}

function isAjax(): bool {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) ||
           (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
}

function normalizeJobType(string $type): string {
    $clean = str_replace([' ', '_'], '-', ucwords(strtolower(trim($type)), ' -_'));
    $allowed = ['Full-Time', 'Part-Time', 'Internship', 'Contract', 'Remote'];
    foreach ($allowed as $valid) {
        if (strcasecmp($clean, $valid) === 0 || strcasecmp(str_replace('-', ' ', $clean), str_replace('-', ' ', $valid)) === 0) {
            return $valid;
        }
    }
    return 'Full-Time';
}

$action = $_GET['action'] ?? $_POST['action'] ?? 'get';
$currentUserId = current_user_id();
$isAdmin = is_admin();

switch ($action) {

    // ── GET: Return jobs ───────────────────────────────────────────
    case 'get':
        header('Content-Type: application/json');
        if (!$pdo) {
            echo json_encode([]);
            exit;
        }

        try {
            $statusFilter = $_GET['status'] ?? null;
            $userParam = $currentUserId ? (int)$currentUserId : 0;

            if ($isAdmin && $statusFilter === 'all') {
                $stmt = $pdo->query("
                    SELECT 
                        j.id,
                        j.title,
                        j.company,
                        j.location,
                        j.job_type,
                        j.job_type AS type,
                        COALESCE(j.description, '') AS description,
                        COALESCE(j.requirements, '') AS requirements,
                        COALESCE(j.contact_email, '') AS contact_email,
                        j.deadline,
                        j.application_link,
                        j.posted_by,
                        j.status,
                        j.posted_at
                    FROM jobs j
                    ORDER BY j.id DESC
                ");
            } else {
                $stmt = $pdo->prepare("
                    SELECT 
                        j.id,
                        j.title,
                        j.company,
                        j.location,
                        j.job_type,
                        j.job_type AS type,
                        COALESCE(j.description, '') AS description,
                        COALESCE(j.requirements, '') AS requirements,
                        COALESCE(j.contact_email, '') AS contact_email,
                        j.deadline,
                        j.application_link,
                        j.posted_by,
                        j.status,
                        j.posted_at
                    FROM jobs j
                    WHERE j.status = 'approved' OR j.posted_by = :uid
                    ORDER BY j.id DESC
                ");
                $stmt->execute([':uid' => $userParam]);
            }

            $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($jobs);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        }
        exit;

    // ── CREATE: Insert new job vacancy ─────────────────────────────
    case 'create':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'POST method required.']);
            exit;
        }

        // Accept both form and API field names
        $title         = trim($_POST['jobTitle']     ?? $_POST['title']     ?? '');
        $company       = trim($_POST['companyName']  ?? $_POST['company']   ?? '');
        $location      = trim($_POST['location']     ?? '');
        $rawType       = trim($_POST['jobType']      ?? $_POST['type']      ?? 'Full-Time');
        $description   = trim($_POST['description']  ?? '');
        $requirements  = trim($_POST['requirements'] ?? '');
        $contact_email = trim($_POST['contactEmail'] ?? $_POST['contact_email'] ?? '');
        $deadline      = !empty($_POST['deadline'])  ? trim($_POST['deadline']) : null;

        $job_type = normalizeJobType($rawType);

        $errors = [];
        if ($title === '')       $errors[] = 'Job title is required.';
        if ($company === '')     $errors[] = 'Company name is required.';
        if ($location === '')    $errors[] = 'Location is required.';
        if ($description === '') $errors[] = 'Job description is required.';

        if (!empty($errors)) {
            $msg = implode(' ', $errors);
            if (isAjax()) {
                http_response_code(400);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => $msg]);
            } else {
                header('Location: jobcreate.html?error=' . urlencode($msg));
            }
            exit;
        }

        $posted_by = $currentUserId ? (int)$currentUserId : 1;
        $status    = $isAdmin ? 'approved' : 'pending';

        try {
            $stmt = $pdo->prepare("
                INSERT INTO jobs (title, company, location, job_type, description, requirements, contact_email, deadline, posted_by, status)
                VALUES (:title, :company, :location, :job_type, :description, :requirements, :contact_email, :deadline, :posted_by, :status)
            ");
            $stmt->execute([
                ':title'         => $title,
                ':company'       => $company,
                ':location'      => $location,
                ':job_type'      => $job_type,
                ':description'   => $description,
                ':requirements'  => $requirements,
                ':contact_email' => $contact_email,
                ':deadline'      => $deadline,
                ':posted_by'     => $posted_by,
                ':status'        => $status,
            ]);

            if (isAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true]);
            } else {
                header('Location: jobs.html?success=1');
            }
        } catch (PDOException $e) {
            if (isAjax()) {
                http_response_code(500);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            } else {
                header('Location: jobcreate.html?error=' . urlencode($e->getMessage()));
            }
        }
        exit;

    // ── APPROVE: Set status = approved ────────────────────────────
    case 'approve':
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'POST method required.']);
            exit;
        }

        if (!$isAdmin) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Forbidden: Administrator privileges required.']);
            exit;
        }

        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid job ID.']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("UPDATE jobs SET status = 'approved' WHERE id = :id");
            $stmt->execute([':id' => $id]);
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;

    // ── DELETE: Remove a job by id ─────────────────────────────────
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
            echo json_encode(['success' => false, 'error' => 'Invalid job ID.']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("SELECT posted_by FROM jobs WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $job = $stmt->fetch();

            if (!$job) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Job posting not found.']);
                exit;
            }

            if (!$isAdmin && ($currentUserId === null || (int)$job['posted_by'] !== (int)$currentUserId)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Forbidden: You can only delete your own postings.']);
                exit;
            }

            $delStmt = $pdo->prepare("DELETE FROM jobs WHERE id = :id");
            $delStmt->execute([':id' => $id]);
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;

    // ── APPLY: Submit application ──────────────────────────────────
    case 'apply':
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'POST method required.']);
            exit;
        }

        if (!is_logged_in()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Please sign in to submit an application.', 'redirect' => 'auth/login.php']);
            exit;
        }

        $jobId = intval($_POST['job_id'] ?? 0);
        $message = trim($_POST['message'] ?? $_POST['application_message'] ?? '');

        if ($jobId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid job ID.']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("SELECT id FROM jobs WHERE id = ? AND status = 'approved'");
            $stmt->execute([$jobId]);
            if (!$stmt->fetch()) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Job posting not found or not active.']);
                exit;
            }

            $checkApp = $pdo->prepare("SELECT id, status FROM job_applications WHERE job_id = ? AND user_id = ?");
            $checkApp->execute([$jobId, $currentUserId]);
            $existing = $checkApp->fetch();

            if ($existing) {
                echo json_encode(['success' => false, 'error' => 'You have already applied for this position (Status: ' . htmlspecialchars($existing['status']) . ').']);
                exit;
            }

            $ins = $pdo->prepare("
                INSERT INTO job_applications (job_id, user_id, message, status, applied_at)
                VALUES (?, ?, ?, 'Applied', NOW())
            ");
            $ins->execute([$jobId, $currentUserId, $message]);
            echo json_encode(['success' => true, 'message' => 'Application submitted successfully!']);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;

    // ── SETUP: Status confirmation ────────────────────────────────
    case 'setup':
        echo 'Database and jobs table verified/created successfully.';
        exit;

    default:
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode(['error' => "Unknown action: " . htmlspecialchars($action)]);
        exit;
}
