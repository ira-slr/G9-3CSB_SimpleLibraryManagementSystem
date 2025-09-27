<?php
session_start();
include 'config/database.php';

if (isset($_POST['register'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $role = $_POST['role'];

    $check = $conn->query("SELECT * FROM users WHERE username='$username'");
    if ($check->num_rows > 0) {
        $reg_error = "Username already exists!";
    } else {
        $conn->query("INSERT INTO users (username, password, role) VALUES ('$username','$password','$role')");
        $reg_success = "Registration successful! You can now log in.";
    }
}

if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $result = $conn->query("SELECT * FROM users WHERE username='$username' AND password='$password'");
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        if ($user['role'] == 'librarian') {
            header("Location: librarian.php");
        } else {
            header("Location: user.php");
        }
        exit;
    } else {
        $login_error = "Invalid username or password!";
    }
}
?>

<!DOCTYPE html>
<html>
    <head>
        <title>Login / Register</title>
    </head>
    <body>
        <h2>Login</h2>
        <form method="POST" action="">
            <label>Username:</label><input type="text" name="username" required><br>
            <label>Password:</label><input type="text" name="password" required><br>
            <button type="submit" name="login">Login</button>
        </form>
        <?php if(isset($login_error)) echo "<p style='color:red;'>$login_error</p>"; ?>

        <h2>Register</h2>
        <form method="POST" action="">
            <label>Username:</label><input type="text" name="username" required><br>
            <label>Password:</label><input type="text" name="password" required><br>
            <label>Role:</label>
            <select name="role">
                <option value="user">User</option>
                <option value="librarian">Librarian</option>
            </select><br>
            <button type="submit" name="register">Register</button>
        </form>
        <?php if(isset($reg_error)) echo "<p style='color:red;'>$reg_error</p>"; ?>
        <?php if(isset($reg_success)) echo "<p style='color:green;'>$reg_success</p>"; ?>
    </body>
</html>