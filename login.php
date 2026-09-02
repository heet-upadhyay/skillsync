<?php
// login.php - Two-step login: select role, then email + password
require_once 'includes/db_connect.php';

if (is_logged_in()) {
    redirect_to_dashboard();
}

$errors = array();
$selectedRole = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedRole = $_POST['role'] ?? '';
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // validate role
    if (!in_array($selectedRole, array('student','academician','industry','institution'))) {
        $errors[] = 'Please select your account type.';
    }
    if ($email === '' || $password === '') {
        $errors[] = 'Please enter both email and password.';
    }

    if (empty($errors)) {
        $stmt = db_query('SELECT id, name, email, password, role FROM users WHERE email = ?', 's', array($email));
        mysqli_stmt_store_result($stmt);
        mysqli_stmt_bind_result($stmt, $uid, $uname, $uemail, $uhash, $urole);
        $found = mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);

        if ($found && password_verify($password, $uhash) && $urole === $selectedRole) {
            // success -> set session
            session_regenerate_id(true);
            $_SESSION['user_id'] = $uid;
            $_SESSION['name'] = $uname;
            $_SESSION['email'] = $uemail;
            $_SESSION['role'] = $urole;
            redirect_to_dashboard();
        } elseif ($found && password_verify($password, $uhash) && $urole !== $selectedRole) {
            $errors[] = 'This account is not registered as ' . ucfirst($selectedRole) . '. Please select the correct account type.';
        } else {
            $errors[] = 'Invalid email or password.';
        }
    }
}

$pageTitle = 'Login';
require 'includes/auth_header.php';
?>

<div class="container py-4">
  <div class="text-center mb-4">
    <a href="index.php" class="text-decoration-none"><i class="fa-solid fa-arrow-left"></i> Back to home</a>
  </div>

  <?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
      <?php foreach ($errors as $err): ?><div><?php echo e($err); ?></div><?php endforeach; ?>
    </div>
  <?php endif; ?>

  <div class="auth-card">
    <div class="card">
      <div class="card-body">

        <!-- Step 1: Role selection -->
        <div id="roleStep">
          <div class="text-center mb-4">
            <i class="fa-solid fa-user-lock text-primary" style="font-size: 2.5rem;"></i>
            <h4 class="fw-bold mt-2 mb-0">Welcome Back</h4>
            <p class="text-muted-2 mb-0">Step 1 — Who are you?</p>
          </div>
          <div class="row g-3">
            <div class="col-6 col-md-3">
              <div class="role-card" data-role="student" onclick="selectRole('student', this)">
                <div class="role-icon"><i class="fa-solid fa-graduation-cap"></i></div>
                <h6>Student</h6>
              </div>
            </div>
            <div class="col-6 col-md-3">
              <div class="role-card" data-role="academician" onclick="selectRole('academician', this)">
                <div class="role-icon"><i class="fa-solid fa-chalkboard-user"></i></div>
                <h6>Teacher</h6>
              </div>
            </div>
            <div class="col-6 col-md-3">
              <div class="role-card" data-role="industry" onclick="selectRole('industry', this)">
                <div class="role-icon"><i class="fa-solid fa-industry"></i></div>
                <h6>Industry</h6>
              </div>
            </div>
            <div class="col-6 col-md-3">
              <div class="role-card" data-role="institution" onclick="selectRole('institution', this)">
                <div class="role-icon"><i class="fa-solid fa-building-columns"></i></div>
                <h6>College</h6>
              </div>
            </div>
          </div>
          <p class="text-center mt-3 mb-0 text-muted-2">
            Don't have an account? <a href="register.php" class="fw-semibold">Register here</a>
          </p>
        </div>

        <!-- Step 2: Credentials -->
        <form method="post" action="login.php" id="loginForm" onsubmit="return validateLogin(event)">
          <input type="hidden" name="role" id="selectedRole">
          <div class="d-flex align-items-center mb-3">
            <button type="button" class="btn btn-sm btn-outline-secondary me-2" onclick="backToRole()"><i class="fa-solid fa-arrow-left"></i></button>
            <h5 class="fw-bold mb-0 text-primary">Login</h5>
            <span class="badge bg-navy ms-2" id="roleLabel"></span>
          </div>
          <div class="mb-3">
            <label class="form-label">Email Address</label>
            <input type="email" class="form-control" name="email" id="loginEmail" value="<?php echo e($_POST['email'] ?? ''); ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" class="form-control" name="password" id="loginPassword" required>
          </div>
          <button type="submit" class="btn btn-navy w-100"><i class="fa-solid fa-right-to-bracket"></i> Login</button>
        </form>

      </div>
    </div>

    <!-- Demo accounts box -->
    <div class="card mt-3">
      <div class="card-body">
        <h6 class="card-title mb-3"><i class="fa-solid fa-circle-info text-primary"></i> Demo Accounts (password: <code>...123</code>)</h6>
        <div class="small">
          <div class="d-flex justify-content-between border-bottom py-1"><span>👨‍🎓 Student</span><code>student@demo.com</code></div>
          <div class="d-flex justify-content-between border-bottom py-1"><span>👨‍🏫 Teacher</span><code>teacher@demo.com</code></div>
          <div class="d-flex justify-content-between border-bottom py-1"><span>🏭 Industry</span><code>industry@demo.com</code></div>
          <div class="d-flex justify-content-between py-1"><span>🏛️ College</span><code>college@demo.com</code></div>
        </div>
        <p class="small text-muted-2 mt-2 mb-0">All demo passwords end with <code>123</code>. Select the matching role on Step 1.</p>
      </div>
    </div>
  </div>
</div>

<script>
  const roleNames = { student:'Student', academician:'Teacher', industry:'Industry', institution:'College' };
  let chosenRole = '';

  function selectRole(role, el) {
    document.querySelectorAll('.role-card').forEach(c => c.classList.remove('selected'));
    el.classList.add('selected');
    chosenRole = role;
    proceed();
  }

  function proceed() {
    if (!chosenRole || !roleNames[chosenRole]) return;
    document.getElementById('roleStep').classList.add('d-none');
    document.getElementById('loginForm').classList.remove('d-none');
    document.getElementById('selectedRole').value = chosenRole;
    document.getElementById('roleLabel').textContent = roleNames[chosenRole];
  }

  function backToRole() {
    document.getElementById('loginForm').classList.add('d-none');
    document.getElementById('roleStep').classList.remove('d-none');
  }

  function validateLogin(e) {
    if (!chosenRole) {
      e.preventDefault();
      alert('Please select your account type.');
      return false;
    }
    const em = document.getElementById('loginEmail').value.trim();
    const pw = document.getElementById('loginPassword').value;
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(em)) {
      e.preventDefault();
      alert('Please enter a valid email address.');
      return false;
    }
    if (pw === '') {
      e.preventDefault();
      alert('Please enter your password.');
      return false;
    }
    return true;
  }

  // If a role came back from validation error on POST, jump straight to form
  <?php if ($selectedRole !== ''): ?>
  document.addEventListener('DOMContentLoaded', function(){ proceed(); });
  <?php endif; ?>
</script>

<?php require 'includes/auth_footer.php'; ?>
