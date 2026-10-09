<?php
// ============================================================
// University Alumni Network - Alumni Directory
// Server-side & Live Search, Degree/Year Filters, Networking & Privacy
// ============================================================
$pageTitle = 'Alumni Directory';
$currentPage = 'alumni';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/functions.php';

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$yearFilter = isset($_GET['year']) ? trim($_GET['year']) : '';
$degreeFilter = isset($_GET['degree']) ? trim($_GET['degree']) : '';
$deptFilter = isset($_GET['department']) ? trim($_GET['department']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 6;
$totalCount = 0;
$totalPages = 1;

$alumniList = [];

if ($db_connected && $pdo) {
    try {
        $whereSql = " WHERE u.status = 'active' AND p.is_public = 1";
        $params = [];

        if ($search !== '') {
            $whereSql .= " AND (u.first_name LIKE :s1 OR u.last_name LIKE :s2 OR u.username LIKE :s3 OR p.current_company LIKE :s4 OR p.current_job_title LIKE :s5 OR p.skills LIKE :s6)";
            $params[':s1'] = "%{$search}%";
            $params[':s2'] = "%{$search}%";
            $params[':s3'] = "%{$search}%";
            $params[':s4'] = "%{$search}%";
            $params[':s5'] = "%{$search}%";
            $params[':s6'] = "%{$search}%";
        }

        if ($yearFilter !== '') {
            $whereSql .= " AND p.graduation_year = :year";
            $params[':year'] = $yearFilter;
        }

        if ($degreeFilter !== '') {
            $whereSql .= " AND p.degree_programme LIKE :degree";
            $params[':degree'] = "%{$degreeFilter}%";
        }

        if ($deptFilter !== '') {
            $whereSql .= " AND p.department LIKE :dept";
            $params[':dept'] = "%{$deptFilter}%";
        }

        // Count total matching profiles
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM users u JOIN alumni_profiles p ON u.id = p.user_id" . $whereSql);
        $countStmt->execute($params);
        $totalCount = (int)$countStmt->fetchColumn();
        $totalPages = max(1, (int)ceil($totalCount / $perPage));
        if ($page > $totalPages) $page = $totalPages;
        $offset = ($page - 1) * $perPage;

        // Fetch paginated results
        $sql = "
            SELECT u.id, u.username, u.first_name, u.last_name, u.email,
                   p.graduation_year, p.degree_programme, p.department,
                   p.current_job_title, p.current_company, p.location,
                   p.linkedin_url, p.bio, p.skills, p.profile_picture
            FROM users u
            JOIN alumni_profiles p ON u.id = p.user_id
            {$whereSql}
            ORDER BY p.graduation_year DESC, u.first_name ASC
            LIMIT " . intval($perPage) . " OFFSET " . intval($offset);

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $alumniList = $stmt->fetchAll();
    } catch (Exception $e) {
        $alumniList = [];
    }
}

// Fallback to sample data if DB is offline
if (empty($alumniList) && empty($search) && empty($yearFilter) && empty($degreeFilter) && empty($deptFilter)) {
    $alumniList = get_sample_alumni();
}

// Fetch distinct degrees and years for dropdown filters
$availableYears = [];
$availableDegrees = [];
if ($db_connected && $pdo) {
    try {
        $availableYears = $pdo->query("SELECT DISTINCT graduation_year FROM alumni_profiles ORDER BY graduation_year DESC")->fetchAll(PDO::FETCH_COLUMN);
        $availableDegrees = $pdo->query("SELECT DISTINCT degree_programme FROM alumni_profiles ORDER BY degree_programme ASC")->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
        $availableYears = [2022, 2021, 2020, 2019, 2018];
        $availableDegrees = ['B.Sc. in Computer Engineering', 'B.Sc. in Software Engineering', 'B.Sc. in Electrical Engineering', 'B.Sc. in Information Technology'];
    }
}
?>

<!-- Cinematic Header Section -->
<section style="background: radial-gradient(circle at 50% 20%, rgba(147, 51, 234, 0.22) 0%, rgba(16, 9, 29, 0.95) 70%, #080510 100%); color: var(--text-light); padding: 4.5rem 0 3.5rem; border-bottom: 1px solid var(--border-color);">
    <div class="container text-center" style="text-align: center;">
        <span class="section-eyebrow">Distinguished Graduates &bull; Global Leaders &bull; Mentors</span>
        <h1 class="section-title" style="color: var(--text-light); margin-bottom: 0.8rem;">Alumni Directory</h1>
        <div class="gold-divider"></div>
        <p style="color: var(--text-muted); max-width: 600px; margin: 1rem auto 0;">
            Connect with alumni across international technology hubs, research institutes, and entrepreneurial ventures.
        </p>
    </div>
</section>

<div class="container" style="padding-top: 3rem; padding-bottom: 5rem;">

    <!-- Filter Card -->
    <div class="card" style="margin-bottom: 3rem; padding: 2rem; border-top: 3px solid var(--accent-color);">
        <form method="GET" action="directory.php" id="filterForm">
            <div class="grid grid-3" style="gap: 1.5rem; margin-bottom: 1.5rem;">
                
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="search">Live Search (Name, Role, Company, Skills)</label>
                    <input type="text" id="search" name="search" class="form-control" 
                           placeholder="Type to search immediately..." 
                           value="<?php echo htmlspecialchars($search); ?>" onkeyup="liveSearchAlumni();">
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label for="year">Graduation Year</label>
                    <select id="year" name="year" class="form-control" onchange="document.getElementById('filterForm').submit();">
                        <option value="">All Batches</option>
                        <?php foreach ($availableYears as $y): ?>
                            <option value="<?php echo $y; ?>" <?php echo ($yearFilter == $y) ? 'selected' : ''; ?>>
                                Class of <?php echo $y; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label for="degree">Degree Programme</label>
                    <select id="degree" name="degree" class="form-control" onchange="document.getElementById('filterForm').submit();">
                        <option value="">All Programmes</option>
                        <?php foreach ($availableDegrees as $deg): ?>
                            <option value="<?php echo htmlspecialchars($deg); ?>" <?php echo ($degreeFilter === $deg) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($deg); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                <span id="resultCountBadge" style="font-size: 0.9rem; color: var(--text-muted); font-weight: 600;">
                    Displaying <?php echo count($alumniList); ?> verified alumni profiles
                </span>
                <div style="display: flex; gap: 0.8rem;">
                    <button type="submit" class="btn btn-gold btn-sm gold-glow">Filter Directory</button>
                    <?php if (!empty($search) || !empty($yearFilter) || !empty($degreeFilter) || !empty($deptFilter)): ?>
                        <a href="directory.php" class="btn btn-outline-light btn-sm" style="color: var(--text-color); border-color: var(--border-color);">Clear Filters</a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>

    <!-- Alumni Profiles Grid -->
    <div class="grid grid-3" id="alumniGrid">
        <?php if (!empty($alumniList)): ?>
            <?php foreach ($alumniList as $alumnus): ?>
                <?php 
                    $skills = !empty($alumnus['skills']) ? array_filter(array_map('trim', explode(',', $alumnus['skills']))) : [];
                    $avatar = get_user_avatar_url($alumnus['profile_picture'] ?? 'default-avatar.svg');
                ?>
                <div class="card alumni-card">
                    
                    <div class="alumni-header-ribbon">
                        <span class="alumni-cohort-badge">Class of <?php echo htmlspecialchars($alumnus['graduation_year']); ?></span>
                    </div>

                    <div class="alumni-avatar-wrapper">
                        <img src="<?php echo $avatar; ?>" 
                             alt="<?php echo htmlspecialchars($alumnus['first_name']); ?>" 
                             class="alumni-avatar">
                    </div>

                    <h3 class="alumni-name">
                        <a href="profile.php?id=<?php echo $alumnus['id']; ?>" style="color: inherit; text-decoration: none;">
                            <?php echo htmlspecialchars($alumnus['first_name'] . ' ' . $alumnus['last_name']); ?>
                        </a>
                    </h3>
                    
                    <div class="alumni-credentials">
                        <div class="alumni-role-company">
                            <span class="alumni-role"><?php echo htmlspecialchars(!empty($alumnus['current_job_title']) ? $alumnus['current_job_title'] : 'Alumnus'); ?></span>
                            <?php if (!empty($alumnus['current_company'])): ?>
                                <span class="alumni-company-tag"><?php echo htmlspecialchars($alumnus['current_company']); ?></span>
                            <?php endif; ?>
                        </div>

                        <p class="alumni-degree">&#127891; <?php echo htmlspecialchars($alumnus['degree_programme']); ?></p>

                        <?php if (!empty($alumnus['location'])): ?>
                            <p style="font-size: 0.82rem; color: var(--text-muted); margin-top: 0.25rem;">
                                &#128205; <?php echo htmlspecialchars($alumnus['location']); ?>
                            </p>
                        <?php endif; ?>
                    </div>

                    <div class="alumni-divider"></div>

                    <p class="alumni-desc">
                        <?php echo htmlspecialchars(!empty($alumnus['bio']) ? $alumnus['bio'] : 'Distinguished university graduate.'); ?>
                    </p>

                    <!-- Skills preview -->
                    <?php if (!empty($skills)): ?>
                        <div style="display: flex; flex-wrap: wrap; gap: 0.35rem; justify-content: center; margin-bottom: 1.2rem; position: relative; z-index: 1;">
                            <?php foreach (array_slice($skills, 0, 3) as $sk): ?>
                                <span class="badge" style="font-size: 0.72rem; background: rgba(124, 58, 237, 0.15); color: var(--pale-highlight); border: 1px solid rgba(196, 181, 253, 0.25);">
                                    <?php echo htmlspecialchars($sk); ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Action Buttons -->
                    <div style="display: flex; gap: 0.4rem; justify-content: center; padding-top: 1rem; border-top: 1px solid var(--border-color); flex-wrap: wrap; position: relative; z-index: 1;">
                        <a href="profile.php?id=<?php echo $alumnus['id']; ?>" class="btn btn-outline-light btn-sm" style="font-size: 0.8rem; color: var(--text-light); border-color: var(--border-color);">
                            Profile
                        </a>

                        <?php if ($isLoggedIn && $alumnus['id'] != $currentUserId): ?>
                            <a href="messages.php?user_id=<?php echo $alumnus['id']; ?>" class="btn btn-gold btn-sm gold-glow" style="font-size: 0.8rem;">
                                Message
                            </a>
                        <?php elseif (!$isLoggedIn): ?>
                            <a href="auth/login.php" class="btn btn-gold btn-sm" style="font-size: 0.8rem;">
                                Connect
                            </a>
                        <?php endif; ?>

                        <?php if (!empty($alumnus['linkedin_url'])): ?>
                            <a href="<?php echo htmlspecialchars($alumnus['linkedin_url']); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-gold btn-sm" style="font-size: 0.8rem;" title="LinkedIn">
                                in
                            </a>
                        <?php endif; ?>

                        <?php if ($isLoggedIn && $alumnus['id'] != $currentUserId): ?>
                            <button type="button" class="btn btn-outline-light btn-sm" style="font-size: 0.72rem; color: var(--text-muted); border-color: var(--border-color);" 
                                    onclick="openReportModal(<?php echo $alumnus['id']; ?>, 'user', '<?php echo htmlspecialchars(addslashes($alumnus['first_name'] . ' ' . $alumnus['last_name'])); ?>');" title="Report Profile">
                                &#9873;
                            </button>
                        <?php endif; ?>
                    </div>

                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="grid-column: 1 / -1; text-align: center; padding: 4rem 1rem;">
                <p style="font-size: 2.5rem; margin-bottom: 0.5rem;">&#128101;</p>
                <h3 style="color: var(--primary-color); font-weight: 700; margin-bottom: 0.5rem;">No alumni profiles found</h3>
                <p style="color: var(--text-muted);">Try adjusting your search criteria or clearing active filters.</p>
                <a href="directory.php" class="btn btn-gold btn-sm" style="margin-top: 1rem;">Reset All Filters</a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Pagination Controls -->
    <?php if ($totalPages > 1): ?>
        <?php 
            $queryParams = $_GET; 
            function get_page_url($p, $queryParams) {
                $queryParams['page'] = $p;
                return 'directory.php?' . http_build_query($queryParams);
            }
        ?>
        <div style="display: flex; justify-content: center; align-items: center; gap: 0.5rem; margin-top: 3rem; flex-wrap: wrap;">
            <?php if ($page > 1): ?>
                <a href="<?php echo htmlspecialchars(get_page_url($page - 1, $queryParams)); ?>" class="btn btn-outline-light btn-sm" style="color: var(--text-color); border-color: var(--border-color);">&larr; Previous</a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="<?php echo htmlspecialchars(get_page_url($i, $queryParams)); ?>" 
                   class="btn btn-sm <?php echo ($i === $page) ? 'btn-gold' : 'btn-outline-light'; ?>" 
                   style="<?php echo ($i === $page) ? '' : 'color: var(--text-color); border-color: var(--border-color);'; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>

            <?php if ($page < $totalPages): ?>
                <a href="<?php echo htmlspecialchars(get_page_url($page + 1, $queryParams)); ?>" class="btn btn-outline-light btn-sm" style="color: var(--text-color); border-color: var(--border-color);">Next &rarr;</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</div>

<!-- Modal for reporting user -->
<div id="reportModal" class="modal-overlay">
    <div class="modal-box">
        <h3 style="color: var(--text-light); margin-bottom: 0.5rem; font-weight: 800;">Report Member Profile</h3>
        <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1.2rem;">
            Help ensure community authenticity and safety.
        </p>

        <form method="POST" action="actions/report.php">
            <input type="hidden" name="reported_user_id" id="report_user_id" value="">
            
            <div class="form-group">
                <label for="report_reason">Reason for Report *</label>
                <select name="reason" id="report_reason" class="form-control" required>
                    <option value="">Select a reason...</option>
                    <option value="Fake or Inaccurate Credentials">Fake or Inaccurate Credentials</option>
                    <option value="Spam / Commercial Solicitation">Spam / Commercial Solicitation</option>
                    <option value="Harassment or Inappropriate Behavior">Harassment or Inappropriate Behavior</option>
                    <option value="Other Policy Violation">Other Policy Violation</option>
                </select>
            </div>

            <div class="form-group">
                <label for="report_desc">Details</label>
                <textarea name="description" id="report_desc" rows="3" class="form-control" placeholder="Provide additional details..."></textarea>
            </div>

            <div style="display: flex; gap: 0.8rem; justify-content: flex-end; margin-top: 1.5rem;">
                <button type="button" class="btn btn-outline-light" onclick="closeReportModal();">Cancel</button>
                <button type="submit" class="btn btn-gold">Submit Report</button>
            </div>
        </form>
    </div>
</div>

<script>
// Requirement 25: Live Search without external libraries (Vanilla JS)
function liveSearchAlumni() {
    const query = document.getElementById('search').value.toLowerCase().trim();
    const cards = document.querySelectorAll('.alumni-card');
    let visibleCount = 0;

    cards.forEach(function(card) {
        const text = card.innerText.toLowerCase();
        if (text.includes(query)) {
            card.style.display = 'flex';
            visibleCount++;
        } else {
            card.style.display = 'none';
        }
    });

    const badge = document.getElementById('resultCountBadge');
    if (badge) {
        badge.innerText = 'Displaying ' + visibleCount + ' matching alumni profiles';
    }
}

function openReportModal(userId, type, name) {
    document.getElementById('report_user_id').value = userId;
    document.getElementById('reportModal').style.display = 'flex';
}
function closeReportModal() {
    document.getElementById('reportModal').style.display = 'none';
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
