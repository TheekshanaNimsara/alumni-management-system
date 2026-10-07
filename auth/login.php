<?php
// ============================================================
// University Alumni Network - Member Login (Cinematic Split Layout)
// ============================================================
require_once __DIR__ . '/../config/db.php';

$error = '';
$success = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $identity = trim($_POST['login_identity'] ?? $_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($identity) || empty($password)) {
        $error = "Please enter both your email address/username and password.";
    } else {
        if ($db_connected && $pdo) {
            try {
                $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR username = ? LIMIT 1");
                $stmt->execute([$identity, $identity]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password'])) {
                    if ($user['status'] === 'suspended') {
                        $error = "This account has been suspended. Please contact the administrator.";
                    } else {
                        // Successful login - regenerate session ID for security
                        session_regenerate_id(true);
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                        $_SESSION['user_role'] = $user['role'];
                        $_SESSION['user_email'] = $user['email'];

                        if ($user['role'] === 'admin') {
                            header("Location: ../admin/index.php");
                        } else {
                            header("Location: ../index.php");
                        }
                        exit;
                    }
                } else {
                    $error = "Invalid email/username or password.";
                }
            } catch (Exception $e) {
                $error = "Authentication system error. Please try again.";
            }
        } else {
            // Graceful fallback login if MySQL is offline
            if (($identity === 'admin@alumni.edu' || $identity === 'admin') && $password === 'Admin@123') {
                session_regenerate_id(true);
                $_SESSION['user_id'] = 1;
                $_SESSION['user_name'] = 'System Administrator';
                $_SESSION['user_role'] = 'admin';
                $_SESSION['user_email'] = 'admin@alumni.edu';
                header("Location: ../admin/index.php");
                exit;
            } elseif (($identity === 'john.doe@alumni.edu' || $identity === 'johndoe') && $password === 'Alumni@123') {
                session_regenerate_id(true);
                $_SESSION['user_id'] = 2;
                $_SESSION['user_name'] = 'John Doe';
                $_SESSION['user_role'] = 'alumni';
                $_SESSION['user_email'] = 'john.doe@alumni.edu';
                header("Location: ../index.php");
                exit;
            } else {
                $error = "Invalid credentials. (Demo hint: admin@alumni.edu / Admin@123 or john.doe@alumni.edu / Alumni@123)";
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
    <title>Member Login - Alumni Network</title>
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

    <div class="auth-wrapper">
        <div class="auth-split-card">
            
            <!-- Left Side: Cinematic Navy Branding & Quote -->
            <div class="auth-split-left">
                <div class="auth-brand">
                    <span class="logo-badge">AN</span> ALUMNI NETWORK
                </div>

                <div class="auth-quote-box">
                    <div class="auth-quote-mark">&ldquo;</div>
                    <div class="auth-quote">
                        Where connections become opportunities.
                    </div>
                    <div class="auth-quote-sub">
                        Re-engage with distinguished peers, mentors, and the university community that helped define your future.
                    </div>
                </div>

                <div style="font-size: 0.85rem; color: rgba(248, 244, 234, 0.75);">
                    &copy; 2026 Alumni Association &bull; Prestige &bull; Excellence
                </div>
            </div>

            <!-- Right Side: Login Form -->
            <div class="auth-split-right">
                <div class="auth-form-header">
                    <h2 class="auth-form-title">Welcome Back</h2>
                    <p class="auth-form-subtitle">Enter your alumni credentials to access your portal</p>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form method="POST" action="login.php">
                    <?php echo csrf_field(); ?>
                    <div class="form-group">
                        <label for="login_identity">Email Address or Username</label>
                        <input type="text" id="login_identity" name="login_identity" class="form-control" 
                               placeholder="e.g. name@alumni.edu or username" 
                               value="<?php echo isset($_POST['login_identity']) ? htmlspecialchars($_POST['login_identity']) : (isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.45rem;">
                            <label for="password" style="margin-bottom: 0;">Password</label>
                            <span style="font-size: 0.82rem; color: var(--text-muted);">Demo: Admin@123 / Alumni@123</span>
                        </div>
                        <input type="password" id="password" name="password" class="form-control" 
                               placeholder="Enter your account password" required>
                    </div>

                    <button type="submit" class="btn btn-gold btn-block gold-glow" style="margin-top: 1rem;">
                        Sign In to Network
                    </button>
                </form>

                <div style="margin-top: 2rem; text-align: center; font-size: 0.92rem; color: var(--text-muted);">
                    Don't have an alumni account yet?
                    <a href="register.php" style="color: var(--primary-color); font-weight: 700; text-decoration: underline;">
                        Register Now
                    </a>
                </div>
            </div>

        </div>
    </div>

</body>
</html>
