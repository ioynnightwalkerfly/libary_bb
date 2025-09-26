<?php
require_once __DIR__ . '/includes/functions.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_with_message('return_book.php', 'Invalid request method.', 'error');
}

$borrowCode = trim($_POST['borrow_code'] ?? '');

if ($borrowCode === '') {
    redirect_with_message('return_book.php', 'Please provide a borrow code.', 'error');
}

$conn = get_db_connection();
$conn->begin_transaction();

$redirectUrl = '';
$successMessage = '';
$error = '';

try {
    $stmt = $conn->prepare("SELECT b.id, b.book_id, b.user_id, b.status, b.borrow_code, b.due_date, bk.title
        FROM borrows b
        JOIN books bk ON bk.id = b.book_id
        WHERE b.borrow_code = ? FOR UPDATE");
    $stmt->bind_param('s', $borrowCode);
    $stmt->execute();
    $borrow = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$borrow) {
        throw new Exception('Borrow record not found.');
    }

    if (!is_admin() && (int)$borrow['user_id'] !== (int)$_SESSION['user_id']) {
        throw new Exception('You can only confirm returns for your own borrowings.');
    }

    if ($borrow['status'] === 'returned') {
        throw new Exception('This borrow has already been marked as returned.');
    }

    $returnDate = date('Y-m-d');

    $stmt = $conn->prepare('UPDATE borrows SET status = "returned", return_date = ? WHERE id = ?');
    $stmt->bind_param('si', $returnDate, $borrow['id']);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare('UPDATE books SET quantity_available = quantity_available + 1 WHERE id = ?');
    $stmt->bind_param('i', $borrow['book_id']);
    $stmt->execute();
    $stmt->close();

    $conn->commit();

    $redirectUrl = is_admin() && (int)$borrow['user_id'] !== (int)$_SESSION['user_id']
        ? 'admin_borrows.php'
        : 'borrow_history.php';
    $successMessage = 'Return confirmed for ' . $borrow['title'] . '. Thank you!';
} catch (Exception $e) {
    $conn->rollback();
    $error = $e->getMessage();
} finally {
    $conn->close();
}

if ($redirectUrl !== '') {
    redirect_with_message($redirectUrl, $successMessage);
}

redirect_with_message('return_book.php', $error !== '' ? $error : 'Unable to process return.', 'error');
