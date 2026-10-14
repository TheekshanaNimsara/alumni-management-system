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
    $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || !empty($_POST['ajax']);

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
    }

    if (!empty($error)) {
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => $error]);
            exit;
        }
    } else {
        if ($db_connected && $pdo) {
            try {
                // Check for existing account by email or username
                $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ? LIMIT 1");
                $checkStmt->execute([$formData['email'], $formData['username']]);
                
                if ($checkStmt->fetch()) {
                    $error = "An account with that email address or username already exists. Please sign in or use different details.";
                    if ($isAjax) {
                        header('Content-Type: application/json; charset=utf-8');
                        echo json_encode(['success' => false, 'error' => $error]);
                        exit;
                    }
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

                    // Establish authenticated session & regenerate ID
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $newUserId;
                    $_SESSION['user_name'] = $formData['first_name'] . ' ' . $formData['last_name'];
                    $_SESSION['user_role'] = 'alumni';
                    $_SESSION['user_email'] = $formData['email'];
                    $_SESSION['auth_success_type'] = 'signup';

                    $successPage = 'signup-success.php';

                    if ($isAjax) {
                        header('Content-Type: application/json; charset=utf-8');
                        echo json_encode([
                            'success' => true,
                            'title' => 'Account Created Successfully!',
                            'message' => 'Welcome to Alumni Connect! Your journey starts here.',
                            'redirect' => $successPage,
                            'delay' => 2.0
                        ]);
                        exit;
                    } else {
                        header("Location: " . $successPage);
                        exit;
                    }
                }
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log("Registration transaction failed: " . $e->getMessage());
                $error = "A system error occurred while creating your account. Please try again.";
                if ($isAjax) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['success' => false, 'error' => $error]);
                    exit;
                }
            }
        } else {
            $error = "Database service is temporarily unavailable. Please try again shortly.";
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'error' => $error]);
                exit;
            }
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
    <!-- Zero FOUC Theme Initializer -->
    <script>
      (function(){try{var s=localStorage.getItem('alumni_theme_preference');var t=(s==='light'||(s!=='dark'&&window.matchMedia&&window.matchMedia('(prefers-color-scheme: light)').matches))?'light':'dark';document.documentElement.setAttribute('data-theme',t);if(s)document.documentElement.setAttribute('data-theme-preference',s);}catch(e){}})();
    </script>
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="../assets/favicon.svg?v=3">
    <link rel="icon" type="image/png" sizes="32x32" href="../assets/favicon-32x32.png?v=3">
    <link rel="icon" type="image/png" sizes="16x16" href="../assets/favicon-16x16.png?v=3">
    <link rel="shortcut icon" href="../favicon.ico?v=3">
    <link rel="apple-touch-icon" sizes="180x180" href="../assets/apple-touch-icon.png?v=3">
    <!-- Master Theme Stylesheets -->
    <link rel="stylesheet" href="../assets/css/theme.css?v=8">
    <link rel="stylesheet" href="../assets/css/auth.css?v=8">
