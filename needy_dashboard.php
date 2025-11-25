<?php
session_start();
require_once "config.php";

// Redirect if not logged in or not a needy
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "Needy") {
    header("Location: login.php");
    exit;
}

$needy_id = $_SESSION["user_id"];
$name = $_SESSION["name"];

// Fetch all available plates paid by donors (fulfilled = FALSE)
$availableQuery = "
    SELECT d.donation_id, d.quantity, p.description, r.restaurant_name
    FROM donations d
    INNER JOIN plates p ON d.plate_id = p.plate_id
    INNER JOIN restaurants r ON p.restaurant_id = r.restaurant_id
    WHERE d.needy_id IS NULL AND d.fulfilled = FALSE
";
$availableResult = $conn->query($availableQuery);

// Fetch needy’s pickup history
$historyQuery = "
    SELECT d.donation_id, d.quantity, d.donated_at, d.fulfilled,
           p.description AS plate_description,
           r.restaurant_name
    FROM donations d
    INNER JOIN plates p ON d.plate_id = p.plate_id
    INNER JOIN restaurants r ON p.restaurant_id = r.restaurant_id
    WHERE d.needy_id = ?
    ORDER BY d.donated_at DESC
";
$historyStmt = $conn->prepare($historyQuery);
$historyStmt->bind_param("i", $needy_id);
$historyStmt->execute();
$historyResult = $historyStmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Needy Dashboard</title>
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

        .btn-reserve {
            padding: 7px 12px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .btn-reserve:hover {
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

<h1>Welcome, <?php echo htmlspecialchars($name); ?> (Needy)</h1>


<!-- AVAILABLE DONATED PLATES -->
<div class="section">
    <h2>Available Free Plates</h2>
    <table>
        <tr>
            <th>Restaurant</th>
            <th>Plate</th>
            <th>Quantity</th>
            <th>Reserve</th>
        </tr>

        <?php while ($row = $availableResult->fetch_assoc()): ?>
        <tr>
            <td><?php echo htmlspecialchars($row["restaurant_name"]); ?></td>
            <td><?php echo htmlspecialchars($row["description"]); ?></td>
            <td><?php echo $row["quantity"]; ?></td>
            <td>
                <form method="POST" action="needy_reserve.php">
                    <input type="hidden" name="donation_id" value="<?php echo $row["donation_id"]; ?>">
                    <input type="number" name="quantity" min="1" max="<?php echo $row["quantity"]; ?>" required>
                    <button class="btn-pickup" type="submit">Reserve</button>
                </form>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>

<!-- PICKUP HISTORY -->
<div class="section">
    <h2>Your Pickup History</h2>

    <?php if ($historyResult->num_rows === 0): ?>
        <p>You have not picked up any plates yet.</p>
    <?php else: ?>
    <table>
        <tr>
            <th>Date</th>
            <th>Restaurant</th>
            <th>Plate</th>
            <th>Quantity</th>
            <th>Status</th>
        </tr>

        <?php while ($row = $historyResult->fetch_assoc()): ?>
        <tr>
            <td><?php echo $row["donated_at"]; ?></td>
            <td><?php echo htmlspecialchars($row["restaurant_name"]); ?></td>
            <td><?php echo htmlspecialchars($row["plate_description"]); ?></td>
            <td><?php echo $row["quantity"]; ?></td>
            <td><?php echo $row["fulfilled"] ? "Picked Up" : "Reserved"; ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
    <?php endif; ?>
</div>

</body>
</html>
