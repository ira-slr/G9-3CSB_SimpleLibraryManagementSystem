<?php
session_start();
include 'config/database.php'; // ✅ Use first code's connection ($conn)

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'librarian') {
    header("Location: login.php");
    exit;
}

/* ---------- ADD BOOK (from first code) ---------- */
if (isset($_POST['add_book'])) {
    $lastBook = $conn->query("SELECT book_id FROM books ORDER BY book_id DESC LIMIT 1");
    if ($lastBook->num_rows == 0) {
        $book_id = "BK001";
    } else {
        $row = $lastBook->fetch_assoc();
        $lastID = $row['book_id'];
        $num = intval(substr($lastID, 2)) + 1;
        $book_id = "BK" . str_pad($num, 3, "0", STR_PAD_LEFT);
    }

    $title = $_POST['title'];
    $author = $_POST['author'];
    $pub_date = $_POST['publication_date'];
    $category = $_POST['category'];
    $description = $_POST['description'];

    $cover_image = "";
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] == 0) {
        $cover_image = "assets/images/" . basename($_FILES['cover_image']['name']);
        move_uploaded_file($_FILES['cover_image']['tmp_name'], $cover_image);
    }

    $stmt = $conn->prepare("INSERT INTO books (book_id, title, author, publication_date, category, cover_image, description) 
                            VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssss", $book_id, $title, $author, $pub_date, $category, $cover_image, $description);
    $stmt->execute();

    $success = "Book added successfully! (ID: $book_id)";
}

/* ---------- DELETE BOOK (from second code, adapted) ---------- */
if (isset($_POST['delete_id'])) {
    $deleteId = $_POST['delete_id'];
    $deleteQuery = $conn->prepare("DELETE FROM books WHERE book_id = ?");
    $deleteQuery->bind_param("s", $deleteId);

    if ($deleteQuery->execute()) {
        header("Location: librarian.php?deleted=1");
        exit;
    } else {
        echo "<h3 style='color:red;'>Delete failed: " . $conn->error . "</h3>";
    }
}

/* ---------- FETCH BOOKS ---------- */
$result = $conn->query("SELECT * FROM books ORDER BY book_id ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Librarian Dashboard</title>
    <link rel="stylesheet" href="./assets/css/librarian.css">
    <script src="./assets/js/librarian.js" defer></script>
</head>
<body>
    <h2>Welcome, <?php echo $_SESSION['username']; ?> (Librarian)</h2>

    <!-- ✅ ADD BOOK FORM -->
    <h3>Add Book</h3>
    <form method="POST" action="" enctype="multipart/form-data" class="add-book-form">
        <label>Title:</label><input type="text" name="title" required><br>
        <label>Author:</label><input type="text" name="author" required><br>
        <label>Publication Date:</label><input type="date" name="publication_date"><br>
        <label>Category:</label><input type="text" name="category"><br>
        <label>Cover Image:</label><input type="file" name="cover_image"><br>
        <label>Description:</label><br><textarea name="description" rows="4" cols="50"></textarea><br>
        <button type="submit" name="add_book">Add Book</button>
    </form>

    <?php if(isset($success)) echo "<p class='success'>$success</p>"; ?>
    <?php if (isset($_GET['deleted'])) echo "<p class='success'>✅ Book deleted successfully.</p>"; ?>
    <?php if (isset($_GET['updated'])) echo "<p class='success'>✅ Book updated successfully.</p>"; ?>

    <!-- ✅ CATALOG TABLE -->
    <h3>📚 Catalog</h3>
    <table border="1">
        <tr>
            <th>Book ID</th>
            <th>Title</th>
            <th>Author</th>
            <th>Publication Date</th>
            <th>Category</th>
            <th>Cover</th>
            <th>Description</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
        <?php while($book = $result->fetch_assoc()): ?>
        <tr>
            <td><?php echo $book['book_id']; ?></td>
            <td><?php echo $book['title']; ?></td>
            <td><?php echo $book['author']; ?></td>
            <td><?php echo $book['publication_date']; ?></td>
            <td><?php echo $book['category']; ?></td>
            <td>
                <?php if($book['cover_image'] != ""): ?>
                    <img src="<?php echo $book['cover_image']; ?>" width="50">
                <?php endif; ?>
            </td>
            <td><?php echo $book['description']; ?></td>
             <td>
                <?php if($book['status'] == 'available'): ?>
                    <span class="status available">Available</span>
                <?php else: ?>
                    <span class="status unavailable">Unavailable</span>
                <?php endif; ?>
            </td>
            <td>
                <!-- Edit -->
                <form method="GET" action="./edit.php" style="display:inline;">
                    <input type="hidden" name="id" value="<?php echo $book['book_id']; ?>">
                    <input type="submit" value="Edit" class="edit-btn">
                </form>
                <!-- Delete -->
                <form method="POST" onsubmit="return confirmDelete(this);" style="display:inline;">
                    <input type="hidden" name="delete_id" value="<?php echo $book['book_id']; ?>">
                    <input type="submit" value="Delete" class="delete-btn">
                </form>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>

    <a href="login.php" class="logout">⬅ Log Out</a>
</body>
</html>
