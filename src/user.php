<?php
session_start();
require_once __DIR__ . "/config/database.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'user') {
    header("Location: login.php");
    exit;
}

$db = new Database();
$conn = $db->getConnection();
$username = $_SESSION['username'];

// Handle logout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'logout') {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit;
}

// Borrow action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'borrow') {
    $book_id = intval($_POST['book_id']);
    $stmt = $conn->prepare("UPDATE books SET status = 'borrowed', borrowed_by = :username WHERE id = :id AND status = 'available'");
    $stmt->execute([':username' => $username, ':id' => $book_id]);
}

// Return action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'return') {
    $book_id = intval($_POST['book_id']);
    $stmt = $conn->prepare("UPDATE books SET status = 'available', borrowed_by = NULL WHERE id = :id AND borrowed_by = :username");
    $stmt->execute([':id' => $book_id, ':username' => $username]);
}

// Function to fetch books
function fetch_books($conn, $search = '') {
    if ($search !== '') {
        $stmt = $conn->prepare("SELECT id, title, author, year, status, borrowed_by 
                                FROM books 
                                WHERE title LIKE :search OR author LIKE :search
                                ORDER BY id ASC");
        $stmt->execute([':search' => "%$search%"]);
        return $stmt->fetchAll();
    } else {
        return $conn->query("SELECT id, title, author, year, status, borrowed_by FROM books ORDER BY id ASC")->fetchAll();
    }
}

// Handle AJAX search
if (isset($_GET['ajax_search'])) {
    $search_term = trim($_GET['ajax_search']);
    $books = fetch_books($conn, $search_term);
    foreach ($books as $b) {
        echo "<tr>";
        echo "<td>{$b['id']}</td>";
        echo "<td>" . htmlspecialchars($b['title']) . "</td>";
        echo "<td>" . htmlspecialchars($b['author']) . "</td>";
        echo "<td>" . htmlspecialchars($b['year']) . "</td>";
        echo "<td>" . htmlspecialchars($b['status']) . "</td>";
        echo "<td>";
        if ($b['status'] === 'available') {
            echo '<form method="post" style="display:inline;">
                    <input type="hidden" name="action" value="borrow">
                    <input type="hidden" name="book_id" value="' . $b['id'] . '">
                    <button type="submit">Borrow</button>
                  </form>';
        } elseif ($b['borrowed_by'] === $username) {
            echo '<form method="post" style="display:inline;">
                    <input type="hidden" name="action" value="return">
                    <input type="hidden" name="book_id" value="' . $b['id'] . '">
                    <button type="submit">Return</button>
                  </form>';
        } else {
            echo "Borrowed by " . htmlspecialchars($b['borrowed_by']);
        }
        echo "</td>";
        echo "</tr>";
    }
    exit;
}

// Initial load
$books = fetch_books($conn);
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>User Dashboard</title>
  <style>
    table { border-collapse: collapse; width: 100%; }
    th, td { padding: 6px 8px; border: 1px solid #ccc; text-align: left; }
    form.inline { display: inline; }
    #searchInput { padding: 4px 6px; width: 250px; margin-bottom: 10px; }
  </style>
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
      <tr>
        <th>ID</th><th>Title</th><th>Author</th><th>Year</th><th>Status</th><th>Action</th>
      </tr>
    </thead>
    <tbody id="booksTable">
    <?php foreach ($books as $b): ?>
      <tr>
        <td><?php echo $b['id']; ?></td>
        <td><?php echo htmlspecialchars($b['title']); ?></td>
        <td><?php echo htmlspecialchars($b['author']); ?></td>
        <td><?php echo htmlspecialchars($b['year']); ?></td>
        <td><?php echo htmlspecialchars($b['status']); ?></td>
        <td>
          <?php if ($b['status'] === 'available'): ?>
            <form method="post" class="inline">
              <input type="hidden" name="action" value="borrow">
              <input type="hidden" name="book_id" value="<?php echo $b['id']; ?>">
              <button type="submit">Borrow</button>
            </form>
          <?php elseif ($b['borrowed_by'] === $username): ?>
            <form method="post" class="inline">
              <input type="hidden" name="action" value="return">
              <input type="hidden" name="book_id" value="<?php echo $b['id']; ?>">
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
            .then(response => response.text())
            .then(html => {
                booksTable.innerHTML = html;
            });
    });
  </script>
</body>
</html>