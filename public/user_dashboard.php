<?php
require_once __DIR__ . '/includes/functions.php';
require_login();

$conn = get_db_connection();

// Active borrow count
$stmt = $conn->prepare("SELECT COUNT(*) AS active_count FROM borrows WHERE user_id = ? AND status = 'borrowed'");
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$activeBorrows = $stmt->get_result()->fetch_assoc()['active_count'] ?? 0;
$stmt->close();

// Overdue count
$stmt = $conn->prepare("SELECT COUNT(*) AS overdue_count FROM borrows WHERE user_id = ? AND status = 'borrowed' AND due_date < CURDATE()");
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$overdueBorrows = $stmt->get_result()->fetch_assoc()['overdue_count'] ?? 0;
$stmt->close();

// Recent borrow records
$stmt = $conn->prepare("SELECT b.borrow_code, bk.title, bk.author, b.borrow_date, b.due_date, b.status
    FROM borrows b
    JOIN books bk ON bk.id = b.book_id
    WHERE b.user_id = ?
    ORDER BY b.borrow_date DESC
    LIMIT 5");
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$recentBorrows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();

$pageTitle = 'User Dashboard';
include __DIR__ . '/includes/header.php';
?>
<section class="card">
    <h2>Hello, <?= sanitize($_SESSION['name']); ?>!</h2>
    <p>Welcome to the University Library borrowing system. Use the links above to search for books or manage your borrowing.</p>
    <div class="flex-between">
        <div>
            <strong>Active Borrows:</strong> <?= sanitize((string)$activeBorrows); ?>
        </div>
        <div>
            <strong>Overdue:</strong> <?= sanitize((string)$overdueBorrows); ?>
        </div>
    </div>
</section>
<section class="card">
    <h3>Recent Activity</h3>
    <?php if ($recentBorrows): ?>
        <table>
            <thead>
            <tr>
                <th>Borrow Code</th>
                <th>Book</th>
                <th>Borrowed</th>
                <th>Due</th>
                <th>Status</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($recentBorrows as $borrow): ?>
                <?php
                $statusClass = $borrow['status'] === 'returned' ? 'returned' : (strtotime($borrow['due_date']) < time() && $borrow['status'] === 'borrowed' ? 'overdue' : 'active');
                ?>
                <tr>
                    <td><?= sanitize($borrow['borrow_code']); ?></td>
                    <td><?= sanitize($borrow['title']); ?> by <?= sanitize($borrow['author']); ?></td>
                    <td><?= sanitize(date('d M Y', strtotime($borrow['borrow_date']))); ?></td>
                    <td><?= sanitize(date('d M Y', strtotime($borrow['due_date']))); ?></td>
                    <td><span class="badge <?= $statusClass; ?>"><?= sanitize(ucfirst($borrow['status'])); ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No borrowing activity yet. <a href="browse_books.php">Search for a book to borrow</a>.</p>
    <?php endif; ?>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>