<?php
// ============================================================
// University Alumni Network - Alumni Profile System
// View & Edit Profile, Photo Upload, Skills, and Networking
// ============================================================
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$successMsg = '';
$errorMsg = '';

// Determine target profile to view
$currentUserId = current_user_id();
$viewUserId = isset($_GET['id']) ? intval($_GET['id']) : $currentUserId;

if (!$viewUserId) {
    // If not logged in and no ID specified, redirect to login
    require_login();
}

$isOwner = ($currentUserId && $viewUserId == $currentUserId);
$activeTab = 'overview';

// Handle Profile Updates (Owner only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isOwner) {
    $action = $_POST['action'] ?? '';

        // 1. Photo Upload Action
        if ($action === 'upload_photo') {
            $activeTab = 'edit';
            if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
                $fileTmp = $_FILES['profile_photo']['tmp_name'];
                $fileSize = $_FILES['profile_photo']['size'];
                $fileName = $_FILES['profile_photo']['name'];

                // Max 2MB
                if ($fileSize > 2 * 1024 * 1024) {
                    $errorMsg = "Image size exceeds 2MB limit. Please upload an image under 2MB.";
                } else {
                    $imgInfo = @getimagesize($fileTmp);
                    $finfo = class_exists('finfo') ? new finfo(FILEINFO_MIME_TYPE) : null;
                    $finfoMime = $finfo ? $finfo->file($fileTmp) : ($imgInfo['mime'] ?? '');
                    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];

                    if ($imgInfo === false || !in_array($imgInfo['mime'], $allowedMimes) || !in_array($finfoMime, $allowedMimes)) {
                        $errorMsg = "Invalid image file. Allowed image formats are JPG, PNG, and WebP.";
                    } else {
                        $extMap = [
                            'image/jpeg' => 'jpg',
                            'image/png'  => 'png',
                            'image/webp' => 'webp'
                        ];
                        $ext = $extMap[$imgInfo['mime']] ?? 'jpg';
                        $newFileName = 'avatar_' . $currentUserId . '_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
                        $targetDir = __DIR__ . '/uploads/profiles/';
                        if (!is_dir($targetDir)) {
                            @mkdir($targetDir, 0755, true);
                        }
                        $targetPath = $targetDir . $newFileName;

                        if (move_uploaded_file($fileTmp, $targetPath)) {
                            if ($db_connected && $pdo) {
                                // Fetch old photo to safely remove if custom
                                $oldPicStmt = $pdo->prepare("SELECT profile_picture FROM alumni_profiles WHERE user_id = ? ORDER BY id DESC LIMIT 1");
                                $oldPicStmt->execute([$currentUserId]);
                                $oldPic = $oldPicStmt->fetchColumn();

                                $stmt = $pdo->prepare("UPDATE alumni_profiles SET profile_picture = ? WHERE user_id = ?");
                                $stmt->execute([$newFileName, $currentUserId]);

                                if (!empty($oldPic) && $oldPic !== 'default-avatar.svg') {
                                    $oldFile = $targetDir . basename($oldPic);
                                    if (file_exists($oldFile) && is_file($oldFile) && basename($oldPic) === $oldPic) {
                                        @unlink($oldFile);
                                    }
                                }
                            }
                            $successMsg = "Profile photo updated successfully!";
                        } else {
                            $errorMsg = "Failed to save the uploaded image. Please check directory permissions or try again.";
                        }
                    }
                }
            } else {
                $errorMsg = "Please select a valid image file to upload.";
            }
        }

        // 2. Remove Photo Action (revert to default avatar)
        elseif ($action === 'remove_photo') {
            $activeTab = 'edit';
            if ($db_connected && $pdo) {
                $oldPicStmt = $pdo->prepare("SELECT profile_picture FROM alumni_profiles WHERE user_id = ? ORDER BY id DESC LIMIT 1");
                $oldPicStmt->execute([$currentUserId]);
                $oldPic = $oldPicStmt->fetchColumn();

                $stmt = $pdo->prepare("UPDATE alumni_profiles SET profile_picture = 'default-avatar.svg' WHERE user_id = ?");
                $stmt->execute([$currentUserId]);

                if (!empty($oldPic) && $oldPic !== 'default-avatar.svg') {
                    $targetDir = __DIR__ . '/uploads/profiles/';
                    $oldFile = $targetDir . basename($oldPic);
                    if (file_exists($oldFile) && is_file($oldFile) && basename($oldPic) === $oldPic) {
                        @unlink($oldFile);
                    }
                }
            }
            $successMsg = "Profile photo removed and reverted to default avatar.";
        }

        // 3. Profile Details Update Action
        elseif ($action === 'update_profile') {
            $activeTab = 'edit';
            $firstName = trim($_POST['first_name'] ?? '');
            $lastName = trim($_POST['last_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $gradYear = intval($_POST['graduation_year'] ?? 0);
            $degree = trim($_POST['degree_programme'] ?? '');
            $department = trim($_POST['department'] ?? '');
            $jobTitle = trim($_POST['current_job_title'] ?? '');
            $company = trim($_POST['current_company'] ?? '');
            $location = trim($_POST['location'] ?? '');
            $linkedin = trim($_POST['linkedin_url'] ?? '');
            $skills = trim($_POST['skills'] ?? '');
            $bio = trim($_POST['bio'] ?? '');

            if (empty($firstName) || empty($lastName) || empty($email) || empty($degree) || empty($department) || empty($gradYear)) {
                $errorMsg = "Please complete all required fields (First Name, Last Name, Email, Degree Programme, Department, and Graduation Year).";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errorMsg = "Please provide a valid email address.";
            } elseif ($gradYear < 1960 || $gradYear > 2030) {
                $errorMsg = "Please enter a valid graduation year (between 1960 and 2030).";
            } else {
                if ($db_connected && $pdo) {
                    try {
                        // Check if email taken by someone else
                        $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                        $checkStmt->execute([$email, $currentUserId]);
                        if ($checkStmt->fetch()) {
                            $errorMsg = "This email address is already associated with another account.";
                        } else {
                            $pdo->beginTransaction();

                            // Update users
                            $uStmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ? WHERE id = ?");
                            $uStmt->execute([$firstName, $lastName, $email, $currentUserId]);

                            // Update alumni_profiles
                            $pStmt = $pdo->prepare("
                                UPDATE alumni_profiles 
                                SET graduation_year = ?, degree_programme = ?, department = ?, 
                                    current_job_title = ?, current_company = ?, location = ?, 
                                    phone = ?, linkedin_url = ?, bio = ?, skills = ?
                                WHERE user_id = ?
                            ");
                            $pStmt->execute([$gradYear, $degree, $department, $jobTitle, $company, $location, $phone, $linkedin, $bio, $skills, $currentUserId]);

                            $pdo->commit();
                            $_SESSION['user_name'] = $firstName . ' ' . $lastName;
                            $_SESSION['user_email'] = $email;
                            $successMsg = "Your profile changes have been saved successfully!";
                            $activeTab = 'overview';
                        }
                    } catch (Exception $e) {
                        if ($pdo->inTransaction()) {
                            $pdo->rollBack();
                        }
                        $errorMsg = "Database update error: " . $e->getMessage();
                    }
                } else {
                    $successMsg = "Profile updated in demo mode.";
                    $activeTab = 'overview';
                }
            }
        }
}

// Fetch Profile Data
$profile = null;
if ($db_connected && $pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT u.id, u.username, u.first_name, u.last_name, u.email, u.role, u.status, u.created_at,
                   p.graduation_year, p.degree_programme, p.department, p.current_job_title,
                   p.current_company, p.location, p.phone, p.linkedin_url, p.bio, p.skills,
                   p.profile_picture, p.is_public
            FROM users u
            LEFT JOIN alumni_profiles p ON p.id = (
                SELECT id FROM alumni_profiles WHERE user_id = u.id ORDER BY id DESC LIMIT 1
            )
            WHERE u.id = ?
            LIMIT 1
        ");
        $stmt->execute([$viewUserId]);
        $profile = $stmt->fetch();
    } catch (Exception $e) {
        $profile = null;
    }
}

// Fallback profile if not in DB
if (!$profile) {
    if ($viewUserId == 2) {
        $profile = [
            'id' => 2,
            'username' => 'johndoe',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@alumni.edu',
            'role' => 'alumni',
            'status' => 'active',
            'created_at' => '2024-01-15',
            'graduation_year' => 2022,
            'degree_programme' => 'B.Sc. in Computer Engineering',
            'department' => 'Computer Engineering',
            'current_job_title' => 'Software Engineer',
            'current_company' => 'Google',
            'location' => 'Mountain View, CA, USA',
            'phone' => '+1 650 253 0000',
            'linkedin_url' => 'https://linkedin.com/in/johndoe',
            'bio' => 'Passionate about distributed cloud systems, modern web platforms, and mentoring university students.',
            'skills' => 'Cloud Architecture, Golang, Python, Distributed Systems, Mentoring',
            'profile_picture' => 'default-avatar.svg',
            'is_public' => 1
        ];
    } else {
        $pageTitle = 'Profile Not Found';
        $currentPage = 'profile';
        require_once __DIR__ . '/includes/header.php';
        echo "<div class='container' style='padding: 5rem 0;'><div class='alert alert-danger'>Alumni profile not found. <a href='directory.php'>Return to the Alumni Directory</a></div></div>";
        require_once __DIR__ . '/includes/footer.php';
        exit;
    }
}

$avatarUrl = get_user_avatar_url($profile['profile_picture'] ?? 'default-avatar.svg');
$hasCustomPhoto = !empty($profile['profile_picture']) && $profile['profile_picture'] !== 'default-avatar.svg';
$skillsList = !empty($profile['skills']) ? array_filter(array_map('trim', explode(',', $profile['skills']))) : [];

if (isset($_GET['tab']) && $_GET['tab'] === 'edit' && $isOwner) {
    $activeTab = 'edit';
}

$pageTitle = htmlspecialchars($profile['first_name'] . ' ' . $profile['last_name']) . ' — Alumni Profile';
$currentPage = 'profile';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Scoped Liquid Glass Profile Stylesheet -->
<link rel="stylesheet" href="<?php echo $baseUrl; ?>assets/css/profile.css?v=1.0">

<!-- Profile Cover & Hero Header (Tier 2 Elevated Glass) -->
<section class="profile-hero" id="profileHero">
    <div class="container">
        
        <!-- Breadcrumbs & Quick Return Bar -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.8rem; flex-wrap: wrap; gap: 1rem;">
            <nav class="breadcrumbs" aria-label="Breadcrumb">
                <a href="<?php echo $baseUrl; ?>index.php">Home</a>
                <span>&rsaquo;</span>
                <a href="<?php echo $baseUrl; ?>directory.php">Alumni Directory</a>
                <span>&rsaquo;</span>
                <span class="current"><?php echo htmlspecialchars($profile['first_name'] . ' ' . $profile['last_name']); ?></span>
            </nav>
            <a href="<?php echo $baseUrl; ?>directory.php" class="btn btn-return-home btn-outline-glass btn-sm">&larr; Back to Directory</a>
        </div>

        <!-- Hero Main Content -->
        <div class="profile-hero-inner">
            <div class="profile-hero-main">
                
                <!-- Circular Halo Avatar with Upload Badge -->
                <div class="profile-avatar-wrap">
                    <img id="heroAvatarImg" src="<?php echo $avatarUrl; ?>" 
                         alt="<?php echo htmlspecialchars($profile['first_name'] . ' ' . $profile['last_name']); ?>" 
                         class="profile-avatar-img">
                    <?php if ($isOwner): ?>
                        <label for="heroProfilePhotoInput" class="profile-avatar-badge" title="Change profile photo (Max 2MB, JPG, PNG, WEBP)" aria-label="Upload new profile photo">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path><circle cx="12" cy="13" r="4"></circle></svg>
                        </label>
                        <form id="heroPhotoUploadForm" method="POST" action="profile.php" enctype="multipart/form-data" style="display: none;">
                            <input type="hidden" name="action" value="upload_photo">
                            <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">
                            <input type="file" id="heroProfilePhotoInput" name="profile_photo" accept="image/jpeg,image/png,image/webp" onchange="submitHeroPhoto(this);">
                        </form>
                    <?php endif; ?>
                </div>

                <!-- Identity, Badges & Headline -->
                <div class="profile-identity">
                    <div class="profile-name-row">
                        <h1 class="profile-name">
                            <?php echo htmlspecialchars($profile['first_name'] . ' ' . $profile['last_name']); ?>
                        </h1>
                        <span class="profile-badge-pill badge-class-year">
                            <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 10v6M2 10l10-5 10 5-10 5z"></path><path d="M6 12v5c3 3 9 3 12 0v-5"></path></svg>
                            Class of <?php echo htmlspecialchars($profile['graduation_year']); ?>
                        </span>
                        <?php if (($profile['role'] ?? '') === 'admin'): ?>
                            <span class="profile-badge-pill badge-role-verified" style="background: rgba(84, 25, 35, 0.4); border-color: rgba(216, 185, 106, 0.35); color: var(--accent-light);">
                                <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                                Administrator
                            </span>
                        <?php else: ?>
                            <span class="profile-badge-pill badge-role-verified">
                                <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                Verified Alumnus
                            </span>
                        <?php endif; ?>
                    </div>

                    <p class="profile-headline">
                        <?php echo htmlspecialchars($profile['current_job_title'] ?? 'Graduate Alumnus'); ?>
                        <?php if (!empty($profile['current_company'])): ?>
                            <span>&bull;</span>
                            <span class="company-name"><?php echo htmlspecialchars($profile['current_company']); ?></span>
                        <?php endif; ?>
                    </p>

                    <div class="profile-meta-tags">
                        <span class="profile-meta-tag" title="Degree Programme">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
                            <?php echo htmlspecialchars($profile['degree_programme']); ?>
                        </span>
                        <?php if (!empty($profile['location'])): ?>
                            <span class="profile-meta-tag" title="Location">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                                <?php echo htmlspecialchars($profile['location']); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

            <!-- Action Buttons in Hero Header -->
            <div class="profile-hero-actions">
                <?php if ($isOwner): ?>
                    <button type="button" class="btn btn-primary btn-liquid" onclick="switchProfileTab('edit');" id="heroEditBtn">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                        Edit Profile
                    </button>
                <?php else: ?>
                    <?php if ($isLoggedIn): ?>
                        <a href="messages.php?user_id=<?php echo $profile['id']; ?>" class="btn btn-primary btn-liquid">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                            Send Message
                        </a>
                        <button type="button" class="btn btn-outline-glass btn-sm" onclick="openReportModal(<?php echo $profile['id']; ?>, 'user', '<?php echo htmlspecialchars(addslashes($profile['first_name'] . ' ' . $profile['last_name'])); ?>');">
                            <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"></path><line x1="4" y1="22" x2="4" y2="15"></line></svg>
                            Report
                        </button>
                    <?php else: ?>
                        <a href="auth/login.php?redirect=<?php echo urlencode('profile.php?id=' . $profile['id']); ?>" class="btn btn-primary btn-liquid">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path><polyline points="10 17 15 12 10 7"></polyline><line x1="15" y1="12" x2="3" y2="12"></line></svg>
                            Log In to Message
                        </a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

        </div>
    </div>
</section>

<!-- Main Profile Content Area -->
<div class="container" style="padding-top: 2.8rem; padding-bottom: 5rem;">

    <!-- User Feedback Notifications -->
    <?php if (!empty($successMsg)): ?>
        <div class="alert alert-success" style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 2rem;">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
            <span><?php echo htmlspecialchars($successMsg); ?></span>
        </div>
    <?php endif; ?>

    <?php if (!empty($errorMsg)): ?>
        <div class="alert alert-danger" style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 2rem;">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
            <span><?php echo htmlspecialchars($errorMsg); ?></span>
        </div>
    <?php endif; ?>

    <!-- Segmented Navigation for Profile Owner -->
    <?php if ($isOwner): ?>
        <div class="profile-tabs-wrapper">
            <div class="profile-segmented-nav" role="tablist" aria-label="Profile Views">
                <button type="button" class="profile-tab-btn <?php echo ($activeTab === 'overview') ? 'active' : ''; ?>" id="btn-tab-overview" onclick="switchProfileTab('overview');" role="tab" aria-selected="<?php echo ($activeTab === 'overview') ? 'true' : 'false'; ?>">
                    <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                    Profile Overview
                </button>
                <button type="button" class="profile-tab-btn <?php echo ($activeTab === 'edit') ? 'active' : ''; ?>" id="btn-tab-edit" onclick="switchProfileTab('edit');" role="tab" aria-selected="<?php echo ($activeTab === 'edit') ? 'true' : 'false'; ?>">
                    <svg viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                    Edit Profile Details
                </button>
            </div>
        </div>
    <?php endif; ?>

    <!-- Two-Column Profile Grid -->
    <div class="profile-grid">

        <!-- Left Column: Dynamic Tab Content (Overview or Edit) -->
        <div class="profile-main-col">

            <!-- TAB 1: PROFILE OVERVIEW -->
            <div id="profileOverviewTab" style="<?php echo ($activeTab === 'edit') ? 'display: none;' : ''; ?>">
                
                <!-- Biography Card -->
                <div class="profile-card">
                    <div class="profile-card-header">
                        <h3 class="profile-card-title">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                            About <?php echo htmlspecialchars($profile['first_name']); ?>
                        </h3>
                    </div>
                    <?php if (!empty($profile['bio'])): ?>
                        <p class="profile-bio-text">
                            <?php echo nl2br(htmlspecialchars($profile['bio'])); ?>
                        </p>
                    <?php else: ?>
                        <div class="profile-empty-state">
                            No biography added yet. <?php if ($isOwner): ?>Share your career milestones, academic interests, or mentoring background by editing your profile.<?php else: ?>This member has not yet added a personal biography.<?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Professional Skills Card -->
                <div class="profile-card">
                    <div class="profile-card-header">
                        <h3 class="profile-card-title">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                            Key Skills & Expertise
                        </h3>
                    </div>
                    <?php if (!empty($skillsList)): ?>
                        <div class="profile-skills-cloud">
                            <?php foreach ($skillsList as $skill): ?>
                                <span class="skill-chip">
                                    <?php echo htmlspecialchars($skill); ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="profile-empty-state">
                            No skills listed yet. <?php if ($isOwner): ?>Highlight your technical skills and industry proficiencies to help fellow alumni discover your profile.<?php else: ?>This member has not yet listed their professional skills.<?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Academic & Professional Highlights Card -->
                <div class="profile-card">
                    <div class="profile-card-header">
                        <h3 class="profile-card-title">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg>
                            Academic & Career Background
                        </h3>
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1.5rem;">
                        <div style="padding: 1.2rem; background: rgba(84, 25, 35, 0.25); border: 1px solid rgba(216, 185, 106, 0.18); border-radius: 12px;">
                            <span style="display: block; font-size: 0.76rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--accent-light); margin-bottom: 0.4rem;">Education</span>
                            <strong style="display: block; font-size: 1.05rem; color: var(--text-light); margin-bottom: 0.2rem;"><?php echo htmlspecialchars($profile['degree_programme']); ?></strong>
                            <span style="font-size: 0.88rem; color: var(--text-secondary);"><?php echo htmlspecialchars($profile['department']); ?> &bull; Class of <?php echo htmlspecialchars($profile['graduation_year']); ?></span>
                        </div>

                        <div style="padding: 1.2rem; background: rgba(84, 25, 35, 0.25); border: 1px solid rgba(216, 185, 106, 0.18); border-radius: 12px;">
                            <span style="display: block; font-size: 0.76rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--accent-light); margin-bottom: 0.4rem;">Current Position</span>
                            <strong style="display: block; font-size: 1.05rem; color: var(--text-light); margin-bottom: 0.2rem;"><?php echo htmlspecialchars($profile['current_job_title'] ?? 'Graduate Alumnus'); ?></strong>
                            <span style="font-size: 0.88rem; color: var(--text-secondary);">
                                <?php echo !empty($profile['current_company']) ? htmlspecialchars($profile['current_company']) : 'Independent Professional'; ?>
                                <?php if (!empty($profile['location'])): ?>
                                    &bull; <?php echo htmlspecialchars($profile['location']); ?>
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>
                </div>

            </div>

            <!-- TAB 2: EDIT PROFILE FORM (RENDERED FOR OWNER) -->
            <?php if ($isOwner): ?>
                <div id="profileEditTab" style="<?php echo ($activeTab === 'edit') ? 'display: block;' : 'display: none;'; ?>">
                    <div id="editProfileSection" class="profile-card profile-card-edit">
                        
                        <div class="profile-card-header">
                            <div>
                                <h3 class="profile-card-title">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                    Edit Profile Details
                                </h3>
                                <p style="color: var(--text-muted); font-size: 0.9rem; margin: 0.35rem 0 0 0;">
                                    Update your professional experience, academic background, and contact information.
                                </p>
                            </div>
                        </div>

                        <!-- Section 0: Dedicated Profile Photo Management -->
                        <div class="profile-form-section" style="margin-bottom: 2rem;">
                            <h4 class="profile-form-section-heading">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path><circle cx="12" cy="13" r="4"></circle></svg>
                                Profile Photo
                            </h4>

                            <div class="profile-photo-management-card">
                                <div class="profile-photo-preview-wrap">
                                    <img id="editTabAvatarPreview" src="<?php echo $avatarUrl; ?>" alt="Current Avatar Preview">
                                </div>
                                <div class="profile-photo-info">
                                    <div style="display: flex; align-items: center; gap: 0.6rem; margin-bottom: 0.4rem; flex-wrap: wrap;">
                                        <strong style="color: var(--text-light); font-size: 1rem;">Profile Picture</strong>
                                        <?php if ($hasCustomPhoto): ?>
                                            <span class="badge" style="background: rgba(16, 185, 129, 0.15); color: #34D399; border: 1px solid rgba(52, 211, 153, 0.3); font-size: 0.75rem;">Custom Photo</span>
                                        <?php else: ?>
                                            <span class="badge" style="background: rgba(216, 185, 106, 0.15); color: var(--accent-light); border: 1px solid rgba(216, 185, 106, 0.3); font-size: 0.75rem;">Default Avatar</span>
                                        <?php endif; ?>
                                    </div>
                                    <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 0.9rem; line-height: 1.4;">
                                        Select an image to personalize your alumni profile across the network. Formats: <strong>JPG, PNG, WebP</strong> (Max 2MB).
                                    </p>
                                    
                                    <div style="display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap;">
                                        <form id="editTabPhotoForm" method="POST" action="profile.php" enctype="multipart/form-data" style="display: inline-flex; gap: 0.6rem; align-items: center; flex-wrap: wrap; margin: 0;">
                                            <input type="hidden" name="action" value="upload_photo">
                                            <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">
                                            <input type="file" id="editTabPhotoInput" name="profile_photo" accept="image/jpeg,image/png,image/webp" style="display: none;" onchange="handleEditTabPhotoSelected(this);">
                                            
                                            <button type="button" class="btn btn-outline-glass btn-sm" onclick="document.getElementById('editTabPhotoInput').click();">
                                                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 0.35rem;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                                                Choose Photo
                                            </button>
                                            
                                            <button type="submit" id="editTabPhotoSubmitBtn" class="btn btn-primary btn-liquid btn-sm" style="display: none;">
                                                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 0.35rem;"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                                Save Photo
                                            </button>
                                            
                                            <span id="selectedPhotoName" style="font-size: 0.82rem; color: var(--text-muted);"></span>
                                        </form>

                                        <?php if ($hasCustomPhoto): ?>
                                            <form method="POST" action="profile.php" onsubmit="return confirm('Are you sure you want to remove your custom photo and restore the default avatar?');" style="display: inline; margin: 0;">
                                                <input type="hidden" name="action" value="remove_photo">
                                                <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">
                                                <button type="submit" class="btn btn-outline-danger btn-sm" style="border: 1px solid rgba(239, 68, 68, 0.4); color: #F87171; background: rgba(239, 68, 68, 0.08); font-size: 0.8rem; padding: 0.4rem 0.8rem;">
                                                    <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 0.3rem;"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                                    Remove Photo
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <form method="POST" action="profile.php">
                            <input type="hidden" name="action" value="update_profile">
                            <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">

                            <!-- Section 1: Basic Identity & Contact -->
                            <div class="profile-form-section">
                                <h4 class="profile-form-section-heading">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                                    Personal & Contact Information
                                </h4>

                                <div class="form-row-2" style="margin-bottom: 1.1rem;">
                                    <div class="form-group" style="margin-bottom: 0;">
                                        <label for="first_name" class="form-label">First Name <span class="required-asterisk">*</span></label>
                                        <input type="text" id="first_name" name="first_name" class="form-control" 
                                               value="<?php echo htmlspecialchars($profile['first_name']); ?>" required>
                                    </div>
                                    <div class="form-group" style="margin-bottom: 0;">
                                        <label for="last_name" class="form-label">Last Name <span class="required-asterisk">*</span></label>
                                        <input type="text" id="last_name" name="last_name" class="form-control" 
                                               value="<?php echo htmlspecialchars($profile['last_name']); ?>" required>
                                    </div>
                                </div>

                                <div class="form-row-2">
                                    <div class="form-group" style="margin-bottom: 0;">
                                        <label for="email" class="form-label">Email Address <span class="required-asterisk">*</span></label>
                                        <input type="email" id="email" name="email" class="form-control" 
                                               value="<?php echo htmlspecialchars($profile['email']); ?>" required>
                                    </div>
                                    <div class="form-group" style="margin-bottom: 0;">
                                        <label for="phone" class="form-label">Contact Phone</label>
                                        <input type="text" id="phone" name="phone" class="form-control" 
                                               value="<?php echo htmlspecialchars($profile['phone'] ?? ''); ?>" placeholder="+94 77 123 4567">
                                    </div>
                                </div>
                            </div>

                            <!-- Section 2: Academic Background -->
                            <div class="profile-form-section">
                                <h4 class="profile-form-section-heading">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 10v6M2 10l10-5 10 5-10 5z"></path><path d="M6 12v5c3 3 9 3 12 0v-5"></path></svg>
                                    Academic Credentials
                                </h4>

                                <div class="form-row-3" style="margin-bottom: 1.1rem;">
                                    <div class="form-group" style="margin-bottom: 0;">
                                        <label for="graduation_year" class="form-label">Graduation Year <span class="required-asterisk">*</span></label>
                                        <input type="number" id="graduation_year" name="graduation_year" class="form-control" 
                                               value="<?php echo htmlspecialchars($profile['graduation_year']); ?>" min="1960" max="2030" required>
                                    </div>
                                    <div class="form-group" style="margin-bottom: 0; grid-column: span 2;">
                                        <label for="degree_programme" class="form-label">Degree Programme <span class="required-asterisk">*</span></label>
                                        <input type="text" id="degree_programme" name="degree_programme" class="form-control" 
                                               value="<?php echo htmlspecialchars($profile['degree_programme']); ?>" placeholder="e.g. B.Sc. in Computer Engineering" required>
                                    </div>
                                </div>

                                <div class="form-group" style="margin-bottom: 0;">
                                    <label for="department" class="form-label">Department / Faculty <span class="required-asterisk">*</span></label>
                                    <input type="text" id="department" name="department" class="form-control" 
                                           value="<?php echo htmlspecialchars($profile['department']); ?>" placeholder="e.g. Faculty of Computing / Department of Computer Engineering" required>
                                </div>
                            </div>

                            <!-- Section 3: Professional Career -->
                            <div class="profile-form-section">
                                <h4 class="profile-form-section-heading">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg>
                                    Current Career & Employment
                                </h4>

                                <div class="form-row-3" style="margin-bottom: 1.1rem;">
                                    <div class="form-group" style="margin-bottom: 0;">
                                        <label for="current_job_title" class="form-label">Current Job Title</label>
                                        <input type="text" id="current_job_title" name="current_job_title" class="form-control" 
                                               value="<?php echo htmlspecialchars($profile['current_job_title'] ?? ''); ?>" placeholder="e.g. Senior Software Engineer">
                                    </div>
                                    <div class="form-group" style="margin-bottom: 0;">
                                        <label for="current_company" class="form-label">Company / Organization</label>
                                        <input type="text" id="current_company" name="current_company" class="form-control" 
                                               value="<?php echo htmlspecialchars($profile['current_company'] ?? ''); ?>" placeholder="e.g. Google, IFS, Virtusa">
                                    </div>
                                    <div class="form-group" style="margin-bottom: 0;">
                                        <label for="location" class="form-label">Location / City</label>
                                        <input type="text" id="location" name="location" class="form-control" 
                                               value="<?php echo htmlspecialchars($profile['location'] ?? ''); ?>" placeholder="e.g. Mountain View, CA or Colombo">
                                    </div>
                                </div>

                                <div class="form-group" style="margin-bottom: 0;">
                                    <label for="linkedin_url" class="form-label">LinkedIn Profile URL</label>
                                    <input type="url" id="linkedin_url" name="linkedin_url" class="form-control" 
                                           value="<?php echo htmlspecialchars($profile['linkedin_url'] ?? ''); ?>" placeholder="https://linkedin.com/in/username">
                                </div>
                            </div>

                            <!-- Section 4: Skills & Biography -->
                            <div class="profile-form-section">
                                <h4 class="profile-form-section-heading">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                                    Skills & Biography
                                </h4>

                                <div class="form-group">
                                    <label for="skills" class="form-label">Skills & Expertise (Comma-separated)</label>
                                    <input type="text" id="skills" name="skills" class="form-control" 
                                           value="<?php echo htmlspecialchars($profile['skills'] ?? ''); ?>" placeholder="e.g. Cloud Architecture, Python, Distributed Systems, Mentoring">
                                    <span class="form-help-text">Separate individual skills with commas to display them as individual tags.</span>
                                </div>

                                <div class="form-group" style="margin-bottom: 0;">
                                    <label for="bio" class="form-label">Professional Biography</label>
                                    <textarea id="bio" name="bio" rows="4" class="form-control" placeholder="Share your career milestones, academic journey, research, or mentoring interests with the alumni community..."><?php echo htmlspecialchars($profile['bio'] ?? ''); ?></textarea>
                                </div>
                            </div>

                            <!-- Form Actions -->
                            <div class="form-actions-bar">
                                <button type="submit" class="btn btn-primary btn-liquid">
                                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                                    Save Profile Changes
                                </button>
                                <button type="button" class="btn btn-outline-glass" onclick="switchProfileTab('overview');">
                                    Cancel
                                </button>
                            </div>
                        </form>

                    </div>
                </div>
            <?php endif; ?>

        </div>

        <!-- Right Column: Sidebar Meta & Community -->
        <div class="profile-side-col">
            
            <!-- Academic & Contact Overview Card -->
            <div class="profile-card">
                <div class="profile-card-header">
                    <h4 class="profile-card-title">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
                        Academic & Contact Overview
                    </h4>
                </div>

                <div class="profile-overview-list">
                    <div class="profile-overview-item">
                        <div class="profile-overview-icon">
                            <svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                        </div>
                        <div class="profile-overview-content">
                            <span class="profile-overview-label">Department / Faculty</span>
                            <span class="profile-overview-val"><?php echo htmlspecialchars($profile['department']); ?></span>
                        </div>
                    </div>

                    <div class="profile-overview-item">
                        <div class="profile-overview-icon">
                            <svg viewBox="0 0 24 24"><path d="M22 10v6M2 10l10-5 10 5-10 5z"></path><path d="M6 12v5c3 3 9 3 12 0v-5"></path></svg>
                        </div>
                        <div class="profile-overview-content">
                            <span class="profile-overview-label">Graduation Year</span>
                            <span class="profile-overview-val">Class of <?php echo htmlspecialchars($profile['graduation_year']); ?></span>
                        </div>
                    </div>

                    <?php if (!empty($profile['email'])): ?>
                        <div class="profile-overview-item">
                            <div class="profile-overview-icon">
                                <svg viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                            </div>
                            <div class="profile-overview-content">
                                <span class="profile-overview-label">Verified Email</span>
                                <span class="profile-overview-val">
                                    <a href="mailto:<?php echo htmlspecialchars($profile['email']); ?>"><?php echo htmlspecialchars($profile['email']); ?></a>
                                </span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($profile['phone'])): ?>
                        <div class="profile-overview-item">
                            <div class="profile-overview-icon">
                                <svg viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                            </div>
                            <div class="profile-overview-content">
                                <span class="profile-overview-label">Contact Phone</span>
                                <span class="profile-overview-val">
                                    <a href="tel:<?php echo htmlspecialchars($profile['phone']); ?>"><?php echo htmlspecialchars($profile['phone']); ?></a>
                                </span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($profile['created_at'])): ?>
                        <div class="profile-overview-item">
                            <div class="profile-overview-icon">
                                <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                            </div>
                            <div class="profile-overview-content">
                                <span class="profile-overview-label">Member Since</span>
                                <span class="profile-overview-val"><?php echo date('F Y', strtotime($profile['created_at'])); ?></span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($profile['linkedin_url'])): ?>
                        <div style="margin-top: 0.75rem;">
                            <a href="<?php echo htmlspecialchars($profile['linkedin_url']); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-glass btn-sm btn-block" style="text-align: center; width: 100%; justify-content: center;">
                                <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="margin-right: 0.4rem;"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"></path><rect x="2" y="9" width="4" height="12"></rect><circle cx="4" cy="4" r="2"></circle></svg>
                                View LinkedIn Profile &rarr;
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Mentorship & Alumni Community Card -->
            <div class="profile-card profile-community-card">
                <h4 class="profile-community-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                    Alumni Mentorship & Community
                </h4>
                <p class="profile-community-desc">
                    Meaningful alumni connections drive career growth. Connect with fellow graduates, engage in university events, and mentor upcoming students stepping into industry roles.
                </p>
                <a href="<?php echo $baseUrl; ?>directory.php" class="btn btn-primary btn-liquid btn-sm btn-block" style="width: 100%; text-align: center; justify-content: center;">
                    Browse Alumni Directory
                </a>
            </div>

        </div>

    </div>

