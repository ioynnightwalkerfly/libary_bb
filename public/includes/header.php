<?php
if (!isset($pageTitle)) {
    $pageTitle = 'Library System';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($pageTitle); ?></title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
<header class="site-header">
    <h1>University Library</h1>
    <nav>
        <ul>
            <?php if (is_logged_in()): ?>
                <li><a href="user_dashboard.php">Dashboard</a></li>
                <li><a href="browse_books.php">Search Books</a></li>
                <li><a href="borrow_history.php">My Borrows</a></li>
                <li><a href="return_book.php">Return Book</a></li>
                <?php if (is_admin()): ?>
                    <li><a href="admin_dashboard.php">Admin</a></li>
                <?php endif; ?>
                <li><a href="logout.php">Logout</a></li>
            <?php else: ?>
                <li><a href="login.php">Login</a></li>
                <li><a href="register.php">Register</a></li>
            <?php endif; ?>
        </ul>
    </nav>
</header>
<main class="container">
    <?php if ($flash = get_flash()): ?>
        <div class="alert <?= sanitize($flash['type']); ?>">
            <?= sanitize($flash['message']); ?>
        </div>
    <?php endif; ?>
