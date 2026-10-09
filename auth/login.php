<?php
// ============================================================
// Alumni Connect - Member Login
// Cinematic Dark Purple Split-Screen Authentication Experience
// Secure PDO Prepared Statements • Bcrypt Verification • Session Hardening
// ============================================================
require_once __DIR__ . '/../config/db.php';

$error = '';
$identity = '';

// If already authenticated, redirect to appropriate destination
if (is_logged_in()) {
    if (is_admin()) {
        header("Location: ../admin/index.php");
    } else {
        header("Location: ../index.php");
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identity = trim($_POST['identity'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($identity) || empty($password)) {
        $error = "Please enter both your email address or username and password.";
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
                    } else {
                        // Regenerate session ID upon privilege escalation
                        session_regenerate_id(true);

                        $_SESSION['user_id'] = (int)$user['id'];
                        $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                        $_SESSION['user_role'] = $user['role'];
                        $_SESSION['user_email'] = $user['email'];

                        // Safe redirect based on verified role
                        if ($user['role'] === 'admin') {
                            header("Location: ../admin/index.php");
                        } else {
                            $redirect = $_GET['redirect'] ?? '../index.php';
                            // Restrict to safe internal relative paths
                            if (!is_string($redirect) || str_starts_with($redirect, '//') || str_contains($redirect, '://') || !str_starts_with($redirect, '..')) {
                                $redirect = '../index.php';
                            }
                            header("Location: " . $redirect);
                        }
                        exit;
                    }
                } else {
                    // Generic credential error to prevent user enumeration
                    $error = "Invalid email/username or password. Please verify your credentials and try again.";
                }
            } catch (Exception $e) {
                error_log("Login database exception: " . $e->getMessage());
                $error = "Authentication system error. Please try again shortly.";
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
    <title>Sign In &bull; Alumni Connect</title>
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

                <form method="POST" action="login.php<?php echo isset($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : ''; ?>" novalidate>
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
    </script>
</body>
</html>
