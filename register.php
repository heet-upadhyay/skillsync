<?php
// register.php - Role selection + dynamic registration form
require_once 'includes/db_connect.php';

if (is_logged_in()) {
    redirect_to_dashboard();
}

$errors = array();
$success = '';

// ---------- Handle POST submission ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = isset($_POST['role']) ? $_POST['role'] : '';
    $name = trim(isset($_POST['name']) ? $_POST['name'] : '');
    $email = trim(isset($_POST['email']) ? $_POST['email'] : '');
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirm = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

    // --- Validation ---
    if (!in_array($role, array('student','industry','academician','institution'))) {
        $errors[] = 'Please select a valid account type.';
    }
    if ($name === '' || strlen($name) < 3) {
        $errors[] = 'Please enter your full name (min 3 characters).';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters long.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    // --- Email uniqueness ---
    if (empty($errors)) {
        $stmt = db_query('SELECT id FROM users WHERE email = ?', 's', array($email));
        mysqli_stmt_store_result($stmt);
        if (mysqli_stmt_num_rows($stmt) > 0) {
            $errors[] = 'This email is already registered. Please login instead.';
        }
        mysqli_stmt_close($stmt);
    }

    // --- Insert if valid ---
    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = db_query(
            'INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)',
            'ssss',
            array($name, $email, $hash, $role)
        );
        $new_user_id = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);

        if ($new_user_id) {
            $inserted = false;
            switch ($role) {
                case 'student':
                    $college = trim($_POST['college_name'] ?? '');
                    $branch  = trim($_POST['course_branch'] ?? '');
                    $year    = (int) ($_POST['year'] ?? 0);
                    $s = db_query(
                        'INSERT INTO student_details (user_id, college_name, course_branch, year) VALUES (?, ?, ?, ?)',
                        'issi',
                        array($new_user_id, $college, $branch, $year)
                    );
                    $inserted = $s !== false;
                    mysqli_stmt_close($s);
                    break;

                case 'industry':
                    $company = trim($_POST['company_name'] ?? '');
                    $itype   = trim($_POST['industry_type'] ?? '');
                    $size    = trim($_POST['company_size'] ?? '');
                    $web     = trim($_POST['website'] ?? '');
                    $s = db_query(
                        'INSERT INTO industry_details (user_id, company_name, industry_type, company_size, website) VALUES (?, ?, ?, ?, ?)',
                        'issss',
                        array($new_user_id, $company, $itype, $size, $web)
                    );
                    $inserted = $s !== false;
                    mysqli_stmt_close($s);
                    break;

                case 'academician':
                    $col   = trim($_POST['college_name'] ?? '');
                    $dept  = trim($_POST['department'] ?? '');
                    $desig = trim($_POST['designation'] ?? '');
                    $s = db_query(
                        'INSERT INTO academician_details (user_id, college_name, department, designation) VALUES (?, ?, ?, ?)',
                        'isss',
                        array($new_user_id, $col, $dept, $desig)
                    );
                    $inserted = $s !== false;
                    mysqli_stmt_close($s);
                    break;

                case 'institution':
                    $iname = trim($_POST['institution_name'] ?? '');
                    $itype = trim($_POST['institution_type'] ?? '');
                    $loc   = trim($_POST['location'] ?? '');
                    $s = db_query(
                        'INSERT INTO institution_details (user_id, institution_name, institution_type, location) VALUES (?, ?, ?, ?)',
                        'isss',
                        array($new_user_id, $iname, $itype, $loc)
                    );
                    $inserted = $s !== false;
                    mysqli_stmt_close($s);
                    break;
            }

            if ($inserted) {
                // Auto-login after successful registration
                $_SESSION['user_id'] = $new_user_id;
                $_SESSION['name'] = $name;
                $_SESSION['email'] = $email;
                $_SESSION['role'] = $role;
                redirect_to_dashboard();
            } else {
                $errors[] = 'Account created but details failed to save. Please contact support.';
            }
        } else {
            $errors[] = 'Registration failed. Please try again.';
        }
    }
}

$pageTitle = 'Register';
require 'includes/auth_header.php';
?>

