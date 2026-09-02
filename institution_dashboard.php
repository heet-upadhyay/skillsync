<?php
// institution_dashboard.php - Institution (College) dashboard
// Sections via ?s= jobs|internship|skill|industry|about
require_once 'includes/db_connect.php';
require_role('institution');

$uid = (int) $_SESSION['user_id'];
$section = isset($_GET['s']) ? $_GET['s'] : 'jobs';
if (!in_array($section, array('jobs','internship','skill','industry','about'), true)) $section = 'jobs';

// ---- Institution details ----
$inst = array('institution_name'=>'','institution_type'=>'','location'=>'','about'=>'');
$st = db_query('SELECT institution_name, institution_type, location, about FROM institution_details WHERE user_id = ?', 'i', array($uid));
$res = mysqli_stmt_get_result($st);
$dr = mysqli_fetch_assoc($res);
mysqli_free_result($res);
if ($dr) $inst = array_merge($inst, $dr);
mysqli_stmt_close($st);

$collegeName = $inst['institution_name'];

// ---- The college's students (by matching college_name, case-insensitive) ----
// Build a helper: returns list of student user_ids for this college
function college_student_ids($conn, $collegeName) {
    $ids = array();
    if ($collegeName === '') return $ids;
    $st = mysqli_prepare($conn,
        'SELECT sd.user_id FROM student_details sd
         WHERE LOWER(TRIM(sd.college_name)) = LOWER(?)');
    mysqli_stmt_bind_param($st, 's', $collegeName);
    mysqli_stmt_execute($st);
    $res = mysqli_stmt_get_result($st);
    while ($row = mysqli_fetch_assoc($res)) $ids[] = (int)$row['user_id'];
    mysqli_stmt_close($st);
    return $ids;
}
$studentIds = college_student_ids($conn, $collegeName);

// ==== DATA PER SECTION ====

// JOBS / INTERNSHIP: per-industry breakdown for the college's students
// Returns list of industries with counts, restricted to a given opportunity type
function industry_breakdown($conn, $collegeName, $type) {
    $rows = array();
    $st = mysqli_prepare($conn,
        'SELECT u.id AS industry_id, u.name AS company,
                COUNT(DISTINCT a.student_id) AS applied,
                COUNT(DISTINCT CASE WHEN a.status IN ("test_pending","shortlisted") THEN a.student_id END) AS selected,
                COUNT(DISTINCT CASE WHEN a.status = "rejected" THEN a.student_id END) AS rejected,
                COUNT(DISTINCT CASE WHEN a.status = "applied" THEN a.student_id END) AS pending
         FROM applications a
         JOIN internships i ON i.id = a.internship_id
         JOIN users u ON u.id = i.industry_id
         JOIN student_details sd ON sd.user_id = a.student_id
         WHERE i.type = ? AND LOWER(TRIM(sd.college_name)) = LOWER(?)
         GROUP BY u.id, u.name
         ORDER BY applied DESC');
    mysqli_stmt_bind_param($st, 'ss', $type, $collegeName);
    mysqli_stmt_execute($st);
    $res = mysqli_stmt_get_result($st);
    while ($row = mysqli_fetch_assoc($res)) $rows[] = $row;
    mysqli_stmt_close($st);
    return $rows;
}

// SKILL: total students, test takers, per-skill "lacking" counts
function skill_analytics($conn, $collegeName) {
    $result = array('total'=>0, 'test_takers'=>0, 'skills'=>array());
    if ($collegeName === '') return $result;
    // total students in college
    $st = mysqli_prepare($conn, 'SELECT COUNT(*) FROM student_details WHERE LOWER(TRIM(college_name)) = LOWER(?)');
    mysqli_stmt_bind_param($st, 's', $collegeName);
    mysqli_stmt_execute($st); mysqli_stmt_bind_result($st, $total);
    mysqli_stmt_fetch($st); $result['total'] = (int)$total; mysqli_stmt_close($st);
    // students who took assessment (have >=1 student_skills row) in this college
    $st = mysqli_prepare($conn,
      'SELECT COUNT(DISTINCT ss.student_id) FROM student_skills ss
       JOIN student_details sd ON sd.user_id = ss.student_id
       WHERE LOWER(TRIM(sd.college_name)) = LOWER(?)');
    mysqli_stmt_bind_param($st, 's', $collegeName);
    mysqli_stmt_execute($st); mysqli_stmt_bind_result($st, $tt);
    mysqli_stmt_fetch($st); $result['test_takers'] = (int)$tt; mysqli_stmt_close($st);
    // per-skill: # students lacking (score < 70 / low) in this college
    $st = mysqli_prepare($conn,
      'SELECT s.skill_name, COUNT(DISTINCT ss.student_id) AS lacking,
              ROUND(AVG(ss.score)) AS avg_score
       FROM student_skills ss
       JOIN skills s ON s.id = ss.skill_id
       JOIN student_details sd ON sd.user_id = ss.student_id
       WHERE LOWER(TRIM(sd.college_name)) = LOWER(?) AND ss.score < 70
       GROUP BY s.id, s.skill_name
       ORDER BY lacking DESC');
    mysqli_stmt_bind_param($st, 's', $collegeName);
    mysqli_stmt_execute($st);
    $res = mysqli_stmt_get_result($st);
    while ($row = mysqli_fetch_assoc($res)) $result['skills'][] = $row;
    mysqli_stmt_close($st);
    return $result;
}

// INDUSTRY: portal-wide industry stats
$industryStats = array();
$st = db_query(
  'SELECT u.name AS company, u.id AS industry_id,
          COUNT(DISTINCT i.id) AS postings,
          COUNT(DISTINCT CASE WHEN i.type="job" THEN i.id END) AS jobs,
          COUNT(DISTINCT CASE WHEN i.type="internship" THEN i.id END) AS internships
   FROM users u
   LEFT JOIN internships i ON i.industry_id = u.id
   WHERE u.role = "industry"
   GROUP BY u.id, u.name ORDER BY postings DESC');
$res = mysqli_stmt_get_result($st);
while ($row = mysqli_fetch_assoc($res)) $industryStats[] = $row;
mysqli_stmt_close($st);

$totalIndustries = count($industryStats);
$totalJobPosts = 0; $totalInternPosts = 0; $totalPosts = 0;
foreach ($industryStats as $is) { $totalJobPosts += (int)$is['jobs']; $totalInternPosts += (int)$is['internships']; $totalPosts += (int)$is['postings']; }

$pageTitle = 'Institution Dashboard';
require 'includes/page_header_institution.php';
?>

<!-- Header -->
<div class="landing-hero p-4 mb-4">
  <div class="d-flex align-items-center gap-3 flex-wrap">
    <i class="fa-solid fa-building-columns" style="font-size: 3rem;"></i>
    <div class="flex-grow-1">
      <h4 class="fw-bold mb-1"><?php echo e($inst['institution_name'] ?: $_SESSION['name']); ?></h4>
      <p class="mb-0"><?php echo e($inst['institution_type'] ?: 'Institution'); ?> &middot; <?php echo e($inst['location'] ?: ''); ?></p>
    </div>
    <a href="institution_dashboard.php?s=about" class="btn btn-accent btn-sm"><i class="fa-solid fa-pen"></i> Update About</a>
  </div>
</div>

<?php if (count($studentIds) === 0): ?>
  <div class="alert alert-warning"><i class="fa-solid fa-triangle-exclamation"></i>
    No student data is linked to "<?php echo e($collegeName); ?>" yet. Students register with this college name to appear in your analytics.
  </div>
<?php endif; ?>

<?php
// ========== JOBS SECTION ==========
if ($section === 'jobs'):
    $jrows = industry_breakdown($conn, $collegeName, 'job');
?>
  <h5 class="section-title"><i class="fa-solid fa-briefcase me-2"></i>Jobs — Placement Overview (numbers only)</h5>
  <p class="text-muted-2 mb-3">How many students from <?php echo e($collegeName); ?> applied / got selected / are pending / rejected per company.</p>
  <?php if (count($jrows) === 0): ?>
    <div class="alert alert-light">No job applications from your college yet.</div>
  <?php else: ?>
    <div class="row g-4">
      <?php foreach ($jrows as $r): ?>
        <div class="col-md-6 col-xl-4">
          <div class="card card-hover h-100"><div class="card-body">
            <h6 class="fw-bold text-primary"><i class="fa-solid fa-building"></i> <?php echo e($r['company']); ?></h6>
            <hr>
            <div class="row text-center g-2">
              <div class="col-6"><div class="stat-num" style="font-size:1.5rem;"><?php echo (int)$r['applied']; ?></div><div class="small text-muted-2">Applied</div></div>
              <div class="col-6"><div class="stat-num text-success" style="font-size:1.5rem;"><?php echo (int)$r['selected']; ?></div><div class="small text-muted-2">Selected</div></div>
              <div class="col-6"><div class="stat-num text-warning" style="font-size:1.5rem;"><?php echo (int)$r['pending']; ?></div><div class="small text-muted-2">Pending</div></div>
              <div class="col-6"><div class="stat-num text-danger" style="font-size:1.5rem;"><?php echo (int)$r['rejected']; ?></div><div class="small text-muted-2">Rejected</div></div>
            </div>
          </div></div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
<?php endif; ?>

<?php
// ========== INTERNSHIP SECTION ==========
if ($section === 'internship'):
    $iro = industry_breakdown($conn, $collegeName, 'internship');
?>
  <h5 class="section-title"><i class="fa-solid fa-rocket me-2"></i>Internships — Overview</h5>
  <p class="text-muted-2 mb-3">Students from <?php echo e($collegeName); ?> applying to internships per company.</p>
  <?php if (count($iro) === 0): ?>
    <div class="alert alert-light">No internship applications from your college yet.</div>
  <?php else: ?>
    <div class="row g-4">
      <?php foreach ($iro as $r): ?>
        <div class="col-md-6 col-xl-4">
          <div class="card card-hover h-100"><div class="card-body">
            <h6 class="fw-bold text-primary"><i class="fa-solid fa-building"></i> <?php echo e($r['company']); ?></h6>
            <hr>
            <div class="row text-center g-2">
              <div class="col-6"><div class="stat-num" style="font-size:1.5rem;"><?php echo (int)$r['applied']; ?></div><div class="small text-muted-2">Applied</div></div>
              <div class="col-6"><div class="stat-num text-success" style="font-size:1.5rem;"><?php echo (int)$r['selected']; ?></div><div class="small text-muted-2">Selected</div></div>
              <div class="col-6"><div class="stat-num text-warning" style="font-size:1.5rem;"><?php echo (int)$r['pending']; ?></div><div class="small text-muted-2">Pending</div></div>
              <div class="col-6"><div class="stat-num text-danger" style="font-size:1.5rem;"><?php echo (int)$r['rejected']; ?></div><div class="small text-muted-2">Rejected</div></div>
            </div>
          </div></div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
<?php endif; ?>

<?php
// ========== SKILL SECTION ==========
if ($section === 'skill'):
    $sa = skill_analytics($conn, $collegeName);
?>
  <h5 class="section-title"><i class="fa-solid fa-chart-simple me-2"></i>Skill Analytics — <?php echo e($collegeName); ?></h5>

  <div class="row g-3 mb-4">
    <div class="col-md-4">
      <div class="stat-card bg-navy">
        <div class="stat-icon"><i class="fa-solid fa-user-graduate"></i></div>
        <div><div class="stat-num"><?php echo $sa['total']; ?></div><div class="stat-label">Total Students</div></div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="stat-card bg-accent">
        <div class="stat-icon"><i class="fa-solid fa-clipboard-check"></i></div>
        <div><div class="stat-num"><?php echo $sa['test_takers']; ?></div><div class="stat-label">Took Skill Test</div></div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="stat-card bg-success-2">
        <div class="stat-icon"><i class="fa-solid fa-graduation-cap"></i></div>
        <div><div class="stat-num"><?php echo $sa['total'] - $sa['test_takers']; ?></div><div class="stat-label">Yet to Test</div></div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-body">
      <h6 class="card-title mb-3"><i class="fa-solid fa-triangle-exclamation text-warning"></i> Students Lacking Skills (score &lt; 70)</h6>
      <?php if (count($sa['skills']) === 0): ?>
        <p class="text-muted-2 mb-0">No skill assessment data available for your college yet.</p>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table align-middle">
            <thead class="table-light"><tr><th>Skill</th><th>Avg Score</th><th># Students Lacking</th><th style="width:40%;">Trend</th></tr></thead>
            <tbody>
              <?php foreach ($sa['skills'] as $sk): ?>
                <?php $pct = $sa['total'] > 0 ? round(((int)$sk['lacking'] / $sa['total']) * 100) : 0; ?>
                <tr>
                  <td class="fw-semibold"><?php echo e($sk['skill_name']); ?></td>
                  <td><span class="badge <?php echo (int)$sk['avg_score']>=50?'text-bg-warning':'text-bg-danger'; ?>"><?php echo (int)$sk['avg_score']; ?>/100</span></td>
                  <td><span class="badge text-bg-danger"><?php echo (int)$sk['lacking']; ?> students</span></td>
                  <td>
                    <div class="d-flex align-items-center gap-2 small text-muted-2">
                      <div class="progress flex-grow-1" style="height:8px;"><div class="progress-bar bg-danger" style="width:<?php echo $pct; ?>%;"></div></div>
                      <?php echo $pct; ?>%
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
<?php endif; ?>

<?php
// ========== INDUSTRY SECTION ==========
if ($section === 'industry'):
?>
  <h5 class="section-title"><i class="fa-solid fa-industry me-2"></i>Industry Partners on the Portal</h5>

  <div class="row g-3 mb-4">
    <div class="col-md-3"><div class="stat-card bg-navy"><div class="stat-icon"><i class="fa-solid fa-industry"></i></div><div><div class="stat-num"><?php echo $totalIndustries; ?></div><div class="stat-label">Industries</div></div></div></div>
    <div class="col-md-3"><div class="stat-card bg-accent"><div class="stat-icon"><i class="fa-solid fa-layer-group"></i></div><div><div class="stat-num"><?php echo $totalPosts; ?></div><div class="stat-label">Total Postings</div></div></div></div>
    <div class="col-md-3"><div class="stat-card bg-success-2"><div class="stat-icon"><i class="fa-solid fa-briefcase"></i></div><div><div class="stat-num"><?php echo $totalJobPosts; ?></div><div class="stat-label">Jobs</div></div></div></div>
    <div class="col-md-3"><div class="stat-card bg-danger-2"><div class="stat-icon"><i class="fa-solid fa-rocket"></i></div><div><div class="stat-num"><?php echo $totalInternPosts; ?></div><div class="stat-label">Internships</div></div></div></div>
  </div>

  <div class="card">
    <div class="card-body">
      <h6 class="card-title mb-3"><i class="fa-solid fa-list me-2 text-primary"></i>Industry-wise Postings</h6>
      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead class="table-light"><tr><th>Company</th><th>Postings</th><th>Internships</th><th>Jobs</th></tr></thead>
          <tbody>
            <?php foreach ($industryStats as $is): ?>
              <tr>
                <td class="fw-semibold"><i class="fa-solid fa-building me-1 text-muted"></i> <?php echo e($is['company']); ?></td>
                <td><span class="badge text-bg-primary"><?php echo (int)$is['postings']; ?></span></td>
                <td><span class="badge text-bg-info text-dark"><?php echo (int)$is['internships']; ?></span></td>
                <td><span class="badge text-bg-warning"><?php echo (int)$is['jobs']; ?></span></td>
              </tr>
            <?php endforeach; ?>
            <?php if (count($industryStats) === 0): ?><tr><td colspan="4" class="text-center text-muted-2">No industries registered yet.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
<?php endif; ?>

<?php
// ========== ABOUT SECTION ==========
if ($section === 'about'):
    $errors = array(); $msg = ''; $msgType = 'success';
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_inst') {
        $iname = trim($_POST['institution_name'] ?? '');
        $itype = trim($_POST['institution_type'] ?? '');
        $loc = trim($_POST['location'] ?? '');
        $about = trim($_POST['about'] ?? '');
        if ($iname === '') $errors[] = 'Institution name cannot be empty.';
        if (empty($errors)) {
            $st = db_query('UPDATE institution_details SET institution_name=?, institution_type=?, location=?, about=? WHERE user_id=?', 'ssssi', array($iname, $itype, $loc, $about, $uid));
            mysqli_stmt_close($st);
            $msg = 'Institution information updated.';
            $inst['institution_name']=$iname; $inst['institution_type']=$itype; $inst['location']=$loc; $inst['about']=$about;
        }
    }
?>
  <h5 class="section-title"><i class="fa-solid fa-building-columns me-2"></i>About Institution</h5>
  <?php if ($msg): ?><div class="alert alert-<?php echo $msgType; ?>"><?php echo e($msg); ?></div><?php endif; ?>
  <?php if (!empty($errors)): ?><div class="alert alert-danger"><?php foreach ($errors as $err): ?><div><?php echo e($err); ?></div><?php endforeach; ?></div><?php endif; ?>

  <div class="row g-4">
    <div class="col-lg-7">
      <div class="card">
        <div class="card-body">
          <h6 class="card-title mb-3"><i class="fa-solid fa-pen me-2 text-primary"></i>Institution Information</h6>
          <form method="post" action="institution_dashboard.php?s=about">
            <input type="hidden" name="action" value="update_inst">
            <div class="mb-3"><label class="form-label">Institution Name</label><input type="text" class="form-control" name="institution_name" value="<?php echo e($inst['institution_name']); ?>"></div>
            <div class="row g-3 mb-3">
              <div class="col-md-6"><label class="form-label">Institution Type</label><input type="text" class="form-control" name="institution_type" value="<?php echo e($inst['institution_type']); ?>"></div>
              <div class="col-md-6"><label class="form-label">Location</label><input type="text" class="form-control" name="location" value="<?php echo e($inst['location']); ?>"></div>
            </div>
            <div class="mb-3"><label class="form-label">About</label><textarea class="form-control" name="about" rows="4" placeholder="About your institution..."><?php echo e($inst['about']); ?></textarea></div>
            <button class="btn btn-navy"><i class="fa-solid fa-floppy-disk"></i> Save</button>
          </form>
        </div>
      </div>
    </div>
    <div class="col-lg-5">
      <div class="card">
        <div class="card-body">
          <h6 class="card-title mb-3"><i class="fa-solid fa-circle-info me-2 text-primary"></i>Preview</h6>
          <h5 class="fw-bold"><?php echo e($inst['institution_name']); ?></h5>
          <p class="text-muted-2 mb-2"><?php echo e($inst['institution_type']); ?> &middot; <?php echo e($inst['location']); ?></p>
          <p class="mb-0"><?php echo e($inst['about'] ?: 'No about text yet.'); ?></p>
          <hr>
          <p class="small text-muted-2 mb-0"><i class="fa-solid fa-shield-halved"></i> Signed in as <strong><?php echo e($_SESSION['email']); ?></strong> (Institution)</p>
          <a href="logout.php" class="btn btn-outline-danger w-100 mt-3"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>

<?php require 'includes/page_footer_role.php'; ?>
