<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();

$user = current_user();
$id = (int)($_GET['id'] ?? 0);

$stmt = $mysqli->prepare("SELECT * FROM sessions WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$session = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$session) {
    http_response_code(404);
    echo "Session not found";
    exit;
}

if ($user['role'] !== 'admin' && $session['instructor_id'] !== $user['id']) {
    http_response_code(403);
    echo "Forbidden";
    exit;
}

$errors = [];
$success = '';

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $delId = $session['id'];
    $mysqli->query("DELETE FROM bookings WHERE session_id = " . (int)$delId);
    $mysqli->query("DELETE FROM ratings WHERE session_id = " . (int)$delId);
    $delStmt = $mysqli->prepare("DELETE FROM sessions WHERE id = ?");
    $delStmt->bind_param('i', $delId);
    if ($delStmt->execute()) {
        header('Location: instructor_dashboard.php');
        exit;
    } else {
        $errors[] = 'Delete failed: ' . $delStmt->error;
    }
}

// Defaults for sticky values
$title = $session['title'];
$category = $session['category'];
$description = $session['description'];
$duration = $session['duration'];
$fee = $session['fee'];
$location = $session['location'];
$impact = $session['sustainability_impact'];
$capacity = $session['capacity'];
$event_date = $session['event_date'];
$statusSel = $session['status'];
$photo = $session['photo'];
$start_time = $session['start_time'];
$end_time = $session['end_time'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') !== 'delete') {
    $title = trim($_POST['title'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $duration = trim($_POST['duration'] ?? '');
    $fee = (float)($_POST['fee'] ?? 0);
    $location = trim($_POST['location'] ?? '');
    $impact = trim($_POST['sustainability_impact'] ?? '');
    $capacity = (int)($_POST['capacity'] ?? 10);
    $event_date = trim($_POST['event_date'] ?? '');
    $statusSel = $_POST['status'] === 'completed' ? 'completed' : 'active';
    $photo = trim($_POST['photo'] ?? '');
    $start_time = trim($_POST['start_time'] ?? '');
    $end_time = trim($_POST['end_time'] ?? '');

    $hoursList = ['08:00','09:00','10:00','11:00','12:00','13:00','14:00','15:00','16:00','17:00','18:00','19:00','20:00'];

    if ($title === '' || $description === '' || $event_date === '' || $location === '') {
        $errors[] = 'Title, description, date, and location are required.';
    }
    if (!in_array($start_time, $hoursList, true) || !in_array($end_time, $hoursList, true)) {
        $errors[] = 'Please pick start and end times.';
    } elseif (strtotime($start_time) >= strtotime($end_time)) {
        $errors[] = 'End time must be after start time.';
    }

    if ($capacity < 1) $capacity = 1;

    if (empty($errors)) {
        $stmt = $mysqli->prepare("UPDATE sessions SET title=?, category=?, description=?, duration=?, fee=?, location=?, sustainability_impact=?, capacity=?, event_date=?, start_time=?, end_time=?, status=?, photo=? WHERE id=?");
        $stmt->bind_param(
            'ssssdssisssssi',
            $title, $category, $description, $duration,
            $fee, $location, $impact, $capacity, $event_date, $start_time, $end_time, $statusSel, $photo, $id
        );
        $stmt->execute();
        $success = 'Saved changes.';
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container" style="max-width: 820px;">
  <div class="hero-spotlight fade-up mb-3">
    <div class="d-flex justify-content-between align-items-center">
      <div>
        <div class="pill mb-2">Edit session</div>
        <h1 class="page-title">Update details</h1>
        <p class="page-subtitle">Keep your learners informed with fresh info.</p>
      </div>
      <div class="badge-soft">Status: <?= e($statusSel) ?></div>
    </div>
  </div>

  <?php if ($errors): ?>
    <div class="alert-darkish mb-3 text-danger"><?= implode('<br>', array_map('e', $errors)) ?></div>
  <?php endif; ?>
  <?php if ($success): ?>
    <div class="alert-darkish mb-3 text-success"><?= e($success) ?></div>
  <?php endif; ?>

  <div class="card-neo fade-up">
    <form method="post" class="d-grid gap-3" id="editSessionForm">
      <div class="row g-3">
        <div class="col-md-8"><input class="form-control" name="title" value="<?= e($title) ?>" required></div>
        <div class="col-md-4"><input class="form-control" name="category" value="<?= e($category) ?>" placeholder="Category" required></div>
      </div>

      <textarea class="form-control" name="description" rows="4" required><?= e($description) ?></textarea>
      <textarea class="form-control" name="sustainability_impact" rows="2" placeholder="How does this help the planet?"><?= e($impact) ?></textarea>

      <div class="row g-3">
        <div class="col-md-4"><input class="form-control" name="duration" value="<?= e($duration) ?>" placeholder="Duration" required></div>
        <div class="col-md-4"><input class="form-control" type="number" step="0.01" name="fee" value="<?= e($fee) ?>" placeholder="Fee" required></div>
        <div class="col-md-4"><input class="form-control" type="number" min="1" name="capacity" value="<?= e($capacity) ?>" placeholder="Capacity" required></div>
      </div>

      <div class="row g-3">
        <div class="col-md-6"><input class="form-control" name="location" value="<?= e($location) ?>" required></div>
        <div class="col-md-3"><input class="form-control" type="date" name="event_date" value="<?= e($event_date) ?>" required></div>
        <div class="col-md-3">
          <select class="form-select" name="status">
            <option value="active" <?= $statusSel==='active'?'selected':'' ?>>Active</option>
            <option value="completed" <?= $statusSel==='completed'?'selected':'' ?>>Completed</option>
          </select>
        </div>
      </div>

      <div class="row g-3">
        <?php $hoursList = ['08:00','09:00','10:00','11:00','12:00','13:00','14:00','15:00','16:00','17:00','18:00','19:00','20:00']; ?>
        <div class="col-md-6">
          <label class="text-muted-soft small mb-1">Start time *</label>
          <select class="form-select" name="start_time" required>
            <option value="" <?= $start_time===''?'selected':'' ?>>Select start</option>
            <?php foreach($hoursList as $h): ?>
              <option value="<?= $h ?>" <?= $start_time===$h?'selected':'' ?>><?= $h ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6">
          <label class="text-muted-soft small mb-1">End time *</label>
          <select class="form-select" name="end_time" required>
            <option value="" <?= $end_time===''?'selected':'' ?>>Select end</option>
            <?php foreach($hoursList as $h): ?>
              <option value="<?= $h ?>" <?= $end_time===$h?'selected':'' ?>><?= $h ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <input class="form-control" name="photo" value="<?= e($photo) ?>" placeholder="Cover photo URL (optional)">
    </form>

    <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mt-2">
      <form method="post" onsubmit="return confirm('Delete this session?');">
        <input type="hidden" name="action" value="delete">
        <button type="submit" class="btn btn-ghost-danger">Delete</button>
      </form>
      <div class="d-flex gap-2">
        <a href="instructor_dashboard.php" class="btn btn-outline-light">Back</a>
        <button class="btn btn-primary" form="editSessionForm">Save changes</button>
      </div>
    </div>
  </div>
</div>

<script>
  (function() {
    const form = document.getElementById('editSessionForm');
    const startSel = document.querySelector('select[name="start_time"]');
    const endSel = document.querySelector('select[name="end_time"]');
    if (form && startSel && endSel) {
      form.addEventListener('submit', (e) => {
        if (startSel.value && endSel.value && startSel.value >= endSel.value) {
          e.preventDefault();
          alert('End time must be after start time.');
        }
      });
    }
  })();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
