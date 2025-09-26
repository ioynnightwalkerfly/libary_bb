<?php
require_once __DIR__ . '/includes/functions.php';
require_login();

$searchTerm = trim($_GET['q'] ?? '');
$category = trim($_GET['category'] ?? '');

$conn = get_db_connection();
$query = "SELECT * FROM categories ORDER BY name";
$categories = $conn->query($query)->fetch_all(MYSQLI_ASSOC);

$sql = "SELECT b.*, c.name AS category_name
        FROM books b
        LEFT JOIN categories c ON c.id = b.category_id
        WHERE 1=1";
$params = [];
$types = '';

if ($searchTerm !== '') {
    $sql .= " AND (b.title LIKE ? OR b.author LIKE ? OR b.isbn LIKE ? OR b.description LIKE ?)";
    $term = '%' . $searchTerm . '%';
    $params = array_merge($params, [$term, $term, $term, $term]);
    $types .= 'ssss';
}

if ($category !== '') {
    $sql .= " AND c.id = ?";
    $params[] = $category;
    $types .= 'i';
}

$sql .= " ORDER BY b.title";
$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$books = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();

$pageTitle = 'Search Books';
include __DIR__ . '/includes/header.php';
?>
<section class="card">
    <h2>Search for Books</h2>
    <form method="get" action="" class="search-form">
        <label for="q">Keyword</label>
        <input type="text" name="q" id="q" placeholder="Title, author, ISBN..." value="<?= sanitize($searchTerm); ?>">

        <label for="category">Category</label>
        <select name="category" id="category">
            <option value="">All categories</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id']; ?>" <?= $category === (string)$cat['id'] ? 'selected' : ''; ?>>
                    <?= sanitize($cat['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button type="submit">Search</button>
    </form>
</section>

<section class="card">
    <h3>Results</h3>
    <?php if ($books): ?>
        <div class="search-grid">
            <?php foreach ($books as $book): ?>
                <div class="search-result">
                    <h3><?= sanitize($book['title']); ?></h3>
                    <p><strong>Author:</strong> <?= sanitize($book['author']); ?></p>
                    <p><strong>Category:</strong> <?= sanitize($book['category_name'] ?? 'Uncategorized'); ?></p>
                    <p><strong>ISBN:</strong> <?= sanitize($book['isbn']); ?></p>
                    <p><?= sanitize(mb_strimwidth($book['description'] ?? '', 0, 120, '...')); ?></p>
                    <p><strong>Available:</strong> <?= sanitize((string)$book['quantity_available']); ?> of <?= sanitize((string)$book['total_quantity']); ?></p>
                    <?php if ((int)$book['quantity_available'] > 0): ?>
                        <form method="post" action="process_borrow.php">
                            <input type="hidden" name="book_id" value="<?= $book['id']; ?>">
                            <label for="duration-<?= $book['id']; ?>">Borrow duration</label>
                            <select name="duration" id="duration-<?= $book['id']; ?>" required>
                                <option value="7">7 days</option>
                                <option value="14">14 days</option>
                                <option value="30">30 days</option>
                            </select>
                            <button type="submit">Borrow</button>
                        </form>
                    <?php else: ?>
                        <p class="badge overdue">Not available</p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p>No books found. Try adjusting your search.</p>
    <?php endif; ?>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>