<?php
require_once __DIR__ . '/includes/functions.php';
require_admin();

$conn = get_db_connection();

$book = null;
if (isset($_GET['edit'])) {
    $bookId = (int)$_GET['edit'];
    $stmt = $conn->prepare('SELECT * FROM books WHERE id = ?');
    $stmt->bind_param('i', $bookId);
    $stmt->execute();
    $book = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

if (isset($_GET['delete'])) {
    $bookId = (int)$_GET['delete'];
    $stmt = $conn->prepare('DELETE FROM books WHERE id = ?');
    $stmt->bind_param('i', $bookId);
    if ($stmt->execute()) {
        redirect_with_message('admin_books.php', 'Book deleted successfully.');
    } else {
        redirect_with_message('admin_books.php', 'Unable to delete book.', 'error');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $isbn = trim($_POST['isbn'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $totalQuantity = (int)($_POST['total_quantity'] ?? 0);
    $quantityAvailable = (int)($_POST['quantity_available'] ?? $totalQuantity);

    if ($quantityAvailable > $totalQuantity) {
        $quantityAvailable = $totalQuantity;
    }

    if ($title === '' || $author === '' || $totalQuantity <= 0) {
        redirect_with_message('admin_books.php', 'Title, author, and positive quantity are required.', 'error');
    }

    if (isset($_POST['book_id']) && $_POST['book_id'] !== '') {
        $bookId = (int)$_POST['book_id'];
        $stmt = $conn->prepare('UPDATE books SET title = ?, author = ?, isbn = ?, description = ?, category_id = NULLIF(?, 0), total_quantity = ?, quantity_available = ? WHERE id = ?');
        $stmt->bind_param('ssssiiii', $title, $author, $isbn, $description, $categoryId, $totalQuantity, $quantityAvailable, $bookId);
        if ($stmt->execute()) {
            redirect_with_message('admin_books.php', 'Book updated successfully.');
        } else {
            redirect_with_message('admin_books.php', 'Failed to update book.', 'error');
        }
    } else {
        $stmt = $conn->prepare('INSERT INTO books (title, author, isbn, description, category_id, total_quantity, quantity_available) VALUES (?, ?, ?, ?, NULLIF(?, 0), ?, ?)');
        $stmt->bind_param('ssssiii', $title, $author, $isbn, $description, $categoryId, $totalQuantity, $quantityAvailable);
        if ($stmt->execute()) {
            redirect_with_message('admin_books.php', 'Book added successfully.');
        } else {
            redirect_with_message('admin_books.php', 'Failed to add book.', 'error');
        }
    }
}

$categories = $conn->query('SELECT id, name FROM categories ORDER BY name')->fetch_all(MYSQLI_ASSOC);
$books = $conn->query('SELECT b.*, c.name AS category_name FROM books b LEFT JOIN categories c ON c.id = b.category_id ORDER BY b.title')->fetch_all(MYSQLI_ASSOC);
$conn->close();

$pageTitle = 'Manage Books';
include __DIR__ . '/includes/header.php';
?>
<section class="card">
    <h2><?= $book ? 'Edit Book' : 'Add New Book'; ?></h2>
    <form method="post" action="">
        <?php if ($book): ?>
            <input type="hidden" name="book_id" value="<?= $book['id']; ?>">
        <?php endif; ?>
        <label for="title">Title</label>
        <input type="text" name="title" id="title" required value="<?= sanitize($book['title'] ?? ''); ?>">

        <label for="author">Author</label>
        <input type="text" name="author" id="author" required value="<?= sanitize($book['author'] ?? ''); ?>">

        <label for="isbn">ISBN</label>
        <input type="text" name="isbn" id="isbn" value="<?= sanitize($book['isbn'] ?? ''); ?>">

        <label for="category_id">Category</label>
        <select name="category_id" id="category_id">
            <option value="">Uncategorized</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id']; ?>" <?= isset($book['category_id']) && (int)$book['category_id'] === (int)$cat['id'] ? 'selected' : ''; ?>>
                    <?= sanitize($cat['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="description">Description</label>
        <textarea name="description" id="description" rows="4"><?= sanitize($book['description'] ?? ''); ?></textarea>

        <label for="total_quantity">Total Quantity</label>
        <input type="number" min="1" name="total_quantity" id="total_quantity" required value="<?= sanitize((string)($book['total_quantity'] ?? 1)); ?>">

        <label for="quantity_available">Quantity Available</label>
        <input type="number" min="0" name="quantity_available" id="quantity_available" required value="<?= sanitize((string)($book['quantity_available'] ?? ($book['total_quantity'] ?? 1))); ?>">

        <button type="submit">Save</button>
        <?php if ($book): ?>
            <a class="button secondary" href="admin_books.php">Cancel</a>
        <?php endif; ?>
    </form>
</section>
<section class="card">
    <h3>Library Inventory</h3>
    <?php if ($books): ?>
        <table>
            <thead>
            <tr>
                <th>Title</th>
                <th>Author</th>
                <th>Category</th>
                <th>ISBN</th>
                <th>Total</th>
                <th>Available</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($books as $item): ?>
                <tr>
                    <td><?= sanitize($item['title']); ?></td>
                    <td><?= sanitize($item['author']); ?></td>
                    <td><?= sanitize($item['category_name'] ?? 'Uncategorized'); ?></td>
                    <td><?= sanitize($item['isbn']); ?></td>
                    <td><?= sanitize((string)$item['total_quantity']); ?></td>
                    <td><?= sanitize((string)$item['quantity_available']); ?></td>
                    <td class="table-actions">
                        <a class="button secondary" href="admin_books.php?edit=<?= $item['id']; ?>">Edit</a>
                        <a class="button" style="background:#dc2626" href="admin_books.php?delete=<?= $item['id']; ?>" onclick="return confirm('Delete this book?');">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No books in the catalog yet.</p>
    <?php endif; ?>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>