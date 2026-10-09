<?php
// ============================================================
// Alumni Connect - Member Registration
// Cinematic Dark Purple Split-Screen Authentication Experience
// Secure PDO Transactions • Bcrypt Password Hashing • Session Hardening
// ============================================================
require_once __DIR__ . '/../config/db.php';

$error = '';
$success = '';

// If already authenticated, redirect
if (is_logged_in()) {
    header("Location: ../index.php");
    exit;
}

// Preserve submitted values (except passwords)
$formData = [
    'first_name' => '',
    'last_name' => '',
    'username' => '',
    'email' => '',
    'degree' => '',
    'department' => '',
    'grad_year' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['first_name'] = trim($_POST['first_name'] ?? '');
    $formData['last_name']  = trim($_POST['last_name'] ?? '');
    $formData['username']   = trim($_POST['username'] ?? '');
    $formData['email']      = strtolower(trim($_POST['email'] ?? ''));
    $formData['degree']     = trim($_POST['degree'] ?? '');
    $formData['department'] = trim($_POST['department'] ?? '');
    $formData['grad_year']  = trim($_POST['grad_year'] ?? '');

    $password         = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $gradYearInt      = (int)$formData['grad_year'];

    // Comprehensive Server-Side Validation
    if (
        empty($formData['first_name']) || 
        empty($formData['last_name']) || 
        empty($formData['username']) || 
        empty($formData['email']) || 
        empty($formData['degree']) || 
        empty($formData['department']) || 
        empty($formData['grad_year']) || 
        empty($password) || 
        empty($confirm_password)
    ) {
        $error = "All fields are required. Please complete the registration form.";
    } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $error = "Please provide a valid email address.";
    } elseif (!preg_match('/^[a-zA-Z0-9_.-]{3,30}$/', $formData['username'])) {
        $error = "Username must be between 3 and 30 characters and contain only letters, numbers, underscores, dots, or hyphens.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match. Please re-enter.";
    } elseif ($gradYearInt < 1960 || $gradYearInt > 2030) {
        $error = "Please specify a valid graduation year between 1960 and 2030.";
    } else {
        if ($db_connected && $pdo) {
            try {
                // Check for existing account by email or username
                $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ? LIMIT 1");
                $checkStmt->execute([$formData['email'], $formData['username']]);
                
                if ($checkStmt->fetch()) {
                    $error = "An account with that email address or username already exists. Please sign in or use different details.";
                } else {
                    // Hash password securely with PHP standard bcrypt
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                    // Execute database transaction across users and alumni_profiles
                    $pdo->beginTransaction();

                    // Insert into users table (Public registration strictly defaults to 'alumni' role)
                    $userStmt = $pdo->prepare("
                        INSERT INTO users (username, first_name, last_name, email, password, role, status)
                        VALUES (?, ?, ?, ?, ?, 'alumni', 'active')
                    ");
                    $userStmt->execute([
                        $formData['username'],
                        $formData['first_name'],
                        $formData['last_name'],
                        $formData['email'],
                        $hashedPassword
                    ]);
                    $newUserId = (int)$pdo->lastInsertId();

                    // Insert corresponding profile in alumni_profiles table
                    $profileStmt = $pdo->prepare("
                        INSERT INTO alumni_profiles (user_id, graduation_year, degree_programme, department, profile_picture, is_public)
                        VALUES (?, ?, ?, ?, 'default-avatar.svg', 1)
                    ");
                    $profileStmt->execute([
                        $newUserId,
                        $gradYearInt,
                        $formData['degree'],
                        $formData['department']
                    ]);

                    $pdo->commit();

                    // Post/Redirect/Get: Establish authenticated session & regenerate ID
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $newUserId;
                    $_SESSION['user_name'] = $formData['first_name'] . ' ' . $formData['last_name'];
                    $_SESSION['user_role'] = 'alumni';
                    $_SESSION['user_email'] = $formData['email'];

                    header("Location: ../index.php?registered=1");
                    exit;
                }
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log("Registration transaction failed: " . $e->getMessage());
                $error = "A system error occurred while creating your account. Please try again.";
            }
        } else {
            $error = "Database service is temporarily unavailable. Please try again shortly.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account &bull; Alumni Connect</title>
    <!-- Cinematic Dark Purple Authentication Stylesheet -->
    <link rel="stylesheet" href="../assets/css/auth.css">
</head>
<body class="auth-body">

    <!-- Top Navigation -->
    <header class="auth-nav">
        <a href="../index.php" class="auth-nav-logo">
            <span class="auth-logo-badge">AC</span>
            <span>ALUMNI <span style="color: var(--auth-lavender-soft);">CONNECT</span></span>
        </a>
        <a href="../index.php" class="auth-nav-back">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="19" y1="12" x2="5" y2="12"></line>
                <polyline points="12 19 5 12 12 5"></polyline>
            </svg>
            Return to Homepage
        </a>
    </header>

    <!-- Main Split-Screen Container -->
    <main class="auth-viewport">
        <div class="auth-card-split register-card">
            
            <!-- Left Column: Registration Form (~52%) -->
            <section class="auth-column-form" aria-labelledby="registerHeading">
                <div class="auth-form-brand">
                    <span class="auth-logo-badge">AC</span>
                    <span>ALUMNI <span class="highlight">CONNECT</span></span>
                </div>

                <div class="auth-form-header">
                    <h1 id="registerHeading" class="auth-form-title">Create an Account</h1>
                    <p class="auth-form-subtitle">Join your alumni network to reconnect and grow</p>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="auth-alert auth-alert-error" role="alert">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" action="register.php" novalidate>
                    
                    <!-- Full Name (first_name, last_name) -->
                    <div class="auth-grid-2">
                        <div class="auth-form-group">
                            <label for="first_name" class="auth-label">First Name *</label>
                            <input type="text" 
                                   id="first_name" 
                                   name="first_name" 
                                   class="auth-input" 
                                   placeholder="e.g. John" 
                                   value="<?php echo htmlspecialchars($formData['first_name']); ?>" 
                                   required 
                                   autofocus>
                        </div>
                        <div class="auth-form-group">
                            <label for="last_name" class="auth-label">Last Name *</label>
                            <input type="text" 
                                   id="last_name" 
                                   name="last_name" 
                                   class="auth-input" 
                                   placeholder="e.g. Doe" 
                                   value="<?php echo htmlspecialchars($formData['last_name']); ?>" 
                                   required>
                        </div>
                    </div>

                    <!-- Username & Email -->
                    <div class="auth-grid-2">
                        <div class="auth-form-group">
                            <label for="username" class="auth-label">Username *</label>
                            <input type="text" 
                                   id="username" 
                                   name="username" 
                                   class="auth-input" 
                                   placeholder="e.g. jdoe22" 
                                   value="<?php echo htmlspecialchars($formData['username']); ?>" 
                                   required>
                        </div>
                        <div class="auth-form-group">
                            <label for="email" class="auth-label">Email Address *</label>
                            <input type="email" 
                                   id="email" 
                                   name="email" 
                                   class="auth-input" 
                                   placeholder="e.g. name@alumni.edu" 
                                   value="<?php echo htmlspecialchars($formData['email']); ?>" 
                                   required>
                        </div>
                    </div>

                    <!-- Degree Programme & Department / Faculty -->
                    <div class="auth-grid-2">
                        <div class="auth-form-group">
                            <label for="degree" class="auth-label">Degree Programme *</label>
                            <input type="text" 
                                   id="degree" 
                                   name="degree" 
                                   class="auth-input" 
                                   placeholder="e.g. B.Sc. in Computer Engineering" 
                                   value="<?php echo htmlspecialchars($formData['degree']); ?>" 
                                   required>
                        </div>
                        <div class="auth-form-group">
                            <label for="department" class="auth-label">Department / Faculty *</label>
                            <input type="text" 
                                   id="department" 
                                   name="department" 
                                   class="auth-input" 
                                   placeholder="e.g. Computer Science & Eng." 
                                   value="<?php echo htmlspecialchars($formData['department']); ?>" 
                                   required>
                        </div>
                    </div>

                    <!-- Graduation Year -->
                    <div class="auth-form-group">
                        <label for="grad_year" class="auth-label">Graduation Year *</label>
                        <input type="number" 
                               id="grad_year" 
                               name="grad_year" 
                               class="auth-input" 
                               min="1960" 
                               max="2030" 
                               placeholder="e.g. 2022" 
                               value="<?php echo htmlspecialchars($formData['grad_year']); ?>" 
                               required>
                    </div>

                    <!-- Password and Confirm Password -->
                    <div class="auth-grid-2">
                        <div class="auth-form-group">
                            <label for="reg_password" class="auth-label">Password (Min. 8 chars) *</label>
                            <div class="auth-input-wrapper">
                                <input type="password" 
                                       id="reg_password" 
                                       name="password" 
                                       class="auth-input auth-input-password" 
                                       placeholder="Create secure password" 
                                       autocomplete="new-password"
                                       required>
                                <button type="button" 
                                        class="auth-toggle-pwd" 
                                        id="toggleRegPwdBtn" 
                                        aria-label="Toggle password visibility">
                                    <svg id="eyeIconReg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                        <circle cx="12" cy="10" r="3"></circle>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <div class="auth-form-group">
                            <label for="reg_confirm_password" class="auth-label">Confirm Password *</label>
                            <div class="auth-input-wrapper">
                                <input type="password" 
                                       id="reg_confirm_password" 
                                       name="confirm_password" 
                                       class="auth-input auth-input-password" 
                                       placeholder="Re-enter password" 
                                       autocomplete="new-password"
                                       required>
                                <button type="button" 
                                        class="auth-toggle-pwd" 
                                        id="toggleRegConfirmBtn" 
                                        aria-label="Toggle confirm password visibility">
                                    <svg id="eyeIconConfirm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                        <circle cx="12" cy="10" r="3"></circle>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="auth-btn-primary">
                        <span>Complete Registration &amp; Join</span>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                            <polyline points="12 5 19 12 12 19"></polyline>
                        </svg>
                    </button>
                </form>

                <div class="auth-switch-link">
                    Already have an account? 
                    <a href="login.php">Sign in</a>
                </div>
            </section>

            <!-- Right Column: Cinematic Promotional Panel (~48%) -->
            <aside class="auth-column-promo" aria-label="Alumni Network Community">
                <div class="auth-promo-top">
                    <h2 class="auth-promo-heading">Your Community. Your Connections. Your Future.</h2>
                    <p class="auth-promo-desc">
                        Join your alumni network to reconnect with familiar faces, discover new opportunities, and grow together.
                    </p>

                    <div class="auth-testimonial-box">
                        <div class="auth-quote-mark">&ldquo;</div>
                        <p class="auth-testimonial-quote">
                            &ldquo;Every graduation marks the beginning of an enduring legacy of leadership and mutual empowerment.&rdquo;
                        </p>
                        <div class="auth-testimonial-meta">
                            <div>
                                <div class="auth-testimonial-author">Alumni Connect Network</div>
                                <div class="auth-testimonial-role">Global Community &bull; Distinguished Alumni</div>
                            </div>
                            <div class="auth-promo-arrows" aria-hidden="true">
                                <div class="auth-promo-btn">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="15 18 9 12 15 6"></polyline>
                                    </svg>
                                </div>
                                <div class="auth-promo-btn">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="9 18 15 12 9 6"></polyline>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Lower Promotional Card with Decorative Badge -->
                <div class="auth-promo-card">
                    <div class="auth-promo-card-content">
                        <h3 class="auth-promo-card-title">Empower Your Career</h3>
                        <p class="auth-promo-card-text">
                            Access exclusive career openings, mentorship programs, and international alumni chapters.
                        </p>
                    </div>
                    <div class="auth-promo-badge" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2L14.7 9.3L22 12L14.7 14.7L12 22L9.3 14.7L2 12L9.3 9.3L12 2Z"></path>
                        </svg>
                    </div>
                </div>
            </aside>

        </div>
    </main>

    <script>
        // Password Visibility Controls
        function setupPasswordToggle(btnId, inputId, iconId) {
            const btn = document.getElementById(btnId);
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);

            if (btn && input && icon) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const isPassword = input.getAttribute('type') === 'password';
                    input.setAttribute('type', isPassword ? 'text' : 'password');
                    
                    if (isPassword) {
                        icon.innerHTML = `
                            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                            <line x1="1" y1="1" x2="23" y2="23"></line>
                        `;
                    } else {
                        icon.innerHTML = `
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="10" r="3"></circle>
                        `;
                    }
                });
            }
        }

        setupPasswordToggle('toggleRegPwdBtn', 'reg_password', 'eyeIconReg');
        setupPasswordToggle('toggleRegConfirmBtn', 'reg_confirm_password', 'eyeIconConfirm');
    </script>
</body>
</html>
