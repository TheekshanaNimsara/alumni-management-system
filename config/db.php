<?php

//To provide the essential tools (database connection, session security, and helper functions) required by the rest of the application.

// Session security hardening
if (session_status() === PHP_SESSION_NONE) {
    ini_set("session.use_only_cookies", 1);
    ini_set("session.use_strict_mode", 1);
    $is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
    session_set_cookie_params([
        "lifetime" => 0,
        "path" => "/",
        "secure" => $is_https,
        "httponly" => true,
        "samesite" => "Lax"
    ]);
    session_start();
}

// ------------------------------------------------------------
// Authentication & Role Helpers
// ------------------------------------------------------------
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function current_user_id() {
    return $_SESSION['user_id'] ?? null;
}

function is_admin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function require_login($redirect = null) {
    //Never save a copy of this page in your history.
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Pragma: no-cache");
    
    //It uses the first function to check if you are logged in. If you are not, it forces your browser to redirect to the login.php page.

    if (!is_logged_in()) {
        $target = $redirect ? $redirect : get_base_url() . 'auth/login.php';
        header("Location: " . $target);
        exit;
    }
}

function require_admin($redirect = null) {
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Pragma: no-cache");
    if (!is_admin()) {
        $target = $redirect ? $redirect : get_base_url() . 'auth/login.php?admin_required=1';
        header("Location: " . $target);
        exit;
    }
}

function get_unread_messages_count($pdo, $user_id) {
    if (!$pdo || !$user_id) return 0;
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0");
        $stmt->execute([$user_id]);
        return (int)$stmt->fetchColumn();
    } catch (Exception $e) {
        return 0;
    }
}

function get_pending_reports_count($pdo) {
    if (!$pdo) return 0;
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'pending'");
        return (int)$stmt->fetchColumn();
    } catch (Exception $e) {
        return 0;
    }
}

$db_host = '127.0.0.1';
$db_name = 'alumni_network';
$db_user = 'root';
$db_pass = '';

$pdo = null;
$db_connected = false;

