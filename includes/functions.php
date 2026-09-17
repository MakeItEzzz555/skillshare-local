<?php
// includes/functions.php
require_once __DIR__ . '/config.php';

// Escape for HTML output
function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function is_logged_in(): bool {
    return !empty($_SESSION['user_id']);
}

function current_user(): ?array {
    global $mysqli;
    if (!is_logged_in()) return null;

    $id = (int)$_SESSION['user_id'];
    $stmt = $mysqli->prepare("SELECT id, name, email, role, city, suspended FROM users WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $user = $res->fetch_assoc();
    $stmt->close();

    return $user ?: null;
}

function require_login(): void {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function require_role(string $role): void {
    require_login();
    $user = current_user();
    if (!$user || $user['role'] !== $role) {
        http_response_code(403);
        echo "Forbidden";
        exit;
    }
}

function redirect(string $path): void {
    // $path is something like 'index.php' or 'login.php'
    header('Location: ' . $path);
    exit;
}
