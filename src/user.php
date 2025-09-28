<?php
session_start();
include 'config/database.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php");
    exit;
}

$username = $_SESSION['username'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'logout') {
        session_unset();
        session_destroy();
        header("Location: login.php");
        exit;
    }

    if (isset($_POST['action']) && $_POST['action'] === 'borrow') {
        $book_id = $_POST['book_id'];
        $stmt = $conn->prepare("UPDATE books 
                                SET status='borrowed', borrowed_by=? 
                                WHERE book_id=? AND status='available'");
        $stmt->bind_param("ss", $username, $book_id);
        $stmt->execute();
        $stmt->close();
    }

    if (isset($_POST['action']) && $_POST['action'] === 'return') {
        $book_id = $_POST['book_id'];
        $stmt = $conn->prepare("UPDATE books 
                                SET status='available', borrowed_by=NULL 
                                WHERE book_id=? AND borrowed_by=?");
        $stmt->bind_param("ss", $book_id, $username);
        $stmt->execute();
        $stmt->close();
    }
}

function fetch_books($conn, $search = '') {
    if ($search !== '') {
        $stmt = $conn->prepare("SELECT book_id, title, author, publication_date, status, borrowed_by 
                                FROM books 
                                WHERE title LIKE ? OR author LIKE ? 
                                ORDER BY book_id ASC");
        $like = "%$search%";
        $stmt->bind_param("ss", $like, $like);
        $stmt->execute();
        $res = $stmt->get_result();
        return $res->fetch_all(MYSQLI_ASSOC);
    } else {
        $res = $conn->query("SELECT book_id, title, author, publication_date, status, borrowed_by 
                             FROM books ORDER BY book_id ASC");
        return $res->fetch_all(MYSQLI_ASSOC);
    }
}

if (isset($_GET['ajax_search'])) {
    $books = fetch_books($conn, trim($_GET['ajax_search']));
    foreach ($books as $b) {
        echo "<tr>
                <td>{$b['book_id']}</td>
                <td>" . htmlspecialchars($b['title']) . "</td>
                <td>" . htmlspecialchars($b['author']) . "</td>
                <td>" . htmlspecialchars($b['publication_date']) . "</td>
                <td>" . htmlspecialchars($b['status']) . "</td>
                <td>";
        if ($b['status'] === 'available') {
            echo "<form method='post' style='display:inline;'>
                    <input type='hidden' name='action' value='borrow'>
                    <input type='hidden' name='book_id' value='{$b['book_id']}'>
                    <button type='submit'>Borrow</button>
                  </form>";
        } elseif ($b['borrowed_by'] === $username) {
            echo "<form method='post' style='display:inline;'>
                    <input type='hidden' name='action' value='return'>
                    <input type='hidden' name='book_id' value='{$b['book_id']}'>
                    <button type='submit'>Return</button>
                  </form>";
        } else {
            echo "Borrowed by " . htmlspecialchars($b['borrowed_by']);
        }
        echo "</td></tr>";
    }
    exit;
}

$books = fetch_books($conn);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <link rel="stylesheet" type="text/css" href="../assets/css/user.css">
    <title>User Dashboard</title
</head>
<body>
<h1>Welcome, <?php echo htmlspecialchars($username); ?> (User)</h1>

<form method="post" class="inline">
    <input type="hidden" name="action" value="logout">
    <button type="submit">Logout</button>
</form>

<h2>Search Books</h2>
<input type="text" id="searchInput" placeholder="Enter title or author">

<h2>Available Books / Manage Borrowing</h2>
<table>
    <thead>
        <tr><th>Book ID</th><th>Title</th><th>Author</th><th>Publication Date</th><th>Status</th><th>Action</th></tr>
    </thead>
    <tbody id="booksTable">
        <?php foreach ($books as $b): ?>
        <tr>
            <td><?php echo $b['book_id']; ?></td>
            <td><?php echo htmlspecialchars($b['title']); ?></td>
            <td><?php echo htmlspecialchars($b['author']); ?></td>
            <td><?php echo htmlspecialchars($b['publication_date']); ?></td>
            <td><?php echo htmlspecialchars($b['status']); ?></td>
            <td>
                <?php if ($b['status']==='available'): ?>
                    <form method="post" class="inline">
                        <input type="hidden" name="action" value="borrow">
                        <input type="hidden" name="book_id" value="<?php echo $b['book_id']; ?>">
                        <button type="submit">Borrow</button>
                    </form>
                <?php elseif ($b['borrowed_by']===$username): ?>
                    <form method="post" class="inline">
                        <input type="hidden" name="action" value="return">
                        <input type="hidden" name="book_id" value="<?php echo $b['book_id']; ?>">
                        <button type="submit">Return</button>
                    </form>
                <?php else: ?>
                    Borrowed by <?php echo htmlspecialchars($b['borrowed_by']); ?>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<script>
const searchInput = document.getElementById('searchInput');
const booksTable = document.getElementById('booksTable');

searchInput.addEventListener('input', function() {
    const query = this.value;
    fetch(`<?php echo basename(__FILE__); ?>?ajax_search=${encodeURIComponent(query)}`)
        .then(r => r.text())
        .then(html => booksTable.innerHTML = html);
});
</script>
</body>
</html>
