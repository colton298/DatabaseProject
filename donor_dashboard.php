<?php
session_start();
require_once "config.php";

// Donor access check
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "Donor") {
    header("Location: login.php");
    exit;
}

$donor_id = $_SESSION["user_id"];
$name = $_SESSION["name"];

// Fetch all available plates
$platesQuery = "
    SELECT p.plate_id, p.description, p.price, p.quantity, r.restaurant_name
    FROM plates p
    INNER JOIN restaurants r ON p.restaurant_id = r.restaurant_id
    WHERE p.status = 'Available' AND p.quantity > 0
";
$platesResult = $conn->query($platesQuery);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Donor Dashboard</title>
    <style>
        body { font-family: Arial; background: #f4f7f8; margin: 0; }
        nav {
            background-color: #4CAF50;
            padding: 15px; display: flex; justify-content: center; gap: 30px;
        }
        nav a { color: white; text-decoration: none; font-weight: bold; }
        .section {
            background: white; padding: 20px; max-width: 900px;
            margin: 40px auto; border-radius: 10px; box-shadow: 0 0 10px #0002;
        }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px; border-bottom: 1px solid #ccc; text-align: center; }
        th { background: #4CAF50; color: white; }
        button {
            padding: 7px 12px; background: #4CAF50; color: white;
            border: none; border-radius: 5px; cursor: pointer;
        }
    </style>
</head>
<body>

<!-- Navigation Bar -->
<nav>
    <a href="index.php">Home</a>
    <a href="donor_dashboard.php">Donate</a>
    <a href="donation_history.php">Donation History</a>
    <a href="logout.php">Logout</a>
</nav>

<h1 style="text-align:center;">Welcome, <?php echo htmlspecialchars($name); ?> (Donor)</h1>

<!-- AVAILABLE PLATES -->
<div class="section">
    <h2>Donate Plates</h2>

    <table>
        <tr>
            <th>Restaurant</th>
            <th>Description</th>
            <th>Price</th>
            <th>Available Qty</th>
            <th>Donate</th>
        </tr>

        <?php while ($row = $platesResult->fetch_assoc()): ?>
        <tr>
            <td><?php echo htmlspecialchars($row["restaurant_name"]); ?></td>
            <td><?php echo htmlspecialchars($row["description"]); ?></td>
            <td>$<?php echo $row["price"]; ?></td>
            <td><?php echo $row["quantity"]; ?></td>
            <td>
                <form method="POST" action="make_donation.php">
                    <input type="hidden" name="plate_id" value="<?php echo $row["plate_id"]; ?>">
                    <input type="number" name="quantity" min="1" max="<?php echo $row["quantity"]; ?>" required>
                    <button type="submit">Donate</button>
                </form>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>

</body>
</html>
