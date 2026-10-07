<?php
// ============================================================
// University Alumni Network - Database Connection & Helpers
// Pure PHP & PDO for beginner-friendly, secure SQL queries
// ============================================================

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
// CSRF Protection Helpers
// ------------------------------------------------------------
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    $token = generate_csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

function verify_csrf_token($token = null) {
    if ($token === null) {
        $token = $_POST['csrf_token'] ?? '';
    }
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
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
    if (!is_logged_in()) {
        $target = $redirect ? $redirect : get_base_url() . 'auth/login.php';
        header("Location: " . $target);
        exit;
    }
}

function require_admin($redirect = null) {
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
        ],
        [
            'id' => 4,
            'first_name' => 'Michael',
            'last_name' => 'Chen',
            'degree_programme' => 'B.Sc. in Electrical Engineering',
            'department' => 'Electrical Engineering',
            'current_job_title' => 'Senior Systems Architect',
            'current_company' => 'Intel Corporation',
            'graduation_year' => 2019,
            'location' => 'Austin, TX',
            'profile_picture' => 'default-avatar.svg',
            'bio' => 'Developing next-generation high performance computing architectures and semiconductor interconnects.'
        ],
        [
            'id' => 5,
            'first_name' => 'Ananya',
            'last_name' => 'Perera',
            'degree_programme' => 'B.Sc. in Software Engineering',
            'department' => 'Computer Engineering',
            'current_job_title' => 'Senior Cloud Consultant',
            'current_company' => 'Amazon Web Services',
            'graduation_year' => 2021,
            'location' => 'Singapore',
            'profile_picture' => 'default-avatar.svg',
            'bio' => 'Helping enterprises build resilient architectures on AWS. Active participant in community hackathons.'
        ],
        [
            'id' => 6,
            'first_name' => 'David',
            'last_name' => 'Kim',
            'degree_programme' => 'B.Sc. in Information Technology',
            'department' => 'Information Technology',
            'current_job_title' => 'Director of Engineering',
            'current_company' => 'Stripe',
            'graduation_year' => 2018,
            'location' => 'Dublin, Ireland',
            'profile_picture' => 'default-avatar.svg',
            'bio' => 'Leading developer experience and payments infrastructure teams. Committed to tech talent growth.'
        ],
        [
            'id' => 7,
            'first_name' => 'Priya',
            'last_name' => 'Sharma',
            'degree_programme' => 'B.Sc. in Computer Engineering',
            'department' => 'Computer Engineering',
            'current_job_title' => 'Product Lead',
            'current_company' => 'Microsoft',
            'graduation_year' => 2021,
            'location' => 'Seattle, WA',
            'profile_picture' => 'default-avatar.svg',
            'bio' => 'Building intelligent enterprise workplace products. Passionate about empowering women in engineering.'
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
        ],
        [
            'id' => 3,
            'title' => 'ANNUAL CAREER & INTERNSHIP EXPO',
            'description' => 'Connect directly with leading multinational tech giants, research institutes, and innovative startups. Resume reviews, on-site interviews, and networking booths.',
            'event_date' => '2026-12-05',
            'event_time' => '09:00',
            'location' => 'University Convocation Grounds',
            'organizer_name' => 'Career Guidance Unit',
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
        ],
        [
            'id' => 3,
            'title' => 'Associate AI Engineer',
            'company' => 'WSO2',
            'location' => 'Colombo, Sri Lanka',
            'job_type' => 'Full-Time',
            'description' => 'Work on open-source API management combined with LLM orchestrations. Great opportunity for recent university graduates.',
            'application_link' => 'https://wso2.com/careers',
            'status' => 'approved',
            'posted_at' => '2026-10-03'
        ],
        [
            'id' => 4,
            'title' => 'DevOps & Platform Intern',
            'company' => 'Sysco LABS',
            'location' => 'Colombo, Sri Lanka',
            'job_type' => 'Internship',
            'description' => 'Exciting 6-month internship on continuous deployment pipelines, Kubernetes cluster management, and infrastructure observability.',
            'application_link' => 'https://syscolabs.com/careers',
            'status' => 'approved',
            'posted_at' => '2026-10-04'
        ]
    ];
}
