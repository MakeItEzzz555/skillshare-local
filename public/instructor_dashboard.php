<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('instructor');

$user = current_user();

$totals = [
    'sessions' => (int)$mysqli->query("SELECT COUNT(*) AS c FROM sessions WHERE instructor_id = {$user['id']}")->fetch_assoc()['c'],
    'upcoming' => (int)$mysqli->query("SELECT COUNT(*) AS c FROM sessions WHERE instructor_id = {$user['id']} AND event_date >= CURDATE() AND status='active'")->fetch_assoc()['c'],
    'learners' => (int)$mysqli->query("SELECT COUNT(*) AS c FROM bookings b JOIN sessions s ON b.session_id = s.id WHERE s.instructor_id = {$user['id']} AND b.status='confirmed'")->fetch_assoc()['c'],
];

$sessionsStmt = $mysqli->prepare("SELECT s.*, (SELECT AVG(r.rating) FROM ratings r WHERE r.session_id=s.id) AS avg_rating,
    (SELECT COUNT(*) FROM bookings b WHERE b.session_id = s.id AND b.status='confirmed') AS confirmed
    FROM sessions s WHERE s.instructor_id = ? ORDER BY s.event_date DESC");
$sessionsStmt->bind_param('i', $user['id']);
$sessionsStmt->execute();
$sessions = $sessionsStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$bookingsStmt = $mysqli->prepare("SELECT b.*, s.title, s.event_date, u.name AS learner_name FROM bookings b
    JOIN sessions s ON b.session_id = s.id
    JOIN users u ON b.learner_id = u.id
    WHERE s.instructor_id = ?
    ORDER BY b.id DESC LIMIT 8");
$bookingsStmt->bind_param('i', $user['id']);
$bookingsStmt->execute();
$bookings = $bookingsStmt->get_result()->fetch_all(MYSQLI_ASSOC);

include __DIR__ . '/../includes/header.php';
?>

<div class="container">
  <div class="hero-spotlight fade-up mb-3">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">
      <div>
        <div class="pill mb-2">Welcome back, <?= e($user['name']) ?></div>
        <h1 class="page-title">Your teaching studio</h1>
        <p class="page-subtitle">Keep sessions sharp and learners engaged.</p>
      </div>
      <div class="d-flex gap-2 mt-3 mt-md-0">
        <div class="badge-soft badge-shine">Sessions: <?= $totals['sessions'] ?></div>
        <div class="badge-soft badge-shine">Upcoming: <?= $totals['upcoming'] ?></div>
        <div class="badge-soft badge-shine">Learners: <?= $totals['learners'] ?></div>
      </div>
    </div>
  </div>

  <div class="d-flex justify-content-between align-items-center mb-3 fade-up">
    <div class="pill">Manage your experiences</div>
    <a class="btn btn-primary" href="create_sessions.php">+ New session</a>
  </div>

  <div class="card-neo no-sweep fade-up mb-4">
    <div class="subtle-card">
      <table class="table-darkish">
        <thead>
        <tr>
          <th>Title</th><th>Date</th><th>Capacity</th><th>Rating</th><th>Status</th><th></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach($sessions as $s): ?>
          <tr>
            <td>
              <?= e($s['title']) ?><br>
              <small class="text-muted-soft"><?= e($s['category']) ?></small>
            </td>
            <td class="text-muted-soft"><?= e($s['event_date']) ?></td>
            <td><?= (int)$s['confirmed'] ?>/<?= (int)$s['capacity'] ?></td>
            <td class="text-muted-soft"><?= $s['avg_rating'] ? number_format($s['avg_rating'],1).'/5' : '—' ?></td>
            <td>
              <span class="status-dot <?= $s['status']==='completed'?'status-completed':'status-active' ?>"></span>
              <span class="text-muted-soft ms-1 text-capitalize"><?= e($s['status']) ?></span>
            </td>
            <td class="text-end">
              <a class="btn btn-sm btn-outline-light" href="session_view.php?id=<?= $s['id'] ?>">View</a>
              <a class="btn btn-sm btn-primary" href="edit_session.php?id=<?= $s['id'] ?>">Edit</a>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card-neo no-sweep fade-up">
    <div class="d-flex justify-content-between align-items-center mb-2">
      <h5 class="mb-0">Latest bookings</h5>
      <span class="chip">Recent 8</span>
    </div>
    <div class="subtle-card">
      <table class="table-darkish">
        <thead>
        <tr>
          <th>Learner</th><th>Session</th><th>Date</th><th>Status</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach($bookings as $b): ?>
          <tr>
            <td><?= e($b['learner_name']) ?></td>
            <td><?= e(mb_strimwidth($b['title'],0,30,'…')) ?></td>
            <td class="text-muted-soft"><?= e($b['event_date']) ?></td>
            <td>
              <span class="status-dot <?= $b['status']==='declined'?'status-declined':($b['status']==='confirmed'?'status-active':'status-pending') ?>"></span>
              <span class="text-muted-soft text-capitalize ms-1"><?= e($b['status']) ?></span>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php if (!$bookings): ?>
        <div class="text-muted-soft">No bookings yet.</div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
