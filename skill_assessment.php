<?php
// skill_assessment.php - Skill assessment quiz
require_once 'includes/db_connect.php';
require_role('student');

$uid = (int) $_SESSION['user_id'];
$message = '';

// Shared MCQ bank (also used for industry test autogeneration)
require_once 'includes/question_bank.php';

// Handle assessment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_assessment'])) {
    // We'll track score per skill. Build a lookup array.
    // For each skill assessed, count correct answers.
    $perSkill = array();
    foreach ($_POST as $key => $val) {
        if (preg_match('/^q_(.+)_(\d+)$/', $key, $m)) {
            $skill = $m[1];
            $qi = (int)$m[2];
            $answerIdx = (int)$val;
            if (!isset($perSkill[$skill])) $perSkill[$skill] = array('correct' => 0, 'total' => 0);
            $questions = $question_bank[$skill] ?? array();
            $perSkill[$skill]['total']++;
            if (isset($questions[$qi]) && $questions[$qi]['a'] === $answerIdx) {
                $perSkill[$skill]['correct']++;
            }
        }
    }

    if (count($perSkill) > 0) {
        // Delete previous assessments for this student, then insert new ones
        $d = db_query('DELETE FROM student_skills WHERE student_id = ?', 'i', array($uid));
        mysqli_stmt_close($d);

        $saved = 0;
        foreach ($perSkill as $skill => $data) {
            if ($data['total'] === 0) continue;
            $score = round(($data['correct'] / $data['total']) * 100);

            // Get or create skill id
            $st = db_query('SELECT id FROM skills WHERE skill_name = ?', 's', array($skill));
            mysqli_stmt_store_result($st);
            mysqli_stmt_bind_result($st, $sid);
            $exists = mysqli_stmt_fetch($st);
            mysqli_stmt_close($st);

            if (!$exists) {
                $ins = db_query('INSERT INTO skills (skill_name) VALUES (?)', 's', array($skill));
                $sid = mysqli_insert_id($conn);
                mysqli_stmt_close($ins);
            }

            $ins2 = db_query(
                'INSERT INTO student_skills (student_id, skill_id, score) VALUES (?, ?, ?)',
                'iii', array($uid, $sid, $score));
            mysqli_stmt_close($ins2);
            $saved++;
        }

        if ($saved > 0) {
            header('Location: student_dashboard.php?assess=1');
            exit;
        }
    }
}

$pageTitle = 'Skill Assessment';
$activeNav = 'skill';
require 'includes/page_header.php';

// load existing assessment
$existing = array();
$st = db_query(
  'SELECT s.skill_name, ss.score FROM student_skills ss JOIN skills s ON s.id = ss.skill_id WHERE ss.student_id = ?',
  'i', array($uid));
$res = mysqli_stmt_get_result($st);
while ($row = mysqli_fetch_assoc($res)) $existing[$row['skill_name']] = (int)$row['score'];
mysqli_stmt_close($st);
?>

<?php if (isset($_GET['assess'])): ?>
  <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> Your assessment has been saved successfully!</div>
<?php endif; ?>

<div class="row g-4">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-body">
        <h5 class="card-title mb-1"><i class="fa-solid fa-clipboard-question me-2 text-primary"></i>Skill Assessment Quiz</h5>
        <p class="text-muted-2 small mb-4">Answer the questions for each skill you'd like to assess. Your score is saved to your profile automatically.</p>

        <form method="post" action="skill_assessment.php">
          <?php foreach ($question_bank as $skill => $questions): ?>
            <div class="border rounded p-3 mb-4">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0 text-primary"><?php echo e($skill); ?></h6>
                <?php if (isset($existing[$skill])): ?>
                  <span class="badge text-bg-secondary">Last score: <?php echo $existing[$skill]; ?>/100</span>
                <?php endif; ?>
              </div>
              <?php foreach ($questions as $qi => $qq): ?>
                <div class="mb-3">
                  <div class="fw-semibold mb-2"><?php echo ($qi+1); ?>. <?php echo e($qq['q']); ?></div>
                  <div class="row g-2">
                    <?php foreach ($qq['o'] as $oi => $opt): ?>
                      <div class="col-md-6">
                        <div class="form-check">
                          <input class="form-check-input" type="radio" name="q_<?php echo e($skill); ?>_<?php echo $qi; ?>" value="<?php echo $oi; ?>" required>
                          <label class="form-check-label"><?php echo e($opt); ?></label>
                        </div>
                      </div>
                    <?php endforeach; ?>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endforeach; ?>

          <button type="submit" name="submit_assessment" class="btn btn-navy btn-lg w-100">
            <i class="fa-solid fa-check-double"></i> Submit Assessment &amp; Save Scores
          </button>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="card mb-4">
      <div class="card-body">
        <h6 class="card-title"><i class="fa-solid fa-circle-info me-2 text-primary"></i>How scoring works</h6>
        <p class="small text-muted-2 mb-0">
          Each skill has 3 multiple-choice questions. Your score for each skill is the percentage of
          correct answers (0–100). Scores of 70+ are considered strengths; below that are areas to improve.
        </p>
      </div>
    </div>
    <?php if (count($existing) > 0): ?>
      <div class="card">
        <div class="card-body">
          <h6 class="card-title"><i class="fa-solid fa-chart-simple me-2 text-primary"></i>Your Current Scores</h6>
          <?php foreach ($existing as $name => $score): ?>
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span><?php echo e($name); ?></span>
              <span class="badge <?php echo $score >= 70 ? 'text-bg-success' : 'text-bg-warning'; ?>"><?php echo $score; ?>/100</span>
            </div>
            <div class="progress mb-3" style="height:6px;">
              <div class="progress-bar" style="width: <?php echo $score; ?>%; background: <?php echo $score >= 70 ? '#2e9e5b' : '#f5a623'; ?>"></div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php require 'includes/page_footer.php'; ?>
