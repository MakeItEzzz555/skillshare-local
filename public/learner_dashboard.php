<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('learner');

$user = current_user();

$bookingsStmt = $mysqli->prepare("SELECT b.*, s.title, s.category, s.event_date, s.location, s.id AS session_id, s.fee, u.name AS instructor_name,
    (SELECT rating FROM ratings r WHERE r.session_id = s.id AND r.learner_id = b.learner_id LIMIT 1) AS my_rating
    FROM bookings b
    JOIN sessions s ON b.session_id = s.id
    JOIN users u ON u.id = s.instructor_id
    WHERE b.learner_id = ?
    ORDER BY s.event_date ASC");
$bookingsStmt->bind_param('i', $user['id']);
$bookingsStmt->execute();
$bookings = $bookingsStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$recommendedStmt = $mysqli->prepare("SELECT s.*, u.name AS instructor_name,
    (SELECT AVG(r.rating) FROM ratings r WHERE r.session_id = s.id) AS avg_rating
    FROM sessions s
    JOIN users u ON u.id = s.instructor_id
    WHERE s.status='active' AND s.id NOT IN (SELECT session_id FROM bookings WHERE learner_id = ?)
    ORDER BY s.event_date ASC
    LIMIT 4");
$recommendedStmt->bind_param('i', $user['id']);
$recommendedStmt->execute();
$recommended = $recommendedStmt->get_result()->fetch_all(MYSQLI_ASSOC);

include __DIR__ . '/../includes/header.php';
?>

<div class="container">
  <div class="hero-spotlight fade-up mb-3">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">
      <div>
        <div class="pill mb-2">Hi <?= e($user['name']) ?></div>
        <h1 class="page-title">Your learning map</h1>
        <p class="page-subtitle">Track bookings and discover new skill sessions.</p>
      </div>
      <div class="badge-soft badge-shine">City: <?= e($user['city'] ?: 'Anywhere') ?></div>
    </div>
  </div>

  <div class="card-neo no-sweep fade-up mb-4">
    <div class="d-flex justify-content-between align-items-center mb-2">
      <h5 class="mb-0">My bookings</h5>
      <span class="chip">What's next</span>
    </div>
    <div class="subtle-card">
      <table class="table-darkish">
        <thead><tr><th>Session</th><th>Date</th><th>Location</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach($bookings as $b): ?>
          <tr>
            <td>
              <?= e($b['title']) ?><br>
              <small class="text-muted-soft">by <?= e($b['instructor_name']) ?></small>
            </td>
            <td class="text-muted-soft"><?= e($b['event_date']) ?></td>
            <td class="text-muted-soft"><?= e($b['location']) ?></td>
            <td>
              <span class="status-dot <?= $b['status']==='declined'?'status-declined':($b['status']==='confirmed'?'status-active':'status-pending') ?>"></span>
              <span class="text-muted-soft text-capitalize ms-1"><?= e($b['status']) ?></span>
            </td>
            <td class="text-end">
              <a class="btn btn-sm btn-outline-light" href="session_view.php?id=<?= $b['session_id'] ?>">View</a>
              <a class="btn btn-sm btn-primary" href="rate.php?session_id=<?= $b['session_id'] ?>"><?= $b['my_rating'] ? 'Update rating' : 'Rate' ?></a>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php if (!$bookings): ?>
        <div class="text-muted-soft">No bookings yet. Start exploring below.</div>
      <?php endif; ?>
    </div>
  </div>

  <div class="d-flex justify-content-between align-items-center mb-2 fade-up">
    <h5 class="mb-0">Recommended for you</h5>
    <a class="btn btn-outline-light btn-sm" href="index.php">Browse all</a>
  </div>
  <div class="session-grid fade-up">
    <?php foreach($recommended as $s): ?>
      <div class="card-neo">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <span class="session-tag"><?= e($s['category']) ?></span>
          <span class="session-price"><?= $s['fee'] > 0 ? '$'.number_format($s['fee'],2) : 'Free' ?></span>
        </div>
        <h5><?= e($s['title']) ?></h5>
        <div class="session-location"><?= e($s['location']) ?> · <?= e($s['instructor_name']) ?></div>
        <p class="text-muted-soft mt-2" style="font-size:0.9rem;">
          <?= e(mb_strimwidth($s['description'], 0, 100, '...')) ?>
        </p>
        <div class="d-flex justify-content-between align-items-center mt-2">
          <small class="text-muted-soft">Rating: <?= $s['avg_rating'] ? number_format($s['avg_rating'],1).'/5' : '—' ?></small>
          <a class="btn btn-sm btn-primary" href="session_view.php?id=<?= $s['id'] ?>">View</a>
        </div>
      </div>
    <?php endforeach; ?>
    <?php if (!$recommended): ?>
      <div class="text-muted-soft">You've joined everything! 🎉</div>
    <?php endif; ?>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
