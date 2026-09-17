<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $pass  = $_POST['password'] ?? '';
    $role  = $_POST['role'] === 'instructor' ? 'instructor' : 'learner';
    $city  = trim($_POST['city'] ?? '');

    if ($name === '' || $email === '' || $pass === '') {
        $errors[] = "All fields are required.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email.";
    }

    if (strlen($pass) < 6) {
        $errors[] = "Password must be at least 6 characters.";
    }

    if (empty($errors)) {
        $check = $mysqli->prepare("SELECT id FROM users WHERE email=?");
        $check->bind_param('s', $email);
        $check->execute();

        if ($check->get_result()->num_rows > 0) {
            $errors[] = "Email already registered.";
        } else {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $stmt = $mysqli->prepare("
                INSERT INTO users (name,email,password_hash,role,city)
                VALUES (?,?,?,?,?)
            ");
            $stmt->bind_param('sssss', $name, $email, $hash, $role, $city);
            $stmt->execute();

            $_SESSION['user_id'] = $stmt->insert_id;
            redirect('index.php');
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container" style="max-width: 520px;">
  <div class="card-neo">
    <h1 class="page-title">Join SkillShare</h1>
    <p class="page-subtitle">Create your account</p>

    <?php if($errors): ?>
      <div class="alert-darkish mt-3">
        <?= implode('<br>', array_map('e',$errors)) ?>
      </div>
    <?php endif; ?>

    <form method="post" class="mt-4 d-grid gap-3">
      <input class="form-control" name="name" placeholder="Full name" required>
      <input class="form-control" type="email" name="email" placeholder="Email address" required>
      <input class="form-control" type="password" name="password" placeholder="Password" required>

      <select class="form-select" name="city">
        <option value="">Select city (optional)</option>
        <?php foreach (['Limassol','Nicosia','Pafos','Larnaca','Online'] as $opt): ?>
          <option value="<?= e($opt) ?>"><?= e($opt) ?></option>
        <?php endforeach; ?>
      </select>

      <select class="form-select" name="role">
        <option value="learner">I'm a Learner</option>
        <option value="instructor">I'm an Instructor</option>
      </select>

      <button class="btn btn-primary btn-pill mt-3">Create Account</button>
      <a href="login.php" class="btn btn-outline-light btn-pill">Already have an account?</a>
    </form>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
