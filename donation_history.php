<?php
session_start();
require_once "config.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "Donor") {
    header("Location: login.php");
    exit;
}

$donor_id = $_SESSION["user_id"];
$name = $_SESSION["name"];

// Fetch donor donation history
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

$stmt = $conn->prepare($historyQuery);
$stmt->bind_param("i", $donor_id);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Donation History - Waste Not Kitchen</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f7f8; margin: 0; padding: 0; }
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
        .container {
            max-width: 900px;
            background: white;
            margin: 40px auto;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 0 10px #0002;
        }
        h1, h2 { text-align: center; color: #333; margin: 0 0 10px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: center; }
        th { background: #4CAF50; color: white; }
        .empty { text-align: center; padding: 20px; color: #666; }
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

<div class="container">
    <h1>Donation History</h1>
    <h2>Welcome, <?php echo htmlspecialchars($name); ?></h2>

    <?php if ($result->num_rows === 0): ?>
        <p class="empty">You have not made any donations yet.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Restaurant</th>
                    <th>Plate</th>
                    <th>Quantity</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['donated_at']); ?></td>
                        <td><?php echo htmlspecialchars($row['restaurant_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['plate_description']); ?></td>
                        <td><?php echo (int)$row['quantity']; ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

</body>
</html>
