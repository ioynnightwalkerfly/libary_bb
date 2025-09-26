<?php
require_once __DIR__ . '/includes/functions.php';
require_admin();

$conn = get_db_connection();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $bookId = (int)$_POST['delete_id'];

    $stmt = $conn->prepare('SELECT cover_image FROM books WHERE id = ?');
    $stmt->bind_param('i', $bookId);
    $stmt->execute();
    $coverImage = $stmt->get_result()->fetch_assoc()['cover_image'] ?? null;
    $stmt->close();

    $stmt = $conn->prepare('DELETE FROM books WHERE id = ?');
    $stmt->bind_param('i', $bookId);

    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();

        if ($coverImage) {
            $existingPath = __DIR__ . '/' . ltrim($coverImage, '/');
            if (is_file($existingPath)) {
                @unlink($existingPath);
            }
        }

        redirect_with_message('admin_books.php', 'Book deleted successfully.');
    }

    $stmt->close();
    $conn->close();
    redirect_with_message('admin_books.php', 'Unable to delete book.', 'error');
}

$books = $conn->query('SELECT b.*, c.name AS category_name FROM books b LEFT JOIN categories c ON c.id = b.category_id ORDER BY b.title')->fetch_all(MYSQLI_ASSOC);
$conn->close();

$pageTitle = 'Manage Books';
include __DIR__ . '/includes/header.php';
?>
<section class="card">
    <div class="card-header">
        <div>
            <h2>Library Inventory</h2>
            <p>Review every title that is currently available to students and staff.</p>
        </div>
        <a class="button" href="admin_book_form.php">Add Book</a>
    </div>
    <?php if ($books): ?>
        <table>
            <thead>
            <tr>
                <th>Cover</th>
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
                    <td>
                        <?php if (!empty($item['cover_image'])): ?>
                            <img class="cover-thumb" src="<?= sanitize($item['cover_image']); ?>" alt="Cover for <?= sanitize($item['title']); ?>">
                        <?php else: ?>
                            <span class="badge muted">No cover</span>
                        <?php endif; ?>
                    </td>
                    <td><?= sanitize($item['title']); ?></td>
                    <td><?= sanitize($item['author']); ?></td>
                    <td><?= sanitize($item['category_name'] ?? 'Uncategorized'); ?></td>
                    <td><?= sanitize($item['isbn']); ?></td>
                    <td><?= sanitize((string)$item['total_quantity']); ?></td>
                    <td><?= sanitize((string)$item['quantity_available']); ?></td>
                    <td class="table-actions">
                        <a class="button secondary" href="admin_book_form.php?id=<?= $item['id']; ?>">Edit</a>
                        <form method="post" class="inline-form" onsubmit="return confirm('Delete this book?');">
                            <input type="hidden" name="delete_id" value="<?= $item['id']; ?>">
                            <button type="submit" class="button danger">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No books in the catalog yet. Start by adding your first title.</p>
    <?php endif; ?>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
