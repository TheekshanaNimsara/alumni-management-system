<?php
// ============================================================
// University Alumni Network - Admin Settings
// ============================================================
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../auth/login.php?admin_required=1");
    exit;
}

$currentAdminPage = 'settings';
$baseUrl = get_base_url();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Platform Settings - Alumni Admin</title>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>assets/css/style.css">
</head>
<body>

<div class="admin-layout">
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

    <main class="admin-main">
        <div class="admin-header">
            <div>
                <h1 class="admin-title">System Settings &amp; Diagnostics</h1>
                <p style="color: var(--text-muted); font-size: 0.95rem;">Configuration parameters and runtime diagnostics</p>
            </div>
            <div>
                <a href="<?php echo $baseUrl; ?>config/setup.php" class="btn btn-gold btn-sm">Database Re-Seed Tool</a>
            </div>
        </div>

        <div class="grid grid-2">
            
            <div class="card" style="padding: 2rem;">
                <h3 style="color: var(--text-light); margin-bottom: 1rem;">Platform Environment</h3>
                <table style="width: 100%; border-collapse: collapse; font-size: 0.92rem;">
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 0.75rem 0; font-weight: 600;">PHP Version:</td>
                        <td style="color: var(--text-muted);"><?php echo phpversion(); ?></td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 0.75rem 0; font-weight: 600;">Database Connection:</td>
                        <td>
                            <?php if ($db_connected): ?>
                                <span class="badge badge-approved">Connected (MySQL/MariaDB)</span>
                            <?php else: ?>
                                <span class="badge badge-pending">Fallback Mode (Offline)</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 0.75rem 0; font-weight: 600;">Database Name:</td>
                        <td style="color: var(--text-muted);"><?php echo htmlspecialchars($db_name); ?></td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 0.75rem 0; font-weight: 600;">Server Software:</td>
                        <td style="color: var(--text-muted);"><?php echo htmlspecialchars($_SERVER['SERVER_SOFTWARE'] ?? 'Apache / XAMPP'); ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 0.75rem 0; font-weight: 600;">Architecture:</td>
                        <td style="color: var(--text-muted);">Pure HTML5, CSS3, Vanilla JS &amp; PHP PDO</td>
                    </tr>
                </table>
            </div>

            <div class="card" style="padding: 2rem;">
                <h3 style="color: var(--text-light); margin-bottom: 1rem;">Design System Guidelines</h3>
                <p style="color: var(--text-muted); font-size: 0.92rem; margin-bottom: 1.2rem;">
                    The platform complies with the cinematic dark-purple editorial design specifications:
                </p>
                <div style="display: flex; gap: 0.8rem; margin-bottom: 1.5rem; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 100px; padding: 0.8rem; background: #080510; color: #F8F7FC; border-radius: 6px; text-align: center; font-size: 0.8rem; border: 1px solid var(--border-color);">
                        <strong>Midnight Purple</strong><br>#080510
                    </div>
                    <div style="flex: 1; min-width: 100px; padding: 0.8rem; background: #7C3AED; color: #FFFFFF; border-radius: 6px; text-align: center; font-size: 0.8rem;">
                        <strong>Electric Violet</strong><br>#7C3AED
                    </div>
                    <div style="flex: 1; min-width: 100px; padding: 0.8rem; background: #241735; color: #C4B5FD; border-radius: 6px; text-align: center; font-size: 0.8rem; border: 1px solid var(--border-color);">
                        <strong>Soft Lavender</strong><br>#C4B5FD
                    </div>
                </div>
                <div style="font-size: 0.88rem; color: var(--text-muted);">
                    &bull; Zero external UI frameworks (No Bootstrap/Tailwind)<br>
                    &bull; Zero animation or 3D libraries (Pure CSS transitions &amp; keyframes)<br>
                    &bull; High-contrast accessible midnight-purple editorial identity
                </div>
            </div>

        </div>
    </main>
</div>

<script src="<?php echo $baseUrl; ?>assets/js/main.js"></script>
</body>
</html>