</head>
<body class="auth-body">

    <!-- Top Navigation -->
    <header class="auth-nav">
        <a href="../index.php" class="auth-nav-logo">
            <span class="auth-logo-badge">AC</span>
            <span>ALUMNI <span style="color: var(--auth-lavender-soft);">CONNECT</span></span>
        </a>
        <div style="display: flex; align-items: center; gap: 1rem;">
            <div class="theme-switcher" role="radiogroup" aria-label="Select appearance theme">
                <button type="button" class="theme-option" data-theme-value="dark" role="radio" aria-checked="false" title="Dark theme (always dark)">
                    <svg class="theme-icon" viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>
                    <span class="theme-label">Dark</span>
                </button>
                <button type="button" class="theme-option" data-theme-value="light" role="radio" aria-checked="false" title="Light theme (always light)">
                    <svg class="theme-icon" viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"></circle><line x1="12" y1="2" x2="12" y2="4"></line><line x1="12" y1="20" x2="12" y2="22"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>
                    <span class="theme-label">Light</span>
                </button>
                <button type="button" class="theme-option" data-theme-value="system" role="radio" aria-checked="false" title="System theme (syncs with OS)">
                    <svg class="theme-icon" viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect><line x1="8" y1="21" x2="16" y2="21"></line><line x1="12" y1="17" x2="12" y2="21"></line></svg>
                    <span class="theme-label">Auto</span>
                </button>
            </div>
            <a href="../index.php" class="auth-nav-back">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                Return to Homepage
            </a>
        </div>
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

                <form method="POST" action="register.php" id="registerForm" novalidate>
                    
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

        // Register Form AJAX Controller
        const registerForm = document.getElementById('registerForm');

        function showAuthError(message) {
            let errorAlert = document.querySelector('.auth-column-form .auth-alert-error');

            if (!errorAlert) {
                errorAlert = document.createElement('div');
                errorAlert.className = 'auth-alert auth-alert-error';
                errorAlert.setAttribute('role', 'alert');
                errorAlert.innerHTML = `
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    <span class="auth-error-msg"></span>
                `;
                if (registerForm && registerForm.parentNode) {
                    registerForm.parentNode.insertBefore(errorAlert, registerForm);
                }
            }
            const msgSpan = errorAlert.querySelector('.auth-error-msg') || errorAlert.querySelector('span:last-child');
            if (msgSpan) {
                msgSpan.textContent = message;
            }
            errorAlert.style.display = 'flex';
            errorAlert.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        if (registerForm) {
            registerForm.addEventListener('submit', function(e) {
                e.preventDefault();

                const firstName = (document.getElementById('first_name')?.value || '').trim();
                const lastName = (document.getElementById('last_name')?.value || '').trim();
                const username = (document.getElementById('username')?.value || '').trim();
                const email = (document.getElementById('email')?.value || '').trim();
                const degree = (document.getElementById('degree')?.value || '').trim();
                const department = (document.getElementById('department')?.value || '').trim();
                const gradYearVal = document.getElementById('grad_year')?.value || '';
                const gradYear = parseInt(gradYearVal, 10);
                const password = document.getElementById('reg_password')?.value || '';
                const confirmPassword = document.getElementById('reg_confirm_password')?.value || '';

                if (!firstName || !lastName || !username || !email || !degree || !department || !gradYearVal || !password || !confirmPassword) {
                    showAuthError('All fields are required. Please complete the registration form.');
                    return;
                }

                if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                    showAuthError('Please provide a valid email address.');
                    document.getElementById('email')?.focus();
                    return;
                }

                if (!/^[a-zA-Z0-9_.-]{3,30}$/.test(username)) {
                    showAuthError('Username must be between 3 and 30 characters and contain only letters, numbers, underscores, dots, or hyphens.');
                    document.getElementById('username')?.focus();
                    return;
                }

                if (password.length < 8) {
                    showAuthError('Password must be at least 8 characters long.');
                    document.getElementById('reg_password')?.focus();
                    return;
                }

                if (password !== confirmPassword) {
                    showAuthError('Passwords do not match. Please re-enter.');
                    document.getElementById('reg_confirm_password')?.focus();
                    return;
                }

                if (isNaN(gradYear) || gradYear < 1960 || gradYear > 2030) {
                    showAuthError('Please specify a valid graduation year between 1960 and 2030.');
                    document.getElementById('grad_year')?.focus();
                    return;
                }

                const submitBtn = registerForm.querySelector('button[type="submit"]');
                const originalBtnContent = submitBtn ? submitBtn.innerHTML : '';
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<span class="auth-btn-spinner" aria-hidden="true"></span><span>Creating Account...</span>';
                }

                const formData = new FormData(registerForm);
                formData.append('ajax', '1');

                fetch(registerForm.getAttribute('action') || window.location.href, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('HTTP error ' + response.status);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data && data.success) {
                        window.location.href = data.redirect || 'signup-success.php';
                    } else {
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = originalBtnContent;
                        }
                        showAuthError(data && data.error ? data.error : 'Registration could not be completed. Please check your details.');
                    }
                })
                .catch(err => {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalBtnContent;
                    }
                    showAuthError('A network or server error occurred. Please try again.');
                });
            });
        }
    </script>
    <script src="../assets/js/theme.js"></script>
    <script src="../assets/js/main.js"></script>
</body>
</html>
