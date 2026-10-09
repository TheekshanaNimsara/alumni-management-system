<?php
// ============================================================
// University Alumni Network - Alumni Profile System
// View & Edit Profile, Photo Upload, Skills, and Networking
// ============================================================
$pageTitle = 'Alumni Profile';
$currentPage = 'profile';
require_once __DIR__ . '/includes/header.php';
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

// Handle Profile Updates (Owner only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isOwner) {
    $action = $_POST['action'] ?? '';

        // 1. Photo Upload Action
        if ($action === 'upload_photo') {
            if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
                $fileTmp = $_FILES['profile_photo']['tmp_name'];
                $fileSize = $_FILES['profile_photo']['size'];
                $fileName = $_FILES['profile_photo']['name'];

                // Max 2MB
                if ($fileSize > 2 * 1024 * 1024) {
                    $errorMsg = "Image size exceeds 2MB. Please upload a smaller image.";
                } else {
                    $imgInfo = @getimagesize($fileTmp);
                    if ($imgInfo === false) {
                        $errorMsg = "Invalid image file format.";
                    } else {
                        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
                        $mime = $imgInfo['mime'];

                        if (!in_array($mime, $allowedMimes)) {
                            $errorMsg = "Allowed formats are JPG, JPEG, PNG, and WEBP.";
                        } else {
                            $extMap = [
                                'image/jpeg' => 'jpg',
                                'image/png' => 'png',
                                'image/webp' => 'webp'
                            ];
                            $ext = $extMap[$mime] ?? 'jpg';
                            $newFileName = 'avatar_' . $currentUserId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                            $targetDir = __DIR__ . '/uploads/profiles/';
                            if (!is_dir($targetDir)) {
                                @mkdir($targetDir, 0755, true);
                            }
                            $targetPath = $targetDir . $newFileName;

                            if (move_uploaded_file($fileTmp, $targetPath)) {
                                if ($db_connected && $pdo) {
                                    $stmt = $pdo->prepare("UPDATE alumni_profiles SET profile_picture = ? WHERE user_id = ?");
                                    $stmt->execute([$newFileName, $currentUserId]);
                                }
                                $successMsg = "Profile photo updated successfully!";
                            } else {
                                $errorMsg = "Failed to save uploaded image. Check directory permissions.";
                            }
                        }
                    }
                }
            } else {
                $errorMsg = "Please select a valid image file.";
            }
        }

        // 2. Profile Details Update Action
        elseif ($action === 'update_profile') {
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
                $errorMsg = "Please complete all required fields (Name, Email, Degree, Department, Year).";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errorMsg = "Please provide a valid email address.";
            } elseif ($gradYear < 1960 || $gradYear > 2030) {
                $errorMsg = "Please enter a valid graduation year.";
            } else {
                if ($db_connected && $pdo) {
                    try {
                        // Check if email taken by someone else
                        $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                        $checkStmt->execute([$email, $currentUserId]);
                        if ($checkStmt->fetch()) {
                            $errorMsg = "This email is already in use by another account.";
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
                        }
                    } catch (Exception $e) {
                        if ($pdo->inTransaction()) {
                            $pdo->rollBack();
                        }
                        $errorMsg = "Database update error: " . $e->getMessage();
                    }
                } else {
                    $successMsg = "Profile updated in demo mode.";
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
            LEFT JOIN alumni_profiles p ON u.id = p.user_id
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
        echo "<div class='container' style='padding: 5rem 0;'><div class='alert alert-danger'>Alumni profile not found. <a href='directory.php'>Return to Alumni Directory</a></div></div>";
        require_once __DIR__ . '/includes/footer.php';
        exit;
    }
}

$avatarUrl = get_user_avatar_url($profile['profile_picture'] ?? 'default-avatar.svg');
$skillsList = !empty($profile['skills']) ? array_filter(array_map('trim', explode(',', $profile['skills']))) : [];
?>

