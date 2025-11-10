<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include('config.php');

if (isset($_POST['register'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];
    $address = $_POST['address'];
    $phone = $_POST['phone'];

    $sql = "INSERT INTO users (name, email, password_hash, role, address, phone)
            VALUES ('$name', '$email', '$password', '$role', '$address', '$phone')";
    if ($conn->query($sql)) {
        $message = "Registration successful. <a href='login.php'>Login here</a>";
    } else {
        $message = "Error: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Register - Waste Not Kitchen</title>
</head>
<body>
  <nav style="margin-bottom: 20px;">
    <a href="register.php">Register</a> |
    <a href="login.php">Login</a> |
    <a href="restaurant.php">Restaurant</a> |
    <a href="edit_profile.php">Edit Profile</a>
  </nav>
  <hr>

  <h2>Register</h2>
  <?php if (!empty($message)) echo "<p>$message</p>"; ?>
  <form action="register.php" method="POST">
    <label>Full Name:</label><br>
    <input type="text" name="name" required><br><br>

    <label>Email:</label><br>
    <input type="email" name="email" required><br><br>

    <label>Password:</label><br>
    <input type="password" name="password" required><br><br>

    <label>Role:</label><br>
    <select name="role" required>
      <option value="Restaurant">Restaurant</option>
      <option value="Customer">Customer</option>
      <option value="Donor">Donor</option>
      <option value="Needy">Needy</option>
    </select><br><br>

    <label>Address:</label><br>
    <input type="text" name="address" required><br><br>

    <label>Phone (optional):</label><br>
    <input type="text" name="phone"><br><br>

    <button type="submit" name="register">Register</button>
  </form>
</body>
</html>
