<?php
session_start();
include 'config/database.php';

// Ensure only librarian can access
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'librarian') {
    header("Location: login.php");
    exit;
}

// Handle Add Book
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_book'])) {
    $book_id = $_POST['book_id']; // Librarian enters manually
    $title = $_POST['title'];
    $author = $_POST['author'];
    $pub_date = $_POST['publication_date'];
    $category = $_POST['category'];
    $description = $_POST['description'];

    // Handle cover image
    $cover_image = "";
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] == 0) {
        $cover_image = "assets/images/" . basename($_FILES['cover_image']['name']);
        move_uploaded_file($_FILES['cover_image']['tmp_name'], $cover_image);
    }

    $stmt = $conn->prepare("INSERT INTO books (book_id, title, author, publication_date, category, cover_image, description) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssss", $book_id, $title, $author, $pub_date, $category, $cover_image, $description);
    $stmt->execute();

    $success = "Book added successfully!";
}

// Fetch all books to display
$result = $conn->query("SELECT * FROM books ORDER BY book_id ASC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Librarian - Add Book</title>
</head>
<body>
    <h2>Welcome, <?php echo $_SESSION['username']; ?> (Librarian)</h2>

    <h3>Add Book</h3>
    <form method="POST" action="" enctype="multipart/form-data">
        <label>Book ID:</label><input type="text" name="book_id" required><br>
        <label>Title:</label><input type="text" name="title" required><br>
        <label>Author:</label><input type="text" name="author" required><br>
        <label>Publication Date:</label><input type="date" name="publication_date"><br>
        <label>Category:</label><input type="text" name="category"><br>
        <label>Cover Image:</label><input type="file" name="cover_image"><br>
        <label>Description:</label><br><textarea name="description" rows="4" cols="50"></textarea><br>
        <button type="submit" name="add_book">Add Book</button>
    </form>

    <?php if(isset($success)) echo "<p style='color:green;'>$success</p>"; ?>

    <h3>Catalog</h3>
    <table border="1">
        <tr>
            <th>Book ID</th><th>Title</th><th>Author</th><th>Publication Date</th><th>Category</th><th>Cover</th><th>Description</th>
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
        </tr>
        <?php endwhile; ?>
    </table>

    <a href="login.php">Logout</a>
</body>
</html>