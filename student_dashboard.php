<?php
// student_dashboard.php - Student dashboard home
require_once 'includes/db_connect.php';
require_role('student');

$uid = (int) $_SESSION['user_id'];

// ---- Fetch student_details ----
$details = array('college_name'=>'','course_branch'=>'','year'=>'');
$st = db_query('SELECT college_name, course_branch, year FROM student_details WHERE user_id = ?', 'i', array($uid));
$res = mysqli_stmt_get_result($st);
$dr = mysqli_fetch_assoc($res);
mysqli_free_result($res);
if ($dr) $details = $dr;
mysqli_stmt_close($st);

// ---- Fetch student skills (assessment) ----
$skills = array();
$st = db_query(
  'SELECT s.skill_name, ss.score FROM student_skills ss
   JOIN skills s ON s.id = ss.skill_id
   WHERE ss.student_id = ?', 'i', array($uid));
$res = mysqli_stmt_get_result($st);
while ($row = mysqli_fetch_assoc($res)) { $skills[] = $row; }
mysqli_stmt_close($st);
$hasAssessment = count($skills) > 0;

// strengths = skills with score >= 70, gaps = skills with score < 70
$strengths = array_filter($skills, fn($s) => $s['score'] >= 70);
$gaps = array_filter($skills, fn($s) => $s['score'] < 70);

// ---- Quick stats ----
$totalApps = 0; $pending = 0; $shortlisted = 0;
$st = db_query('SELECT COUNT(*) AS c, status FROM applications WHERE student_id = ? GROUP BY status', 'i', array($uid));
$res = mysqli_stmt_get_result($st);
while ($row = mysqli_fetch_assoc($res)) {
    $totalApps += (int)$row['c'];
    if ($row['status'] === 'shortlisted') $shortlisted += (int)$row['c'];
    if ($row['status'] === 'applied' || $row['status'] === 'test_pending') $pending += (int)$row['c'];
}
mysqli_stmt_close($st);

// "courses completed" - count from portfolio_certificates as a proxy
$coursesDone = 0;
$st = db_query('SELECT COUNT(*) AS c FROM portfolio_certificates WHERE student_id = ?', 'i', array($uid));
$res = mysqli_stmt_get_result($st);
$r2 = mysqli_fetch_assoc($res);
mysqli_free_result($res);
$coursesDone = (int)($r2['c'] ?? 0);
mysqli_stmt_close($st);

// ---- Recommended courses (based on skill gaps) ----
$recommendedCourses = array();
$st = db_query('SELECT * FROM courses ORDER BY id DESC LIMIT 6');
$res = mysqli_stmt_get_result($st);
while ($row = mysqli_fetch_assoc($res)) { $recommendedCourses[] = $row; }
mysqli_stmt_close($st);

// ---- Recommended internships ----
$recommendedInternships = array();
$st = db_query(
  'SELECT i.*, u.name AS company FROM internships i
   JOIN users u ON u.id = i.industry_id
   ORDER BY i.posted_at DESC
   LIMIT 6');
$res = mysqli_stmt_get_result($st);
while ($row = mysqli_fetch_assoc($res)) { $recommendedInternships[] = $row; }
mysqli_stmt_close($st);

// ---- Recent activity ----
$activity = array();
$st = db_query(
  'SELECT a.status, a.applied_at, i.title FROM applications a
   JOIN internships i ON i.id = a.internship_id
   WHERE a.student_id = ?
   ORDER BY a.applied_at DESC LIMIT 6', 'i', array($uid));
$res = mysqli_stmt_get_result($st);
while ($row = mysqli_fetch_assoc($res)) { $activity[] = $row; }
mysqli_stmt_close($st);

$pageTitle = 'Dashboard';
$activeNav = 'dashboard';
require 'includes/page_header.php';
?>

<!-- Top welcome -->
<div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
  <div>
    <h4 class="fw-bold text-navy mb-1">Hello, <?php echo e($_SESSION['name']); ?> 👋</h4>
    <p class="text-muted-2 mb-0">
      <?php echo e($details['college_name'] ? $details['college_name'] : 'Your college'); ?>
      &middot; <?php echo e($details['course_branch'] ? $details['course_branch'] : 'Course'); ?>
      <?php if ($details['year']): ?> &middot; Year <?php echo e($details['year']); ?><?php endif; ?>
    </p>
  </div>
</div>

<!-- Quick Stats -->
<div class="row g-3 mb-4">
  <div class="col-md-6 col-xl-3">
    <div class="stat-card bg-navy">
      <div class="stat-icon"><i class="fa-solid fa-paper-plane"></i></div>
      <div><div class="stat-num"><?php echo $totalApps; ?></div><div class="stat-label">Total Applications</div></div>
    </div>
  </div>
  <div class="col-md-6 col-xl-3">
    <div class="stat-card bg-accent">
      <div class="stat-icon"><i class="fa-solid fa-clock"></i></div>
      <div><div class="stat-num"><?php echo $pending; ?></div><div class="stat-label">Pending / In Progress</div></div>
    </div>
  </div>
  <div class="col-md-6 col-xl-3">
    <div class="stat-card bg-success-2">
      <div class="stat-icon"><i class="fa-solid fa-star"></i></div>
      <div><div class="stat-num"><?php echo $shortlisted; ?></div><div class="stat-label">Shortlisted</div></div>
    </div>
  </div>
  <div class="col-md-6 col-xl-3">
    <div class="stat-card bg-danger-2">
      <div class="stat-icon"><i class="fa-solid fa-award"></i></div>
      <div><div class="stat-num"><?php echo $coursesDone; ?></div><div class="stat-label">Courses Completed</div></div>
    </div>
  </div>
</div>

<div class="row g-4">

  <!-- Skill profile summary -->
  <div class="col-lg-8">
    <div class="card h-100">
      <div class="card-body">
        <h6 class="card-title mb-3"><i class="fa-solid fa-chart-simple me-2 text-primary"></i>Skill Profile Summary</h6>

        <?php if (!$hasAssessment): ?>
          <div class="text-center py-4">
            <i class="fa-solid fa-clipboard-question text-primary" style="font-size: 3rem;"></i>
            <p class="mt-3 text-muted-2">You haven't taken the skill assessment yet.</p>
            <a href="skill_assessment.php" class="btn btn-navy"><i class="fa-solid fa-play"></i> Start Assessment</a>
          </div>
        <?php else: ?>
          <div class="d-flex align-items-center mb-3">
            <span class="badge rounded-pill text-bg-success me-2"><i class="fa-solid fa-check"></i> Assessed</span>
            <small class="text-muted-2"><?php echo count($skills); ?> skill(s) assessed</small>
          </div>

          <?php if (count($strengths) > 0): ?>
            <p class="mb-2 fw-semibold small text-success"><i class="fa-solid fa-thumbs-up"></i> Strengths</p>
            <div class="d-flex flex-wrap gap-2 mb-3">
              <?php foreach ($strengths as $s): ?>
                <span class="badge text-bg-success"><?php echo e($s['skill_name']); ?> (<?php echo (int)$s['score']; ?>/100)</span>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <?php if (count($gaps) > 0): ?>
            <p class="mb-2 fw-semibold small text-warning"><i class="fa-solid fa-brain"></i> Areas to Improve</p>
            <div class="d-flex flex-wrap gap-2 mb-3">
              <?php foreach ($gaps as $g): ?>
                <span class="badge text-bg-warning"><?php echo e($g['skill_name']); ?> (<?php echo (int)$g['score']; ?>/100)</span>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <div class="alert alert-success mb-0">Great! You have strong scores across all assessed skills.</div>
          <?php endif; ?>

          <a href="skill_assessment.php" class="btn btn-sm btn-outline-primary">Retake Assessment</a>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Recent activity -->
  <div class="col-lg-4">
    <div class="card h-100">
      <div class="card-body">
        <h6 class="card-title mb-3"><i class="fa-solid fa-clock-rotate-left me-2 text-primary"></i>Recent Activity</h6>
        <?php if (count($activity) === 0): ?>
          <p class="text-muted-2 mb-0">No activity yet. Browse internships to get started.</p>
        <?php else: ?>
          <?php foreach ($activity as $a): ?>
            <div class="activity-item">
              <div class="fw-semibold small">Applied to <?php echo e($a['title']); ?></div>
              <div>
                <span class="badge-status badge-<?php echo e($a['status']); ?>"><?php echo e(str_replace('_',' ', $a['status'])); ?></span>
                <span class="time"><?php echo date('d M Y, g:i A', strtotime($a['applied_at'])); ?></span>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Recommended courses -->
<div class="mt-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="section-title mb-0"><i class="fa-solid fa-book-open me-2"></i>Recommended Courses For You</h5>
    <a href="courses.php" class="btn btn-sm btn-navy">View All</a>
  </div>
  <div class="row g-3">
    <?php foreach (array_slice($recommendedCourses, 0, 3) as $c): ?>
      <div class="col-md-6 col-lg-4">
        <div class="card card-hover h-100">
          <div class="card-body">
            <span class="badge bg-navy mb-2"><?php echo e($c['skill_tag'] ?: 'General'); ?></span>
            <h6 class="fw-bold"><?php echo e($c['title']); ?></h6>
            <p class="small text-muted-2 mb-2">Platform: <?php echo e($c['platform'] ?: 'N/A'); ?></p>
            <?php if ($c['link']): ?>
              <a href="<?php echo e($c['link']); ?>" target="_blank" class="btn btn-sm btn-navy"><i class="fa-solid fa-arrow-up-right-from-square"></i> Start Learning</a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
    <?php if (count($recommendedCourses) === 0): ?>
      <div class="col-12"><div class="alert alert-light">No courses available yet. Check back soon.</div></div>
    <?php endif; ?>
  </div>
</div>

<!-- Recommended internships -->
<div class="mt-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="section-title mb-0"><i class="fa-solid fa-briefcase me-2"></i>Matching Internships</h5>
    <a href="internships.php" class="btn btn-sm btn-outline-primary">View All</a>
  </div>
  <div class="row g-3">
    <?php foreach (array_slice($recommendedInternships, 0, 3) as $in): ?>
      <div class="col-md-6 col-lg-4">
        <div class="card card-hover h-100">
          <div class="card-body">
            <h6 class="fw-bold"><?php echo e($in['title']); ?></h6>
            <p class="small text-primary mb-2"><i class="fa-solid fa-building"></i> <?php echo e($in['company']); ?></p>
            <p class="small text-muted-2 mb-3"><?php echo e(substr(strip_tags($in['description']), 0, 120)); ?>...</p>
            <a href="internships.php" class="btn btn-sm btn-accent"><i class="fa-solid fa-paper-plane"></i> Apply</a>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
    <?php if (count($recommendedInternships) === 0): ?>
      <div class="col-12"><div class="alert alert-light">No internships posted yet.</div></div>
    <?php endif; ?>
  </div>
</div>

<?php require 'includes/page_footer.php'; ?>
