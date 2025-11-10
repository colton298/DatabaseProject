<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include('config.php');

if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $result = $conn->query("SELECT * FROM users WHERE email='$email'");
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password_hash'])) {
            $_SESSION['user_id'] = $row['user_id'];
            $_SESSION['role'] = $row['role'];
            header("Location: edit_profile.php");
            exit;
        } else {
            $message = "❌ Invalid password.";
        }
    } else {
        $message = "❌ User not found.";
    }
}
?>

<nav style="margin-bottom: 20px;">
  <a href="register.php">Register</a> |
  <a href="login.php">Login</a> |
  <a href="restaurant.php">Restaurant</a> |
  <a href="edit_profile.php">Edit Profile</a>
</nav>
<hr>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>Login</title></head>
<body>
  <?php if (!empty($message)) echo "<p>$message</p>"; ?>
  <h2>Login</h2>
  <form action="login.php" method="POST">
    <input type="email" name="email" placeholder="Email" required><br>
    <input type="password" name="password" placeholder="Password" required><br>
    <button type="submit" name="login">Login</button>
  </form>
</body>
</html>
