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

// Handle logout (no external logout.php)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'logout') {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit;
}

// Borrow action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'borrow') {
    $book_id = intval($_POST['book_id']);
    // update only if available
    $stmt = $conn->prepare("UPDATE books SET status = 'borrowed', borrowed_by = :username WHERE id = :id AND status = 'available'");
    $stmt->execute([':username' => $username, ':id' => $book_id]);
}

// Return action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'return') {
    $book_id = intval($_POST['book_id']);
    // allow return only if this user borrowed it
    $stmt = $conn->prepare("UPDATE books SET status = 'available', borrowed_by = NULL WHERE id = :id AND borrowed_by = :username");
    $stmt->execute([':id' => $book_id, ':username' => $username]);
}

// Fetch books list
$books = $conn->query("SELECT id, title, author, year, status, borrowed_by FROM books ORDER BY id ASC")->fetchAll();
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>User Dashboard</title>
  <style>
    table { border-collapse: collapse; width: 100%; }
    th, td { padding: 6px 8px; border: 1px solid #ccc; text-align: left; }
  </style>
</head>
<body>
  <h1>Welcome, <?php echo htmlspecialchars($username); ?> (User)</h1>

  <form method="post" style="display:inline;">
    <input type="hidden" name="action" value="logout">
    <button type="submit">Logout</button>
  </form>

  <h2>Available Books / Manage Borrowing</h2>

  <table>
    <thead>
      <tr>
        <th>ID</th><th>Title</th><th>Author</th><th>Year</th><th>Status</th><th>Action</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($books as $b): ?>
      <tr>
        <td><?php echo $b['id']; ?></td>
        <td><?php echo htmlspecialchars($b['title']); ?></td>
        <td><?php echo htmlspecialchars($b['author']); ?></td>
        <td><?php echo htmlspecialchars($b['year']); ?></td>
        <td><?php echo htmlspecialchars($b['status']); ?></td>
        <td>
          <?php if ($b['status'] === 'available'): ?>
            <form method="post" style="display:inline;">
              <input type="hidden" name="action" value="borrow">
              <input type="hidden" name="book_id" value="<?php echo $b['id']; ?>">
              <button type="submit">Borrow</button>
            </form>
          <?php elseif ($b['borrowed_by'] === $username): ?>
            <form method="post" style="display:inline;">
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
</body>
</html>
