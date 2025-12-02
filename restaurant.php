<?php
session_start();
require_once "config.php";

// Require restaurant login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Restaurant') {
    die("<h3 style='color:red; text-align:center;'>Access denied. Please log in as a Restaurant.</h3>");
}

$user_id = $_SESSION['user_id'];
$message = "";
$messageClass = "error";

// Retrieve restaurant_id for this user
$restaurant_id = null;
$query = "SELECT restaurant_id, restaurant_name FROM restaurants WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $restaurant_id = $row['restaurant_id'];
    $restaurant_name = $row['restaurant_name'];
} else {
    die("<h3 style='color:red; text-align:center;'>Error: No restaurant record found for this user. Please register first.</h3>");
}
$stmt->close();

// Handle plate submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $description = trim($_POST["description"]);
    $price = trim($_POST["price"]);
    $quantity = trim($_POST["quantity"]);
    $available_from = trim($_POST["available_from"]);
    $available_until = trim($_POST["available_until"]);

    if (empty($description) || empty($price) || empty($quantity) || empty($available_from) || empty($available_until)) {
        $message = "All fields are required.";
    } elseif (!is_numeric($price) || !is_numeric($quantity) || $quantity <= 0) {
        $message = "Price and quantity must be valid positive numbers.";
    } else {
        $insert = "INSERT INTO plates (restaurant_id, description, price, quantity, available_from, available_until)
           VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insert);
        $stmt->bind_param("isdiss", $restaurant_id, $description, $price, $quantity, $available_from, $available_until);

        if ($stmt->execute()) {
            $message = "Plate advertised successfully.";
            $messageClass = "success";
        } else {
            $message = "Error adding plate: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Fetch existing plates for display
$plates = [];
$fetch = "SELECT * FROM plates WHERE restaurant_id = ? ORDER BY available_from DESC";
$stmt = $conn->prepare($fetch);
$stmt->bind_param("i", $restaurant_id);
$stmt->execute();
$plates = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Restaurant - Waste Not Kitchen</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f7f8;
            margin: 0;
            padding: 0;
        }

        /* Navigation Bar */
        nav {
            background-color: #4CAF50;
            padding: 15px;
            display: flex;
            justify-content: center;
            gap: 30px;
        }

        nav a {
            color: white;
            text-decoration: none;
            font-weight: bold;
            font-size: 16px;
            transition: 0.3s;
        }

        nav a:hover {
            text-decoration: underline;
        }

        .container {
            max-width: 800px;
            background: white;
            margin: 50px auto;
            padding: 30px 40px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        h2, h3 {
            text-align: center;
            color: #333;
        }

        label {
            display: block;
            margin-top: 15px;
            color: #333;
        }

        input, textarea {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 6px;
        }

        button {
            margin-top: 20px;
            width: 100%;
            background: #4CAF50;
            color: white;
            padding: 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
        }

        button:hover {
            background: #45a049;
        }

        .message {
            margin-top: 20px;
            text-align: center;
            padding: 10px;
            border-radius: 6px;
        }

        .error {
            color: #b00020;
            background-color: #ffe6e6;
        }

        .success {
            color: #0c750c;
            background-color: #e6ffe6;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 25px;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
        }

        th {
            background-color: #4CAF50;
            color: white;
        }

        td {
            background-color: #f9f9f9;
        }
    </style>
</head>
<body>

<!-- Navigation Bar -->
<nav>
    <a href="index.php">Home</a>
    <a href="edit_profile.php">Edit Profile</a>
    <a href="logout.php">Logout</a>
</nav>

<div class="container">
    <h2><?php echo htmlspecialchars($restaurant_name); ?> - Restaurant Dashboard</h2>
    <h3>Advertise Surplus Plates</h3>

    <form method="POST" action="restaurant.php">
        <label for="description">Plate Description:</label>
        <textarea name="description" id="description" rows="3" required></textarea>

        <label for="price">Fixed Price ($):</label>
        <input type="number" step="0.01" name="price" id="price" required>

        <label for="quantity">Quantity Available:</label>
        <input type="number" name="quantity" id="quantity" min="1" required>

        <label for="available_from">Available From:</label>
        <input type="datetime-local" name="available_from" id="available_from" required>

        <label for="available_until">Available Until:</label>
        <input type="datetime-local" name="available_until" id="available_until" required>

        <button type="submit">Post Plate</button>
    </form>

    <?php if (!empty($message)): ?>
        <div class="message <?php echo $messageClass; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <h3>Current Plates</h3>
    <?php if ($plates->num_rows > 0): ?>
        <table>
            <tr>
                <th>Description</th>
                <th>Price ($)</th>
                <th>Quantity</th>
                <th>Available From</th>
                <th>Available Until</th>
            </tr>
            <?php while ($row = $plates->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['description']); ?></td>
                    <td><?php echo number_format($row['price'], 2); ?></td>
                    <td><?php echo (int)$row['quantity']; ?></td>
                    <td><?php echo htmlspecialchars($row['available_from']); ?></td>
                    <td><?php echo htmlspecialchars($row['available_until']); ?></td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <p style="text-align:center;">No plates have been advertised yet.</p>
    <?php endif; ?>
</div>

</body>
</html>
