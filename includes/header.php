<?php
// ============================================================
// University Alumni Network - Global Header Component
// ============================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/db.php';

$baseUrl = get_base_url();
$pageTitle = isset($pageTitle) ? $pageTitle . ' - Alumni Network' : 'Alumni Network - Connect. Inspire. Grow.';
$currentPage = isset($currentPage) ? $currentPage : '';
$isLoggedIn = isset($_SESSION['user_id']);
$userRole = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : '';
$userName = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : '';
$userAvatar = $baseUrl . 'assets/images/default-avatar.svg';
$unreadMessagesCount = 0;
if ($isLoggedIn && $db_connected && $pdo) {
    try {
        $uStmt = $pdo->prepare("SELECT profile_picture FROM alumni_profiles WHERE user_id = ? LIMIT 1");
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
    <!-- Cinematic Style -->
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>assets/css/style.css">
</head>
<body>

    <!-- Minimal Cinematic Navigation Bar -->
    <header class="navbar">
        <div class="navbar-container">
            <a href="<?php echo $baseUrl; ?>index.php" class="logo" title="University Alumni Network">
                <span class="logo-badge">AN</span>
                <span>ALUMNI <span class="logo-gold">NETWORK</span></span>
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
                                    <span class="badge" style="background: var(--accent-color); color: var(--primary-color); font-size: 0.72rem; padding: 0.15rem 0.45rem; border-radius: 10px; margin-left: 0.3rem;">
                                        <?php echo $unreadMessagesCount; ?>
                                    </span>
                                <?php endif; ?>
                            </a>
                        </li>
                    <?php endif; ?>
                    <?php if ($isLoggedIn && $userRole === 'admin'): ?>
                        <li><a href="<?php echo $baseUrl; ?>admin/index.php" class="nav-link <?php echo ($currentPage === 'admin') ? 'active' : ''; ?>" style="color: var(--accent-color); font-weight: 700;">Admin</a></li>
                    <?php endif; ?>
                </ul>

                <div class="nav-auth">
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
