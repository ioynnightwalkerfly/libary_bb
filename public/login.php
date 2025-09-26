<?php
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    header('Location: user_dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Please enter both email and password.';
    } else {
        $conn = get_db_connection();
        $stmt = $conn->prepare('SELECT id, name, role, password_hash, status FROM users WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            if ($row['status'] !== 'active') {
                $error = 'Your account is suspended. Please contact the library desk.';
            } elseif (password_verify($password, $row['password_hash'])) {
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['name'] = $row['name'];
                $_SESSION['role'] = $row['role'];
                redirect_with_message('user_dashboard.php', 'Welcome back, ' . $row['name'] . '!');
            }
        }
        $error = 'Invalid credentials. Please try again.';
        $stmt->close();
        $conn->close();
    }
}

$pageTitle = 'Login';
include __DIR__ . '/includes/header.php';
?>
<div class="card">
    <h2>Login</h2>
    <form method="post" action="">
        <label for="email">Email</label>
        <input type="email" name="email" id="email" required value="<?= sanitize($_POST['email'] ?? ''); ?>">

        <label for="password">Password</label>
        <input type="password" name="password" id="password" required>

        <?php if ($error): ?>
            <p class="alert error"><?= sanitize($error); ?></p>
        <?php endif; ?>

        <button type="submit">Login</button>
    </form>
    <p>Don't have an account? <a href="register.php">Register here</a>.</p>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
