<?php
// includes/config.php
// ---------- SESSION ----------
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ---------- DATABASE ----------
$DB_HOST = '127.0.0.1';      // XAMPP default
$DB_USER = 'root';           // XAMPP default
$DB_PASS = '';               // XAMPP default (empty)
$DB_NAME = 'skillshare_local';

$mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

if ($mysqli->connect_errno) {
    die('Database connection failed: ' . $mysqli->connect_error);
}

$mysqli->set_charset('utf8mb4');

// ---------- PATHS ----------
define('BASE_URL', '/skillshare/public/');
define('UPLOAD_DIR', __DIR__ . '/../assets/uploads/');

if (!is_dir(UPLOAD_DIR)) {
    @mkdir(UPLOAD_DIR, 0777, true);
}
