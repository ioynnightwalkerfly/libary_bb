<?php
require_once __DIR__ . '/includes/functions.php';
require_login();

$conn = get_db_connection();
$stmt = $conn->prepare("SELECT b.borrow_code, bk.title, bk.author, b.borrow_date, b.due_date, b.return_date, b.status
    FROM borrows b
    JOIN books bk ON bk.id = b.book_id
    WHERE b.user_id = ?
    ORDER BY b.borrow_date DESC");
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$borrows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();

$pageTitle = 'My Borrowed Books';
include __DIR__ . '/includes/header.php';
?>
<section class="card">
    <h2>Borrowing History</h2>
    <?php if ($borrows): ?>
        <table>
            <thead>
            <tr>
                <th>Borrow Code</th>
                <th>Book</th>
                <th>Borrowed</th>
                <th>Due</th>
                <th>Returned</th>
                <th>Status</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($borrows as $borrow): ?>
                <?php
                $statusClass = $borrow['status'] === 'returned' ? 'returned' : (strtotime($borrow['due_date']) < time() && $borrow['status'] === 'borrowed' ? 'overdue' : 'active');
                ?>
                <tr>
                    <td><?= sanitize($borrow['borrow_code']); ?></td>
                    <td><?= sanitize($borrow['title']); ?> by <?= sanitize($borrow['author']); ?></td>
                    <td><?= sanitize(date('d M Y', strtotime($borrow['borrow_date']))); ?></td>
                    <td><?= sanitize(date('d M Y', strtotime($borrow['due_date']))); ?></td>
                    <td><?= $borrow['return_date'] ? sanitize(date('d M Y', strtotime($borrow['return_date']))) : '-'; ?></td>
                    <td><span class="badge <?= $statusClass; ?>"><?= sanitize(ucfirst($borrow['status'])); ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>You haven't borrowed any books yet.</p>
    <?php endif; ?>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
