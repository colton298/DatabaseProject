<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include('config.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Restaurant') {
    die("Access denied. <a href='login.php'>Login</a>");
}

$user_id = $_SESSION['user_id'];

// ✅ Get the restaurant_id for the currently logged-in user
$restaurantQuery = $conn->prepare("SELECT restaurant_id, restaurant_name FROM restaurants WHERE user_id = ?");
$restaurantQuery->bind_param("i", $user_id);
$restaurantQuery->execute();
$restaurantResult = $restaurantQuery->get_result();

if ($restaurantResult->num_rows === 0) {
    // If the restaurant record doesn't exist yet, create one automatically
    $default_name = "My Restaurant " . $user_id;
    $insertRestaurant = $conn->prepare("INSERT INTO restaurants (user_id, restaurant_name) VALUES (?, ?)");
    $insertRestaurant->bind_param("is", $user_id, $default_name);
    $insertRestaurant->execute();
    $restaurant_id = $insertRestaurant->insert_id;
    $restaurant_name = $default_name;
} else {
    $restaurantData = $restaurantResult->fetch_assoc();
    $restaurant_id = $restaurantData['restaurant_id'];
    $restaurant_name = $restaurantData['restaurant_name'];
}

// Handle new plate submission
if (isset($_POST['add_plate'])) {
    $desc = $_POST['description'];
    $price = $_POST['price'];
    $quantity = $_POST['quantity'];
    $from = $_POST['available_from'];
    $until = $_POST['available_until'];

    $stmt = $conn->prepare("INSERT INTO plates (restaurant_id, description, price, quantity, available_from, available_until)
                            VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isdiss", $restaurant_id, $desc, $price, $quantity, $from, $until);

    if ($stmt->execute()) {
        $message = "Plate added successfully!";
    } else {
        $message = "Error adding plate: " . $stmt->error;
    }
}


// Retrieve existing plates for this restaurant
$platesQuery = $conn->prepare("SELECT * FROM plates WHERE restaurant_id = ? ORDER BY available_from DESC");
$platesQuery->bind_param("i", $restaurant_id);
$platesQuery->execute();
$plates = $platesQuery->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Restaurant Dashboard</title>
</head>
<body>
  <nav style="margin-bottom: 20px;">
    <a href="register.php">Register</a> |
    <a href="login.php">Login</a> |
    <a href="restaurant.php">Restaurant</a> |
    <a href="edit_profile.php">Edit Profile</a>
  </nav>
  <hr>

  <h2>Welcome, <?php echo htmlspecialchars($restaurant_name); ?>!</h2>
  <?php if (!empty($message)) echo "<p>$message</p>"; ?>

  <h3>Add a New Surplus Plate</h3>
  <form action="restaurant.php" method="POST">
    <label>Description:</label><br>
    <textarea name="description" required></textarea><br><br>

    <label>Price (USD):</label><br>
    <input type="number" step="0.01" name="price" required><br><br>

    <label>Quantity Available:</label><br>
    <input type="number" name="quantity" min="1" required><br><br>

    <label>Available From:</label><br>
    <input type="datetime-local" name="available_from" required><br><br>

    <label>Available Until:</label><br>
    <input type="datetime-local" name="available_until" required><br><br>

    <button type="submit" name="add_plate">Add Plate</button>
  </form>

  <hr>
  <h3>Current Plates</h3>
  <table border="1" cellpadding="6">
    <tr>
      <th>Description</th>
      <th>Price</th>
      <th>Quantity</th>
      <th>Available From</th>
      <th>Available Until</th>
      <th>Status</th>
    </tr>
    <?php while ($row = $plates->fetch_assoc()): ?>
      <tr>
        <td><?php echo htmlspecialchars($row['description']); ?></td>
        <td>$<?php echo $row['price']; ?></td>
        <td><?php echo $row['quantity']; ?></td>
        <td><?php echo $row['available_from']; ?></td>
        <td><?php echo $row['available_until']; ?></td>
        <td><?php echo $row['status']; ?></td>
      </tr>
    <?php endwhile; ?>
  </table>
</body>
</html>
