<?php
// ============================================================
// University Alumni Network - Global Header Component
// ============================================================
require_once __DIR__ . '/../config/db.php';

$baseUrl = get_base_url();
$pageTitle = isset($pageTitle) ? $pageTitle . ' - Alumni Network' : 'Alumni Network - Connect. Inspire. Grow.';
$currentPage = isset($currentPage) ? $currentPage : '';
$isLoggedIn = isset($_SESSION['user_id']);
$currentUserId = current_user_id();
$userRole = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : '';
$userName = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : '';
$userAvatar = $baseUrl . 'assets/images/default-avatar.svg';
$unreadMessagesCount = 0;
if ($isLoggedIn && $db_connected && $pdo) {
    try {
        $uStmt = $pdo->prepare("SELECT profile_picture FROM alumni_profiles WHERE user_id = ? ORDER BY id DESC LIMIT 1");
        $uStmt->execute([$_SESSION['user_id']]);
        $pic = $uStmt->fetchColumn();
        if ($pic && $pic !== 'default-avatar.svg' && file_exists(__DIR__ . '/../uploads/profiles/' . $pic)) {
            $userAvatar = $baseUrl . 'uploads/profiles/' . htmlspecialchars($pic);
        }
        $unreadMessagesCount = get_unread_messages_count($pdo, $_SESSION['user_id']);
    } catch (Exception $e) {
        // fallback
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <!-- Zero FOUC Theme Initializer -->
    <script>
      (function(){try{var s=localStorage.getItem('alumni_theme_preference');var t=(s==='light'||(s!=='dark'&&window.matchMedia&&window.matchMedia('(prefers-color-scheme: light)').matches))?'light':'dark';document.documentElement.setAttribute('data-theme',t);if(s)document.documentElement.setAttribute('data-theme-preference',s);}catch(e){}})();
    </script>
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="<?php echo $baseUrl; ?>assets/favicon.svg?v=3">
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo $baseUrl; ?>assets/favicon-32x32.png?v=3">
    <link rel="icon" type="image/png" sizes="16x16" href="<?php echo $baseUrl; ?>assets/favicon-16x16.png?v=3">
    <link rel="shortcut icon" href="<?php echo $baseUrl; ?>favicon.ico?v=3">
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo $baseUrl; ?>assets/apple-touch-icon.png?v=3">
    <!-- Cinematic Style -->
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>assets/css/theme.css?v=8">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>assets/css/style.css?v=8">
    <script src="<?php echo $baseUrl; ?>assets/js/theme.js" defer></script>
</head>
<body>

    <!-- Minimal Cinematic Navigation Bar -->
    <header class="navbar">
        <div class="navbar-container">
            <a href="<?php echo $baseUrl; ?>index.php" class="logo" title="University Alumni Network">
                <span class="logo-badge">AN</span>
                <span class="logo-text">ALUMNI <span class="logo-gold">NETWORK</span></span>
            </a>

            <button class="nav-toggle" aria-label="Toggle navigation menu">&#9776;</button>

            <nav class="nav-menu">
                <ul class="nav-links">
                    <li><a href="<?php echo $baseUrl; ?>index.php" class="nav-link <?php echo ($currentPage === 'home') ? 'active' : ''; ?>">Home</a></li>
                    <li><a href="<?php echo $baseUrl; ?>directory.php" class="nav-link <?php echo ($currentPage === 'alumni') ? 'active' : ''; ?>">Alumni</a></li>
                    <li><a href="<?php echo $baseUrl; ?>events.php" class="nav-link <?php echo ($currentPage === 'events') ? 'active' : ''; ?>">Events</a></li>
                    <li><a href="<?php echo $baseUrl; ?>jobs.php" class="nav-link <?php echo ($currentPage === 'jobs') ? 'active' : ''; ?>">Jobs</a></li>
                    <li><a href="<?php echo $baseUrl; ?>about.php" class="nav-link <?php echo ($currentPage === 'about') ? 'active' : ''; ?>">About</a></li>
                    <?php if ($isLoggedIn): ?>
                        <li>
                            <a href="<?php echo $baseUrl; ?>messages.php" class="nav-link <?php echo ($currentPage === 'messages') ? 'active' : ''; ?>">
                                Messages
                                <?php if ($unreadMessagesCount > 0): ?>
                                    <span class="badge" style="background: var(--accent-color); color: #FFFFFF; font-size: 0.72rem; font-weight: 700; padding: 0.15rem 0.45rem; border-radius: 10px; margin-left: 0.3rem;">
                                        <?php echo $unreadMessagesCount; ?>
                                    </span>
                                <?php endif; ?>
                            </a>
                        </li>
                    <?php endif; ?>
                    <?php if ($isLoggedIn && $userRole === 'admin'): ?>
                        <li><a href="<?php echo $baseUrl; ?>admin/index.php" class="nav-link <?php echo ($currentPage === 'admin') ? 'active' : ''; ?>" style="color: var(--accent-light); font-weight: 700;">Admin</a></li>
                    <?php endif; ?>
                </ul>

                <div class="nav-auth">
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
                    <?php if ($isLoggedIn): ?>
                        <a href="<?php echo $baseUrl; ?>profile.php" class="nav-user" style="text-decoration: none;" title="View & Edit Your Profile">
                            <img src="<?php echo $userAvatar; ?>" alt="Profile" class="nav-user-avatar">
                            <span><?php echo htmlspecialchars($userName); ?></span>
                        </a>
                        <a href="<?php echo $baseUrl; ?>auth/logout.php" class="btn btn-outline-light btn-sm">Logout</a>
                    <?php else: ?>
                        <a href="<?php echo $baseUrl; ?>auth/login.php" class="btn btn-outline-light btn-sm">Login</a>
                        <a href="<?php echo $baseUrl; ?>auth/register.php" class="btn btn-gold btn-sm gold-glow">Register</a>
                    <?php endif; ?>
                </div>
            </nav>
        </div>
    </header>