</div>

<!-- Modal for Reporting Member Profile -->
<div id="reportModal" class="modal-overlay" style="display: none;" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="reportModalTitle">
    <div class="modal-box">
        <h3 id="reportModalTitle" style="color: var(--text-light); margin-bottom: 0.5rem; font-weight: 800;">Report Member Profile</h3>
        <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1.4rem;">
            Help maintain the integrity and professionalism of the KDU Alumni Network.
        </p>

        <form method="POST" action="actions/report.php">
            <input type="hidden" name="reported_user_id" id="report_user_id" value="">
            
            <div class="form-group">
                <label for="report_reason" class="form-label">Reason for Report <span class="required-asterisk">*</span></label>
                <select name="reason" id="report_reason" class="form-control" required>
                    <option value="">Select a reason...</option>
                    <option value="Fake or Inaccurate Credentials">Fake or Inaccurate Credentials</option>
                    <option value="Spam or Unsolicited Commercial Message">Spam or Unsolicited Commercial Messages</option>
                    <option value="Harassment or Inappropriate Conduct">Harassment or Inappropriate Conduct</option>
                    <option value="Impersonation of University Faculty">Impersonation of University Faculty or Staff</option>
                    <option value="Other Policy Violation">Other Community Policy Violation</option>
                </select>
            </div>

            <div class="form-group">
                <label for="report_desc" class="form-label">Additional Description (Optional)</label>
                <textarea name="description" id="report_desc" rows="3" class="form-control" placeholder="Provide relevant context or details for network administrators..."></textarea>
            </div>

            <div style="display: flex; gap: 0.85rem; justify-content: flex-end; margin-top: 1.8rem;">
                <button type="button" class="btn btn-outline-glass btn-sm" onclick="closeReportModal();">Cancel</button>
                <button type="submit" class="btn btn-primary btn-liquid btn-sm">Submit Report</button>
            </div>
        </form>
    </div>
