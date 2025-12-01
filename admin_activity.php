<?php
session_start();
require_once "config.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "Administrator") {
    header("Location: login.php");
    exit;
}

// Get recent transactions
$result = $conn->query("
    SELECT t.transaction_id, u.name, p.description, t.amount, t.transaction_type, t.transaction_date 
    FROM transactions t 
    LEFT JOIN users u ON t.user_id = u.user_id 
    LEFT JOIN plates p ON t.plate_id = p.plate_id 
    ORDER BY t.transaction_date DESC 
    LIMIT 10
");

$recent_transactions = [];
while ($row = $result->fetch_assoc()) {
    $recent_transactions[] = $row;
}

// Get system stats
$total_plates = $conn->query("SELECT COUNT(*) as count FROM plates")->fetch_assoc()['count'];
$active_plates = $conn->query("SELECT COUNT(*) as count FROM plates WHERE status = 'Available'")->fetch_assoc()['count'];
$donation_result = $conn->query("SELECT SUM(amount) as total FROM transactions WHERE transaction_type = 'Donation'");
$donation_row = $donation_result->fetch_assoc();
$total_donations = $donation_row['total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Activity - Waste Not Kitchen</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<!-- Navigation Bar -->
<nav>
    <a href="admin_dashboard.php">Dashboard</a>
    <a href="admin_members.php">Members</a>
    <a href="admin_reports.php">Reports</a>
    <a href="admin_activity.php">Activity</a>
    <a href="logout.php">Logout</a>
</nav>

<div class="container">
    <h1>System Activity</h1>
    
    <a href="admin_dashboard.php" class="back-link">← Back to Dashboard</a>
    
    <!-- Quick Stats -->
    <div style="display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 30px; justify-content: center;">
        <div style="background: white; padding: 15px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); text-align: center; min-width: 150px;">
            <div style="font-size: 24px; font-weight: bold; color: #4CAF50;"><?php echo $total_plates; ?></div>
            <div>Total Plates</div>
        </div>
        <div style="background: white; padding: 15px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); text-align: center; min-width: 150px;">
            <div style="font-size: 24px; font-weight: bold; color: #4CAF50;"><?php echo $active_plates; ?></div>
            <div>Active Plates</div>
        </div>
        <div style="background: white; padding: 15px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); text-align: center; min-width: 150px;">
            <div style="font-size: 24px; font-weight: bold; color: #4CAF50;">$<?php echo number_format($total_donations, 2); ?></div>
            <div>Total Donations</div>
        </div>
    </div>
    
    <!-- Recent Transactions -->
    <h3>Recent Transactions</h3>
    
    <?php if (empty($recent_transactions)): ?>
        <p style="text-align: center; color: #666;">No recent transactions found</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Description</th>
                    <th>Amount</th>
                    <th>Type</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent_transactions as $transaction): ?>
                <tr>
                    <td>#<?php echo $transaction['transaction_id']; ?></td>
                    <td><?php echo htmlspecialchars($transaction['name']); ?></td>
                    <td><?php echo htmlspecialchars($transaction['description'] ?: 'N/A'); ?></td>
                    <td><strong>$<?php echo number_format($transaction['amount'], 2); ?></strong></td>
                    <td>
                        <span class="role-badge">
                            <?php echo $transaction['transaction_type']; ?>
                        </span>
                    </td>
                    <td><?php echo date('M j, Y H:i', strtotime($transaction['transaction_date'])); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

</body>
</html>