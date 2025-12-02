<?php
session_start();
require_once "config.php";

// Ensure only customers can view this page
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Customer') {
    die("<h3 style='color:red; text-align:center;'>Access denied. Please log in as a Customer.</h3>");
}

$customer_id = $_SESSION['user_id'];
$customer_name = $_SESSION['name'];
$message = "";
$messageClass = "error";


if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $plate_id = intval($_POST['plate_id']);
    $quantity = intval($_POST['quantity']);

    // Check plate availability
    $query = "SELECT quantity, price FROM plates WHERE plate_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $plate_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $plate = $result->fetch_assoc();
    $stmt->close();

    if (!$plate) {
        $message = "Invalid plate selection.";
    } elseif ($quantity <= 0 || $quantity > $plate['quantity']) {
        $message = "Invalid quantity selected.";
    } else {
        // Create reservation
        $insert = "INSERT INTO reservations (plate_id, user_id, quantity, confirmed)
                   VALUES (?, ?, ?, TRUE)";
        $stmt = $conn->prepare($insert);
        $stmt->bind_param("iii", $plate_id, $customer_id, $quantity);
        $stmt->execute();
        $stmt->close();

        // Create transaction record
        $amount = $plate['price'] * $quantity;
        $txn = "INSERT INTO transactions (user_id, plate_id, amount, transaction_type)
                VALUES (?, ?, ?, 'Purchase')";
        $stmt = $conn->prepare($txn);
        $stmt->bind_param("iid", $customer_id, $plate_id, $amount);
        $stmt->execute();
        $stmt->close();

        // Update plate remaining quantity
        $update = "UPDATE plates SET quantity = quantity - ? WHERE plate_id = ?";
        $stmt = $conn->prepare($update);
        $stmt->bind_param("ii", $quantity, $plate_id);
        $stmt->execute();
        $stmt->close();

        $message = "Reservation successful!";
        $messageClass = "success";
    }
}

//FETCH AVAILABLE PLATES
$plates = $conn->query("
    SELECT p.*, r.restaurant_name
    FROM plates p
    JOIN restaurants r ON p.restaurant_id = r.restaurant_id
    WHERE p.status = 'Available' AND p.quantity > 0
    ORDER BY p.available_from DESC
");

//FETCH CUSTOMER RESERVATIONS
$reservations = $conn->prepare("
    SELECT res.*, p.description, p.price, r.restaurant_name
    FROM reservations res
    JOIN plates p ON res.plate_id = p.plate_id
    JOIN restaurants r ON p.restaurant_id = r.restaurant_id
    WHERE res.user_id = ?
    ORDER BY res.reserved_at DESC
");
$reservations->bind_param("i", $customer_id);
$reservations->execute();
$reservations_result = $reservations->get_result();
$reservations->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Customer Dashboard - Waste Not Kitchen</title>
    <style>
        body { font-family: Arial; background: #f4f7f8; margin: 0; padding: 0; }
        nav {
            background-color: #4CAF50; padding: 15px; display: flex;
            justify-content: center; gap: 30px;
        }
        nav a { color: white; text-decoration: none; font-weight: bold; }
        .container {
            max-width: 900px; background: white; margin: 40px auto;
            padding: 25px; border-radius: 10px; box-shadow: 0 0 10px #0002;
        }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: center; }
        th { background: #4CAF50; color: white; }
        button {
            padding: 8px 15px; background: #4CAF50; color: white;
            border: none; border-radius: 6px; cursor: pointer;
        }
        .message { margin-top: 20px; padding: 10px; border-radius: 6px; text-align: center; }
        .success { background: #e6ffe6; color: #0c750c; }
        .error { background: #ffe6e6; color: #b00020; }
    </style>
</head>

<body>

<!-- Navigation Bar -->
<nav>
    <a href="index.php">Home</a>
    <a href="customer_dashboard.php">Plates</a>
    <a href="customer_reservations.php">Reservations</a>
    <a href="logout.php">Logout</a>
</nav>


<div class="container">
    <h2>Welcome, <?php echo htmlspecialchars($customer_name); ?>!</h2>
    <h3>Available Plates</h3>

    <?php if (!empty($message)): ?>
        <div class="message <?php echo $messageClass; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <!-- Available Plates Table -->
    <table>
        <tr>
            <th>Restaurant</th>
            <th>Description</th>
            <th>Price</th>
            <th>Qty Left</th>
            <th>Available Until</th>
            <th>Action</th>
        </tr>

        <?php while ($p = $plates->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($p['restaurant_name']); ?></td>
                <td><?php echo htmlspecialchars($p['description']); ?></td>
                <td>$<?php echo number_format($p['price'], 2); ?></td>
                <td><?php echo $p['quantity']; ?></td>
                <td><?php echo htmlspecialchars($p['available_until']); ?></td>
                <td>
                    <form method="POST" style="display:flex; gap:5px;">
                        <input type="hidden" name="plate_id" value="<?php echo $p['plate_id']; ?>">
                        <input type="number" name="quantity" min="1" max="<?php echo $p['quantity']; ?>" required>
                        <button type="submit">Reserve</button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>

</div>

</body>
</html>
