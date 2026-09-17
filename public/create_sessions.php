
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('instructor');

$user = current_user();
$errors = [];
$success = '';

$categoriesRes = $mysqli->query("SELECT DISTINCT category FROM sessions WHERE category IS NOT NULL AND category <> '' ORDER BY category");
$categories = $categoriesRes ? $categoriesRes->fetch_all(MYSQLI_ASSOC) : [];

// Defaults for sticky form values
$title = $description = $impact = $duration = $photo = '';
$categorySelect = '';
$categoryCustom = '';
$feeSelect = '0';
$feeCustom = '';
$locationMode = 'city';
$cityChoice = '';
$onlineInfo = '';
$capacity = 10;
$event_date = '';
$start_time = '';
$end_time = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $categorySelect = trim($_POST['category_select'] ?? '');
    $categoryCustom = trim($_POST['category_custom'] ?? '');
    $category = $categorySelect === 'custom' ? $categoryCustom : $categorySelect;

    $description = trim($_POST['description'] ?? '');
    $impact = trim($_POST['sustainability_impact'] ?? '');

    $duration = trim($_POST['duration'] ?? '');

    $feeSelect = $_POST['fee_select'] ?? '0';
    $feeCustom = trim($_POST['fee_custom'] ?? '');
    $fee = $feeSelect === 'custom' ? (float)$feeCustom : (float)$feeSelect;

    $locationMode = $_POST['location_mode'] ?? 'city';
    $cityChoice = trim($_POST['city'] ?? '');
    $onlineInfo = trim($_POST['online_info'] ?? '');
    $location = $locationMode === 'online'
        ? ($onlineInfo !== '' ? $onlineInfo : 'Online')
        : $cityChoice;

    $capacity = (int)($_POST['capacity'] ?? 10);
    $event_date = trim($_POST['event_date'] ?? '');
    $start_time = trim($_POST['start_time'] ?? '');
    $end_time = trim($_POST['end_time'] ?? '');
    $photo = trim($_POST['photo'] ?? '');

    $hoursList = ['08:00','09:00','10:00','11:00','12:00','13:00','14:00','15:00','16:00','17:00','18:00','19:00','20:00'];

    if ($title === '' || $description === '' || $event_date === '') {
        $errors[] = 'Title, description, and date are required.';
    }
    if ($category === '') {
        $errors[] = 'Please select a category.';
    }
    if ($duration === '') {
        $errors[] = 'Please select a duration.';
    }
    if (!in_array($start_time, $hoursList, true) || !in_array($end_time, $hoursList, true)) {
        $errors[] = 'Please pick start and end times.';
    } elseif (strtotime($start_time) >= strtotime($end_time)) {
        $errors[] = 'End time must be after start time.';
    }
    if ($locationMode === 'city' && $cityChoice === '') {
        $errors[] = 'Please pick a city.';
    }
    if ($locationMode === 'online' && $onlineInfo === '') {
        $location = 'Online';
    }

    if ($capacity < 1) $capacity = 1;

    if (empty($errors)) {
        $status = 'active';
        $stmt = $mysqli->prepare("INSERT INTO sessions (instructor_id, title, category, description, duration, fee, location, sustainability_impact, capacity, event_date, start_time, end_time, status, photo) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        if (!$stmt) {
            $errors[] = 'Database error: ' . $mysqli->error;
        } else {
            $stmt->bind_param(
                'issssdssisssss',
                $user['id'], $title, $category, $description, $duration,
                $fee, $location, $impact, $capacity, $event_date, $start_time, $end_time, $status, $photo
            );
            if (!$stmt->execute()) {
                $errors[] = 'Database error: ' . $stmt->error;
            } else {
                $success = 'Session created. Redirecting to dashboard...';
                header('Refresh:1; url=instructor_dashboard.php');
            }
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container" style="max-width: 820px;">
  <div class="hero-spotlight fade-up mb-3">
    <div class="d-flex justify-content-between align-items-center">
      <div>
        <div class="pill mb-2">Create a session</div>
        <h1 class="page-title">Share what you know</h1>
        <p class="page-subtitle">Fill the details and inspire your learners.</p>
      </div>
      <div class="badge-soft badge-shine">Instructor: <?= e($user['name']) ?></div>
    </div>
  </div>

  <?php if ($errors): ?>
    <div class="alert-darkish mb-3"><?= implode('<br>', array_map('e', $errors)) ?></div>
  <?php endif; ?>
  <?php if ($success): ?>
    <div class="alert-darkish mb-3 text-success"><?= e($success) ?></div>
  <?php endif; ?>

  <div class="card-neo no-sweep fade-up">
    <form method="post" class="d-grid gap-3" id="createSessionForm">
      <div class="row g-3">
        <div class="col-md-8">
          <label class="text-muted-soft small mb-1">Title *</label>
          <input class="form-control" name="title" placeholder="Session title" value="<?= e($title) ?>" required>
        </div>
        <div class="col-md-4">
          <label class="text-muted-soft small mb-1">Category *</label>
          <select class="form-select" name="category_select" id="categorySelect" required>
            <option value="" <?= $categorySelect===''?'selected':'' ?>>Choose category</option>
            <?php foreach($categories as $c): $val = $c['category']; ?>
              <option value="<?= e($val) ?>" <?= $categorySelect===$val?'selected':'' ?>><?= e($val) ?></option>
            <?php endforeach; ?>
            <option value="custom" <?= $categorySelect==='custom'?'selected':'' ?>>Other...</option>
          </select>
          <input class="form-control mt-2 <?= $categorySelect==='custom'?'':'d-none' ?>" name="category_custom" id="categoryCustom" placeholder="Custom category" value="<?= e($categoryCustom) ?>">
        </div>
      </div>

      <label class="text-muted-soft small mb-1">Description *</label>
      <textarea class="form-control" name="description" rows="4" placeholder="What will learners experience?" required><?= e($description) ?></textarea>

      <label class="text-muted-soft small mb-1">Sustainability impact (optional)</label>
      <textarea class="form-control" name="sustainability_impact" rows="2" placeholder="How does this help the planet?" ><?= e($impact) ?></textarea>

      <div class="row g-3">
        <div class="col-md-4">
          <label class="text-muted-soft small mb-1">Duration *</label>
          <select class="form-select" name="duration" required>
            <option value="" <?= $duration===''?'selected':'' ?>>Select duration</option>
            <?php $durations = ['30 min','45 min','1 hour','1.5 hours','2 hours','3 hours','Half-day','Full-day'];
            foreach($durations as $d): ?>
              <option value="<?= e($d) ?>" <?= $duration===$d?'selected':'' ?>><?= e($d) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4">
          <label class="text-muted-soft small mb-1">Fee *</label>
          <select class="form-select" name="fee_select" id="feeSelect" required>
            <option value="0" <?= $feeSelect==='0'?'selected':'' ?>>Free</option>
            <option value="10" <?= $feeSelect==='10'?'selected':'' ?>>$10</option>
            <option value="20" <?= $feeSelect==='20'?'selected':'' ?>>$20</option>
            <option value="30" <?= $feeSelect==='30'?'selected':'' ?>>$30</option>
            <option value="custom" <?= $feeSelect==='custom'?'selected':'' ?>>Custom...</option>
          </select>
          <input class="form-control mt-2 <?= $feeSelect==='custom'?'':'d-none' ?>" type="number" step="0.01" min="0" name="fee_custom" id="feeCustom" placeholder="Enter fee" value="<?= e($feeCustom) ?>">
        </div>
        <div class="col-md-4">
          <label class="text-muted-soft small mb-1">Capacity *</label>
          <input class="form-control" type="number" name="capacity" min="1" value="<?= e($capacity) ?>" required>
        </div>
      </div>

      <div class="row g-3">
        <div class="col-md-6">
          <label class="text-muted-soft small mb-1">Location *</label>
          <div class="d-flex align-items-center gap-3 flex-wrap mb-2">
            <div class="form-check">
              <input class="form-check-input" type="radio" name="location_mode" id="locCityCreate" value="city" <?= $locationMode==='city'?'checked':'' ?> required>
              <label class="form-check-label" for="locCityCreate">City</label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="location_mode" id="locOnlineCreate" value="online" <?= $locationMode==='online'?'checked':'' ?> required>
              <label class="form-check-label" for="locOnlineCreate">Online</label>
            </div>
          </div>
          <div class="city-select-create <?= $locationMode==='online'?'d-none':'' ?>">
            <select class="form-select" name="city" id="citySelectCreate">
              <option value="" <?= $cityChoice===''?'selected':'' ?>>Choose city</option>
              <?php foreach(['Limassol','Nicosia','Pafos','Larnaca'] as $city): ?>
                <option value="<?= e($city) ?>" <?= $cityChoice===$city?'selected':'' ?>><?= e($city) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <input class="form-control mt-2 <?= $locationMode==='online'?'':'d-none' ?>" name="online_info" id="onlineInfoCreate" placeholder="Online link or platform" value="<?= e($onlineInfo) ?>">
        </div>
        <div class="col-md-6">
          <label class="text-muted-soft small mb-1">Event date *</label>
          <input class="form-control" type="date" name="event_date" value="<?= e($event_date) ?>" required>
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

      <label class="text-muted-soft small mb-1">Cover photo URL (optional)</label>
      <input class="form-control" name="photo" placeholder="https://..." value="<?= e($photo) ?>">

      <div class="d-flex gap-2 justify-content-end">
        <a href="instructor_dashboard.php" class="btn btn-outline-light">Cancel</a>
        <button class="btn btn-primary">Publish session</button>
      </div>
    </form>
  </div>
</div>

<script>
  (function() {
    const catSelect = document.getElementById('categorySelect');
    const catCustom = document.getElementById('categoryCustom');
    const feeSelect = document.getElementById('feeSelect');
    const feeCustom = document.getElementById('feeCustom');
    const citySelect = document.getElementById('citySelectCreate');
    const onlineInput = document.getElementById('onlineInfoCreate');
    const cityWrap = document.querySelector('.city-select-create');
    const cityRadio = document.getElementById('locCityCreate');
    const onlineRadio = document.getElementById('locOnlineCreate');

    if (catSelect && catCustom) {
      catSelect.addEventListener('change', () => {
        if (catSelect.value === 'custom') {
          catCustom.classList.remove('d-none');
          catCustom.required = true;
        } else {
          catCustom.classList.add('d-none');
          catCustom.required = false;
          catCustom.value = '';
        }
      });
    }

    if (feeSelect && feeCustom) {
      feeSelect.addEventListener('change', () => {
        if (feeSelect.value === 'custom') {
          feeCustom.classList.remove('d-none');
          feeCustom.required = true;
        } else {
          feeCustom.classList.add('d-none');
          feeCustom.required = false;
          feeCustom.value = '';
        }
      });
    }

    const syncLocation = () => {
      if (!cityRadio || !onlineRadio) return;
      if (onlineRadio.checked) {
        cityWrap && cityWrap.classList.add('d-none');
        onlineInput && onlineInput.classList.remove('d-none');
        citySelect && (citySelect.required = false);
        onlineInput && (onlineInput.required = true);
      } else {
        cityWrap && cityWrap.classList.remove('d-none');
        onlineInput && onlineInput.classList.add('d-none');
        citySelect && (citySelect.required = true);
        onlineInput && (onlineInput.required = false);
      }
    };

    if (cityRadio && onlineRadio) {
      cityRadio.addEventListener('change', syncLocation);
      onlineRadio.addEventListener('change', syncLocation);
      syncLocation();
    }

    // Prevent submit if times are invalid client-side
    const form = document.getElementById('createSessionForm');
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