try {
    $pdo = new PDO("mysql:host={$db_host};dbname={$db_name};charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_TIMEOUT => 1
    ]);
    $db_connected = true;
} catch (PDOException $e) {
    // If database does not exist yet, attempt auto-creation
    try {
        $root_pdo = new PDO("mysql:host={$db_host};charset=utf8mb4", $db_user, $db_pass);
        $root_pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db_name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        
        $pdo = new PDO("mysql:host={$db_host};dbname={$db_name};charset=utf8mb4", $db_user, $db_pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        
        //get sql schema from the schema.sql file and execute it to create tables and seed data
        $schemaPath = __DIR__ . '/../database/schema.sql';
        if (file_exists($schemaPath)) {
            $sql = file_get_contents($schemaPath);
            $pdo->exec($sql);
        }
        $db_connected = true;
    } catch (Exception $sub_ex) {
        $db_connected = false;
    }
}

if ($db_connected && $pdo) {
    try { $pdo->exec("ALTER TABLE events ADD COLUMN reg_date DATE DEFAULT NULL AFTER event_date"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE events ADD COLUMN start_time TIME DEFAULT NULL AFTER event_time"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE events ADD COLUMN end_time TIME DEFAULT NULL AFTER start_time"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE events ADD COLUMN capacity INT NOT NULL DEFAULT 100 AFTER location"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE jobs ADD COLUMN contact_email VARCHAR(200) DEFAULT NULL AFTER description"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE jobs ADD COLUMN requirements TEXT DEFAULT NULL AFTER contact_email"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE jobs ADD COLUMN deadline DATE DEFAULT NULL AFTER application_link"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE alumni_profiles ADD COLUMN skills TEXT NULL AFTER bio"); } catch (Exception $e) {}
}

// ------------------------------------------------------------
// Helper: Get base URL for clean internal routing
// ------------------------------------------------------------
function get_base_url() {
    if (!isset($_SERVER['SCRIPT_NAME']) || php_sapi_name() === 'cli') {
        return './';
    }
    $script_dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    $parts = array_values(array_filter(explode('/', trim($script_dir, '/')), function($p) {
        return $p !== '' && $p !== '.';
    }));
    if (!empty($parts) && in_array(end($parts), ['auth', 'admin', 'config', 'includes'])) {
        array_pop($parts);
    }
    $path = implode('/', $parts);
    return '/' . ($path ? $path . '/' : '');
}

// ------------------------------------------------------------
// Helper: Fallback Alumni Data (When MySQL is offline)
// ------------------------------------------------------------
function get_sample_alumni() {
    return [
        [
            'id' => 2,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'degree_programme' => 'B.Sc. in Computer Engineering',
            'department' => 'Computer Engineering',
            'current_job_title' => 'Software Engineer',
            'current_company' => 'Google',
            'graduation_year' => 2022,
            'location' => 'Mountain View, CA',
            'profile_picture' => 'default-avatar.svg',
            'bio' => 'Passionate about distributed cloud systems, modern web platforms, and mentoring university students.'
        ],
        [
            'id' => 3,
            'first_name' => 'Sarah',
            'last_name' => 'Jenkins',
            'degree_programme' => 'B.Sc. in Computer Engineering',
            'department' => 'Computer Engineering',
            'current_job_title' => 'Lead AI Researcher',
            'current_company' => 'Google DeepMind',
            'graduation_year' => 2020,
            'location' => 'London, UK',
            'profile_picture' => 'default-avatar.svg',
            'bio' => 'Focusing on large generative AI models and efficient on-device intelligence. Proud university alumna.'
        ]
    ];
}

// ------------------------------------------------------------
// Helper: Fallback Events Data
// ------------------------------------------------------------
function get_sample_events() {
    return [
        [
            'id' => 1,
            'title' => 'ALUMNI MEET 2026',
            'description' => 'The flagship annual gathering of alumni, faculty, and graduating students. Reconnect with batchmates, tour the newly expanded university research quad, and celebrate community achievements.',
            'event_date' => '2026-10-24',
            'event_time' => '17:00',
            'location' => 'Main Campus Grand Auditorium',
            'organizer_name' => 'Alumni Relations Directorate',
            'status' => 'approved'
        ],
        [
            'id' => 2,
            'title' => 'GLOBAL TECH & AI SUMMIT',
            'description' => 'A premier showcase featuring keynotes by prominent alumni leaders at Google, AWS, and Intel. Topics include generative AI, semiconductor hardware, and cloud resilience.',
            'event_date' => '2026-11-15',
            'event_time' => '10:00',
            'location' => 'University Main Hall',
            'organizer_name' => 'University Alumni Council',
            'status' => 'approved'
        ]
    ];
}

// ------------------------------------------------------------
// Helper: Fallback Jobs Data
// ------------------------------------------------------------
function get_sample_jobs() {
    return [
        [
            'id' => 1,
            'title' => 'Software Engineer',
            'company' => 'ABC Technologies',
            'location' => 'Colombo, Sri Lanka',
            'job_type' => 'Full-Time',
            'description' => 'Seeking an ambitious software engineer proficient in modern full-stack web technologies, clean API design, and relational databases. Mentorship provided.',
            'application_link' => 'https://careers.abctechnologies.com',
            'status' => 'approved',
            'posted_at' => '2026-10-01'
        ],
        [
            'id' => 2,
            'title' => 'Cloud Solutions Architect',
            'company' => 'Virtusa',
            'location' => 'Colombo / Hybrid',
            'job_type' => 'Full-Time',
            'description' => 'Lead enterprise cloud architecture transformations for global financial clients. Deep expertise in microservices and scalable cloud patterns required.',
            'application_link' => 'https://virtusa.com/careers',
            'status' => 'approved',
            'posted_at' => '2026-10-02'
        ]
    ];
}

// ------------------------------------------------------------
// Helper: Fallback Reports Data (Two Examples)
// ------------------------------------------------------------
function get_sample_reports() {
    return [
        [
            'id' => 1,
            'reporter_id' => 2,
            'reporter_name' => 'John Doe',
            'reporter_email' => 'john.doe@alumni.edu',
            'reported_user_id' => 7,
            'reported_user_name' => 'Priya Sharma',
            'reported_user_email' => 'priya.s@alumni.edu',
            'reported_user_status' => 'active',
            'event_id' => null,
            'event_title' => null,
            'job_id' => null,
            'job_title' => null,
            'message_id' => null,
            'reason' => 'Suspicious / Inactive Profile Information',
            'description' => 'Please verify profile details and graduation credentials.',
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s', strtotime('-2 days'))
        ],
        [
            'id' => 2,
            'reporter_id' => 3,
            'reporter_name' => 'Sarah Jenkins',
            'reporter_email' => 'sarah.j@alumni.edu',
            'reported_user_id' => null,
            'reported_user_name' => null,
            'reported_user_email' => null,
            'reported_user_status' => null,
            'event_id' => 1,
            'event_title' => 'ALUMNI MEET 2026',
            'job_id' => null,
            'job_title' => null,
            'message_id' => null,
            'reason' => 'Outdated Venue Location',
            'description' => 'Event location mentions the old campus quad instead of the newly expanded Grand Auditorium.',
            'status' => 'reviewed',
            'created_at' => date('Y-m-d H:i:s', strtotime('-5 days'))
        ]
    ];
}

