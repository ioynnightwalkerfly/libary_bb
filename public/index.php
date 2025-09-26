<?php
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    header('Location: user_dashboard.php');
    exit;
}

header('Location: login.php');
exit;
