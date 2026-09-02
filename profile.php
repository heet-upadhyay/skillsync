<?php
// profile.php - Edit personal info, change password, consent toggle
require_once 'includes/db_connect.php';
if (!is_logged_in()) { header('Location: login.php'); exit; }

$uid = (int) $_SESSION['user_id'];
$role = $_SESSION['role'];
$message = '';
$msgType = 'success';
$errors = array();

// ---------- Fetch current details (role-specific) ----------
$details = array('college_name'=>'','course_branch'=>'','year'=>'','consent_share_data'=>0,
                 'company_name'=>'','industry_type'=>'','company_size'=>'','website'=>'',
                 'department'=>'','designation'=>'','institution_name'=>'','institution_type'=>'','location'=>'');
switch ($role) {
    case 'student':
        $st = db_query('SELECT college_name, course_branch, year, consent_share_data FROM student_details WHERE user_id = ?', 'i', array($uid));
        $res = mysqli_stmt_get_result($st);
        $dr = mysqli_fetch_assoc($res);
        mysqli_free_result($res);
        if ($dr) $details = array_merge($details, $dr);
        mysqli_stmt_close($st);
        break;
    case 'industry':
        $st = db_query('SELECT company_name, industry_type, company_size, website FROM industry_details WHERE user_id = ?', 'i', array($uid));
        $res = mysqli_stmt_get_result($st);
        $dr = mysqli_fetch_assoc($res);
        mysqli_free_result($res);
        if ($dr) $details = array_merge($details, $dr);
        mysqli_stmt_close($st);
        break;
    case 'academician':
        $st = db_query('SELECT college_name, department, designation FROM academician_details WHERE user_id = ?', 'i', array($uid));
        $res = mysqli_stmt_get_result($st);
        $dr = mysqli_fetch_assoc($res);
        mysqli_free_result($res);
        if ($dr) $details = array_merge($details, $dr);
        mysqli_stmt_close($st);
        break;
    case 'institution':
        $st = db_query('SELECT institution_name, institution_type, location FROM institution_details WHERE user_id = ?', 'i', array($uid));
        $res = mysqli_stmt_get_result($st);
        $dr = mysqli_fetch_assoc($res);
        mysqli_free_result($res);
        if ($dr) $details = array_merge($details, $dr);
        mysqli_stmt_close($st);
        break;
}

// ---------- Update profile ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $name = trim($_POST['name'] ?? '');

        if ($name === '') {
            $errors[] = 'Name cannot be empty.';
        }
        if (empty($errors)) {
            $st = db_query('UPDATE users SET name = ? WHERE id = ?', 'si', array($name, $uid));
            mysqli_stmt_close($st);
            $_SESSION['name'] = $name;

            // Update role-specific details
            switch ($role) {
                case 'student':
                    $college = trim($_POST['college_name'] ?? '');
                    $branch = trim($_POST['course_branch'] ?? '');
                    $year = (int) ($_POST['year'] ?? 0);
                    $consent = isset($_POST['consent_share_data']) ? 1 : 0;
                    $st2 = db_query(
                        'UPDATE student_details SET college_name=?, course_branch=?, year=?, consent_share_data=? WHERE user_id=?',
                        'ssiii', array($college, $branch, $year, $consent, $uid));
                    mysqli_stmt_close($st2);
                    break;
                case 'industry':
                    $st2 = db_query(
                        'UPDATE industry_details SET company_name=?, industry_type=?, company_size=?, website=? WHERE user_id=?',
                        'ssssi', array(trim($_POST['company_name'] ?? ''), trim($_POST['industry_type'] ?? ''),
                                       trim($_POST['company_size'] ?? ''), trim($_POST['website'] ?? ''), $uid));
                    mysqli_stmt_close($st2);
                    break;
                case 'academician':
                    $st2 = db_query(
                        'UPDATE academician_details SET college_name=?, department=?, designation=? WHERE user_id=?',
                        'sssi', array(trim($_POST['college_name'] ?? ''), trim($_POST['department'] ?? ''),
                                      trim($_POST['designation'] ?? ''), $uid));
                    mysqli_stmt_close($st2);
                    break;
                case 'institution':
                    $st2 = db_query(
                        'UPDATE institution_details SET institution_name=?, institution_type=?, location=? WHERE user_id=?',
                        'sssi', array(trim($_POST['institution_name'] ?? ''), trim($_POST['institution_type'] ?? ''),
                                      trim($_POST['location'] ?? ''), $uid));
                    mysqli_stmt_close($st2);
                    break;
            }
            $message = 'Profile updated successfully.';
        }
    }

    if ($action === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        // verify current password
        $st = db_query('SELECT password FROM users WHERE id = ?', 'i', array($uid));
        mysqli_stmt_store_result($st);
        mysqli_stmt_bind_result($st, $hash);
        mysqli_stmt_fetch($st);
        mysqli_stmt_close($st);

        if (!password_verify($current, $hash)) {
            $errors[] = 'Current password is incorrect.';
        } elseif (strlen($new) < 6) {
            $errors[] = 'New password must be at least 6 characters.';
        } elseif ($new !== $confirm) {
            $errors[] = 'New passwords do not match.';
        } else {
            $nHash = password_hash($new, PASSWORD_DEFAULT);
            $st2 = db_query('UPDATE users SET password = ? WHERE id = ?', 'si', array($nHash, $uid));
            mysqli_stmt_close($st2);
            $message = 'Password changed successfully.';
        }
    }
}

