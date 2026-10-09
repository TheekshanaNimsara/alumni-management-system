<?php
// ============================================================
// University Alumni Network - Cinematic Homepage
// ============================================================
$pageTitle = 'Home';
$currentPage = 'home';
require_once __DIR__ . '/includes/header.php';

// Fetch alumni, events, and jobs from DB with fallback
$featuredAlumni = [];
$upcomingEvents = [];
$recentJobs = [];

if ($db_connected && $pdo) {
    try {
        // Fetch up to 3 featured public alumni
        $stmt = $pdo->query("
            SELECT u.first_name, u.last_name, p.degree_programme, p.department, p.current_job_title, p.current_company, p.graduation_year, p.profile_picture
            FROM users u
            JOIN alumni_profiles p ON u.id = p.user_id
            WHERE u.status = 'active' AND p.is_public = 1
            ORDER BY u.id ASC
            LIMIT 3
        ");
        $featuredAlumni = $stmt->fetchAll();

        // Fetch up to 3 upcoming approved events
        $stmt = $pdo->query("
            SELECT * FROM events
            WHERE status = 'approved'
            ORDER BY event_date ASC
            LIMIT 3
        ");
        $upcomingEvents = $stmt->fetchAll();

        // Fetch up to 3 approved jobs
        $stmt = $pdo->query("
            SELECT * FROM jobs
            WHERE status = 'approved'
            ORDER BY posted_at DESC
            LIMIT 3
        ");
        $recentJobs = $stmt->fetchAll();
    } catch (Exception $e) {
        // Fallback to sample data
    }
}

// Fallbacks if database query yielded empty results
if (empty($featuredAlumni)) {
    $featuredAlumni = array_slice(get_sample_alumni(), 0, 3);
}
if (empty($upcomingEvents)) {
    $upcomingEvents = get_sample_events();
}
if (empty($recentJobs)) {
    $recentJobs = array_slice(get_sample_jobs(), 0, 3);
}
?>

<!-- ------------------------------------------------------------
     1. CINEMATIC HERO SECTION
------------------------------------------------------------- -->
<section class="hero">
    <div class="hero-content">
        <span class="hero-subtitle-tag">University Alumni Network</span>
        <h1 class="hero-title">
            CONNECT. <span class="highlight-gold">INSPIRE.</span> GROW.
        </h1>
        <p class="hero-description">
            One community. Thousands of stories. A lifetime of connections.
            Step into the next chapter of our university’s enduring legacy.
        </p>
        <div class="hero-cta-group">
            <a href="auth/register.php" class="btn btn-gold gold-glow">JOIN THE NETWORK</a>
            <a href="directory.php" class="btn btn-outline-light">EXPLORE DIRECTORY</a>
        </div>
    </div>
</section>

<!-- ------------------------------------------------------------
     2. ALUMNI STORY / STATS SECTION (DARK NAVY)
------------------------------------------------------------- -->
<section class="section section-dark reveal">
    <div class="container text-center">
        <div class="section-header">
            <span class="section-eyebrow">Our Legacy &bull; Our Strength</span>
            <h2 class="section-title">
                MORE THAN A NETWORK.<br>
                IT'S A COMMUNITY OF ACHIEVERS.
            </h2>
            <div class="gold-divider"></div>
            <p style="max-width: 650px; margin: 0 auto; font-size: 1.05rem;">
                From leading global enterprises and cultural institutions to advanced research centers, our university graduates are shaping the future across every discipline.
            </p>
        </div>

        <div class="stats-grid">
            <div class="stat-item">
                <div class="stat-number">1,250+</div>
                <div class="stat-label">Alumni</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">50+</div>
                <div class="stat-label">Events</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">100+</div>
                <div class="stat-label">Companies</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">25+</div>
                <div class="stat-label">Years</div>
            </div>
        </div>
    </div>
</section>

<!-- ------------------------------------------------------------
     3. FEATURED ALUMNI SECTION (LIGHT)
------------------------------------------------------------- -->
<section class="section section-light section-alumni reveal">
    <div class="container">
        <div class="section-header">
            <span class="section-eyebrow">Excellence in Action</span>
            <h2 class="section-title">Alumni Achievers</h2>
            <div class="gold-divider"></div>
            <p style="color: var(--text-muted); max-width: 600px; margin: 0 auto;">
                Meet some of the exceptional graduates and visionaries who began their journey right here.
            </p>
        </div>

        <div class="grid grid-3 reveal-stagger">
            <?php foreach ($featuredAlumni as $alumni): ?>
                <div class="card alumni-card">
                    <div class="alumni-header-ribbon">
                        <span class="alumni-badge-grad">Class of <?php echo htmlspecialchars($alumni['graduation_year']); ?></span>
                    </div>
                    <div class="alumni-avatar-wrapper">
                        <img src="assets/images/<?php echo !empty($alumni['profile_picture']) ? htmlspecialchars($alumni['profile_picture']) : 'default-avatar.svg'; ?>" 
                             alt="<?php echo htmlspecialchars($alumni['first_name'] . ' ' . $alumni['last_name']); ?>" 
                             class="alumni-avatar">
                    </div>
                    <h3 class="alumni-name"><?php echo htmlspecialchars($alumni['first_name'] . ' ' . $alumni['last_name']); ?></h3>
                    <div class="alumni-credentials">
                        <div class="alumni-role-company">
                            <span class="alumni-role"><?php echo htmlspecialchars($alumni['current_job_title']); ?></span>
                            <?php if (!empty($alumni['current_company'])): ?>
                                <span class="alumni-company-tag"><?php echo htmlspecialchars($alumni['current_company']); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="alumni-degree"><?php echo htmlspecialchars($alumni['degree_programme']); ?></div>
                    </div>
                    <div class="alumni-divider"></div>
                    <p class="alumni-desc">
                        <?php echo !empty($alumni['bio']) ? htmlspecialchars(substr($alumni['bio'], 0, 110)) . '...' : 'Distinguished alumnus contributing across industry and active in student mentorship.'; ?>
                    </p>
                    <a href="directory.php" class="btn btn-outline-gold btn-sm btn-block alumni-cta">View Profile &rarr;</a>
                </div>
            <?php endforeach; ?>
        </div>

        <div style="text-align: center; margin-top: 3.5rem;">
            <a href="directory.php" class="btn btn-primary">View Full Alumni Directory &rarr;</a>
        </div>
    </div>
</section>

<!-- ------------------------------------------------------------
     4. UPCOMING EVENTS SECTION (DARK NAVY)
------------------------------------------------------------- -->
<section class="section section-dark section-events reveal">
    <div class="container">
        <div class="section-header">
            <span class="section-eyebrow">Connect &amp; Celebrate</span>
            <h2 class="section-title">Upcoming Events</h2>
            <div class="gold-divider"></div>
            <p style="max-width: 600px; margin: 0 auto;">
                Join inspiring reunions, technical keynote symposiums, and university career fairs.
            </p>
        </div>

        <div class="grid grid-3 reveal-stagger">
            <?php foreach ($upcomingEvents as $event): ?>
                <div class="card card-dark event-card">
                    <div class="event-image-box">
                        <img src="assets/images/event-placeholder.svg" alt="<?php echo htmlspecialchars($event['title']); ?>" class="event-image">
                        <div class="event-artwork-overlay"></div>
                        <div class="event-badge-row">
                            <span class="event-date-badge">
                                <?php echo date('d M Y', strtotime($event['event_date'])); ?>
                            </span>
                            <span class="event-category-badge">
                                <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                                Campus Event
                            </span>
                        </div>
                    </div>
                    <div class="event-body">
                        <div class="event-meta">
                            <span>
                                <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                                <?php echo date('h:i A', strtotime($event['event_time'])); ?>
                            </span>
                            <span class="event-meta-sep">&bull;</span>
                            <span>
                                <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                                <?php echo htmlspecialchars($event['location']); ?>
                            </span>
                        </div>
                        <h3 class="event-title"><?php echo htmlspecialchars($event['title']); ?></h3>
                        <p class="event-desc">
                            <?php echo htmlspecialchars(substr($event['description'], 0, 110)) . '...'; ?>
                        </p>
                        <a href="events.php" class="btn btn-gold btn-block btn-sm">VIEW EVENT</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div style="text-align: center; margin-top: 3.5rem;">
            <a href="events.php" class="btn btn-gold gold-glow">Explore All Events</a>
        </div>
    </div>
</section>

<!-- ------------------------------------------------------------
     5. JOBS / CAREERS SECTION (LIGHT)
------------------------------------------------------------- -->
<section class="section section-light section-jobs reveal">
    <div class="container">
        <div class="section-header">
            <span class="section-eyebrow">Career Opportunities</span>
            <h2 class="section-title">Latest Vacancies</h2>
            <div class="gold-divider"></div>
            <p style="color: var(--text-muted); max-width: 600px; margin: 0 auto;">
                Exclusive career openings posted by alumni and top industry partners.
            </p>
        </div>

        <div class="grid grid-3 reveal-stagger">
            <?php foreach ($recentJobs as $job): ?>
                <div class="card job-card">
                    <div class="job-card-header">
                        <span class="job-type-pill"><?php echo htmlspecialchars($job['job_type']); ?></span>
                        <span class="job-badge-verified">
                            <svg viewBox="0 0 24 24" width="12" height="12" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path></svg>
                            Alumni Network
                        </span>
                    </div>
                    <h3 class="job-title"><?php echo htmlspecialchars($job['title']); ?></h3>
                    <div class="job-company-row">
                        <span class="job-company"><?php echo htmlspecialchars($job['company']); ?></span>
                    </div>
                    <div class="job-meta-strip">
                        <span>
                            <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                            <?php echo htmlspecialchars($job['location']); ?>
                        </span>
                    </div>
                    <div class="job-divider"></div>
                    <p class="job-desc"><?php echo htmlspecialchars(substr($job['description'], 0, 120)) . '...'; ?></p>
                    <a href="jobs.php" class="btn btn-primary btn-block btn-sm job-cta">Apply for Position &rarr;</a>
                </div>
            <?php endforeach; ?>
        </div>

        <div style="text-align: center; margin-top: 3.5rem;">
            <a href="jobs.php" class="btn btn-primary">Browse All Opportunities &rarr;</a>
        </div>
    </div>
</section>

<!-- ------------------------------------------------------------
     6. UNIVERSITY QUOTE & CTA BANNER (DARK MAROON)
------------------------------------------------------------- -->
<section class="section section-dark reveal" style="border-top: 1px solid rgba(212, 175, 55, 0.25);">
    <div class="container-narrow text-center" style="text-align: center;">
        <span class="section-eyebrow">Once a Student, Always an Alumnus</span>
        <h2 class="section-title" style="font-size: 2rem; margin: 1rem 0;">
            Ready to reconnect with your university family?
        </h2>
        <p style="font-size: 1.1rem; margin-bottom: 2.2rem; color: rgba(248, 244, 234, 0.75);">
            Share your story, mentor aspiring students, recruit fellow alumni, and attend exclusive university gatherings.
        </p>
        <a href="auth/register.php" class="btn btn-gold gold-glow" style="padding: 1rem 2.5rem; font-size: 1.05rem;">
            CREATE YOUR PROFILE TODAY
        </a>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
