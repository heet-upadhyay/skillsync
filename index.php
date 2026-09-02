<?php
// index.php - Landing page
require_once 'includes/db_connect.php';

// If already logged in, send to their dashboard
if (is_logged_in()) {
    redirect_to_dashboard();
}

$pageTitle = 'Welcome';
require 'includes/auth_header.php';
?>

<div class="container py-5">

  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg mb-4">
    <a class="navbar-brand fw-bold text-primary" href="#"><i class="fa-solid fa-graduation-cap"></i> Collab Portal</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navMain">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="login.php"><i class="fa-solid fa-right-to-bracket"></i> Login</a></li>
        <li class="nav-item ms-2"><a class="btn btn-navy" href="register.php"><i class="fa-solid fa-user-plus"></i> Register</a></li>
      </ul>
    </div>
  </nav>

  <!-- Hero -->
  <div class="landing-hero mb-5">
    <div class="row align-items-center">
      <div class="col-lg-7">
        <h1 class="display-5 mb-3">Bridging Academia &amp; Industry</h1>
        <p class="lead mb-4">
          One platform connecting <strong>students</strong>, <strong>industries</strong>,
          <strong>academicians</strong> and <strong>institutions</strong> to collaborate on
          internships, skill development, courses and campus-industry partnerships.
        </p>
        <div class="d-flex gap-3 flex-wrap">
          <a href="register.php" class="btn btn-accent btn-lg px-4"><i class="fa-solid fa-user-plus"></i> Get Started</a>
          <a href="login.php" class="btn btn-outline-light btn-lg px-4"><i class="fa-solid fa-right-to-bracket"></i> Login</a>
        </div>
      </div>
      <div class="col-lg-5 text-center d-none d-lg-block">
        <i class="fa-solid fa-people-group" style="font-size: 9rem; color: rgba(255,255,255,0.25);"></i>
      </div>
    </div>
  </div>

  <!-- Features -->
  <div class="row g-4 mb-5">
    <div class="col-md-6 col-lg-3">
      <div class="card feature-card card-hover h-100">
        <div class="feat-icon"><i class="fa-solid fa-graduation-cap"></i></div>
        <h6>For Students</h6>
        <p class="small text-muted-2 mb-0">Skill assessments, internships, recommended courses and a personal portfolio.</p>
      </div>
    </div>
    <div class="col-md-6 col-lg-3">
      <div class="card feature-card card-hover h-100">
        <div class="feat-icon"><i class="fa-solid fa-industry"></i></div>
        <h6>For Industry</h6>
        <p class="small text-muted-2 mb-0">Post internships and find skilled student talent ready for the workforce.</p>
      </div>
    </div>
    <div class="col-md-6 col-lg-3">
      <div class="card feature-card card-hover h-100">
        <div class="feat-icon"><i class="fa-solid fa-chalkboard-user"></i></div>
        <h6>For Academicians</h6>
        <p class="small text-muted-2 mb-0">Collaborate on curriculum, research and industry-partnered projects.</p>
      </div>
    </div>
    <div class="col-md-6 col-lg-3">
      <div class="card feature-card card-hover h-100">
        <div class="feat-icon"><i class="fa-solid fa-building-columns"></i></div>
        <h6>For Institutions</h6>
        <p class="small text-muted-2 mb-0">Track student skill data and build strong campus-industry partnerships.</p>
      </div>
    </div>
  </div>

  <!-- How it works -->
  <div class="row g-4 mb-4">
    <div class="col-12 text-center mb-2">
      <h3 class="section-title">How It Works</h3>
    </div>
    <div class="col-md-4">
      <div class="card card-hover h-100 text-center"><div class="card-body">
        <h1 class="text-primary fw-bold">1</h1>
        <h6>Create your account</h6>
        <p class="small text-muted-2 mb-0">Pick your profile type and complete a quick registration.</p>
      </div></div>
    </div>
    <div class="col-md-4">
      <div class="card card-hover h-100 text-center"><div class="card-body">
        <h1 class="text-primary fw-bold">2</h1>
        <h6>Take the skill assessment</h6>
        <p class="small text-muted-2 mb-0">Students assess their skills to unlock personalized recommendations.</p>
      </div></div>
    </div>
    <div class="col-md-4">
      <div class="card card-hover h-100 text-center"><div class="card-body">
        <h1 class="text-primary fw-bold">3</h1>
        <h6>Connect &amp; apply</h6>
        <p class="small text-muted-2 mb-0">Apply to internships, take tests and track your progress.</p>
      </div></div>
    </div>
  </div>

</div>

<?php require 'includes/auth_footer.php'; ?>
