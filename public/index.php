<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Auto-mark completed sessions
$mysqli->query("UPDATE sessions SET status='completed' WHERE event_date < CURDATE() AND status='active'");

$categoriesRes = $mysqli->query("SELECT DISTINCT category FROM sessions WHERE category IS NOT NULL AND category <> '' ORDER BY category");
$categories = $categoriesRes ? $categoriesRes->fetch_all(MYSQLI_ASSOC) : [];

$q = trim($_GET['q'] ?? '');
$category = trim($_GET['category'] ?? '');
$location = trim($_GET['location'] ?? '');
$fee = trim($_GET['fee'] ?? '');

$sql = "
  SELECT s.*, u.name AS instructor_name,
    (SELECT AVG(r.rating) FROM ratings r WHERE r.session_id = s.id) AS avg_rating
  FROM sessions s
  JOIN users u ON s.instructor_id = u.id
  WHERE s.status = 'active'
";

$params = [];
$types = '';

if ($q !== '') {
    $sql .= " AND (s.title LIKE ? OR s.description LIKE ?)";
    $like = "%$q%";
    $params[] = $like;
    $params[] = $like;
    $types .= 'ss';
}

if ($category !== '') {
    $sql .= " AND s.category = ?";
    $params[] = $category;
    $types .= 's';
}

if ($location !== '') {
    $sql .= " AND s.location LIKE ?";
    $params[] = "%$location%";
    $types .= 's';
}

if ($fee === 'free') {
    $sql .= " AND s.fee = 0";
}
if ($fee === 'paid') {
    $sql .= " AND s.fee > 0";
}

$stmt = $mysqli->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

include __DIR__ . '/../includes/header.php';
?>

<div class="container">
  <div class="card-neo card-ghost no-sweep filter-card mb-4">
    <h1 class="page-title">Discover Sustainable Skills</h1>
    <p class="page-subtitle">Learn from your local community · Teach for a greener future</p>

    <form method="get" class="row g-2 mt-4 align-items-center">
      <div class="col-md-3">
        <label class="text-muted-soft small mb-1">Search</label>
        <input type="text" name="q" class="form-control" placeholder="Keyword (e.g. solar, compost)"
               value="<?= e($q) ?>">
      </div>
      <div class="col-md-3">
        <label class="text-muted-soft small mb-1">Category</label>
        <select name="category" class="form-select">
          <option value="">Any category</option>
          <?php foreach($categories as $c): $val = $c['category']; ?>
            <option value="<?= e($val) ?>" <?= $category===$val?'selected':'' ?>><?= e($val) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="text-muted-soft small mb-1">Location</label>
        <?php
          $cities = ['Limassol','Nicosia','Pafos','Larnaca'];
          $platforms = ['Zoom','Teams','Discord','Other'];
          $selectedCity = in_array($location, $cities, true) ? $location : '';
          $selectedPlatform = in_array($location, $platforms, true) || $location === 'Online' ? $location : '';
        ?>
        <div class="d-flex gap-2 flex-wrap">
          <div class="dropdown flex-grow-1">
            <button class="btn btn-ghost w-100 dropdown-toggle js-city-btn" type="button" data-bs-toggle="dropdown" data-default="City (any)">
              <?= $selectedCity ? 'City: '.e($selectedCity) : 'City (any)' ?>
            </button>
            <ul class="dropdown-menu w-100">
              <li><a class="dropdown-item js-city-option" href="#" data-value="">Any city</a></li>
              <?php foreach ($cities as $cityOpt): ?>
                <li><a class="dropdown-item js-city-option" href="#" data-value="<?= e($cityOpt) ?>"><?= e($cityOpt) ?></a></li>
              <?php endforeach; ?>
            </ul>
          </div>

          <div class="dropdown flex-grow-1">
            <button class="btn btn-ghost w-100 dropdown-toggle js-online-btn" type="button" data-bs-toggle="dropdown" data-default="Online platform">
              <?= $selectedPlatform ? 'Online: '.e($selectedPlatform) : 'Online platform' ?>
            </button>
            <ul class="dropdown-menu w-100">
              <li><a class="dropdown-item js-online-option" href="#" data-value="">Any platform</a></li>
              <?php foreach ($platforms as $plat): ?>
                <li><a class="dropdown-item js-online-option" href="#" data-value="<?= e($plat) ?>"><?= e($plat) ?></a></li>
              <?php endforeach; ?>
            </ul>
          </div>
        </div>
        <input type="hidden" name="location" id="locationValue" value="<?= e($location) ?>">
      </div>
      <div class="col-md-2">
        <label class="text-muted-soft small mb-1">Fee</label>
        <select name="fee" class="form-select">
          <option value="">Any Fee</option>
          <option value="free" <?= $fee==='free'?'selected':'' ?>>Free</option>
          <option value="paid" <?= $fee==='paid'?'selected':'' ?>>Paid</option>
        </select>
      </div>
      <div class="col-md-1 d-grid">
        <button class="btn btn-primary btn-pill">Go</button>
      </div>
    </form>
  </div>

  <div class="session-grid">
    <?php while($s = $result->fetch_assoc()): ?>
      <div class="card-neo">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <span class="session-tag"><?= e($s['category']) ?></span>
          <span class="session-price">
            <?= $s['fee'] > 0 ? '$'.number_format($s['fee'],2) : 'Free' ?>
          </span>
        </div>

        <h5 class="mt-2"><?= e($s['title']) ?></h5>

        <div class="session-location mt-1">
          <?= e($s['location']) ?> · <?= e($s['instructor_name']) ?>
        </div>

        <p class="text-muted-soft mt-2" style="font-size:0.9rem;">
          <?= e(mb_strimwidth($s['description'], 0, 120, '...')) ?>
        </p>

        <div class="d-flex justify-content-between align-items-center mt-3">
          <small class="text-muted-soft">
            Rating: <?= $s['avg_rating'] ? number_format($s['avg_rating'],1).'/5' : '—' ?>
          </small>
          <a href="session_view.php?id=<?= $s['id'] ?>" class="btn btn-outline-light btn-sm btn-pill">
            View
          </a>
        </div>
      </div>
    <?php endwhile; ?>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
