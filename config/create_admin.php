<?php
// ============================================================
// University Alumni Network - Administrative Account Provisioning
// Run via CLI: php config/create_admin.php [username] [email] [password]
// ============================================================

require_once __DIR__ . '/db.php';

// If accessed via web browser, only allow if already authenticated as admin
if (php_sapi_name() !== 'cli') {
    if (!is_admin()) {
        header('HTTP/1.1 403 Forbidden');
        die("<h1>403 Forbidden</h1><p>Administrative provisioning can only be run via CLI or by an active system administrator.</p>");
    }
}

$username = $argv[1] ?? 'admin';
$email    = $argv[2] ?? 'admin@alumni.edu';
$password = $argv[3] ?? 'Admin@12345';

echo "--- Alumni Network Admin Provisioning ---" . PHP_EOL;
echo "Target Username: {$username}" . PHP_EOL;
echo "Target Email:    {$email}" . PHP_EOL;

if (!$pdo) {
    die("Database connection is not available. Please verify MariaDB is running." . PHP_EOL);
}

try {
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Check if user already exists
    $stmt = $pdo->prepare("SELECT id, role FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    $existing = $stmt->fetch();

    if ($existing) {
        $stmt = $pdo->prepare("
            UPDATE users 
            SET password = ?, role = 'admin', status = 'active'
            WHERE id = ?
        ");
        $stmt->execute([$hashedPassword, $existing['id']]);
        echo "Successfully updated user ID {$existing['id']} to Administrator role with new password." . PHP_EOL;
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO users (username, first_name, last_name, email, password, role, status)
            VALUES (?, 'System', 'Administrator', ?, ?, 'admin', 'active')
        ");
        $stmt->execute([$username, $email, $hashedPassword]);
        $newId = $pdo->lastInsertId();
        echo "Successfully created new Administrator account with ID: {$newId}." . PHP_EOL;
    }
    echo "Admin provisioning complete. Password hashed with bcrypt." . PHP_EOL;
} catch (Exception $e) {
    echo "Error provisioning administrator: " . $e->getMessage() . PHP_EOL;
    exit(1);
}
