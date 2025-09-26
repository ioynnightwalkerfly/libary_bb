<?php
require_once __DIR__ . '/includes/functions.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_with_message('browse_books.php', 'Invalid request method.', 'error');
}

$bookId = (int)($_POST['book_id'] ?? 0);
$duration = (int)($_POST['duration'] ?? 0);

if ($bookId <= 0 || !in_array($duration, [7, 14, 30], true)) {
    redirect_with_message('browse_books.php', 'Invalid borrowing details.', 'error');
}

$conn = get_db_connection();
$conn->begin_transaction();

$redirectUrl = '';
$error = '';

try {
    $stmt = $conn->prepare('SELECT id, title, quantity_available FROM books WHERE id = ? FOR UPDATE');
    $stmt->bind_param('i', $bookId);
    $stmt->execute();
    $book = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$book || (int)$book['quantity_available'] <= 0) {
        throw new Exception('The selected book is not available.');
    }

    $borrowDate = date('Y-m-d');
    $dueDate = date('Y-m-d', strtotime("+$duration days"));
    $status = 'borrowed';

    $stmt = $conn->prepare('INSERT INTO borrows (user_id, book_id, borrow_date, due_date, status) VALUES (?, ?, ?, ?, ?)');
    $stmt->bind_param('iisss', $_SESSION['user_id'], $bookId, $borrowDate, $dueDate, $status);
    if (!$stmt->execute()) {
        throw new Exception('Failed to create borrow record.');
    }
    $borrowId = $stmt->insert_id;
    $stmt->close();

    $borrowCode = generate_borrow_code($borrowId);
    $stmt = $conn->prepare('UPDATE borrows SET borrow_code = ? WHERE id = ?');
    $stmt->bind_param('si', $borrowCode, $borrowId);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare('UPDATE books SET quantity_available = quantity_available - 1 WHERE id = ?');
    $stmt->bind_param('i', $bookId);
    $stmt->execute();
    $stmt->close();

    $conn->commit();
    $redirectUrl = 'borrow_success.php?code=' . urlencode($borrowCode) . '&title=' . urlencode($book['title']);
} catch (Exception $e) {
    $conn->rollback();
    $error = $e->getMessage();
} finally {
    $conn->close();
}

if ($redirectUrl !== '') {
    redirect_with_message($redirectUrl, 'Borrow created successfully.');
}

redirect_with_message('browse_books.php', $error !== '' ? $error : 'Unable to create borrow request.', 'error');