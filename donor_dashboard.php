<?php
session_start();
require_once "config.php";

// Redirect if not logged in or not a donor
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

// Fetch donor's donation history
$historyQuery = "
    SELECT d.donation_id, d.quantity, d.donated_at,
           p.description AS plate_description,
           r.restaurant_name
    FROM donations d
    INNER JOIN plates p ON d.plate_id = p.plate_id
    INNER JOIN restaurants r ON p.restaurant_id = r.restaurant_id
    WHERE d.donor_id = ?
    ORDER BY d.donated_at DESC
";

$historyStmt = $conn->prepare($historyQuery);
$historyStmt->bind_param("i", $donor_id);
$historyStmt->execute();
$historyResult = $historyStmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Donor Dashboard</title>
    <style>
        body { font-family: Arial; background: #f4f7f8; margin: 0; padding: 0; }

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
        }

        /* Page Layout */
        .section {
            background: white;
            padding: 20px;
            max-width: 900px;
            margin: 40px auto;
            border-radius: 10px;
            box-shadow: 0 0 10px #0002;
        }

        h1 { color: #333; text-align: center; }

        table {
            width: 100%;
            margin-top: 20px;
            border-collapse: collapse;
        }

        th, td {
            padding: 10px;
            border-bottom: 1px solid #ccc;
            text-align: center;
        }

        th {
            background: #4CAF50;
            color: white;
        }

        .btn-donate {
            padding: 7px 12px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .btn-donate:hover {
            background: #45a049;
        }
    </style>
</head>
<body>

<!-- Navigation Bar -->
<nav>
    <a href="index.php">Home</a>
    <a href="logout.php">Logout</a>
</nav>

<h1>Welcome, <?php echo htmlspecialchars($name); ?> (Donor)</h1>


<!-- AVAILABLE PLATES -->
<div class="section">
    <h2>Donate Plates</h2>
    <table>
        <tr>
            <th>Restaurant</th>
            <th>Plate</th>
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
                    <button class="btn-donate" type="submit">Donate</button>
                </form>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>


<!-- DONATION HISTORY -->
<div class="section">
    <h2>Your Donation History</h2>

    <?php if ($historyResult->num_rows === 0): ?>
        <p>You have not made any donations yet.</p>
    <?php else: ?>
    <table>
        <tr>
            <th>Date</th>
            <th>Restaurant</th>
            <th>Plate</th>
            <th>Quantity</th>
        </tr>

        <?php while ($row = $historyResult->fetch_assoc()): ?>
        <tr>
            <td><?php echo $row["donated_at"]; ?></td>
            <td><?php echo htmlspecialchars($row["restaurant_name"]); ?></td>
            <td><?php echo htmlspecialchars($row["plate_description"]); ?></td>
            <td><?php echo $row["quantity"]; ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
    <?php endif; ?>
</div>

</body>
</html>
