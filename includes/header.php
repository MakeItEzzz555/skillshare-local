<?php
require_once __DIR__ . '/functions.php';
$user = current_user();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>SkillShare Local</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap 5 (for grid & basic components) -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Custom theme -->
  <link rel="stylesheet" href="../assets/css/style.css">
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../assets/js/app.js"></script>
</head>
<body>
<div class="main-shell">
  <nav class="navbar navbar-expand-lg navbar-dark navbar-custom border-bottom border-dark">
    <div class="container-fluid px-3 px-lg-4">
      <a class="navbar-brand text-white" href="index.php">SkillShare Local</a>

      <button class="navbar-toggler text-white border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse justify-content-end" id="navMain">
        <ul class="navbar-nav align-items-center gap-lg-2">
          <li class="nav-item">
            <a class="nav-link text-muted-soft" href="index.php">Sessions</a>
          </li>

          <?php if ($user): ?>
            <?php if ($user['role'] === 'instructor'): ?>
              <li class="nav-item">
                <a class="nav-link text-muted-soft" href="instructor_dashboard.php">Dashboard</a>
              </li>
            <?php elseif ($user['role'] === 'learner'): ?>
              <li class="nav-item">
                <a class="nav-link text-muted-soft" href="learner_dashboard.php">Dashboard</a>
              </li>
            <?php endif; ?>

            <?php if ($user['role'] === 'admin'): ?>
              <li class="nav-item">
                <a class="nav-link text-muted-soft" href="admin_dashboard.php">Admin</a>
              </li>
            <?php endif; ?>

            <li class="nav-item">
              <span class="badge-soft me-2"><?= e($user['role']) ?></span>
            </li>
            <li class="nav-item">
              <span class="nav-link text-muted-soft small"><?= e($user['name']) ?></span>
            </li>
            <li class="nav-item">
              <a class="btn btn-outline-light btn-sm btn-pill" href="logout.php">Logout</a>
            </li>
          <?php else: ?>
            <li class="nav-item">
              <a class="btn btn-outline-light btn-sm btn-pill me-2" href="login.php">Login</a>
            </li>
            <li class="nav-item">
              <a class="btn btn-primary btn-sm btn-pill" href="register.php">Join</a>
            </li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
  </nav>

  <main>
