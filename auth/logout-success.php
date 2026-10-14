<?php
// ============================================================
// Alumni Connect - Dedicated Logout Success Screen
// Apple-inspired Liquid Glass • Centered Viewport • Auto-Redirect to Home
// ============================================================
require_once __DIR__ . '/../config/db.php';

// Security: Prevent browser caching of logout success state
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

$baseUrl = get_base_url();
$homeUrl = '../index.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logged Out Successfully &bull; Alumni Connect</title>
    
    <!-- Meta Refresh Fallback (2s) -->
    <meta http-equiv="refresh" content="2;url=<?php echo htmlspecialchars($homeUrl); ?>">

    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="../assets/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../assets/favicon-16x16.png">
    
    <!-- Dedicated Scoped Stylesheet -->
    <link rel="stylesheet" href="../assets/css/auth-success.css">
</head>
<body>

    <main class="success-viewport" role="main" aria-label="Logged Out Successfully">
        <div class="success-card">
            <div class="success-card-glow" aria-hidden="true"></div>

            <!-- Brand Header -->
            <div class="success-brand">
                <span class="success-brand-badge">AC</span>
                <span class="success-brand-text">ALUMNI <span class="highlight">CONNECT</span></span>
            </div>

            <!-- Animated Gold Circular Checkmark -->
            <div class="success-badge-wrap" aria-hidden="true">
                <svg class="success-svg" viewBox="0 0 80 80">
                    <defs>
                        <linearGradient id="goldGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" stop-color="#E8CB83" />
                            <stop offset="50%" stop-color="#D8B96A" />
                            <stop offset="100%" stop-color="#B49345" />
                        </linearGradient>
                        <linearGradient id="purpleGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" stop-color="#E8CB83" />
                            <stop offset="50%" stop-color="#D8B96A" />
                            <stop offset="100%" stop-color="#B49345" />
                        </linearGradient>
                    </defs>
                    <circle class="success-circle-bg" cx="40" cy="40" r="36" />
                    <circle class="success-circle-draw" cx="40" cy="40" r="36" />
                    <polyline class="success-check-draw" points="25,41 35,51 55,30" />
                </svg>
            </div>

            <!-- Typography Required by Specification -->
            <h1 class="success-title">Logged Out Successfully!</h1>
            <p class="success-subtitle">You have safely signed out of Alumni Connect.</p>

            <!-- Animated Progress Section -->
            <div class="success-progress-section" aria-live="polite">
                <div class="success-progress-track">
                    <div class="success-progress-bar"></div>
                </div>
                <div class="success-status-row">
                    <span class="success-status-text">Redirecting you to the homepage...</span>
                    <span class="success-countdown" id="successCountdown">2s</span>
                </div>
            </div>

            <!-- Fallback Navigation -->
            <div class="success-action-row">
                <a href="<?php echo htmlspecialchars($homeUrl); ?>" class="btn-success-continue" id="btnContinueHome">
                    Continue to Homepage &rarr;
                </a>
            </div>
        </div>
    </main>

    <script>
        (function() {
            const homeUrl = <?php echo json_encode($homeUrl); ?>;
            const prefersReducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            let seconds = 2;
            const countdownEl = document.getElementById('successCountdown');

            const countdownInterval = setInterval(function() {
                seconds--;
                if (seconds > 0 && countdownEl) {
                    countdownEl.textContent = seconds + 's';
                } else {
                    clearInterval(countdownInterval);
                    if (countdownEl) countdownEl.textContent = 'Redirecting...';
                }
            }, 1000);

            // Automatic client-side redirection after 1.8 seconds
            const redirectDelay = prefersReducedMotion ? 600 : 1800;
            setTimeout(function() {
                window.location.replace(homeUrl);
            }, redirectDelay);
        })();
    </script>
</body>
</html>
