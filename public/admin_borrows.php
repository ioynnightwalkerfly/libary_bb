<?php
require_once __DIR__ . '/includes/functions.php';
require_admin();

$conn = get_db_connection();
$filter = trim($_GET['filter'] ?? '');
$search = trim($_GET['search'] ?? '');

$sql = "SELECT b.*, u.name AS user_name, u.email, bk.title AS book_title
        FROM borrows b
        JOIN users u ON u.id = b.user_id
        JOIN books bk ON bk.id = b.book_id
        WHERE 1=1";
$params = [];
$types = '';

if ($filter === 'overdue') {
    $sql .= " AND b.status = 'borrowed' AND b.due_date < CURDATE()";
}

if ($search !== '') {
    $sql .= " AND (b.borrow_code LIKE ? OR u.name LIKE ? OR u.email LIKE ? OR bk.title LIKE ?)";
    $term = '%' . $search . '%';
    $params = array_merge($params, [$term, $term, $term, $term]);
    $types .= 'ssss';
}

$sql .= ' ORDER BY b.borrow_date DESC';
$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$records = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();

$pageTitle = 'Borrow Records';
include __DIR__ . '/includes/header.php';
?>
<section class="card">
    <h2>Borrow Records</h2>
    <form method="get" action="" class="search-form">
        <label for="search">Search</label>
        <input type="text" name="search" id="search" placeholder="Borrow code, user, book" value="<?= sanitize($search); ?>">

        <label for="filter">Filter</label>
        <select name="filter" id="filter">
            <option value="">All</option>
            <option value="overdue" <?= $filter === 'overdue' ? 'selected' : ''; ?>>Overdue</option>
        </select>

        <button type="submit">Apply</button>
    </form>
</section>
<section class="card">
    <?php if ($records): ?>
        <table>
            <thead>
            <tr>
                <th>Borrow Code</th>
                <th>User</th>
                <th>Email</th>
                <th>Book</th>
                <th>Borrowed</th>
                <th>Due</th>
                <th>Returned</th>
                <th>Status</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($records as $record): ?>
                <?php
                $statusClass = $record['status'] === 'returned' ? 'returned' : (strtotime($record['due_date']) < time() && $record['status'] === 'borrowed' ? 'overdue' : 'active');
                ?>
                <tr>
                    <td><?= sanitize($record['borrow_code']); ?></td>
                    <td><?= sanitize($record['user_name']); ?></td>
                    <td><?= sanitize($record['email']); ?></td>
                    <td><?= sanitize($record['book_title']); ?></td>
                    <td><?= sanitize(date('d M Y', strtotime($record['borrow_date']))); ?></td>
                    <td><?= sanitize(date('d M Y', strtotime($record['due_date']))); ?></td>
                    <td><?= $record['return_date'] ? sanitize(date('d M Y', strtotime($record['return_date']))) : '-'; ?></td>
                    <td><span class="badge <?= $statusClass; ?>"><?= sanitize(ucfirst($record['status'])); ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No borrow records found.</p>
    <?php endif; ?>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>