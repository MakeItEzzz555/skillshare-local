<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

$id = (int)($_GET['id'] ?? 0);

$stmt = $mysqli->prepare("SELECT s.*, u.name AS instructor_name, u.city AS instructor_city,
    (SELECT AVG(r.rating) FROM ratings r WHERE r.session_id = s.id) AS avg_rating,
    (SELECT COUNT(*) FROM bookings b WHERE b.session_id = s.id AND b.status='confirmed') AS confirmed
    FROM sessions s
    JOIN users u ON s.instructor_id = u.id
    WHERE s.id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$session = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$session) {
    http_response_code(404);
    echo "Session not found";
    exit;
}

$user = current_user();
$booking = null;
$myRating = null;

if ($user) {
    $bookStmt = $mysqli->prepare("SELECT status FROM bookings WHERE session_id=? AND learner_id=?");
    $bookStmt->bind_param('ii', $id, $user['id']);
    $bookStmt->execute();
    $booking = $bookStmt->get_result()->fetch_assoc();
    $bookStmt->close();

    $rateStmt = $mysqli->prepare("SELECT rating FROM ratings WHERE session_id=? AND learner_id=?");
    $rateStmt->bind_param('ii', $id, $user['id']);
    $rateStmt->execute();
    $myRating = $rateStmt->get_result()->fetch_assoc();
    $rateStmt->close();
}

$capacityLeft = max(0, $session['capacity'] - $session['confirmed']);
$canBook = $user && $user['role'] === 'learner' && !$booking && $session['status'] === 'active';
$photo = $session['photo'] ?: 'https://images.unsplash.com/photo-1521737604893-d14cc237f11d?auto=format&fit=crop&w=1200&q=80';

include __DIR__ . '/../includes/header.php';
?>

<div class="container">
  <div class="card-neo card-ghost no-sweep fade-up mb-3">
    <div class="row g-3 align-items-center">
      <div class="col-md-6">
        <div class="pill mb-2"><?= e($session['category']) ?> · <?= e($session['duration']) ?></div>
        <h1 class="page-title mb-2"><?= e($session['title']) ?></h1>
        <p class="text-muted-soft">Hosted by <?= e($session['instructor_name']) ?><?php if($session['instructor_city']): ?> · <?= e($session['instructor_city']) ?><?php endif; ?></p>
        <div class="d-flex gap-2 align-items-center flex-wrap mt-3">
          <div class="badge-soft">Date: <?= e($session['event_date']) ?></div>
          <div class="badge-soft">Time: <?php if($session['start_time'] && $session['end_time']): ?><?= e(substr($session['start_time'],0,5)) ?> - <?= e(substr($session['end_time'],0,5)) ?><?php else: ?>TBD<?php endif; ?></div>
          <div class="badge-soft">Location: <?= e($session['location']) ?></div>
          <div class="badge-soft">Capacity: <?= e($session['capacity']) ?> (<?= $capacityLeft ?> left)</div>
          <div class="badge-soft">Rating: <?= $session['avg_rating'] ? number_format($session['avg_rating'],1).'/5' : '—' ?></div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="rounded-4 overflow-hidden" style="box-shadow: var(--shadow); max-height: 280px;">
          <img src="<?= e($photo) ?>" alt="Session" class="img-fluid w-100" style="object-fit: cover;">
        </div>
      </div>
    </div>
  </div>

  <div class="row g-3">
    <div class="col-lg-8 fade-up">
      <div class="card-neo card-ghost no-sweep">
        <h5>What you'll learn</h5>
        <p class="text-muted-soft mt-2"><?= nl2br(e($session['description'])) ?></p>
        <?php if ($session['sustainability_impact']): ?>
          <div class="subtle-card mt-3">
            <strong>Sustainability impact</strong>
            <p class="text-muted-soft mb-0 mt-1"><?= nl2br(e($session['sustainability_impact'])) ?></p>
          </div>
        <?php endif; ?>
      </div>
    </div>
    <div class="col-lg-4 fade-up">
      <div class="card-neo card-ghost no-sweep">
        <h5 class="mb-3">Join this session</h5>
        <?php if (!$user): ?>
          <p class="text-muted-soft">Login to reserve your spot.</p>
          <a class="btn btn-primary w-100" href="login.php">Login / Register</a>
        <?php elseif ($booking): ?>
          <div class="alert-darkish mb-3">
            You are already booked. Status: <?= e(ucfirst($booking['status'])) ?>.
          </div>
          <a class="btn btn-outline-light w-100" href="learner_dashboard.php">Go to my dashboard</a>
        <?php elseif ($user['role'] !== 'learner'): ?>
          <div class="alert-darkish">Bookings are only for learners.</div>
        <?php elseif ($session['status'] !== 'active'): ?>
          <div class="alert-darkish">This session is closed.</div>
        <?php else: ?>
          <form method="post" action="book.php" class="d-grid gap-2">
            <input type="hidden" name="session_id" value="<?= $session['id'] ?>">
            <button class="btn btn-primary w-100">Book my seat</button>
          </form>
          <?php if ($capacityLeft <= 0): ?>
            <div class="alert-darkish mt-2">Currently full — you'll join the waitlist.</div>
          <?php endif; ?>
          <?php if ($session['fee'] > 0): ?>
            <div class="text-muted-soft small mt-2">Fee: $<?= number_format($session['fee'],2) ?></div>
          <?php else: ?>
            <div class="text-muted-soft small mt-2">This session is free</div>
          <?php endif; ?>
        <?php endif; ?>

        <?php if ($booking && $user['role']==='learner'): ?>
          <a class="btn btn-outline-light w-100 mt-2" href="rate.php?session_id=<?= $session['id'] ?>">
            <?= $myRating ? 'Update rating' : 'Leave a rating' ?>
          </a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
