<?php
require_once __DIR__ . '/../config.php';
session_start();

function is_logged_in(): bool {
    return isset($_SESSION['user_id']);
}

function is_admin(): bool {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function require_login(): void {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function require_admin(): void {
    require_login();
    if (!is_admin()) {
        header('Location: user_dashboard.php');
        exit;
    }
}

function sanitize(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function generate_borrow_code(int $borrowId): string {
    return sprintf('BRW-%s-%03d', date('Ymd'), $borrowId);
}

function redirect_with_message(string $url, string $message, string $type = 'success'): void {
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
    header('Location: ' . $url);
    exit;
}

function get_flash(): ?array {
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}
