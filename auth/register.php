<?php
// ============================================================
// University Alumni Network - Member Registration (Split Layout)
// ============================================================
require_once __DIR__ . '/../config/db.php';

$error = '';
$success = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $grad_year = intval($_POST['grad_year'] ?? 0);
    $degree = trim($_POST['degree'] ?? '');
    $department = trim($_POST['department'] ?? '');

    // Server-side validation
    if (empty($first_name) || empty($last_name) || empty($username) || empty($email) || empty($password) || empty($degree) || empty($department) || empty($grad_year)) {
        $error = "All fields are required. Please complete the form.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please provide a valid email address.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match. Please re-enter.";
    } elseif ($grad_year < 1960 || $grad_year > 2030) {
        $error = "Please enter a realistic graduation year (1960 - 2030).";
    } else {
        if ($db_connected && $pdo) {
            try {
                // Check if email or username already exists
                $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ? LIMIT 1");
                $checkStmt->execute([$email, $username]);
                if ($checkStmt->fetch()) {
                    $error = "An account with this email address or username already exists.";
                } else {
                    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

                    // Insert into users
                    $pdo->beginTransaction();

                    $userStmt = $pdo->prepare("
                        INSERT INTO users (username, first_name, last_name, email, password, role, status)
                        VALUES (?, ?, ?, ?, ?, 'alumni', 'active')
                    ");
                    $userStmt->execute([$username, $first_name, $last_name, $email, $hashed_password]);
                    $new_user_id = $pdo->lastInsertId();

                    // Insert into alumni_profiles
                    $profileStmt = $pdo->prepare("
                        INSERT INTO alumni_profiles (user_id, graduation_year, degree_programme, department, profile_picture, is_public)
                        VALUES (?, ?, ?, ?, 'default-avatar.svg', 1)
                    ");
                    $profileStmt->execute([$new_user_id, $grad_year, $degree, $department]);

                    $pdo->commit();

                    // Log in immediately - regenerate session ID for security
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $new_user_id;
                    $_SESSION['user_name'] = $first_name . ' ' . $last_name;
                    $_SESSION['user_role'] = 'alumni';
                    $_SESSION['user_email'] = $email;

                    header("Location: ../index.php?registered=1");
                    exit;
                }
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error = "Database error during registration: " . $e->getMessage();
            }
        } else {
            // Demo fallback registration
            $_SESSION['user_id'] = 999;
            $_SESSION['user_name'] = $first_name . ' ' . $last_name;
            $_SESSION['user_role'] = 'alumni';
            $_SESSION['user_email'] = $email;

            header("Location: ../index.php?registered=1");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join the Alumni Network - Register</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body style="background-color: var(--bg-color);">

    <!-- Navbar -->
    <header class="navbar">
        <div class="navbar-container">
            <a href="../index.php" class="logo">
                <span class="logo-badge">AN</span>
                <span>ALUMNI <span class="logo-gold">NETWORK</span></span>
            </a>
            <a href="../index.php" class="nav-link">&larr; Return to Homepage</a>
        </div>
    </header>

    <div class="auth-wrapper" style="padding-top: 2rem; padding-bottom: 3rem;">
        <div class="auth-split-card" style="max-width: 1040px;">
            
            <!-- Left Side: Cinematic Navy Branding & Quote -->
            <div class="auth-split-left">
                <div class="auth-brand">
                    <span class="logo-badge">AN</span> ALUMNI NETWORK
                </div>

                <div class="auth-quote-box">
                    <div class="auth-quote-mark">&ldquo;</div>
                    <div class="auth-quote">
                        A lifetime of connections begins here.
                    </div>
                    <div class="auth-quote-sub">
                        Become part of our global alumni roster. Celebrate milestones, unlock career pathways, and inspire future generations of graduates and leaders.
                    </div>
                </div>

                <div style="font-size: 0.85rem; color: rgba(248, 244, 234, 0.75);">
                    University Alumni Association &bull; Open to All Faculties &amp; Disciplines
                </div>
            </div>

            <!-- Right Side: Registration Form -->
            <div class="auth-split-right">
                <div class="auth-form-header">
                    <h2 class="auth-form-title">Create Alumni Account</h2>
                    <p class="auth-form-subtitle">Join fellow university graduates worldwide</p>
                </div>

                <!-- Client-side error banner -->
                <div id="registerErrorAlert" class="alert alert-danger" style="display: none;"></div>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form id="registerForm" method="POST" action="register.php">
                    
                    <!-- Name row -->
                    <div class="auth-row">
                        <div class="form-group">
                            <label for="first_name">First Name *</label>
                            <input type="text" id="first_name" name="first_name" class="form-control" 
                                   placeholder="e.g. John"
                                   value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="last_name">Last Name *</label>
                            <input type="text" id="last_name" name="last_name" class="form-control" 
                                   placeholder="e.g. Doe"
                                   value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>" required>
                        </div>
                    </div>

                    <!-- Username & Email row -->
                    <div class="auth-row">
                        <div class="form-group">
                            <label for="username">Username *</label>
                            <input type="text" id="username" name="username" class="form-control" 
                                   placeholder="e.g. jdoe22" 
                                   value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email Address *</label>
                            <input type="email" id="email" name="email" class="form-control" 
                                   placeholder="e.g. name@alumni.edu" 
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                        </div>
                    </div>

                    <!-- Academics row 1: Degree & Department (Open to all degree holders) -->
                    <div class="auth-row">
                        <div class="form-group">
                            <label for="degree">Degree Programme *</label>
                            <input type="text" id="degree" name="degree" class="form-control" 
                                   placeholder="e.g. B.Sc., B.B.A., B.A., M.Sc." 
                                   value="<?php echo htmlspecialchars($_POST['degree'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="department">Department / Faculty *</label>
                            <input type="text" id="department" name="department" class="form-control" 
                                   placeholder="e.g. Business, Science, Computing, Arts" 
                                   value="<?php echo htmlspecialchars($_POST['department'] ?? ''); ?>" required>
                        </div>
                    </div>

                    <!-- Graduation Year -->
                    <div class="form-group">
                        <label for="grad_year">Graduation Year *</label>
                        <input type="number" id="grad_year" name="grad_year" class="form-control" 
                               min="1960" max="2030" placeholder="e.g. 2022" 
                               value="<?php echo htmlspecialchars($_POST['grad_year'] ?? ''); ?>" required>
                    </div>

                    <!-- Password row -->
                    <div class="auth-row">
                        <div class="form-group">
                            <label for="reg_password">Password (Min. 8 chars) *</label>
                            <input type="password" id="reg_password" name="password" class="form-control" 
                                   placeholder="Create password" required>
                        </div>
                        <div class="form-group">
                            <label for="reg_confirm_password">Confirm Password *</label>
                            <input type="password" id="reg_confirm_password" name="confirm_password" class="form-control" 
                                   placeholder="Re-enter password" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-gold btn-block gold-glow" style="margin-top: 0.5rem;">
                        Complete Registration &amp; Join
                    </button>
                </form>

                <div style="margin-top: 1.5rem; text-align: center; font-size: 0.92rem; color: var(--text-muted);">
                    Already registered as an alumnus? 
                    <a href="login.php" style="color: var(--primary-color); font-weight: 700; text-decoration: underline;">
                        Sign In Here
                    </a>
                </div>
            </div>

        </div>
    </div>

    <!-- Client-side validation script -->
    <script src="../assets/js/main.js"></script>
</body>
</html>
