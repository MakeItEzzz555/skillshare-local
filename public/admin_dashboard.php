<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');

$messages = [];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'toggle_suspend') {
        $userId = (int)($_POST['user_id'] ?? 0);
        $stmt = $mysqli->prepare("SELECT id, role, suspended FROM users WHERE id = ?");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $res = $stmt->get_result();
        $target = $res->fetch_assoc();
        $stmt->close();

        if (!$target) {
            $errors[] = 'User not found.';
        } elseif ($target['role'] === 'admin') {
            $errors[] = 'Cannot suspend administrators.';
        } else {
            $new = (int)!$target['suspended'];
            $stmt = $mysqli->prepare("UPDATE users SET suspended = ? WHERE id = ?");
            $stmt->bind_param('ii', $new, $userId);
            $stmt->execute();
            $messages[] = $new ? 'User suspended.' : 'User reinstated.';
        }
    }

    if ($action === 'session_status') {
        $sessionId = (int)($_POST['session_id'] ?? 0);
        $status = $_POST['status'] === 'completed' ? 'completed' : 'active';
        $stmt = $mysqli->prepare("UPDATE sessions SET status=? WHERE id=?");
        $stmt->bind_param('si', $status, $sessionId);
        $stmt->execute();
        $messages[] = 'Session status updated.';
    }
}

$counts = [
    'users' => (int)$mysqli->query("SELECT COUNT(*) AS c FROM users")->fetch_assoc()['c'],
    'suspended' => (int)$mysqli->query("SELECT COUNT(*) AS c FROM users WHERE suspended=1")->fetch_assoc()['c'],
    'sessions' => (int)$mysqli->query("SELECT COUNT(*) AS c FROM sessions")->fetch_assoc()['c'],
];

$users = $mysqli->query("SELECT id, name, email, role, city, suspended FROM users ORDER BY role ASC, name ASC")
    ->fetch_all(MYSQLI_ASSOC);

$sessions = $mysqli->query("SELECT s.*, u.name AS instructor_name,
    (SELECT COUNT(*) FROM bookings b WHERE b.session_id = s.id AND b.status='confirmed') AS confirmed
    FROM sessions s
    JOIN users u ON u.id = s.instructor_id
    ORDER BY s.event_date DESC")
    ->fetch_all(MYSQLI_ASSOC);

$impactTotals = $mysqli->query("SELECT s.category, IFNULL(SUM(f.co2_saved_per_participant_kg * (
    (SELECT COUNT(*) FROM bookings b WHERE b.session_id = s.id AND b.status='confirmed')
  )),0) AS total_kg
  FROM sessions s
  LEFT JOIN impact_factors f ON f.skill_category = s.category
  GROUP BY s.category
")->fetch_all(MYSQLI_ASSOC);

include __DIR__ . '/../includes/header.php';
?>

<div class="container">
  <div class="hero-spotlight fade-up">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">
      <div>
        <div class="pill mb-2">Admin Control Room</div>
        <h1 class="page-title">System health & moderation</h1>
        <p class="page-subtitle">Suspend users, tidy sessions, and keep SkillShare humming.</p>
      </div>
      <div class="d-flex gap-3 mt-3 mt-md-0">
        <div class="badge-soft badge-shine">Users: <?= $counts['users'] ?></div>
        <div class="badge-soft badge-shine">Active sessions: <?= $counts['sessions'] ?></div>
        <div class="badge-soft badge-shine">Suspended: <?= $counts['suspended'] ?></div>
      </div>
    </div>
  </div>

  <?php if ($messages): ?>
    <div class="alert-darkish mt-3"><?= implode('<br>', array_map('e', $messages)) ?></div>
  <?php endif; ?>
  <?php if ($errors): ?>
    <div class="alert-darkish mt-3 text-danger"><?= implode('<br>', array_map('e', $errors)) ?></div>
  <?php endif; ?>

  <div class="row g-3 mt-4">
    <div class="col-lg-6 fade-up">
      <div class="card-neo no-sweep">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <h5 class="mb-0">People</h5>
          <span class="chip">Suspend / reinstate</span>
        </div>
        <div class="subtle-card table-scroll">
          <table class="table-darkish">
            <thead>
            <tr>
              <th>Name</th><th>Role</th><th>City</th><th>Status</th><th>Action</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach($users as $u): ?>
              <tr>
                <td><?= e($u['name']) ?><br><small class="text-muted-soft"><?= e($u['email']) ?></small></td>
                <td><span class="badge-soft"><?= e($u['role']) ?></span></td>
                <td class="text-muted-soft"><?= e($u['city'] ?: '—') ?></td>
                <td>
                  <span class="status-dot <?= $u['suspended'] ? 'status-declined' : 'status-active' ?>"></span>
                  <span class="text-muted-soft ms-1"><?= $u['suspended'] ? 'Suspended' : 'Active' ?></span>
                </td>
                <td>
                  <?php if($u['role'] !== 'admin'): ?>
                    <form method="post" class="d-inline">
                      <input type="hidden" name="action" value="toggle_suspend">
                      <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                      <?php if($u['suspended']): ?>
                        <button class="btn btn-sm btn-success">Reinstate</button>
                      <?php else: ?>
                        <button class="btn btn-sm btn-outline-danger">Suspend</button>
                      <?php endif; ?>
                    </form>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="col-lg-6 fade-up">
      <div class="card-neo no-sweep">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <h5 class="mb-0">Sessions</h5>
          <span class="chip">Toggle status</span>
        </div>
        <div class="subtle-card" style="max-height: 520px; overflow:auto;">
          <table class="table-darkish">
            <thead>
            <tr>
              <th>Title</th><th>Instructor</th><th>Date</th><th>People</th><th>Status</th><th></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach($sessions as $s): ?>
              <tr>
                <td><?= e(mb_strimwidth($s['title'],0,28,'…')) ?></td>
                <td class="text-muted-soft"><?= e($s['instructor_name']) ?></td>
                <td class="text-muted-soft"><?= e($s['event_date']) ?></td>
                <td><?= (int)$s['confirmed'] ?>/<?= (int)$s['capacity'] ?></td>
                <td>
                  <span class="status-dot <?= $s['status']==='completed'?'status-completed':'status-active' ?>"></span>
                  <span class="text-muted-soft ms-1 text-capitalize"><?= e($s['status']) ?></span>
                </td>
                <td>
                  <form method="post" class="d-inline">
                    <input type="hidden" name="action" value="session_status">
                    <input type="hidden" name="session_id" value="<?= $s['id'] ?>">
                    <input type="hidden" name="status" value="<?= $s['status']==='completed'?'active':'completed' ?>">
                    <button class="btn btn-sm btn-outline-light">Mark <?= $s['status']==='completed'?'Active':'Done' ?></button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
        <div class="col-lg-12 fade-up">
      <div class="card-neo mt-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <h5 class="mb-0">Estimated CO₂ savings</h5>
          <span class="chip">Approximate</span>
        </div>
        <div class="subtle-card">
          <table class="table-darkish">
            <thead><tr><th>Category</th><th>Approx. saved (kg)</th><th>Approx. saved (tons)</th></tr></thead>
            <tbody>
            <?php foreach($impactTotals as $row): $kg = (float)$row['total_kg']; ?>
              <tr>
                <td><?= e($row['category'] ?: 'Uncategorized') ?></td>
                <td><?= number_format($kg, 0) ?></td>
                <td><?= number_format($kg/1000, 2) ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
          <?php if (!$impactTotals): ?><div class="text-muted-soft">No impact data yet.</div><?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
