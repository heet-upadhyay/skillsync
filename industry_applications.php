<?php
// industry_applications.php - Applications received + shortlist management
require_once 'includes/db_connect.php';
require_role('industry');

$uid = (int) $_SESSION['user_id'];
$message = '';
$msgType = 'success';

// ---- Handle status update actions ----
if (isset($_GET['update']) && isset($_GET['status'])) {
    $app_id = (int) $_GET['update'];
    $new_status = $_GET['status'];
    if (in_array($new_status, array('applied','test_pending','shortlisted','rejected'), true)) {
        // ensure the application belongs to this industry
        $st = db_query(
          'UPDATE applications a
           JOIN internships i ON i.id = a.internship_id
           SET a.status = ?
           WHERE a.id = ? AND i.industry_id = ?',
          'sii', array($new_status, $app_id, $uid));
        mysqli_stmt_close($st);
        $message = 'Application status updated.';
    }
}

// ---- Filters ----
$statusFilter = $_GET['status'] ?? '';
$postingFilter = (int)($_GET['posting'] ?? 0);

$sql = 'SELECT a.id, a.status, a.applied_at,
               i.id AS posting_id, i.title AS posting_title, i.type AS posting_type,
               u.id AS student_id, u.name AS student_name, u.email AS student_email,
               sd.college_name, sd.course_branch, sd.year
        FROM applications a
        JOIN internships i ON i.id = a.internship_id
        JOIN users u ON u.id = a.student_id
        LEFT JOIN student_details sd ON sd.user_id = u.id
        WHERE i.industry_id = ?';
$types = 'i';
$params = array($uid);

if ($statusFilter !== '') {
    $sql .= ' AND a.status = ?';
    $types .= 's';
    $params[] = $statusFilter;
}
if ($postingFilter > 0) {
    $sql .= ' AND i.id = ?';
    $types .= 'i';
    $params[] = $postingFilter;
}
$sql .= ' ORDER BY a.applied_at DESC';

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$apps = array();
while ($row = mysqli_fetch_assoc($res)) $apps[] = $row;
mysqli_stmt_close($stmt);

