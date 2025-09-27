<?php
session_start();
require_once __DIR__ . "/config/database.php";
$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $message = "Please enter username and password.";
    } else {
        $db = new Database();
        $conn = $db->getConnection();

        // plain text password check (per your request)
        $stmt = $conn->prepare("SELECT id, username, role, password FROM users WHERE username = :username LIMIT 1");
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch();

        if ($user && $password === $user['password']) {
            // login success
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            if ($user['role'] === 'librarian') {
                header("Location: librarian.php");
                exit;
            } else {
                header("Location: user.php");
                exit;
            }
        } else {
            $message = "Invalid username or password.";
        }
    }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Library — Login</title>
</head>
<body>
  <h1>Library Login</h1>

  <?php if ($message): ?>
    <p style="color: red;"><?php echo htmlspecialchars($message); ?></p>
  <?php endif; ?>

  <form method="post" action="">
    <label>Username</label><br>
    <input type="text" name="username" required><br><br>

    <label>Password</label><br>
    <input type="password" name="password" required><br><br>

    <button type="submit">Login</button>
  </form>
</body>
</html>
