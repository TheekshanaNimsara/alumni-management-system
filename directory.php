<?php
require_once 'config/db.php';

// Check if a search filter is applied
$search_term = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

// Base query
$sql = "SELECT ap.*, u.first_name, u.last_name 
        FROM alumni_profiles ap
        INNER JOIN users u ON ap.user_id = u.id
        WHERE ap.is_public = 1";

// Append search logic if user typed something
if (!empty($search_term)) {
    $sql .= " AND (u.first_name LIKE '%$search_term%' OR u.last_name LIKE '%$search_term%' OR ap.current_company LIKE '%$search_term%')";
}

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Alumni Directory</title>
    <!-- Connecting to Nethsara's Global CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container" style="padding: 2rem;">
        <h1 style="color: var(--primary-color); font-size: 2.5rem;">Alumni Directory</h1>
        
        <!-- Dynamic member count -->
        <p style="color: #666; margin-bottom: 1.5rem;"><?= $result->num_rows ?> member(s) found</p>

        <!-- Search Form (Moved OUTSIDE the loop) -->
        <div class="card" style="margin-bottom: 3rem;">
            <form action="directory.php" method="GET" style="display: flex; gap: 1rem;">
                <input type="text" class="form-control" name="search" placeholder="Search by name, company..." style="flex: 1;" value="<?= htmlspecialchars($search_term) ?>">
                <button type="submit" class="btn-primary" style="width: auto;">Filter</button>
            </form>
        </div>

        <!-- Grid Layout for Alumni Cards -->
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="card">
                        <h3><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></h3>
                        <p><strong>Degree:</strong> <?= htmlspecialchars($row['degree_programme']) ?></p>
                        <p><strong>Class:</strong> <?= htmlspecialchars($row['graduation_year']) ?></p>
                        <p><strong>Job:</strong> <?= htmlspecialchars($row['current_job_title']) ?> at <?= htmlspecialchars($row['current_company']) ?></p>
                        <p><strong>Location:</strong> <?= htmlspecialchars($row['location']) ?></p>
                        
                        <!-- Link to Nethsara's Messaging Module -->
                        <a href="messages.php?user=<?= $row['user_id'] ?>" class="btn-primary" style="display: inline-block; margin-top: 15px; text-decoration: none; padding: 10px 15px; border-radius: var(--border-radius);">Send Message</a>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state" style="grid-column: 1 / -1; text-align: center; color: #666; padding: 3rem 0;">
                    <h3>No alumni match your search. Try a different filter.</h3>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
