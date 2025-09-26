<?php
require_once __DIR__ . '/includes/functions.php';
require_admin();

$conn = get_db_connection();

$book = null;
if (isset($_GET['id'])) {
    $bookId = (int)$_GET['id'];
    $stmt = $conn->prepare('SELECT * FROM books WHERE id = ?');
    $stmt->bind_param('i', $bookId);
    $stmt->execute();
    $book = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$book) {
        $conn->close();
        redirect_with_message('admin_books.php', 'Book not found.', 'error');
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
    $bookId = isset($_POST['book_id']) && $_POST['book_id'] !== '' ? (int)$_POST['book_id'] : null;

    if ($quantityAvailable > $totalQuantity) {
        $quantityAvailable = $totalQuantity;
    }

    if ($title === '' || $author === '' || $totalQuantity <= 0) {
        redirect_with_message('admin_book_form.php' . ($bookId ? '?id=' . $bookId : ''), 'Title, author, and a positive quantity are required.', 'error');
    }

    $existingCover = null;
    if ($bookId) {
        $stmt = $conn->prepare('SELECT cover_image FROM books WHERE id = ?');
        $stmt->bind_param('i', $bookId);
        $stmt->execute();
        $existingCover = $stmt->get_result()->fetch_assoc()['cover_image'] ?? null;
        $stmt->close();
    }

    $coverImagePath = $existingCover;
    if (!empty($_FILES['cover_image']['name'])) {
        $uploadError = $_FILES['cover_image']['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($uploadError !== UPLOAD_ERR_OK) {
            redirect_with_message('admin_book_form.php' . ($bookId ? '?id=' . $bookId : ''), 'Failed to upload cover image.', 'error');
        }

        $tmpPath = $_FILES['cover_image']['tmp_name'];
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($tmpPath);
        $allowedTypes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];

        if (!isset($allowedTypes[$mimeType])) {
            redirect_with_message('admin_book_form.php' . ($bookId ? '?id=' . $bookId : ''), 'Only JPG, PNG, or WEBP images are allowed for covers.', 'error');
        }

        $uploadDir = __DIR__ . '/uploads';
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
            redirect_with_message('admin_book_form.php' . ($bookId ? '?id=' . $bookId : ''), 'Unable to create upload directory.', 'error');
        }

        $filename = sprintf('cover_%s_%s.%s', date('YmdHis'), bin2hex(random_bytes(4)), $allowedTypes[$mimeType]);
        $destination = $uploadDir . '/' . $filename;

        if (!move_uploaded_file($tmpPath, $destination)) {
            redirect_with_message('admin_book_form.php' . ($bookId ? '?id=' . $bookId : ''), 'Failed to store uploaded cover image.', 'error');
        }

        if ($existingCover) {
            $existingPath = __DIR__ . '/' . ltrim($existingCover, '/');
            if (is_file($existingPath)) {
                @unlink($existingPath);
            }
        }

        $coverImagePath = 'uploads/' . $filename;
    }

    if ($bookId) {
        $stmt = $conn->prepare('UPDATE books SET title = ?, author = ?, isbn = ?, description = ?, category_id = NULLIF(?, 0), total_quantity = ?, quantity_available = ?, cover_image = ? WHERE id = ?');
        $stmt->bind_param('ssssiiisi', $title, $author, $isbn, $description, $categoryId, $totalQuantity, $quantityAvailable, $coverImagePath, $bookId);
        $success = $stmt->execute();
        $stmt->close();

        if ($success) {
            $conn->close();
            redirect_with_message('admin_books.php', 'Book updated successfully.');
        }

        $conn->close();
        redirect_with_message('admin_book_form.php?id=' . $bookId, 'Failed to update book.', 'error');
    } else {
        $stmt = $conn->prepare('INSERT INTO books (title, author, isbn, description, category_id, total_quantity, quantity_available, cover_image) VALUES (?, ?, ?, ?, NULLIF(?, 0), ?, ?, ?)');
        $stmt->bind_param('ssssiiis', $title, $author, $isbn, $description, $categoryId, $totalQuantity, $quantityAvailable, $coverImagePath);
        $success = $stmt->execute();
        $stmt->close();

        if ($success) {
            $conn->close();
            redirect_with_message('admin_books.php', 'Book added successfully.');
        }

        $conn->close();
        redirect_with_message('admin_book_form.php', 'Failed to add book.', 'error');
    }
}

$categories = $conn->query('SELECT id, name FROM categories ORDER BY name')->fetch_all(MYSQLI_ASSOC);
$conn->close();

$pageTitle = $book ? 'Edit Book' : 'Add New Book';
include __DIR__ . '/includes/header.php';
?>
<section class="card">
    <div class="card-header">
        <h2><?= $book ? 'Update Book Details' : 'Add a New Book'; ?></h2>
        <a class="button secondary" href="admin_books.php">Back to Books</a>
    </div>
    <form method="post" action="" enctype="multipart/form-data" class="form-grid">
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

        <label for="cover_image">Cover Image</label>
        <?php if (!empty($book['cover_image'])): ?>
            <div class="cover-preview">
                <img src="<?= sanitize($book['cover_image']); ?>" alt="Cover preview for <?= sanitize($book['title']); ?>">
            </div>
        <?php endif; ?>
        <input type="file" name="cover_image" id="cover_image" accept="image/png,image/jpeg,image/webp">

        <label for="total_quantity">Total Quantity</label>
        <input type="number" min="1" name="total_quantity" id="total_quantity" required value="<?= sanitize((string)($book['total_quantity'] ?? 1)); ?>">

        <label for="quantity_available">Quantity Available</label>
        <input type="number" min="0" name="quantity_available" id="quantity_available" required value="<?= sanitize((string)($book['quantity_available'] ?? ($book['total_quantity'] ?? 1))); ?>">

        <button type="submit"><?= $book ? 'Update Book' : 'Add Book'; ?></button>
    </form>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