$pageTitle = 'Profile & Settings';
$activeNav = 'profile';

// Use student sidebar for student role, generic sidebar for others
if ($role === 'student') {
    require 'includes/page_header.php';
} else {
    require 'includes/page_header_role.php';
}
?>

<?php if ($message): ?><div class="alert alert-<?php echo $msgType; ?>"><?php echo e($message); ?></div><?php endif; ?>
<?php if (!empty($errors)): ?>
  <div class="alert alert-danger">
    <?php foreach ($errors as $err): ?><div><?php echo e($err); ?></div><?php endforeach; ?>
  </div>
<?php endif; ?>

<div class="row g-4">

  <!-- Personal info -->
  <div class="col-lg-7">
    <div class="card">
      <div class="card-body">
        <h6 class="card-title mb-3"><i class="fa-solid fa-user me-2 text-primary"></i>Personal Information</h6>
        <form method="post" action="profile.php">
          <input type="hidden" name="action" value="update_profile">
          <div class="mb-3">
            <label class="form-label">Full Name</label>
            <input type="text" class="form-control" name="name" value="<?php echo e($_SESSION['name']); ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Email (cannot be changed)</label>
            <input type="email" class="form-control" value="<?php echo e($_SESSION['email']); ?>" disabled>
          </div>
          <div class="row g-3 mb-3">
            <?php if ($role === 'student'): ?>
            <div class="col-md-6">
              <label class="form-label">College / University</label>
              <input type="text" class="form-control" name="college_name" value="<?php echo e($details['college_name']); ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Course / Branch</label>
              <input type="text" class="form-control" name="course_branch" value="<?php echo e($details['course_branch']); ?>">
            </div>
            <?php elseif ($role === 'industry'): ?>
            <div class="col-md-6">
              <label class="form-label">Company Name</label>
              <input type="text" class="form-control" name="company_name" value="<?php echo e($details['company_name']); ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Industry Type</label>
              <input type="text" class="form-control" name="industry_type" value="<?php echo e($details['industry_type']); ?>">
            </div>
            <?php elseif ($role === 'academician'): ?>
            <div class="col-md-6">
              <label class="form-label">College / University</label>
              <input type="text" class="form-control" name="college_name" value="<?php echo e($details['college_name']); ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Department</label>
              <input type="text" class="form-control" name="department" value="<?php echo e($details['department']); ?>">
            </div>
            <?php elseif ($role === 'institution'): ?>
            <div class="col-md-6">
              <label class="form-label">Institution Name</label>
              <input type="text" class="form-control" name="institution_name" value="<?php echo e($details['institution_name']); ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Institution Type</label>
              <input type="text" class="form-control" name="institution_type" value="<?php echo e($details['institution_type']); ?>">
            </div>
            <?php endif; ?>
          </div>

          <?php if ($role === 'student'): ?>
          <div class="mb-3">
            <label class="form-label">Year of Study</label>
            <select class="form-select" name="year">
              <option value="1" <?php echo $details['year']==1?'selected':''; ?>>1st Year</option>
              <option value="2" <?php echo $details['year']==2?'selected':''; ?>>2nd Year</option>
              <option value="3" <?php echo $details['year']==3?'selected':''; ?>>3rd Year</option>
              <option value="4" <?php echo $details['year']==4?'selected':''; ?>>4th Year</option>
              <option value="5" <?php echo $details['year']==5?'selected':''; ?>>5th Year</option>
            </select>
          </div>

          <div class="form-check form-switch mb-3">
            <input class="form-check-input" type="checkbox" name="consent_share_data" id="consentShare" value="1" <?php echo $details['consent_share_data'] ? 'checked' : ''; ?>>
            <label class="form-check-label" for="consentShare">
              <i class="fa-solid fa-share-nodes me-1 text-primary"></i> Share my data with institutions
            </label>
            <div class="form-text">Allow institutions to view your skill profile and portfolio for campus-industry collaborations.</div>
          </div>
          <?php elseif ($role === 'industry'): ?>
          <div class="mb-3">
            <label class="form-label">Company Size</label>
            <select class="form-select" name="company_size">
              <option value="1-50" <?php echo $details['company_size']=='1-50'?'selected':''; ?>>1 - 50 employees</option>
              <option value="51-250" <?php echo $details['company_size']=='51-250'?'selected':''; ?>>51 - 250 employees</option>
              <option value="251-1000" <?php echo $details['company_size']=='251-1000'?'selected':''; ?>>251 - 1000 employees</option>
              <option value="1000+" <?php echo $details['company_size']=='1000+'?'selected':''; ?>>1000+ employees</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Website <span class="text-muted">(optional)</span></label>
            <input type="url" class="form-control" name="website" value="<?php echo e($details['website']); ?>">
          </div>
          <?php elseif ($role === 'academician'): ?>
          <div class="mb-3">
            <label class="form-label">Designation</label>
            <input type="text" class="form-control" name="designation" value="<?php echo e($details['designation']); ?>">
          </div>
          <?php elseif ($role === 'institution'): ?>
          <div class="mb-3">
            <label class="form-label">Location</label>
            <input type="text" class="form-control" name="location" value="<?php echo e($details['location']); ?>">
          </div>
          <?php endif; ?>

          <button type="submit" class="btn btn-navy"><i class="fa-solid fa-floppy-disk"></i> Save Changes</button>
        </form>
      </div>
    </div>
  </div>

  <!-- Change password + account -->
  <div class="col-lg-5">
    <div class="card mb-4">
      <div class="card-body">
        <h6 class="card-title mb-3"><i class="fa-solid fa-lock me-2 text-primary"></i>Change Password</h6>
        <form method="post" action="profile.php">
          <input type="hidden" name="action" value="change_password">
          <div class="mb-3">
            <label class="form-label">Current Password</label>
            <input type="password" class="form-control" name="current_password" required>
          </div>
          <div class="mb-3">
            <label class="form-label">New Password</label>
            <input type="password" class="form-control" name="new_password" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Confirm New Password</label>
            <input type="password" class="form-control" name="confirm_password" required>
          </div>
          <button type="submit" class="btn btn-navy"><i class="fa-solid fa-key"></i> Update Password</button>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="card-body">
        <h6 class="card-title mb-3 text-danger"><i class="fa-solid fa-right-from-bracket me-2"></i>Session</h6>
        <p class="small text-muted-2 mb-3">Signed in as <strong><?php echo e($_SESSION['email']); ?></strong> (<?php echo e(ucfirst($role)); ?>)</p>
        <a href="logout.php" class="btn btn-outline-danger w-100"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
      </div>
    </div>
  </div>
</div>

<?php
if ($role === 'student') {
    require 'includes/page_footer.php';
} else {
    require 'includes/page_footer_role.php';
}
?>
