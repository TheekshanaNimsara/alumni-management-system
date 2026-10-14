<?php
// ============================================================
// Alumni Connect - Member Login
// Cinematic Dark Purple Split-Screen Authentication Experience
// Secure PDO Prepared Statements • Bcrypt Verification • Session Hardening
// ============================================================
require_once __DIR__ . '/../config/db.php';

$error = '';
$identity = '';
$notice = '';
$rawMsg = $_GET['msg'] ?? $_GET['message'] ?? '';
if ($rawMsg === 'auth_required') {
    $notice = 'Please log in to continue.';
} elseif (!empty($rawMsg)) {
    $notice = $rawMsg;
}

// If already authenticated, redirect to appropriate destination
if (is_logged_in()) {
    $redirect = $_GET['redirect'] ?? '';
    if (!empty($redirect) && is_string($redirect) && 
        !preg_match('#^(https?:|//|javascript:|data:)#i', $redirect) && 
        strpos($redirect, "\r") === false && strpos($redirect, "\n") === false) {
        $dest = (str_starts_with($redirect, '..') || str_starts_with($redirect, '/')) ? $redirect : '../' . ltrim($redirect, '/');
        header("Location: " . $dest);
    } elseif (is_admin()) {
        header("Location: ../admin/index.php");
    } else {
        header("Location: ../index.php");
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || !empty($_POST['ajax']);
    $identity = trim($_POST['identity'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($identity) || empty($password)) {
        $error = "Please enter both your email address or username and password.";
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => $error]);
            exit;
        }
    } else {
        if ($db_connected && $pdo) {
            try {
                // Prepared statement against the verified users table
                $stmt = $pdo->prepare("
                    SELECT id, username, first_name, last_name, email, password, role, status 
                    FROM users 
                    WHERE email = ? OR username = ? 
                    LIMIT 1
                ");
                $stmt->execute([$identity, $identity]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password'])) {
                    if ($user['status'] === 'suspended') {
                        $error = "This account has been suspended. Please contact the administrator.";
                        if ($isAjax) {
                            header('Content-Type: application/json; charset=utf-8');
                            echo json_encode(['success' => false, 'error' => $error]);
                            exit;
                        }
                    } else {
                        // Regenerate session ID upon privilege escalation
                        session_regenerate_id(true);

                        $_SESSION['user_id'] = (int)$user['id'];
                        $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                        $_SESSION['user_role'] = $user['role'];
                        $_SESSION['user_email'] = $user['email'];

                        // Initialize auth success session state
                        $_SESSION['auth_success_type'] = 'login';
                        $successPage = 'login-success.php';

                        if ($isAjax) {
                            header('Content-Type: application/json; charset=utf-8');
                            echo json_encode([
                                'success' => true,
                                'title' => 'Login Successful!',
                                'message' => 'Welcome back to Alumni Connect.',
                                'redirect' => $successPage,
                                'delay' => 2.0
                            ]);
                            exit;
                        } else {
                            header("Location: " . $successPage);
                            exit;
                        }
                    }
                } else {
                    // Generic credential error to prevent user enumeration
                    $error = "Invalid email/username or password. Please verify your credentials and try again.";
                    if ($isAjax) {
                        header('Content-Type: application/json; charset=utf-8');
                        echo json_encode(['success' => false, 'error' => $error]);
                        exit;
                    }
                }
            } catch (Exception $e) {
                error_log("Login database exception: " . $e->getMessage());
                $error = "Authentication system error. Please try again shortly.";
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
    <title>Sign In &bull; Alumni Connect</title>
    <!-- Zero FOUC Theme Initializer -->
    <script>
      (function(){try{var s=localStorage.getItem('alumni_theme_preference');var t=(s==='light'||(s!=='dark'&&window.matchMedia&&window.matchMedia('(prefers-color-scheme: light)').matches))?'light':'dark';document.documentElement.setAttribute('data-theme',t);if(s)document.documentElement.setAttribute('data-theme-preference',s);}catch(e){}})();
    </script>
    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="../assets/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../assets/favicon-16x16.png">
    <link rel="shortcut icon" href="../favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="../assets/apple-touch-icon.png">
    <!-- Master Theme Stylesheets -->
    <link rel="stylesheet" href="../assets/css/theme.css">
    <link rel="stylesheet" href="../assets/css/auth.css">
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
        <div class="auth-card-split">
            
            <!-- Left Column: Login Form (~52%) -->
            <section class="auth-column-form" aria-labelledby="loginHeading">
                <div class="auth-form-brand">
                    <span class="auth-logo-badge">AC</span>
                    <span>ALUMNI <span class="highlight">CONNECT</span></span>
                </div>

                <div class="auth-form-header">
                    <h1 id="loginHeading" class="auth-form-title">Welcome Back</h1>
                    <p class="auth-form-subtitle">Please enter your account details</p>
                </div>

                <?php if (!empty($notice) && empty($error)): ?>
                    <div class="auth-alert auth-alert-warning" role="alert" style="background: rgba(245, 158, 11, 0.15); border: 1px solid rgba(245, 158, 11, 0.35); color: #FBBF24; display: flex; align-items: center; gap: 0.6rem; padding: 0.8rem 1rem; border-radius: 8px; margin-bottom: 1.2rem; font-size: 0.92rem; font-weight: 600;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                        <span><?php echo htmlspecialchars($notice); ?></span>
                    </div>
                <?php endif; ?>

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

                <form method="POST" action="login.php<?php 
                    $params = [];
                    if (!empty($_GET['redirect'])) $params['redirect'] = $_GET['redirect'];
                    if (!empty($_GET['msg'])) $params['msg'] = $_GET['msg'];
                    echo !empty($params) ? '?' . htmlspecialchars(http_build_query($params)) : ''; 
                ?>" id="loginForm" novalidate>
                    <div class="auth-form-group">
                        <label for="identity" class="auth-label">Email or Username</label>
                        <div class="auth-input-wrapper">
                            <input type="text" 
                                   id="identity" 
                                   name="identity" 
                                   class="auth-input" 
                                   placeholder="e.g. name@alumni.edu or username" 
                                   value="<?php echo htmlspecialchars($identity); ?>" 
                                   autocomplete="username"
                                   required 
                                   autofocus>
                        </div>
                    </div>

                    <div class="auth-form-group">
                        <label for="password" class="auth-label">Password</label>
                        <div class="auth-input-wrapper">
                            <input type="password" 
                                   id="password" 
                                   name="password" 
                                   class="auth-input auth-input-password" 
                                   placeholder="Enter your account password" 
                                   autocomplete="current-password"
                                   required>
                            <button type="button" 
                                    class="auth-toggle-pwd" 
                                    id="togglePasswordBtn" 
                                    aria-label="Toggle password visibility" 
                                    title="Show/hide password">
                                <svg id="eyeIcon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="10" r="3"></circle>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="auth-btn-primary">
                        <span>Sign In</span>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                            <polyline points="12 5 19 12 12 19"></polyline>
                        </svg>
                    </button>
                </form>

                <div class="auth-switch-link">
                    Don't have an account? 
                    <a href="register.php">Create an account</a>
                </div>
            </section>

            <!-- Right Column: Cinematic Promotional Panel (~48%) -->
            <aside class="auth-column-promo" aria-label="Alumni Network Introduction">
                <div class="auth-promo-top">
                    <h2 class="auth-promo-heading">Your Alumni Journey Starts Here.</h2>
                    <p class="auth-promo-desc">
                        Reconnect with your community, discover new opportunities, and build meaningful professional relationships.
                    </p>

                    <div class="auth-testimonial-box">
                        <div class="auth-quote-mark">&ldquo;</div>
                        <p class="auth-testimonial-quote">
                            &ldquo;Your next opportunity could begin with a connection from your university community.&rdquo;
                        </p>
                        <div class="auth-testimonial-meta">
                            <div>
                                <div class="auth-testimonial-author">Alumni Connect Community</div>
                                <div class="auth-testimonial-role">Global Graduates &bull; Mentors &bull; Leaders</div>
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
                        <h3 class="auth-promo-card-title">Stay connected. Grow together.</h3>
                        <p class="auth-promo-card-text">
                            Explore your alumni network, share experiences, and discover opportunities to move forward.
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
        // Password Visibility Toggle (Pure Vanilla JS • Non-destructive)
        const toggleBtn = document.getElementById('togglePasswordBtn');
        const pwdInput = document.getElementById('password');
        const eyeIcon = document.getElementById('eyeIcon');

        if (toggleBtn && pwdInput) {
            toggleBtn.addEventListener('click', function(e) {
                e.preventDefault();
                const isPassword = pwdInput.getAttribute('type') === 'password';
                pwdInput.setAttribute('type', isPassword ? 'text' : 'password');
                
                if (isPassword) {
                    eyeIcon.innerHTML = `
                        <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                        <line x1="1" y1="1" x2="23" y2="23"></line>
                    `;
                    toggleBtn.setAttribute('aria-label', 'Hide password');
                } else {
                    eyeIcon.innerHTML = `
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                        <circle cx="12" cy="10" r="3"></circle>
                    `;
                    toggleBtn.setAttribute('aria-label', 'Show password');
                }
            });
        }

        // Login Form AJAX Controller
        const loginForm = document.getElementById('loginForm');

        function showAuthError(message) {
            let errorAlert = document.querySelector('.auth-column-form .auth-alert-error');
            const warningAlert = document.querySelector('.auth-column-form .auth-alert-warning');
            if (warningAlert) warningAlert.style.display = 'none';

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
                if (loginForm && loginForm.parentNode) {
                    loginForm.parentNode.insertBefore(errorAlert, loginForm);
                }
            }
            const msgSpan = errorAlert.querySelector('.auth-error-msg') || errorAlert.querySelector('span:last-child');
            if (msgSpan) {
                msgSpan.textContent = message;
            }
            errorAlert.style.display = 'flex';
        }

        if (loginForm) {
            loginForm.addEventListener('submit', function(e) {
                e.preventDefault();

                const idInput = document.getElementById('identity');
                const pwdInput = document.getElementById('password');
                const identityVal = idInput ? idInput.value.trim() : '';
                const passwordVal = pwdInput ? pwdInput.value : '';

                if (!identityVal || !passwordVal) {
                    showAuthError('Please enter both your email address or username and password.');
                    if (!identityVal && idInput) idInput.focus();
                    else if (!passwordVal && pwdInput) pwdInput.focus();
                    return;
                }

                const submitBtn = loginForm.querySelector('button[type="submit"]');
                const originalBtnContent = submitBtn ? submitBtn.innerHTML : '';
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<span class="auth-btn-spinner" aria-hidden="true"></span><span>Signing In...</span>';
                }

                const formData = new FormData(loginForm);
                formData.append('ajax', '1');

                fetch(loginForm.getAttribute('action') || window.location.href, {
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
                        window.location.href = data.redirect || 'login-success.php';
                    } else {
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = originalBtnContent;
                        }
                        showAuthError(data && data.error ? data.error : 'Invalid credentials. Please verify your details.');
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