<div class="container py-5">
  <div class="text-center mb-4">
    <a href="index.php" class="text-decoration-none"><i class="fa-solid fa-arrow-left"></i> Back to home</a>
  </div>

  <?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
      <h6 class="alert-heading">Please fix the following:</h6>
      <ul class="mb-0">
        <?php foreach ($errors as $err): ?><li><?php echo e($err); ?></li><?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <div class="auth-card">
    <div class="card">
      <div class="card-body">

        <!-- Role selection step -->
        <div id="roleStep">
          <h4 class="text-center fw-bold mb-2 text-primary">Create Your Account</h4>
          <p class="text-center text-muted-2 mb-4">Select who you are</p>
          <div class="row g-3" id="roleCards">
            <div class="col-6 col-lg-3">
              <div class="role-card" data-role="student" onclick="selectRole(this)">
                <div class="role-icon"><i class="fa-solid fa-graduation-cap"></i></div>
                <h6>Student</h6>
                <p class="small text-muted-2 mb-0">Seeking internships &amp; skills</p>
              </div>
            </div>
            <div class="col-6 col-lg-3">
              <div class="role-card" data-role="industry" onclick="selectRole(this)">
                <div class="role-icon"><i class="fa-solid fa-industry"></i></div>
                <h6>Industry</h6>
                <p class="small text-muted-2 mb-0">Hiring student talent</p>
              </div>
            </div>
            <div class="col-6 col-lg-3">
              <div class="role-card" data-role="academician" onclick="selectRole(this)">
                <div class="role-icon"><i class="fa-solid fa-chalkboard-user"></i></div>
                <h6>Academician</h6>
                <p class="small text-muted-2 mb-0">Faculty &amp; researchers</p>
              </div>
            </div>
            <div class="col-6 col-lg-3">
              <div class="role-card" data-role="institution" onclick="selectRole(this)">
                <div class="role-icon"><i class="fa-solid fa-building-columns"></i></div>
                <h6>Institution</h6>
                <p class="small text-muted-2 mb-0">Colleges &amp; universities</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Registration form step -->
        <form method="post" action="register.php" id="registerForm" class="d-none" novalidate>
          <input type="hidden" name="role" id="selectedRole">
          <div class="d-flex align-items-center mb-3">
            <button type="button" class="btn btn-sm btn-outline-secondary me-2" onclick="backToRole()"><i class="fa-solid fa-arrow-left"></i></button>
            <h5 class="fw-bold mb-0 text-primary">Sign Up</h5>
            <span id="roleLabel" class="badge bg-navy ms-2"></span>
          </div>

          <!-- Common fields -->
          <div class="mb-3">
            <label class="form-label">Full Name *</label>
            <input type="text" class="form-control" name="name" id="name" value="<?php echo e($_POST['name'] ?? ''); ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Email Address *</label>
            <input type="email" class="form-control" name="email" id="email" value="<?php echo e($_POST['email'] ?? ''); ?>" required>
          </div>
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label">Password *</label>
              <input type="password" class="form-control" name="password" id="password" required>
              <div class="form-text">At least 6 characters</div>
            </div>
            <div class="col-md-6">
              <label class="form-label">Confirm Password *</label>
              <input type="password" class="form-control" name="confirm_password" id="confirm_password" required>
            </div>
          </div>

          <!-- Student fields -->
          <div class="role-fields d-none" id="fields-student">
            <hr>
            <h6 class="text-primary fw-bold"><i class="fa-solid fa-graduation-cap"></i> Student Details</h6>
            <div class="mb-3">
              <label class="form-label">College / University Name</label>
              <input type="text" class="form-control" name="college_name">
            </div>
            <div class="mb-3">
              <label class="form-label">Course / Branch</label>
              <input type="text" class="form-control" name="course_branch" placeholder="e.g. B.Tech Computer Science">
            </div>
            <div class="mb-3">
              <label class="form-label">Year of Study</label>
              <select class="form-select" name="year">
                <option value="1">1st Year</option>
                <option value="2">2nd Year</option>
                <option value="3">3rd Year</option>
                <option value="4">4th Year</option>
                <option value="5">5th Year</option>
              </select>
            </div>
          </div>

          <!-- Industry fields -->
          <div class="role-fields d-none" id="fields-industry">
            <hr>
            <h6 class="text-primary fw-bold"><i class="fa-solid fa-industry"></i> Industry Details</h6>
            <div class="mb-3">
              <label class="form-label">Company Name *</label>
              <input type="text" class="form-control" name="company_name" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Industry Type</label>
              <input type="text" class="form-control" name="industry_type" placeholder="e.g. IT Services, Manufacturing">
            </div>
            <div class="mb-3">
              <label class="form-label">Company Size</label>
              <select class="form-select" name="company_size">
                <option value="1-50">1 - 50 employees</option>
                <option value="51-250">51 - 250 employees</option>
                <option value="251-1000">251 - 1000 employees</option>
                <option value="1000+">1000+ employees</option>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label">Website <span class="text-muted">(optional)</span></label>
              <input type="url" class="form-control" name="website" placeholder="https://">
            </div>
          </div>

          <!-- Academician fields -->
          <div class="role-fields d-none" id="fields-academician">
            <hr>
            <h6 class="text-primary fw-bold"><i class="fa-solid fa-chalkboard-user"></i> Academician Details</h6>
            <div class="mb-3">
              <label class="form-label">College / University</label>
              <input type="text" class="form-control" name="college_name">
            </div>
            <div class="mb-3">
              <label class="form-label">Department</label>
              <input type="text" class="form-control" name="department" placeholder="e.g. Computer Science">
            </div>
            <div class="mb-3">
              <label class="form-label">Designation</label>
              <input type="text" class="form-control" name="designation" placeholder="e.g. Assistant Professor">
            </div>
          </div>

          <!-- Institution fields -->
          <div class="role-fields d-none" id="fields-institution">
            <hr>
            <h6 class="text-primary fw-bold"><i class="fa-solid fa-building-columns"></i> Institution Details</h6>
            <div class="mb-3">
              <label class="form-label">Institution Name</label>
              <input type="text" class="form-control" name="institution_name">
            </div>
            <div class="mb-3">
              <label class="form-label">Institution Type</label>
              <input type="text" class="form-control" name="institution_type" placeholder="e.g. University, Polytechnic">
            </div>
            <div class="mb-3">
              <label class="form-label">Location</label>
              <input type="text" class="form-control" name="location">
            </div>
          </div>

          <button type="submit" class="btn btn-navy w-100 mt-2"><i class="fa-solid fa-user-plus"></i> Create Account</button>
        </form>

      </div>
    </div>
  </div>
</div>

<script src="assets/js/register.js"></script>
<?php require 'includes/auth_footer.php'; ?>
