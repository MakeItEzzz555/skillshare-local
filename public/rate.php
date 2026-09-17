<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('learner');

$user = current_user();
$session_id = (int)($_GET['session_id'] ?? $_POST['session_id'] ?? 0);
$errors = [];
$success = '';

$stmt = $mysqli->prepare("SELECT s.*, u.name AS instructor_name FROM sessions s JOIN users u ON s.instructor_id = u.id WHERE s.id=?");
$stmt->bind_param('i', $session_id);
$stmt->execute();
$session = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$session) {
    http_response_code(404);
    echo "Session not found";
    exit;
}

$today = date('Y-m-d');
$locked = $session['event_date'] >= $today;

$bookingCheck = $mysqli->prepare("SELECT id, status FROM bookings WHERE session_id=? AND learner_id=?");
$bookingCheck->bind_param('ii', $session_id, $user['id']);
$bookingCheck->execute();
$booking = $bookingCheck->get_result()->fetch_assoc();
$bookingCheck->close();

if (!$booking) {
    http_response_code(403);
    echo "You need to book this session before rating.";
    exit;
}

$existingStmt = $mysqli->prepare("SELECT rating, feedback FROM ratings WHERE session_id=? AND learner_id=?");
$existingStmt->bind_param('ii', $session_id, $user['id']);
$existingStmt->execute();
$existing = $existingStmt->get_result()->fetch_assoc();
$existingStmt->close();

if (!$locked && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = (int)($_POST['rating'] ?? 0);
    $feedback = trim($_POST['feedback'] ?? '');

    if ($rating < 1 || $rating > 5) {
        $errors[] = 'Rating must be between 1 and 5.';
    }

    if (empty($errors)) {
        if ($existing) {
            $stmt = $mysqli->prepare("UPDATE ratings SET rating=?, feedback=? WHERE session_id=? AND learner_id=?");
            $stmt->bind_param('isii', $rating, $feedback, $session_id, $user['id']);
        } else {
            $stmt = $mysqli->prepare("INSERT INTO ratings (rating, feedback, session_id, learner_id) VALUES (?,?,?,?)");
            $stmt->bind_param('isii', $rating, $feedback, $session_id, $user['id']);
        }
        $stmt->execute();
        $success = 'Thanks for sharing feedback!';
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container" style="max-width: 720px;">
  <div class="hero-spotlight fade-up mb-3">
    <div class="pill mb-2">Rate a session</div>
    <h1 class="page-title"><?= e($session['title']) ?></h1>
    <p class="page-subtitle">with <?= e($session['instructor_name']) ?></p>
  </div>

  <?php if ($locked): ?>
    <div class="alert-darkish text-warning mb-3">
      Ratings open after the session ends (<?= e($session['event_date']) ?>).
    </div>
  <?php endif; ?>

  <?php if ($errors): ?>
    <div class="alert-darkish text-danger mb-3"><?= implode('<br>', array_map('e',$errors)) ?></div>
  <?php endif; ?>
  <?php if ($success): ?>
    <div class="alert-darkish text-success mb-3"><?= e($success) ?></div>
  <?php endif; ?>

  <div class="card-neo fade-up">
    <form method="post" class="d-grid gap-3">
      <input type="hidden" name="session_id" value="<?= $session_id ?>">
      <div>
        <label class="text-muted-soft mb-2">Rating (1-5)</label>
        <input type="range" class="form-range" name="rating" min="1" max="5" value="<?= $existing['rating'] ?? 4 ?>" oninput="document.getElementById('ratingValue').textContent=this.value" <?= $locked ? 'disabled' : '' ?>>
        <div class="badge-soft mt-1">Score: <span id="ratingValue"><?= $existing['rating'] ?? 4 ?></span></div>
      </div>
      <textarea class="form-control" name="feedback" rows="4" placeholder="What worked? What could be better?" <?= $locked ? 'disabled' : '' ?>><?= e($existing['feedback'] ?? '') ?></textarea>
      <div class="d-flex gap-2 justify-content-end">
        <a class="btn btn-outline-light" href="session_view.php?id=<?= $session_id ?>">Back</a>
        <button class="btn btn-primary" <?= $locked ? 'disabled' : '' ?>>Submit rating</button>
      </div>
    </form>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
