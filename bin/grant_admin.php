<?php
/**
 * Alumni Network - Administrator Privilege Management CLI Tool
 *
 * Usage:
 *   php bin/grant_admin.php <email|username|user_id>
 *   php bin/grant_admin.php --list
 *   php bin/grant_admin.php --demote <email|username|user_id>
 
 */

  //Grant administrator privileges to an existing user.
  //Remove administrator privileges from an existing administrator.
  //List all existing administrators.
  //Protect the primary administrator account from being demoted.

  
// Ensure script is run from CLI
if (php_sapi_name() !== 'cli') {
    header('Content-Type: text/plain');
    echo "This administrative utility must be run from the command line:\n";
    echo "  php bin/grant_admin.php <email|username|user_id>\n";
    exit(1);
}

require_once __DIR__ . '/../config/db.php';

echo "\n============================================================\n";
echo " Alumni Network — Administrator Management Tool\n";
echo "============================================================\n";

if (!$db_connected || !$pdo) {
    echo "\n[ERROR] Unable to connect to MySQL database.\n";
    echo "Check your configuration in config/db.php and ensure MySQL is running.\n\n";
    exit(1);
}

$args = array_slice($argv, 1);

// Help / Usage
if (empty($args) || in_array($args[0], ['-h', '--help', 'help'])) {
    echo "\nUsage:\n";
    echo "  php bin/grant_admin.php <email|username|id>       Grant admin role to user\n";
    echo "  php bin/grant_admin.php --demote <email|username> Demote admin to alumni role\n";
    echo "  php bin/grant_admin.php --list                    List all existing administrators\n\n";
    echo "Examples:\n";
    echo "  php bin/grant_admin.php sarah.jenkins@alumni.edu\n";
    echo "  php bin/grant_admin.php john_doe\n";
    echo "  php bin/grant_admin.php 3\n";
    echo "  php bin/grant_admin.php --list\n\n";
    exit(0);
}

// Action: List all administrators
if ($args[0] === '--list' || $args[0] === '-l') {
    try {

        //get the admin users from the database and display them in a formatted table   
        $stmt = $pdo->query("SELECT id, first_name, last_name, username, email, role, status, created_at FROM users WHERE role = 'admin' ORDER BY id ASC");
        $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "\nListing all system administrators (" . count($admins) . " found):\n\n";
        printf("%-5s | %-24s | %-16s | %-28s | %-8s\n", "ID", "Name", "Username", "Email", "Status");
        echo str_repeat("-", 90) . "\n";
        foreach ($admins as $adm) {
            $name = trim($adm['first_name'] . ' ' . $adm['last_name']);
            printf("%-5d | %-24s | %-16s | %-28s | %-8s\n", $adm['id'], $name, $adm['username'], $adm['email'], $adm['status']);
        }
        echo "\n";
    } catch (Exception $e) {
        echo "[ERROR] Failed to list administrators: " . $e->getMessage() . "\n\n";
        exit(1);
    }
    exit(0);
}

// Action: Demote administrator to alumni
$isDemote = false;
$targetIdentifier = '';
if ($args[0] === '--demote' || $args[0] === '-d') {
    $isDemote = true;
    if (!isset($args[1]) || trim($args[1]) === '') {
        echo "\n[ERROR] Missing identifier to demote. Example: php bin/grant_admin.php --demote user@example.com\n\n";
        exit(1);
    }
    $targetIdentifier = trim($args[1]);
} else {
    $targetIdentifier = trim($args[0]);
}

// Find user by ID, Email, or Username
try {
    if (is_numeric($targetIdentifier)) {
        $stmt = $pdo->prepare("SELECT id, first_name, last_name, username, email, role, status FROM users WHERE id = ?");
        $stmt->execute([(int)$targetIdentifier]);
    } else {
        $stmt = $pdo->prepare("SELECT id, first_name, last_name, username, email, role, status FROM users WHERE email = ? OR username = ?");
        $stmt->execute([$targetIdentifier, $targetIdentifier]);
    }

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo "\n[ERROR] No user found with identifier: '{$targetIdentifier}'\n";
        echo "Make sure the user has registered first via the website.\n\n";
        exit(1);
    }

    $fullName = trim($user['first_name'] . ' ' . $user['last_name']);

    // Administrator privileges removing workflow
    if ($isDemote) {
        if ($user['id'] == 1) {
            echo "\n[REJECTED] User ID 1 is the primary root administrator and cannot be demoted.\n\n";
            exit(1);
        }
        if ($user['role'] !== 'admin') {
            echo "\n[NOTICE] User #{$user['id']} ({$fullName} <{$user['email']}>) is not an administrator (current role: '{$user['role']}').\n\n";
            exit(0);
        }

        $upd = $pdo->prepare("UPDATE users SET role = 'alumni' WHERE id = ?");
        $upd->execute([$user['id']]);

        echo "\n[SUCCESS] Administrator privileges REMOVED for:\n";
        echo "  User ID:   #{$user['id']}\n";
        echo "  Full Name: {$fullName}\n";
        echo "  Username:  @{$user['username']}\n";
        echo "  Email:     {$user['email']}\n";
        echo "  Old Role:  admin\n";
        echo "  New Role:  alumni\n\n";
        exit(0);
    }

    // Promotion Workflow
    if ($user['role'] === 'admin') {
        echo "\n[NOTICE] User #{$user['id']} ({$fullName} <{$user['email']}>) is ALREADY an administrator.\n\n";
        exit(0);
    }

    // Update role to admin and ensure status is active
    $upd = $pdo->prepare("UPDATE users SET role = 'admin', status = 'active' WHERE id = ?");
    $upd->execute([$user['id']]);

    echo "\n[SUCCESS] Administrator privileges GRANTED successfully!\n";
    echo "------------------------------------------------------------\n";
    echo "  User ID:    #{$user['id']}\n";
    echo "  Full Name:  {$fullName}\n";
    echo "  Username:   @{$user['username']}\n";
    echo "  Email:      {$user['email']}\n";
    echo "  Previous:   {$user['role']}\n";
    echo "  New Role:   admin\n";
    echo "  Status:     active\n";
    echo "------------------------------------------------------------\n";
    echo "The user can now log in at http://localhost/alumni-network/auth/login.php\n";
    echo "and access the Admin Panel at http://localhost/alumni-network/admin/\n\n";

} catch (Exception $e) {
    echo "\n[ERROR] Database error: " . $e->getMessage() . "\n\n";
    exit(1);
}