</div>

<!-- Profile Client Interaction Scripts -->
<script>
function switchProfileTab(tab) {
    var overviewTab = document.getElementById('profileOverviewTab');
    var editTab = document.getElementById('profileEditTab');
    var btnOverview = document.getElementById('btn-tab-overview');
    var btnEdit = document.getElementById('btn-tab-edit');

    if (tab === 'edit') {
        if (overviewTab) overviewTab.style.display = 'none';
        if (editTab) {
            editTab.style.display = 'block';
            editTab.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
        if (btnOverview) {
            btnOverview.classList.remove('active');
            btnOverview.setAttribute('aria-selected', 'false');
        }
        if (btnEdit) {
            btnEdit.classList.add('active');
            btnEdit.setAttribute('aria-selected', 'true');
        }
    } else {
        if (overviewTab) overviewTab.style.display = 'block';
        if (editTab) editTab.style.display = 'none';
        if (btnOverview) {
            btnOverview.classList.add('active');
            btnOverview.setAttribute('aria-selected', 'true');
        }
        if (btnEdit) {
            btnEdit.classList.remove('active');
            btnEdit.setAttribute('aria-selected', 'false');
        }
    }
}

// Support deep-link anchor #editProfileSection or #edit
window.addEventListener('DOMContentLoaded', function() {
    if (window.location.hash === '#editProfileSection' || window.location.hash === '#edit') {
        switchProfileTab('edit');
    }
});

function openReportModal(userId, type, name) {
    var input = document.getElementById('report_user_id');
    if (input) input.value = userId;
    var modal = document.getElementById('reportModal');
    if (modal) {
        modal.style.display = 'flex';
        modal.setAttribute('aria-hidden', 'false');
    }
}

function closeReportModal() {
    var modal = document.getElementById('reportModal');
    if (modal) {
        modal.style.display = 'none';
        modal.setAttribute('aria-hidden', 'true');
    }
}

// Close report modal on ESC key or clicking outside the card
window.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeReportModal();
    }
});

