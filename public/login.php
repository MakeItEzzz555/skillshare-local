<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $pass  = $_POST['password'] ?? '';

    if ($email === '' || $pass === '') {
        $errors[] = "All fields are required.";
    }

    if (empty($errors)) {
        $stmt = $mysqli->prepare("SELECT * FROM users WHERE email=?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if (!$user || !password_verify($pass, $user['password_hash'])) {
            $errors[] = "Invalid email or password.";
        } elseif ((int)$user['suspended'] === 1) {
            $errors[] = "Your account has been suspended.";
        } else {
            $_SESSION['user_id'] = $user['id'];
            redirect('index.php');
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container" style="max-width: 520px;">
  <div class="card-neo">
    <h1 class="page-title">Welcome Back</h1>
    <p class="page-subtitle">Sign in to your account</p>

    <?php if($errors): ?>
      <div class="alert-darkish mt-3">
        <?= implode('<br>', array_map('e',$errors)) ?>
      </div>
    <?php endif; ?>

    <form method="post" class="mt-4 d-grid gap-3">
      <input class="form-control" type="email" name="email" placeholder="Email address" required>
      <input class="form-control" type="password" name="password" placeholder="Password" required>

      <button class="btn btn-primary btn-pill mt-2">Login</button>
      <a href="register.php" class="btn btn-outline-light btn-pill">Create new account</a>
    </form>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