<!-- Profile Cover & Header -->
<div style="background: linear-gradient(135deg, rgba(18, 3, 5, 0.98), rgba(36, 7, 10, 0.92), rgba(74, 17, 22, 0.85)); color: var(--text-light); padding: 4rem 0 3rem; border-bottom: 2px solid var(--accent-color);">
    <div class="container">
        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 2rem;">
            
            <div style="display: flex; align-items: center; gap: 2rem; flex-wrap: wrap;">
                <div style="position: relative;">
                    <img src="<?php echo $avatarUrl; ?>" alt="<?php echo htmlspecialchars($profile['first_name']); ?>" 
                         style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 4px solid var(--accent-color); background: var(--surface-color); box-shadow: 0 8px 25px rgba(0,0,0,0.5);">
                    <?php if ($isOwner): ?>
                        <label for="profilePhotoInput" style="position: absolute; bottom: 0; right: 0; background: var(--accent-color); color: var(--primary-color); width: 34px; height: 34px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 1rem; font-weight: bold; box-shadow: 0 2px 8px rgba(0,0,0,0.3);" title="Change Profile Picture">
                            &#128247;
                        </label>
                        <form id="photoUploadForm" method="POST" action="profile.php" enctype="multipart/form-data" style="display: none;">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="action" value="upload_photo">
                            <input type="file" id="profilePhotoInput" name="profile_photo" accept="image/jpeg,image/png,image/webp" onchange="document.getElementById('photoUploadForm').submit();">
                        </form>
                    <?php endif; ?>
                </div>

                <div>
                    <div style="display: flex; align-items: center; gap: 0.8rem; margin-bottom: 0.4rem;">
                        <h1 style="font-size: 2rem; font-weight: 800; color: var(--text-light); margin: 0;">
                            <?php echo htmlspecialchars($profile['first_name'] . ' ' . $profile['last_name']); ?>
                        </h1>
                        <span class="badge" style="background: rgba(212, 175, 55, 0.2); color: var(--accent-light); border: 1px solid var(--accent-color);">
                            Class of <?php echo htmlspecialchars($profile['graduation_year']); ?>
                        </span>
                    </div>
                    
                    <p style="color: var(--accent-light); font-size: 1.1rem; margin-bottom: 0.4rem; font-weight: 600;">
                        <?php echo htmlspecialchars($profile['current_job_title'] ?? 'Alumnus'); ?>
                        <?php if (!empty($profile['current_company'])): ?>
                            &bull; <span style="color: var(--text-light);"><?php echo htmlspecialchars($profile['current_company']); ?></span>
                        <?php endif; ?>
                    </p>

                    <p style="color: rgba(248, 244, 234, 0.7); font-size: 0.92rem; margin: 0;">
                        <span>&#127891; <?php echo htmlspecialchars($profile['degree_programme']); ?></span>
                        <?php if (!empty($profile['location'])): ?>
                            <span style="margin-left: 1rem;">&#128205; <?php echo htmlspecialchars($profile['location']); ?></span>
                        <?php endif; ?>
                    </p>
                </div>
            </div>

            <div style="display: flex; gap: 0.8rem; flex-wrap: wrap;">
                <?php if ($isOwner): ?>
                    <button type="button" class="btn btn-gold btn-sm" onclick="document.getElementById('editProfileSection').scrollIntoView({behavior: 'smooth'});">
                        &#9998; Edit Profile
                    </button>
                <?php else: ?>
                    <?php if ($isLoggedIn): ?>
                        <a href="messages.php?user_id=<?php echo $profile['id']; ?>" class="btn btn-gold btn-sm gold-glow">
                            &#128172; Send Message
                        </a>
                        <button type="button" class="btn btn-outline-light btn-sm" onclick="openReportModal(<?php echo $profile['id']; ?>, 'user', '<?php echo htmlspecialchars(addslashes($profile['first_name'] . ' ' . $profile['last_name'])); ?>');">
                            &#9873; Report
                        </button>
                    <?php else: ?>
                        <a href="auth/login.php" class="btn btn-gold btn-sm">
                            Log in to Message
                        </a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