function handleEditTabPhotoSelected(input) {
    if (!input.files || !input.files[0]) return;
    var file = input.files[0];
    
    if (file.size > 2 * 1024 * 1024) {
        alert("The selected photo exceeds the 2MB size limit (" + (file.size / (1024 * 1024)).toFixed(1) + "MB). Please choose a smaller photo.");
        input.value = "";
        return;
    }
    
    var validTypes = ["image/jpeg", "image/png", "image/webp"];
    if (validTypes.indexOf(file.type) === -1) {
        alert("Please select a JPG, PNG, or WebP image file.");
        input.value = "";
        return;
    }
    
    var reader = new FileReader();
    reader.onload = function(e) {
        var preview = document.getElementById("editTabAvatarPreview");
        if (preview) preview.src = e.target.result;
        var heroPreview = document.getElementById("heroAvatarImg");
        if (heroPreview) heroPreview.src = e.target.result;
    };
    reader.readAsDataURL(file);
    
    var nameSpan = document.getElementById("selectedPhotoName");
    if (nameSpan) nameSpan.textContent = file.name;
    var submitBtn = document.getElementById("editTabPhotoSubmitBtn");
    if (submitBtn) submitBtn.style.display = "inline-flex";
}

function submitHeroPhoto(input) {
    if (!input.files || !input.files[0]) return;
    var file = input.files[0];
    if (file.size > 2 * 1024 * 1024) {
        alert("The selected photo exceeds the 2MB limit (" + (file.size / (1024 * 1024)).toFixed(1) + "MB). Please select a photo under 2MB.");
        input.value = "";
        return;
    }
    var validTypes = ["image/jpeg", "image/png", "image/webp"];
    if (validTypes.indexOf(file.type) === -1) {
        alert("Please select a JPG, PNG, or WebP image file.");
        input.value = "";
        return;
    }
    var form = document.getElementById("heroPhotoUploadForm");
    if (form) form.submit();
}

window.addEventListener('click', function(e) {
    var modal = document.getElementById('reportModal');
    if (e.target === modal) {
        closeReportModal();
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
