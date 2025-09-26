<?php
require_once __DIR__ . '/includes/functions.php';

session_unset();
session_destroy();

redirect_with_message('login.php', 'You have been logged out.', 'info');
