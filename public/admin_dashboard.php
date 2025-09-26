<?php
require_once __DIR__ . '/includes/functions.php';
require_admin();

$conn = get_db_connection();
$stats = [];

$queries = [
    'total_books' => 'SELECT COUNT(*) AS count FROM books',
    'total_users' => "SELECT COUNT(*) AS count FROM users WHERE role = 'user'",
    'active_borrows' => "SELECT COUNT(*) AS count FROM borrows WHERE status = 'borrowed'",
    'overdue_borrows' => "SELECT COUNT(*) AS count FROM borrows WHERE status = 'borrowed' AND due_date < CURDATE()"
];

foreach ($queries as $key => $sql) {
    $result = $conn->query($sql);
    $stats[$key] = $result->fetch_assoc()['count'] ?? 0;
}

$stmt = $conn->query("SELECT b.borrow_code, u.name AS user_name, bk.title, b.due_date
    FROM borrows b
    JOIN users u ON u.id = b.user_id
    JOIN books bk ON bk.id = b.book_id
    WHERE b.status = 'borrowed' AND b.due_date < DATE_ADD(CURDATE(), INTERVAL 3 DAY)
    ORDER BY b.due_date ASC
    LIMIT 5");
$upcomingDue = $stmt->fetch_all(MYSQLI_ASSOC);
$conn->close();

$pageTitle = 'Admin Dashboard';
include __DIR__ . '/includes/header.php';
?>
<section class="card">
    <h2>Admin Overview</h2>
    <div class="search-grid">
        <div class="search-result">
            <h3>Total Books</h3>
            <p><?= sanitize((string)$stats['total_books']); ?></p>
        </div>
        <div class="search-result">
            <h3>Registered Users</h3>
            <p><?= sanitize((string)$stats['total_users']); ?></p>
        </div>
        <div class="search-result">
            <h3>Active Borrows</h3>
            <p><?= sanitize((string)$stats['active_borrows']); ?></p>
        </div>
        <div class="search-result">
            <h3>Overdue</h3>
            <p><?= sanitize((string)$stats['overdue_borrows']); ?></p>
        </div>
    </div>
</section>
<section class="card">
    <h3>Due Soon</h3>
    <?php if ($upcomingDue): ?>
        <table>
            <thead>
            <tr>
                <th>Borrow Code</th>
                <th>User</th>
                <th>Book</th>
                <th>Due Date</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($upcomingDue as $row): ?>
                <tr>
                    <td><?= sanitize($row['borrow_code']); ?></td>
                    <td><?= sanitize($row['user_name']); ?></td>
                    <td><?= sanitize($row['title']); ?></td>
                    <td><?= sanitize(date('d M Y', strtotime($row['due_date']))); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No upcoming due reminders.</p>
    <?php endif; ?>
</section>
<section class="card">
    <div class="table-actions">
        <a class="button" href="admin_books.php">Manage Books</a>
        <a class="button" href="admin_borrows.php">Borrow Records</a>
        <a class="button" href="admin_users.php">Manage Users</a>
    </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>