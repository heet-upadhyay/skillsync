<?php
// page_header_role.php - Generic dashboard header with TOP navigation bar
// Use: set $pageTitle before including. Assumes db_connect.php included + require_role() called.
$pageTitle = isset($pageTitle) ? $pageTitle : 'Dashboard';
$uname = isset($_SESSION['name']) ? $_SESSION['name'] : 'User';
$initial = strtoupper(substr($uname, 0, 1));

// Per-role config
$roleLabel = 'User';
$roleIcon = 'fa-user';
$dashboardPage = 'industry_dashboard.php';
switch ($_SESSION['role'] ?? '') {
    case 'industry':    $roleLabel = 'Industry'; $roleIcon = 'fa-industry'; $dashboardPage = 'industry_dashboard.php'; break;
    case 'academician': $roleLabel = 'Academician'; $roleIcon = 'fa-chalkboard-user'; $dashboardPage = 'academician_dashboard.php'; break;
    case 'institution': $roleLabel = 'Institution'; $roleIcon = 'fa-building-columns'; $dashboardPage = 'institution_dashboard.php'; break;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo e($pageTitle); ?> | <?php echo e($uname); ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="topnav-layout">

<nav class="navbar navbar-expand-lg navbar-dark portal-navbar sticky-top">
  <div class="container-fluid px-4">
    <a class="navbar-brand fw-bold" href="<?php echo $dashboardPage; ?>"><i class="fa-solid fa-graduation-cap me-2"></i>Collab Portal</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#roleNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="roleNav">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link active" href="<?php echo $dashboardPage; ?>"><i class="fa-solid fa-house"></i> <?php echo e($roleLabel); ?> Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="profile.php"><i class="fa-solid fa-gear"></i> Profile</a></li>
      </ul>
      <div class="d-flex align-items-center gap-2">
        <div class="user-chip-light d-flex align-items-center gap-2">
          <span class="avatar-sm"><?php echo e($initial); ?></span>
          <span class="d-none d-md-inline"><?php echo e($uname); ?></span>
        </div>
        <a class="btn btn-sm btn-accent" href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> <span class="d-none d-lg-inline">Logout</span></a>
      </div>
    </div>
  </div>
</nav>

<div class="container-fluid px-4 py-4">
