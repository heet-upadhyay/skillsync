<?php
// industry_dashboard.php - Industry dashboard home
require_once 'includes/db_connect.php';
require_role('industry');

$uid = (int) $_SESSION['user_id'];

// ---- Fetch industry (company) details ----
$details = array('company_name'=>'','industry_type'=>'','company_size'=>'','website'=>'');
$st = db_query('SELECT company_name, industry_type, company_size, website FROM industry_details WHERE user_id = ?', 'i', array($uid));
$res = mysqli_stmt_get_result($st);
$dr = mysqli_fetch_assoc($res);
mysqli_free_result($res);
if ($dr) $details = array_merge($details, $dr);
mysqli_stmt_close($st);

// ---- Stats ----
$myPostings = array();
$st = db_query(
  'SELECT i.*,
     (SELECT COUNT(*) FROM applications a WHERE a.internship_id = i.id) AS app_count,
     (SELECT COUNT(*) FROM applications a WHERE a.internship_id = i.id AND a.status = "shortlisted") AS short_count
   FROM internships i WHERE i.industry_id = ? ORDER BY i.posted_at DESC', 'i', array($uid));
$res = mysqli_stmt_get_result($st);
while ($row = mysqli_fetch_assoc($res)) $myPostings[] = $row;
mysqli_stmt_close($st);

$totalApps = 0; $shortlisted = 0; $testPending = 0;
foreach ($myPostings as $p) {
    $totalApps += (int)$p['app_count'];
    $shortlisted += (int)$p['short_count'];
}

$pageTitle = 'Industry Dashboard';
$activeNav = 'dashboard';
require 'includes/page_header_industry.php';
?>

<!-- Company header -->
<div class="landing-hero p-4 mb-4">
  <div class="d-flex align-items-center gap-3 flex-wrap">
    <i class="fa-solid fa-industry" style="font-size: 3rem;"></i>
    <div class="flex-grow-1">
      <h4 class="fw-bold mb-1"><?php echo e($details['company_name'] ?: $_SESSION['name']); ?></h4>
      <p class="mb-0"><?php echo e($details['industry_type'] ?: 'Industry Partner'); ?> &middot; <?php echo e($details['company_size'] ?: ''); ?>
        <?php if ($details['website']): ?> &middot; <a href="<?php echo e($details['website']); ?>" class="text-white" target="_blank"><?php echo e($details['website']); ?></a><?php endif; ?>
      </p>
    </div>
    <a href="industry_profile.php" class="btn btn-accent btn-sm"><i class="fa-solid fa-pen"></i> Update About / Company</a>
  </div>
</div>

<!-- Quick actions -->
<div class="row g-3 mb-4">
  <div class="col-md-4">
    <div class="stat-card bg-navy">
      <div class="stat-icon"><i class="fa-solid fa-briefcase"></i></div>
      <div><div class="stat-num"><?php echo count($myPostings); ?></div><div class="stat-label">Internships / Jobs Posted</div></div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="stat-card bg-accent">
      <div class="stat-icon"><i class="fa-solid fa-user-graduate"></i></div>
      <div><div class="stat-num"><?php echo $totalApps; ?></div><div class="stat-label">Applications Received</div></div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="stat-card bg-success-2">
      <div class="stat-icon"><i class="fa-solid fa-star"></i></div>
      <div><div class="stat-num"><?php echo $shortlisted; ?></div><div class="stat-label">Shortlisted Candidates</div></div>
    </div>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-md-6">
    <a href="industry_post.php?type=internship" class="text-decoration-none">
      <div class="card card-hover h-100"><div class="card-body d-flex align-items-center gap-3">
        <div class="stat-icon bg-navy text-white" style="width:52px;height:52px;border-radius:12px;display:flex;align-items:center;justify-content:center;"><i class="fa-solid fa-rocket"></i></div>
        <div><h6 class="fw-bold mb-1 text-dark">Post Internship</h6><p class="small text-muted-2 mb-0">Create a new internship opening with skills, salary, age limit, posts &amp; test.</p></div>
        <i class="fa-solid fa-chevron-right ms-auto text-muted"></i>
      </div></div>
    </a>
  </div>
  <div class="col-md-6">
    <a href="industry_post.php?type=job" class="text-decoration-none">
      <div class="card card-hover h-100"><div class="card-body d-flex align-items-center gap-3">
        <div class="stat-icon bg-accent text-dark" style="width:52px;height:52px;border-radius:12px;display:flex;align-items:center;justify-content:center;"><i class="fa-solid fa-briefcase"></i></div>
        <div><h6 class="fw-bold mb-1 text-dark">Post Job</h6><p class="small text-muted-2 mb-0">Create a full-time job opening with the same options.</p></div>
        <i class="fa-solid fa-chevron-right ms-auto text-muted"></i>
      </div></div>
    </a>
  </div>
</div>

<!-- My postings -->
<div class="card">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5 class="card-title mb-0"><i class="fa-solid fa-clipboard-list me-2 text-primary"></i>My Posted Openings</h5>
      <a href="industry_post.php" class="btn btn-sm btn-navy"><i class="fa-solid fa-plus"></i> New Posting</a>
    </div>
    <?php if (count($myPostings) === 0): ?>
      <p class="text-muted-2 mb-0">You haven't posted anything yet. Click "Post Internship / Job" to get started.</p>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead class="table-light">
            <tr><th>Title</th><th>Type</th><th>Mode</th><th>Salary</th><th>Posts</th><th>Applications</th><th>Shortlisted</th><th>Posted On</th></tr>
          </thead>
          <tbody>
            <?php foreach ($myPostings as $p): ?>
              <tr>
                <td class="fw-semibold"><?php echo e($p['title']); ?></td>
                <td><span class="badge <?php echo $p['type']==='job' ? 'text-bg-warning' : 'text-bg-primary'; ?>"><?php echo e(ucfirst($p['type'])); ?></span></td>
                <td><?php echo e($p['mode'] ?: '-'); ?></td>
                <td><?php echo e($p['salary'] ?: '-'); ?></td>
                <td><?php echo $p['no_of_posts']; ?></td>
                <td><a href="industry_applications.php?posting=<?php echo $p['id']; ?>" class="text-decoration-none"><?php echo $p['app_count']; ?></a></td>
                <td><span class="badge text-bg-success"><?php echo $p['short_count']; ?></span></td>
                <td><?php echo date('d M Y', strtotime($p['posted_at'])); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php require 'includes/page_footer_role.php'; ?>
