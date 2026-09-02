<?php
// internships.php - List, search, filter and apply to internships
require_once 'includes/db_connect.php';
require_role('student');

$uid = (int) $_SESSION['user_id'];
$message = '';
$msgType = 'info';

// Handle Apply
if (isset($_GET['apply']) && is_numeric($_GET['apply'])) {
    $internshipId = (int) $_GET['apply'];
    // Prevent duplicate application
    $st = db_query(
        'SELECT id FROM applications WHERE student_id = ? AND internship_id = ?',
        'ii', array($uid, $internshipId));
    mysqli_stmt_store_result($st);
    $already = mysqli_stmt_num_rows($st) > 0;
    mysqli_stmt_close($st);

    if (!$already) {
        $ins = db_query(
            'INSERT INTO applications (student_id, internship_id, status) VALUES (?, ?, "applied")',
            'ii', array($uid, $internshipId));
        mysqli_stmt_close($ins);
        $message = 'Application submitted successfully!';
        $msgType = 'success';
    } else {
        $message = 'You have already applied to this internship.';
        $msgType = 'warning';
    }
}

// ---- Search / filter ----
$keyword = trim($_GET['q'] ?? '');
$skillFilter = trim($_GET['skill'] ?? '');
$typeFilter = $_GET['type'] ?? '';

$sql = 'SELECT i.*, u.name AS company FROM internships i
        JOIN users u ON u.id = i.industry_id
        WHERE 1=1';
$types = '';
$params = array();

if ($keyword !== '') {
    $sql .= ' AND (i.title LIKE ? OR i.description LIKE ? OR u.name LIKE ?)';
    $types .= 'sss';
    $like = '%' . $keyword . '%';
    array_push($params, $like, $like, $like);
}
if ($typeFilter === 'internship' || $typeFilter === 'job') {
    $sql .= ' AND i.type = ?';
    $types .= 's';
    $params[] = $typeFilter;
}
if ($skillFilter !== '') {
    $sql .= ' AND i.required_skills LIKE ?';
    $types .= 's';
    array_push($params, '%' . $skillFilter . '%');
}

$sql .= ' ORDER BY i.posted_at DESC';

$st = mysqli_prepare($conn, $sql);
if ($types !== '') mysqli_stmt_bind_param($st, $types, ...$params);
mysqli_stmt_execute($st);
$result = mysqli_stmt_get_result($st);
$internships = array();
while ($row = mysqli_fetch_assoc($result)) $internships[] = $row;
mysqli_stmt_close($st);

// ---- Fetch skill list for filter dropdown ----
$skills = array();
$st = db_query('SELECT DISTINCT skill_name FROM skills ORDER BY skill_name');
$res = mysqli_stmt_get_result($st);
while ($row = mysqli_fetch_assoc($res)) $skills[] = $row['skill_name'];
mysqli_stmt_close($st);

// ---- Fetch applications the student already submitted ----
$applied = array();
$st = db_query('SELECT internship_id FROM applications WHERE student_id = ?', 'i', array($uid));
$res = mysqli_stmt_get_result($st);
while ($row = mysqli_fetch_assoc($res)) $applied[$row['internship_id']] = true;
mysqli_stmt_close($st);

$pageTitle = 'Internships & Jobs';
$activeNav = 'internships';
require 'includes/page_header.php';
?>

<?php if ($message): ?>
  <div class="alert alert-<?php echo $msgType; ?>"><?php echo e($message); ?></div>
<?php endif; ?>