<!-- Main Content Area -->
<div class="container" style="padding-top: 3rem; padding-bottom: 5rem;">

    <?php if (!empty($successMsg)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($successMsg); ?></div>
    <?php endif; ?>

    <?php if (!empty($errorMsg)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($errorMsg); ?></div>
    <?php endif; ?>

    <div class="grid grid-3" style="align-items: start;">

        <!-- Left Column: Biography & Background -->
        <div style="grid-column: span 2;">
            
            <!-- Biography Card -->
            <div class="card" style="margin-bottom: 2rem;">
                <h3 style="font-size: 1.3rem; font-weight: 700; color: var(--primary-color); margin-bottom: 1rem; border-bottom: 2px solid rgba(212, 175, 55, 0.2); padding-bottom: 0.6rem;">
                    About <?php echo htmlspecialchars($profile['first_name']); ?>
                </h3>
                <p style="color: var(--text-color); font-size: 1rem; line-height: 1.7;">
                    <?php echo nl2br(htmlspecialchars(!empty($profile['bio']) ? $profile['bio'] : 'No biography added yet. Update your profile to share your journey and achievements with fellow graduates.')); ?>
                </p>
            </div>

            <!-- Professional Skills -->
            <div class="card" style="margin-bottom: 2rem;">
                <h3 style="font-size: 1.3rem; font-weight: 700; color: var(--primary-color); margin-bottom: 1rem; border-bottom: 2px solid rgba(212, 175, 55, 0.2); padding-bottom: 0.6rem;">
                    Key Skills & Expertise
                </h3>
                <?php if (!empty($skillsList)): ?>
                    <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                        <?php foreach ($skillsList as $skill): ?>
                            <span class="badge" style="background: rgba(36, 7, 10, 0.08); color: var(--primary-color); border: 1px solid var(--border-color); font-size: 0.88rem; padding: 0.4rem 0.8rem;">
                                <?php echo htmlspecialchars($skill); ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p style="color: var(--text-muted); font-size: 0.95rem;">No skills listed yet.</p>
                <?php endif; ?>
            </div>

            <!-- Edit Profile Form (Rendered for Owner) -->
            <?php if ($isOwner): ?>
                <div id="editProfileSection" class="card" style="border-top: 4px solid var(--accent-color);">
                    <h3 style="font-size: 1.4rem; font-weight: 800; color: var(--primary-color); margin-bottom: 0.5rem;">
                        &#9998; Edit Profile Details
                    </h3>
                    <p style="color: var(--text-muted); font-size: 0.92rem; margin-bottom: 1.8rem;">
                        Update your professional details, degree information, and contact credentials.
                    </p>

                    <form method="POST" action="profile.php">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="action" value="update_profile">

                        <!-- Name Row -->
                        <div class="grid grid-2" style="gap: 1.2rem; margin-bottom: 1rem;">
                            <div class="form-group" style="margin-bottom: 0;">
                                <label for="first_name">First Name *</label>
                                <input type="text" id="first_name" name="first_name" class="form-control" 
                                       value="<?php echo htmlspecialchars($profile['first_name']); ?>" required>
                            </div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <label for="last_name">Last Name *</label>
                                <input type="text" id="last_name" name="last_name" class="form-control" 
                                       value="<?php echo htmlspecialchars($profile['last_name']); ?>" required>
                            </div>
                        </div>

                        <!-- Email & Phone Row -->
                        <div class="grid grid-2" style="gap: 1.2rem; margin-bottom: 1rem;">
                            <div class="form-group" style="margin-bottom: 0;">
                                <label for="email">Email Address *</label>
                                <input type="email" id="email" name="email" class="form-control" 
                                       value="<?php echo htmlspecialchars($profile['email']); ?>" required>
                            </div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <label for="phone">Contact Phone</label>
                                <input type="text" id="phone" name="phone" class="form-control" 
                                       value="<?php echo htmlspecialchars($profile['phone'] ?? ''); ?>" placeholder="+94 77 123 4567">
                            </div>
                        </div>

                        <!-- Academic Details -->
                        <div class="grid grid-3" style="gap: 1.2rem; margin-bottom: 1rem;">
                            <div class="form-group" style="margin-bottom: 0;">
                                <label for="graduation_year">Graduation Year *</label>
                                <input type="number" id="graduation_year" name="graduation_year" class="form-control" 
                                       value="<?php echo htmlspecialchars($profile['graduation_year']); ?>" min="1960" max="2030" required>
                            </div>
                            <div class="form-group" style="margin-bottom: 0; grid-column: span 2;">
                                <label for="degree_programme">Degree Programme *</label>
                                <input type="text" id="degree_programme" name="degree_programme" class="form-control" 
                                       value="<?php echo htmlspecialchars($profile['degree_programme']); ?>" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="department">Department / Faculty *</label>
                            <input type="text" id="department" name="department" class="form-control" 
                                   value="<?php echo htmlspecialchars($profile['department']); ?>" required>
                        </div>

                        <!-- Current Career -->
                        <div class="grid grid-3" style="gap: 1.2rem; margin-bottom: 1rem;">
                            <div class="form-group" style="margin-bottom: 0;">
                                <label for="current_job_title">Current Job Title</label>
                                <input type="text" id="current_job_title" name="current_job_title" class="form-control" 
                                       value="<?php echo htmlspecialchars($profile['current_job_title'] ?? ''); ?>" placeholder="e.g. Senior Software Engineer">
                            </div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <label for="current_company">Company / Organization</label>
                                <input type="text" id="current_company" name="current_company" class="form-control" 
                                       value="<?php echo htmlspecialchars($profile['current_company'] ?? ''); ?>" placeholder="e.g. Google, IFS, Virtusa">
                            </div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <label for="location">Location / City</label>
                                <input type="text" id="location" name="location" class="form-control" 
                                       value="<?php echo htmlspecialchars($profile['location'] ?? ''); ?>" placeholder="e.g. Colombo, Singapore">
                            </div>
                        </div>

                        <!-- LinkedIn & Skills -->
                        <div class="form-group">
                            <label for="linkedin_url">LinkedIn Profile URL</label>
                            <input type="url" id="linkedin_url" name="linkedin_url" class="form-control" 
                                   value="<?php echo htmlspecialchars($profile['linkedin_url'] ?? ''); ?>" placeholder="https://linkedin.com/in/username">
                        </div>

                        <div class="form-group">
                            <label for="skills">Skills & Expertise (Comma separated)</label>
                            <input type="text" id="skills" name="skills" class="form-control" 
                                   value="<?php echo htmlspecialchars($profile['skills'] ?? ''); ?>" placeholder="e.g. Python, Cloud Architecture, DevOps, Project Management">
                        </div>

                        <!-- Bio -->
                        <div class="form-group">
                            <label for="bio">Professional Bio</label>
                            <textarea id="bio" name="bio" rows="4" class="form-control" placeholder="Share your career highlights, academic achievements, or mentoring interests..."><?php echo htmlspecialchars($profile['bio'] ?? ''); ?></textarea>
                        </div>

                        <div style="display: flex; gap: 1rem; align-items: center; margin-top: 1.5rem;">
                            <button type="submit" class="btn btn-gold gold-glow">
                                Save Profile Changes
                            </button>
                            <a href="profile.php" class="btn btn-outline-light" style="color: var(--text-color); border-color: var(--border-color);">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

        </div>

        <!-- Right Column: Quick Contact & Network Meta -->
        <div>
            
            <!-- Quick Meta Card -->
            <div class="card" style="margin-bottom: 2rem;">
                <h4 style="font-size: 1.15rem; font-weight: 700; color: var(--primary-color); margin-bottom: 1rem; border-bottom: 2px solid rgba(212, 175, 55, 0.2); padding-bottom: 0.5rem;">
                    Academic & Contact Details
                </h4>

                <div style="display: flex; flex-direction: column; gap: 0.9rem; font-size: 0.93rem;">
                    <div>
                        <span style="color: var(--text-muted); display: block; font-size: 0.8rem; text-transform: uppercase;">Department</span>
                        <strong><?php echo htmlspecialchars($profile['department']); ?></strong>
                    </div>

                    <div>
                        <span style="color: var(--text-muted); display: block; font-size: 0.8rem; text-transform: uppercase;">Graduation Year</span>
                        <strong>Class of <?php echo htmlspecialchars($profile['graduation_year']); ?></strong>
                    </div>

                    <?php if (!empty($profile['email'])): ?>
                        <div>
                            <span style="color: var(--text-muted); display: block; font-size: 0.8rem; text-transform: uppercase;">Verified Email</span>
                            <span><?php echo htmlspecialchars($profile['email']); ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($profile['phone'])): ?>
                        <div>
                            <span style="color: var(--text-muted); display: block; font-size: 0.8rem; text-transform: uppercase;">Phone Number</span>
                            <span><?php echo htmlspecialchars($profile['phone']); ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($profile['linkedin_url'])): ?>
                        <div style="margin-top: 0.5rem;">
                            <a href="<?php echo htmlspecialchars($profile['linkedin_url']); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-gold btn-sm btn-block" style="text-align: center;">
                                View LinkedIn Profile &rarr;
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Networking Advice Card -->
            <div class="card card-dark">
                <h4 style="color: var(--accent-color); font-size: 1.1rem; margin-bottom: 0.8rem;">
                    KDU Mentorship & Camaraderie
                </h4>
                <p style="font-size: 0.88rem; color: rgba(248, 244, 234, 0.8); line-height: 1.6;">
                    Alumni connections foster career breakthroughs. Connect with batchmates, participate in university summits, and guide junior scholars into international industry roles.
                </p>
                <a href="directory.php" class="btn btn-gold btn-sm btn-block" style="margin-top: 1rem; text-align: center;">
                    Browse Directory
                </a>
            </div>

        </div>

    </div>

