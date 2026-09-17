<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();

$user = current_user();
if ($user['role'] !== 'learner') {
    http_response_code(403);
    echo "Only learners can book sessions.";
    exit;
}

$session_id = (int)($_POST['session_id'] ?? $_GET['session_id'] ?? 0);
$message = '';
$error = '';

$stmt = $mysqli->prepare("SELECT s.*, u.name AS instructor_name,
    (SELECT COUNT(*) FROM bookings b WHERE b.session_id = s.id AND b.status='confirmed') AS confirmed_count
    FROM sessions s JOIN users u ON u.id = s.instructor_id WHERE s.id = ?");
$stmt->bind_param('i', $session_id);
$stmt->execute();
$session = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$session) {
    $error = 'Session not found.';
} elseif ($session['status'] !== 'active') {
    $error = 'This session is not accepting new bookings.';
} elseif ($session['event_date'] < date('Y-m-d')) {
    $error = 'This session already happened.';
} elseif ($session['instructor_id'] == $user['id']) {
    $error = 'You cannot book your own session.';
}

if (!$error) {
    $check = $mysqli->prepare("SELECT id FROM bookings WHERE session_id=? AND learner_id=?");
    $check->bind_param('ii', $session_id, $user['id']);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        $error = 'You are already booked for this session.';
    }
    $check->close();
}

if (!$error) {
    $status = ($session['confirmed_count'] >= $session['capacity']) ? 'pending' : 'confirmed';
    $insert = $mysqli->prepare("INSERT INTO bookings (session_id, learner_id, status) VALUES (?,?,?)");
    $insert->bind_param('iis', $session_id, $user['id'], $status);
    $insert->execute();

    if ($status === 'confirmed') {
        $upd = $mysqli->prepare("UPDATE sessions SET participants_confirmed = participants_confirmed + 1 WHERE id = ?");
        $upd->bind_param('i', $session_id);
        $upd->execute();
    }

    $message = $status === 'confirmed' ? 'You are in! See you there.' : 'Added to the waitlist. We will confirm when a spot opens.';
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container" style="max-width: 700px;">
  <div class="card-neo fade-up">
    <div class="pill mb-2">Booking</div>
    <?php if ($error): ?>
      <h3 class="text-danger">Oops</h3>
      <p class="text-muted-soft"><?= e($error) ?></p>
    <?php else: ?>
      <h3 class="text-success">Booked: <?= e($session['title']) ?></h3>
      <p class="text-muted-soft">Status: <?= e(ucfirst($status)) ?></p>
      <div class="subtle-card mt-3">
        <div><?= e($session['location']) ?> · <?= e($session['event_date']) ?></div>
        <small class="text-muted-soft">Instructor: <?= e($session['instructor_name']) ?></small>
      </div>
      <p class="mt-3"><?= e($message) ?></p>
    <?php endif; ?>

    <div class="d-flex gap-2 mt-3">
      <a class="btn btn-outline-light" href="session_view.php?id=<?= $session_id ?>">Back to session</a>
      <a class="btn btn-primary" href="learner_dashboard.php">My dashboard</a>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