<div class="card mb-4">
  <div class="card-body">
    <form method="get" action="internships.php" class="row g-3 align-items-end">
      <div class="col-md-4">
        <label class="form-label"><i class="fa-solid fa-magnifying-glass"></i> Search</label>
        <input type="text" class="form-control" name="q" value="<?php echo e($keyword); ?>" placeholder="Search by title, keyword or company">
      </div>
      <div class="col-md-3">
        <label class="form-label"><i class="fa-solid fa-briefcase"></i> Type</label>
        <select class="form-select" name="type">
          <option value="">Internships &amp; Jobs</option>
          <option value="internship" <?php echo $typeFilter==='internship'?'selected':''; ?>>Internships</option>
          <option value="job" <?php echo $typeFilter==='job'?'selected':''; ?>>Jobs</option>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label"><i class="fa-solid fa-filter"></i> Skill</label>
        <select class="form-select" name="skill">
          <option value="">All Skills</option>
          <?php foreach ($skills as $sk): ?>
            <option value="<?php echo e($sk); ?>" <?php echo $skillFilter === $sk ? 'selected' : ''; ?>><?php echo e($sk); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2 d-grid">
        <button class="btn btn-navy" type="submit">Filter</button>
      </div>
    </form>
  </div>
</div>

<?php if (count($internships) === 0): ?>
  <div class="alert alert-light text-center py-5">
    <i class="fa-solid fa-briefcase" style="font-size: 3rem; color: #cbd5e1;"></i>
    <p class="mt-3 mb-0">No internships found matching your criteria.</p>
  </div>
<?php else: ?>
  <div class="row g-4">
    <?php foreach ($internships as $in): ?>
      <div class="col-md-6 col-xl-4">
        <div class="card card-hover h-100">
          <div class="card-body d-flex flex-column">
            <div class="d-flex justify-content-between align-items-start mb-1">
              <h6 class="fw-bold mb-0"><?php echo e($in['title']); ?></h6>
              <span class="badge <?php echo $in['type']==='job'?'text-bg-warning':'text-bg-primary'; ?>"><?php echo e(ucfirst($in['type'])); ?></span>
            </div>
            <p class="small text-primary mb-2"><i class="fa-solid fa-building"></i> <?php echo e($in['company']); ?></p>
            <p class="small text-muted-2 mb-3 flex-grow-1"><?php echo e(substr(strip_tags($in['description']), 0, 140)); ?><?php echo strlen(strip_tags($in['description'])) > 140 ? '...' : ''; ?></p>

            <div class="d-flex flex-wrap gap-2 small mb-3">
              <?php if ($in['salary']): ?><span class="badge text-bg-success"><i class="fa-solid fa-indian-rupee-sign"></i> <?php echo e($in['salary']); ?></span><?php endif; ?>
              <?php if ($in['duration']): ?><span class="badge text-bg-secondary"><i class="fa-regular fa-clock"></i> <?php echo e($in['duration']); ?></span><?php endif; ?>
              <?php if ($in['mode']): ?><span class="badge text-bg-info text-dark"><i class="fa-solid fa-location-dot"></i> <?php echo e($in['mode']); ?></span><?php endif; ?>
              <?php if ($in['age_limit']): ?><span class="badge text-bg-dark"><i class="fa-solid fa-cake-candles"></i> <?php echo e($in['age_limit']); ?></span><?php endif; ?>
              <span class="badge text-bg-light border"><i class="fa-solid fa-user"></i> <?php echo (int)$in['no_of_posts']; ?> post(s)</span>
            </div>

            <div class="mb-3">
              <small class="text-muted-2 fw-semibold">Required skills:</small>
              <div class="d-flex flex-wrap gap-1 mt-1">
                <?php foreach (array_filter(array_map('trim', explode(',', $in['required_skills']))) as $rs): ?>
                  <span class="badge text-bg-light border"><?php echo e($rs); ?></span>
                <?php endforeach; ?>
              </div>
            </div>
            <div class="d-flex justify-content-between align-items-center">
              <small class="text-muted-2"><i class="fa-regular fa-clock"></i> <?php echo date('d M Y', strtotime($in['posted_at'])); ?></small>
              <?php if (isset($applied[$in['id']])): ?>
                <span class="badge-status badge-applied">Applied</span>
              <?php else: ?>
                <a href="internships.php?apply=<?php echo $in['id']; ?>" class="btn btn-sm btn-accent"><i class="fa-solid fa-paper-plane"></i> Apply</a>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php require 'includes/page_footer.php'; ?>
