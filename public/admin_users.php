<?php
require_once __DIR__ . '/includes/functions.php';
require_admin();

$conn = get_db_connection();

if (isset($_GET['toggle'])) {
    $userId = (int)$_GET['toggle'];
    $stmt = $conn->prepare("UPDATE users SET status = IF(status = 'active', 'suspended', 'active') WHERE id = ? AND role != 'admin'");
    $stmt->bind_param('i', $userId);
    if ($stmt->execute()) {
        redirect_with_message('admin_users.php', 'User status updated.');
    } else {
        redirect_with_message('admin_users.php', 'Unable to update user status.', 'error');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $userId = (int)$_POST['user_id'];
    $newPassword = password_hash('Library123', PASSWORD_BCRYPT);
    $stmt = $conn->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
    $stmt->bind_param('si', $newPassword, $userId);
    if ($stmt->execute()) {
        redirect_with_message('admin_users.php', 'Password reset to Library123.');
    } else {
        redirect_with_message('admin_users.php', 'Password reset failed.', 'error');
    }
}

$search = trim($_GET['search'] ?? '');
$sql = "SELECT id, name, email, student_id, role, status, created_at FROM users WHERE role != 'admin'";
$params = [];
$types = '';
if ($search !== '') {
    $sql .= ' AND (name LIKE ? OR email LIKE ? OR student_id LIKE ?)';
    $term = '%' . $search . '%';
    $params = [$term, $term, $term];
    $types = 'sss';
}
$sql .= ' ORDER BY created_at DESC';
$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();

$pageTitle = 'Manage Users';
include __DIR__ . '/includes/header.php';
?>
<section class="card">
    <h2>User Accounts</h2>
    <form method="get" action="" class="search-form">
        <label for="search">Search</label>
        <input type="text" name="search" id="search" placeholder="Name, email, student ID" value="<?= sanitize($search); ?>">
        <button type="submit">Search</button>
    </form>
</section>
<section class="card">
    <?php if ($users): ?>
        <table>
            <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Student/Staff ID</th>
                <th>Status</th>
                <th>Joined</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= sanitize($user['name']); ?></td>
                    <td><?= sanitize($user['email']); ?></td>
                    <td><?= sanitize($user['student_id']); ?></td>
                    <td><span class="badge <?= $user['status'] === 'active' ? 'active' : 'overdue'; ?>"><?= sanitize(ucfirst($user['status'])); ?></span></td>
                    <td><?= sanitize(date('d M Y', strtotime($user['created_at']))); ?></td>
                    <td class="table-actions">
                        <a class="button secondary" href="admin_users.php?toggle=<?= $user['id']; ?>">Toggle Status</a>
                        <form method="post" action="" style="display:inline;">
                            <input type="hidden" name="user_id" value="<?= $user['id']; ?>">
                            <button type="submit" name="reset_password" onclick="return confirm('Reset password to Library123?');">Reset Password</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No users found.</p>
    <?php endif; ?>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>