</div>

<!-- Modal for reporting profile -->
<div id="reportModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: #ffffff; width: 90%; max-width: 480px; border-radius: var(--border-radius); padding: 2rem; border-top: 4px solid var(--accent-color); box-shadow: 0 15px 40px rgba(0,0,0,0.5);">
        <h3 style="color: var(--primary-color); margin-bottom: 0.5rem; font-weight: 800;">Report Member Profile</h3>
        <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1.2rem;">
            Help maintain the integrity and professionalism of the KDU Alumni Network.
        </p>

        <form method="POST" action="actions/report.php">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="reported_user_id" id="report_user_id" value="">
            
            <div class="form-group">
                <label for="report_reason">Reason for Report *</label>
                <select name="reason" id="report_reason" class="form-control" required>
                    <option value="">Select a reason...</option>
                    <option value="Fake or Inaccurate Credentials">Fake or Inaccurate Credentials</option>
                    <option value="Spam or Unsolicited Commercial Message">Spam or Unsolicited Commercial Message</option>
                    <option value="Harassment or Inappropriate Conduct">Harassment or Inappropriate Conduct</option>
                    <option value="Impersonation of University Faculty">Impersonation of University Faculty</option>
                    <option value="Other Policy Violation">Other Policy Violation</option>
                </select>
            </div>

            <div class="form-group">
                <label for="report_desc">Additional Description (Optional)</label>
                <textarea name="description" id="report_desc" rows="3" class="form-control" placeholder="Provide relevant details for administrators..."></textarea>
            </div>

            <div style="display: flex; gap: 0.8rem; justify-content: flex-end; margin-top: 1.5rem;">
                <button type="button" class="btn btn-outline-light" style="color: var(--text-color);" onclick="closeReportModal();">Cancel</button>
                <button type="submit" class="btn btn-gold">Submit Report</button>
            </div>
        </form>
    </div>
</div>

<script>
function openReportModal(userId, type, name) {
    document.getElementById('report_user_id').value = userId;
    document.getElementById('reportModal').style.display = 'flex';
}
function closeReportModal() {
    document.getElementById('reportModal').style.display = 'none';
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
