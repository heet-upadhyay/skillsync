<?php
// academician_dashboard.php - Placeholder dashboard for academicians
require_once 'includes/db_connect.php';
require_role('academician');

$uid = (int) $_SESSION['user_id'];

$details = array('college_name'=>'','department'=>'','designation'=>'');
$st = db_query('SELECT college_name, department, designation FROM academician_details WHERE user_id = ?', 'i', array($uid));
$res = mysqli_stmt_get_result($st);
$dr = mysqli_fetch_assoc($res);
mysqli_free_result($res);
if ($dr) $details = array_merge($details, $dr);
mysqli_stmt_close($st);

$pageTitle = 'Academician Dashboard';
require 'includes/page_header_role.php';
?>

<div class="row g-4">
  <div class="col-12">
    <div class="landing-hero p-4">
      <div class="d-flex align-items-center gap-3">
        <i class="fa-solid fa-chalkboard-user" style="font-size:3rem;"></i>
        <div>
          <h4 class="fw-bold mb-1"><?php echo e($_SESSION['name']); ?></h4>
          <p class="mb-0 text-white-50"><?php echo e($details['designation'] ?: 'Academician'); ?> &middot; <?php echo e($details['department'] ?: ''); ?> &middot; <?php echo e($details['college_name'] ?: ''); ?></p>
        </div>
      </div>
    </div>
  </div>

  <div class="col-12">
    <div class="card">
      <div class="card-body">
        <h5 class="card-title mb-3"><i class="fa-solid fa-people-group me-2 text-primary"></i>Research &amp; Collaboration</h5>
        <p class="text-muted-2 mb-0">
          This dashboard is under development. Features for collaborating with industry, managing research
          projects, and advising students will be available here soon.
        </p>
      </div>
    </div>
  </div>
</div>

<?php require 'includes/page_footer_role.php'; ?>
