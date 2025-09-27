<?php
session_start();
include 'config/database.php'; // ✅ use same connection as other pages

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'librarian') {
    header("Location: login.php");
    exit;
}

$id = $_GET['id'] ?? null;
if (!$id) {
    die("Invalid book ID.");
}

// Fetch book info
$stmt = $conn->prepare("SELECT * FROM books WHERE book_id = ?");
$stmt->bind_param("s", $id);
$stmt->execute();
$result = $stmt->get_result();
$book = $result->fetch_assoc();
if (!$book) {
    die("Book not found.");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = $_POST['title'];
    $author      = $_POST['author'];
    $pub_date    = $_POST['publication_date'];
    $category    = $_POST['category'];
    $description = $_POST['description'];

    // Keep old cover if not replaced
    $cover_image = $book['cover_image'];
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] == 0) {
        $cover_image = "assets/images/" . basename($_FILES['cover_image']['name']);
        move_uploaded_file($_FILES['cover_image']['tmp_name'], $cover_image);
    }

    $update = $conn->prepare("UPDATE books 
                              SET title=?, author=?, publication_date=?, category=?, cover_image=?, description=? 
                              WHERE book_id=?");
    $update->bind_param("sssssss", $title, $author, $pub_date, $category, $cover_image, $description, $id);

    if ($update->execute()) {
        header("Location: librarian.php?updated=1");
        exit;
    } else {
        echo "<h3 class='error-msg'>Update failed: " . $conn->error . "</h3>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Book</title>
    <link rel="stylesheet" href="./assets/css/edit.css">
</head>
<body>
    <div class="container">
        <h2>✏️ Edit Book</h2>
        <form method="POST" enctype="multipart/form-data">
            <label>Title:</label>
            <input type="text" name="title" value="<?php echo htmlspecialchars($book['title']); ?>" required>

            <label>Author:</label>
            <input type="text" name="author" value="<?php echo htmlspecialchars($book['author']); ?>" required>

            <label>Publication Date:</label>
            <input type="date" name="publication_date" value="<?php echo $book['publication_date']; ?>">

            <label>Category:</label>
            <input type="text" name="category" value="<?php echo htmlspecialchars($book['category']); ?>">

            <label>Cover Image:</label>
            <?php if($book['cover_image']): ?>
                <div><img src="<?php echo $book['cover_image']; ?>" width="80"></div>
            <?php endif; ?>
            <input type="file" name="cover_image">

            <label>Description:</label>
            <textarea name="description" rows="4"><?php echo htmlspecialchars($book['description']); ?></textarea>

            <button type="submit">💾 Save Changes</button>
        </form>

        <a href="librarian.php">⬅ Back to Catalog</a>
    </div>
    <script src="./assets/js/edit.js"></script>
</body>
</html>
