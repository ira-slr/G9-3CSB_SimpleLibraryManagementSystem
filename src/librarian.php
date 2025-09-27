<?php
session_start();
require_once __DIR__ . "/config/database.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'librarian') {
    header("Location: login.php");
    exit;
}

$db = new Database();
$conn = $db->getConnection();
$username = $_SESSION['username'];

// Logout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'logout') {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit;
}

// Optional: handle simple book add/remove/update here (omitted for brevity)

$books = $conn->query("SELECT id, title, author, year, status, borrowed_by FROM books ORDER BY id ASC")->fetchAll();
$users = $conn->query("SELECT id, username, role FROM users ORDER BY id ASC")->fetchAll();
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Librarian Dashboard</title>
  <style>
    table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
    th, td { padding: 6px 8px; border: 1px solid #ccc; text-align: left; }
  </style>
</head>
<body>
  <h1>Welcome, <?php echo htmlspecialchars($username); ?> (Librarian)</h1>

  <form method="post" style="display:inline;">
    <input type="hidden" name="action" value="logout">
    <button type="submit">Logout</button>
  </form>

  <h2>Users</h2>
  <table>
    <thead><tr><th>ID</th><th>Username</th><th>Role</th></tr></thead>
    <tbody>
      <?php foreach ($users as $u): ?>
        <tr>
          <td><?php echo $u['id']; ?></td>
          <td><?php echo htmlspecialchars($u['username']); ?></td>
          <td><?php echo htmlspecialchars($u['role']); ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <h2>Books</h2>
  <table>
    <thead><tr><th>ID</th><th>Title</th><th>Author</th><th>Year</th><th>Status</th><th>Borrowed By</th></tr></thead>
    <tbody>
      <?php foreach ($books as $b): ?>
        <tr>
          <td><?php echo $b['id']; ?></td>
          <td><?php echo htmlspecialchars($b['title']); ?></td>
          <td><?php echo htmlspecialchars($b['author']); ?></td>
          <td><?php echo htmlspecialchars($b['year']); ?></td>
          <td><?php echo htmlspecialchars($b['status']); ?></td>
          <td><?php echo htmlspecialchars($b['borrowed_by'] ?? ''); ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</body>
</html>
