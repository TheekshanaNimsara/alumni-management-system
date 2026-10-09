<?php
// ============================================================
// University Alumni Network - Database Setup & Installer
// Run this file in browser (http://localhost/alumni-network/config/setup.php)
// or via CLI to automatically initialize or reset the database.
// ============================================================

$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'alumni_network';

require_once __DIR__ . '/db.php';

$messages = [];
$success = false;

// Security Guard: Prevent unauthenticated database resets
if (php_sapi_name() !== 'cli' && $db_connected && $pdo) {
    try {
        $userCount = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        if ($userCount > 0 && !is_admin()) {
            require_admin();
        }
    } catch (Exception $e) {
        // Allow initial setup if tables do not exist yet
    }
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' || php_sapi_name() === 'cli') {
    try {
        $pdo = new PDO("mysql:host={$db_host};charset=utf8mb4", $db_user, $db_pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        
        $messages[] = "Connected to MySQL server on {$db_host}.";

        // Read schema.sql
        $schemaPath = __DIR__ . '/../database/schema.sql';
        if (!file_exists($schemaPath)) {
            throw new Exception("schema.sql not found at: {$schemaPath}");
        }

        $sql = file_get_contents($schemaPath);
        
        // Execute multi-query statements
        $pdo->exec($sql);
        $messages[] = "Database `{$db_name}` and all tables created successfully!";
        $messages[] = "Seed data for users, profiles, events, and jobs inserted successfully!";
        $messages[] = "Default Admin: admin@alumni.edu (Password: Admin@123)";
        $messages[] = "Default Alumni: john.doe@alumni.edu (Password: Alumni@123)";
        $success = true;

    } catch (Exception $e) {
        $messages[] = "Error: " . $e->getMessage();
        $messages[] = "Tip: Make sure the MySQL module is running in your XAMPP Control Panel.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup - Alumni Network</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .setup-container {
            max-width: 600px;
            margin: 4rem auto;
            padding: 2.5rem;
            background: var(--card-bg);
            border-radius: var(--border-radius);
            box-shadow: 0 16px 40px rgba(0, 0, 0, 0.6);
            border: 1px solid var(--border-color);
            border-top: 4px solid var(--accent-color);
        }
        .message-box {
            padding: 1rem;
            margin: 1.5rem 0;
            border-radius: 6px;
            background: #080510;
            color: var(--text-color);
            border: 1px solid var(--border-color);
            font-family: monospace;
            font-size: 0.9rem;
            line-height: 1.6;
        }
    </style>
</head>
<body style="background-color: var(--bg-color);">
    <div class="setup-container">
        <h2 style="color: var(--text-light); margin-bottom: 0.5rem;">Alumni Network Database Setup</h2>
        <p style="color: var(--text-muted); margin-bottom: 1.5rem;">Initialize or reseed the database schema and starter data in MySQL.</p>

        <?php if (!empty($messages)): ?>
            <div class="message-box">
                <?php foreach ($messages as $msg): ?>
                    <div>&bull; <?php echo htmlspecialchars($msg); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div style="margin-top: 1.5rem; display: flex; gap: 1rem;">
                <a href="../index.php" class="btn btn-primary" style="flex: 1; text-align: center;">Go to Homepage</a>
                <a href="../auth/login.php" class="btn btn-gold" style="flex: 1; text-align: center;">Log In</a>
            </div>
        <?php else: ?>
            <form method="POST">
                <button type="submit" class="btn btn-primary btn-block">Initialize Database Now</button>
            </form>
            <div style="margin-top: 1.5rem; text-align: center;">
                <a href="../index.php" style="color: var(--accent-light); text-decoration: underline;">Return to Homepage</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
