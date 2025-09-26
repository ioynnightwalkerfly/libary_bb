<?php
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    header('Location: user_dashboard.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $studentId = trim($_POST['student_id'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($name === '') {
        $errors[] = 'Name is required.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid email is required.';
    }
    if ($studentId === '') {
        $errors[] = 'Student/Staff ID is required.';
    }
    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }
    if ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match.';
    }

    if (!$errors) {
        $conn = get_db_connection();
        $stmt = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors[] = 'Email already registered.';
        }
        $stmt->close();

        if (!$errors) {
            $passwordHash = password_hash($password, PASSWORD_BCRYPT);
            $role = 'user';
            $status = 'active';
            $stmt = $conn->prepare('INSERT INTO users (name, email, student_id, password_hash, role, status) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->bind_param('ssssss', $name, $email, $studentId, $passwordHash, $role, $status);
            if ($stmt->execute()) {
                redirect_with_message('login.php', 'Registration successful. Please login.');
            } else {
                $errors[] = 'Registration failed. Please try again later.';
            }
            $stmt->close();
        }
        $conn->close();
    }
}

$pageTitle = 'Register';
include __DIR__ . '/includes/header.php';
?>
<div class="card">
    <h2>Create Account</h2>
    <form method="post" action="">
        <label for="name">Full Name</label>
        <input type="text" name="name" id="name" required value="<?= sanitize($_POST['name'] ?? ''); ?>">

        <label for="email">University Email</label>
        <input type="email" name="email" id="email" required value="<?= sanitize($_POST['email'] ?? ''); ?>">

        <label for="student_id">Student/Staff ID</label>
        <input type="text" name="student_id" id="student_id" required value="<?= sanitize($_POST['student_id'] ?? ''); ?>">

        <label for="password">Password</label>
        <input type="password" name="password" id="password" required>

        <label for="confirm_password">Confirm Password</label>
        <input type="password" name="confirm_password" id="confirm_password" required>

        <?php if ($errors): ?>
            <div class="alert error">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= sanitize($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <button type="submit">Register</button>
    </form>
    <p>Already have an account? <a href="login.php">Login here</a>.</p>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>