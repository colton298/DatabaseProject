<?php
session_start();
require_once "config.php";

// Ensure only customers can view this page
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Customer') {
    die("<h3 style='color:red; text-align:center;'>Access denied. Please log in as a Customer.</h3>");
}

$customer_id = $_SESSION['user_id'];
$customer_name = $_SESSION['name'];

// Fetch customer reservations
$query = $conn->prepare("
    SELECT res.*, p.description, p.price, r.restaurant_name
    FROM reservations res
    JOIN plates p ON res.plate_id = p.plate_id
    JOIN restaurants r ON p.restaurant_id = r.restaurant_id
    WHERE res.user_id = ?
    ORDER BY res.reserved_at DESC
");
$query->bind_param("i", $customer_id);
$query->execute();
$reservations = $query->get_result();
$query->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Your Reservations - Waste Not Kitchen</title>
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
    <h2>Your Reservations</h2>

    <?php if ($reservations->num_rows > 0): ?>
        <table>
            <tr>
                <th>Restaurant</th>
                <th>Description</th>
                <th>Qty</th>
                <th>Total Paid</th>
                <th>Time</th>
            </tr>

            <?php while ($r = $reservations->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($r['restaurant_name']); ?></td>
                    <td><?php echo htmlspecialchars($r['description']); ?></td>
                    <td><?php echo $r['quantity']; ?></td>
                    <td>$<?php echo number_format($r['quantity'] * $r['price'], 2); ?></td>
                    <td><?php echo htmlspecialchars($r['reserved_at']); ?></td>
                </tr>
            <?php endwhile; ?>
        </table>

    <?php else: ?>
        <p>No reservations yet.</p>
    <?php endif; ?>
</div>

</body>
</html>
