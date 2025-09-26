<?php
require_once __DIR__ . '/includes/functions.php';
require_login();

$borrowCode = trim($_GET['code'] ?? '');
$title = trim($_GET['title'] ?? '');

$pageTitle = 'Borrow Confirmation';
include __DIR__ . '/includes/header.php';
?>
<section class="card">
    <h2>Borrow Confirmed</h2>
    <?php if ($borrowCode): ?>
        <p>You have successfully borrowed <strong><?= sanitize($title); ?></strong>.</p>
        <p>Your borrow code is:</p>
        <p class="badge active" style="font-size: 1.1rem;"><?= sanitize($borrowCode); ?></p>
        <p>Please keep this code safe. You can use it or the QR code on your receipt to confirm the return later.</p>
        <a class="button" href="borrow_history.php">View my borrowed books</a>
    <?php else: ?>
        <p>No borrow code found. <a href="browse_books.php">Search for a book</a>.</p>
    <?php endif; ?>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>