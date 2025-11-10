<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include('config.php');

if (!isset($_SESSION['user_id'])) {
    die("You must be logged in to access this page. <a href='login.php'>Login</a>");
}

$user_id = $_SESSION['user_id'];
$result = $conn->query("SELECT * FROM users WHERE user_id='$user_id'");
$user = $result->fetch_assoc();

if (isset($_POST['update'])) {
    $name = $_POST['name'];
    $address = $_POST['address'];
    $phone = $_POST['phone'];
    $sql = "UPDATE users SET name='$name', address='$address', phone='$phone' WHERE user_id='$user_id'";
    if ($conn->query($sql)) {
        $message = "Profile updated successfully.";
        $result = $conn->query("SELECT * FROM users WHERE user_id='$user_id'");
        $user = $result->fetch_assoc(); // refresh user info
    } else {
        $message = "❌ Error: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Profile</title>
</head>
<body>
  <nav style="margin-bottom: 20px;">
    <a href="register.php">Register</a> |
    <a href="login.php">Login</a> |
    <a href="restaurant.php">Restaurant</a> |
    <a href="edit_profile.php">Edit Profile</a>
  </nav>
  <hr>

  <h2>Edit Your Profile</h2>
  <?php if (!empty($message)) echo "<p>$message</p>"; ?>

  <form action="edit_profile.php" method="POST">
    <label>Full Name:</label><br>
    <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required><br><br>

    <label>Address:</label><br>
    <input type="text" name="address" value="<?php echo htmlspecialchars($user['address']); ?>" required><br><br>

    <label>Phone:</label><br>
    <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>"><br><br>

    <button type="submit" name="update">Update Info</button>
  </form>
</body>
</html>