// ---- Stats for this industry ----
$stats = array('total'=>0,'test_pending'=>0,'shortlisted'=>0,'rejected'=>0,'applied'=>0);
$st = db_query(
  'SELECT a.status, COUNT(*) AS c FROM applications a
   JOIN internships i ON i.id = a.internship_id
   WHERE i.industry_id = ? GROUP BY a.status', 'i', array($uid));
$res = mysqli_stmt_get_result($st);
while ($row = mysqli_fetch_assoc($res)) $stats[$row['status']] = (int)$row['c'];
mysqli_stmt_close($st);
$stats['total'] = array_sum(array_slice($stats, 0));

// ---- My postings for filter dropdown ----
$myPostings = array();
$st = db_query('SELECT id, title, type FROM internships WHERE industry_id = ? ORDER BY posted_at DESC', 'i', array($uid));
$res = mysqli_stmt_get_result($st);
while ($row = mysqli_fetch_assoc($res)) $myPostings[] = $row;
mysqli_stmt_close($st);

$statusLabels = array('applied'=>'Applied','test_pending'=>'Test Pending','shortlisted'=>'Shortlisted','rejected'=>'Rejected');
$statusColors = array('applied'=>'badge-applied','test_pending'=>'badge-test_pending','shortlisted'=>'badge-shortlisted','rejected'=>'badge-rejected');

$pageTitle = 'Applications';
$activeNav = 'apps';
require 'includes/page_header_industry.php';
?>

<?php if ($message): ?><div class="alert alert-<?php echo $msgType; ?>"><?php echo e($message); ?></div><?php endif; ?>

<!-- Summary chips -->
<div class="d-flex flex-wrap gap-2 mb-4">
  <a href="industry_applications.php" class="badge-status badge-applied text-decoration-none <?php echo $statusFilter===''?'':''; ?>">All (<?php echo $stats['total']; ?>)</a>
  <a href="industry_applications.php?status=applied" class="badge-status badge-applied text-decoration-none">Applied (<?php echo $stats['applied']; ?>)</a>
  <a href="industry_applications.php?status=test_pending" class="badge-status badge-test_pending text-decoration-none">Test Pending (<?php echo $stats['test_pending']; ?>)</a>
  <a href="industry_applications.php?status=shortlisted" class="badge-status badge-shortlisted text-decoration-none">Shortlisted (<?php echo $stats['shortlisted']; ?>)</a>
  <a href="industry_applications.php?status=rejected" class="badge-status badge-rejected text-decoration-none">Rejected (<?php echo $stats['rejected']; ?>)</a>
</div>

<!-- Filter by posting -->
<div class="card mb-4">
  <div class="card-body">
    <form method="get" action="industry_applications.php" class="row g-2 align-items-end">
      <div class="col-md-5">
        <label class="form-label">Filter by Posting</label>
        <select class="form-select" name="posting">
          <option value="0">All postings</option>
          <?php foreach ($myPostings as $mp): ?>
            <option value="<?php echo $mp['id']; ?>" <?php echo $postingFilter===$mp['id']?'selected':''; ?>><?php echo e($mp['title']); ?> (<?php echo e(ucfirst($mp['type'])); ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php if ($statusFilter): ?><input type="hidden" name="status" value="<?php echo e($statusFilter); ?>"><?php endif; ?>
      <div class="col-md-2">
        <button class="btn btn-navy w-100">Filter</button>
      </div>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-body">
    <h5 class="card-title mb-3"><i class="fa-solid fa-user-graduate me-2 text-primary"></i>Received Applications
      <?php if ($statusFilter): ?> <span class="badge text-bg-secondary"><?php echo e(ucwords(str_replace('_',' ',$statusFilter))); ?></span><?php endif; ?>
      <?php if ($postingFilter): ?> <span class="badge text-bg-info text-dark"><?php echo e($postingFilter); ?></span><?php endif; ?>
    </h5>

    <?php if (count($apps) === 0): ?>
      <div class="text-center py-5">
        <i class="fa-solid fa-inbox" style="font-size:3rem;color:#cbd5e1;"></i>
        <p class="mt-3 mb-0 text-muted-2">No applications found<?php echo $statusFilter ? ' with this status' : ''; ?>.</p>
      </div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead class="table-light">
            <tr><th>#</th><th>Student</th><th>Posting</th><th>Applied On</th><th>Status</th><th>Action</th></tr>
          </thead>
          <tbody>
            <?php foreach ($apps as $i => $a): ?>
              <tr>
                <td><?php echo $i + 1; ?></td>
                <td>
                  <div class="fw-semibold"><?php echo e($a['student_name']); ?></div>
                  <small class="text-muted-2"><?php echo e($a['student_email']); ?><br><?php echo e($a['college_name'] ?: ''); ?> <?php echo e($a['course_branch'] ?: ''); ?> <?php echo $a['year'] ? ('· Yr '.$a['year']) : ''; ?></small>
                </td>
                <td><span class="badge <?php echo $a['posting_type']==='job'?'text-bg-warning':'text-bg-primary'; ?>"><?php echo e(ucfirst($a['posting_type'])); ?></span> <?php echo e($a['posting_title']); ?></td>
                <td><?php echo date('d M Y', strtotime($a['applied_at'])); ?></td>
                <td><span class="badge-status <?php echo $statusColors[$a['status']]; ?>"><?php echo $statusLabels[$a['status']]; ?></span></td>
                <td>
                  <div class="btn-group btn-group-sm">
                    <a href="industry_applications.php?update=<?php echo $a['id']; ?>&status=test_pending<?php echo $postingFilter?'&posting='.$postingFilter:''; ?>" class="btn btn-outline-warning" title="Mark Test Pending">Test</a>
                    <a href="industry_applications.php?update=<?php echo $a['id']; ?>&status=shortlisted<?php echo $postingFilter?'&posting='.$postingFilter:''; ?>" class="btn btn-outline-success" title="Shortlist">Shortlist</a>
                    <a href="industry_applications.php?update=<?php echo $a['id']; ?>&status=rejected<?php echo $postingFilter?'&posting='.$postingFilter:''; ?>" class="btn btn-outline-danger" title="Reject">Reject</a>
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

<?php require 'includes/page_footer_role.php'; ?>
