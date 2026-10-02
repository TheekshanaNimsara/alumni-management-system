<?php
require_once 'config/db.php';

// 1. Capture all search filters
$search_term = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$year_filter = isset($_GET['year']) ? $conn->real_escape_string($_GET['year']) : '';
$degree_filter = isset($_GET['degree']) ? $conn->real_escape_string($_GET['degree']) : '';

// 2. Base query
$sql = "SELECT ap.*, u.first_name, u.last_name 
        FROM alumni_profiles ap
        INNER JOIN users u ON ap.user_id = u.id
        WHERE ap.is_public = 1";

// 3. Append search logic dynamically
if (!empty($search_term)) {
    $sql .= " AND (u.first_name LIKE '%$search_term%' OR u.last_name LIKE '%$search_term%' OR ap.current_company LIKE '%$search_term%')";
}
if (!empty($year_filter)) {
    $sql .= " AND ap.graduation_year = '$year_filter'";
}
if (!empty($degree_filter)) {
    $sql .= " AND ap.degree_programme LIKE '%$degree_filter%'";
}

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Alumni Directory</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="logo">
            <span class="logo-badge">AN</span>
            <span style="font-weight: bold; font-size: 1.2rem;">Alumni Network</span>
        </div>
    </nav>
    
    <div class="container" style="padding: 2rem;">
        <h1 style="color: var(--primary-color); font-size: 2.5rem;">Alumni Directory</h1>
        
        <p style="color: #666; margin-bottom: 1.5rem;"><?= $result->num_rows ?> member(s) found</p>

        <div class="card" style="margin-bottom: 3rem;">
            <form action="directory.php" method="GET" style="display: flex; flex-wrap: wrap; gap: 1rem;">
                <div style="flex: 1; min-width: 200px;">
                    <input type="text" class="form-control" name="search" placeholder="Search by name, company..." value="<?= htmlspecialchars($search_term) ?>">
                </div>
                <div style="flex: 1; min-width: 200px;">
                    <select class="form-control" name="year">
                        <option value="">All Graduation Years</option>
                        <option value="2026" <?= $year_filter == '2026' ? 'selected' : '' ?>>2026</option>
                        <option value="2025" <?= $year_filter == '2025' ? 'selected' : '' ?>>2025</option>
                    </select>
                </div>
                <div style="flex: 2; display: flex; gap: 1rem; min-width: 300px;">
                     <input type="text" class="form-control" name="degree" placeholder="Filter by degree programme" value="<?= htmlspecialchars($degree_filter) ?>">
                     <button type="submit" class="btn-primary" style="width: auto;">Filter</button>
                </div>
            </form>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="card">
                        <h3><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></h3>
                        <p><strong>Degree:</strong> <?= htmlspecialchars($row['degree_programme']) ?></p>
                        <p><strong>Class:</strong> <?= htmlspecialchars($row['graduation_year']) ?></p>
                        <p><strong>Job:</strong> <?= htmlspecialchars($row['current_job_title']) ?> at <?= htmlspecialchars($row['current_company']) ?></p>
                        <p><strong>Location:</strong> <?= htmlspecialchars($row['location']) ?></p>
                        
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
