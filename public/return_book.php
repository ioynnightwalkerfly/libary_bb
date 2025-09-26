<?php
require_once __DIR__ . '/includes/functions.php';
require_login();

$borrowCode = trim($_GET['code'] ?? '');
$pageTitle = 'Return Book';
include __DIR__ . '/includes/header.php';
?>
<section class="card">
    <h2>Return a Book</h2>
    <p>Enter your borrow code or scan the QR code provided by the library staff to confirm the return.</p>
    <form action="process_return.php" method="post">
        <label for="borrow_code">Borrow Code</label>
        <input type="text" name="borrow_code" id="borrow_code" required value="<?= sanitize($borrowCode); ?>">
        <button type="submit">Confirm Return</button>
    </form>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>