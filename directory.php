<?php
// ============================================================
// University Alumni Network - Alumni Directory
// ============================================================
$pageTitle = 'Alumni Directory';
$currentPage = 'alumni';
require_once __DIR__ . '/includes/header.php';

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$yearFilter = isset($_GET['year']) ? trim($_GET['year']) : '';
$degreeFilter = isset($_GET['degree']) ? trim($_GET['degree']) : '';

$alumniList = [];

if ($db_connected && $pdo) {
    try {
        $sql = "
            SELECT u.id, u.first_name, u.last_name, u.email,
                   p.graduation_year, p.degree_programme, p.department,
                   p.current_job_title, p.current_company, p.location,
                   p.linkedin_url, p.bio, p.profile_picture
            FROM users u
            JOIN alumni_profiles p ON u.id = p.user_id
            WHERE u.status = 'active' AND p.is_public = 1
        ";
        $params = [];

        if ($search !== '') {
            $sql .= " AND (u.first_name LIKE :search1 OR u.last_name LIKE :search2 OR p.current_company LIKE :search3 OR p.current_job_title LIKE :search4)";
            $params[':search1'] = "%{$search}%";
            $params[':search2'] = "%{$search}%";
            $params[':search3'] = "%{$search}%";
            $params[':search4'] = "%{$search}%";
        }

        if ($yearFilter !== '') {
            $sql .= " AND p.graduation_year = :year";
            $params[':year'] = $yearFilter;
        }

        if ($degreeFilter !== '') {
            $sql .= " AND p.degree_programme LIKE :degree";
            $params[':degree'] = "%{$degreeFilter}%";
        }

        $sql .= " ORDER BY p.graduation_year DESC, u.first_name ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $alumniList = $stmt->fetchAll();
    } catch (Exception $e) {
        $alumniList = [];
    }
}

// Fallback to sample data if DB is offline
if (empty($alumniList) && empty($search) && empty($yearFilter) && empty($degreeFilter)) {
    $alumniList = get_sample_alumni();
}
?>

<!-- Cinematic Directory Header -->
<section style="background: var(--primary-color); color: var(--text-light); padding: 4rem 0 3rem; border-bottom: 2px solid var(--accent-color);">
    <div class="container text-center" style="text-align: center;">
        <span class="section-eyebrow">Connect Across The Globe</span>
        <h1 class="section-title" style="color: var(--text-light); margin-bottom: 0.8rem;">Alumni Directory</h1>
        <p style="color: rgba(248, 244, 234, 0.75); max-width: 600px; margin: 0 auto;">
            Discover, connect, and collaborate with graduates across batches, disciplines, and global industries.
        </p>
    </div>
</section>

<div class="container" style="padding-top: 3rem; padding-bottom: 5rem;">

    <!-- Filter & Search Bar -->
    <div class="card" style="padding: 1.8rem; margin-bottom: 3rem; border-top: 3px solid var(--accent-color);">
        <form action="directory.php" method="GET" style="display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 1rem; align-items: end;">
            <div class="form-group" style="margin-bottom: 0;">
                <label for="search">Search Alumni</label>
                <input type="text" id="search" name="search" class="form-control" 
                       placeholder="Name, role, or company..." 
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>

            <div class="form-group" style="margin-bottom: 0;">
                <label for="year">Graduation Year</label>
                <select id="year" name="year" class="form-control">
                    <option value="">All Years</option>
                    <?php for ($y = 2026; $y >= 2015; $y--): ?>
                        <option value="<?php echo $y; ?>" <?php echo ($yearFilter == $y) ? 'selected' : ''; ?>>
                            Class of <?php echo $y; ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>

            <div class="form-group" style="margin-bottom: 0;">
                <label for="degree">Degree Filter</label>
                <input type="text" id="degree" name="degree" class="form-control" 
                       placeholder="e.g. Computer" 
                       value="<?php echo htmlspecialchars($degreeFilter); ?>">
            </div>

            <div>
                <button type="submit" class="btn btn-primary" style="height: 44px; padding: 0 1.5rem;">
                    Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Results Status Count -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <p style="color: var(--text-muted); font-weight: 600;">
            Showing <span style="color: var(--primary-color); font-weight: 800;"><?php echo count($alumniList); ?></span> verified alumni member(s)
        </p>
        <?php if (!empty($search) || !empty($yearFilter) || !empty($degreeFilter)): ?>
            <a href="directory.php" style="color: var(--accent-color); font-weight: 700; font-size: 0.9rem;">&times; Clear Filters</a>
        <?php endif; ?>
    </div>

    <!-- Alumni Cards Grid -->
    <?php if (!empty($alumniList)): ?>
        <div class="grid grid-3">
            <?php foreach ($alumniList as $alumni): ?>
                <div class="card alumni-card">
                    <div class="alumni-avatar-wrapper">
                        <img src="assets/images/<?php echo !empty($alumni['profile_picture']) ? htmlspecialchars($alumni['profile_picture']) : 'default-avatar.svg'; ?>" 
                             alt="<?php echo htmlspecialchars($alumni['first_name'] . ' ' . $alumni['last_name']); ?>" 
                             class="alumni-avatar">
                    </div>

                    <h3 class="alumni-name">
                        <?php echo htmlspecialchars($alumni['first_name'] . ' ' . $alumni['last_name']); ?>
                    </h3>

                    <div class="alumni-degree">
                        <?php echo htmlspecialchars($alumni['degree_programme']); ?>
                    </div>

                    <div class="alumni-role">
                        <?php echo htmlspecialchars($alumni['current_job_title'] ?: 'Graduate Scholar'); ?>
                    </div>

                    <div class="alumni-company">
                        <?php echo htmlspecialchars($alumni['current_company'] ?: 'Alumni Network'); ?>
                    </div>

                    <?php if (!empty($alumni['location'])): ?>
                        <div style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1rem;">
                            &#128205; <?php echo htmlspecialchars($alumni['location']); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($alumni['bio'])): ?>
                        <p style="font-size: 0.88rem; color: var(--text-color); margin-bottom: 1.2rem; font-style: italic;">
                            "<?php echo htmlspecialchars(substr($alumni['bio'], 0, 95)) . '...'; ?>"
                        </p>
                    <?php endif; ?>

                    <div class="alumni-badge-grad">
                        <?php echo htmlspecialchars($alumni['graduation_year']); ?> Graduate
                    </div>

                    <?php if (!empty($alumni['linkedin_url'])): ?>
                        <div style="margin-top: 1rem;">
                            <a href="<?php echo htmlspecialchars($alumni['linkedin_url']); ?>" target="_blank" rel="noopener noreferrer" 
                               style="color: var(--primary-color); font-size: 0.85rem; font-weight: 700; text-decoration: underline;">
                                Connect on LinkedIn &rarr;
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="card" style="text-align: center; padding: 4rem 2rem;">
            <div style="font-size: 3rem; color: var(--accent-color); margin-bottom: 1rem;">&#128269;</div>
            <h3 style="color: var(--primary-color); margin-bottom: 0.5rem;">No alumni match your criteria</h3>
            <p style="color: var(--text-muted); margin-bottom: 1.5rem;">Try modifying your search keywords or resetting the graduation year filter.</p>
            <a href="directory.php" class="btn btn-primary">Reset Filters</a>
        </div>
    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
