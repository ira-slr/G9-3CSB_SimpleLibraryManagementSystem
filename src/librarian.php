<?php
session_start();
include 'config/database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'librarian') {
    header("Location: login.php");
    exit;
}

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

    $stmt = $conn->prepare("INSERT INTO books (book_id, title, author, publication_date, category, cover_image, description) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssss", $book_id, $title, $author, $pub_date, $category, $cover_image, $description);
    $stmt->execute();

    $success = "Book added successfully! (ID: $book_id)";
}

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