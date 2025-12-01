<?php
session_start();
require_once "config.php";

// Check if user is logged in AND is an administrator
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "Administrator") {
    header("Location: login.php");
    exit;
}

// Get statistics
$users_count = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$restaurants_count = $conn->query("SELECT COUNT(*) as count FROM restaurants")->fetch_assoc()['count'];
$plates_count = $conn->query("SELECT COUNT(*) as count FROM plates WHERE status = 'Available'")->fetch_assoc()['count'];
$reservations_count = $conn->query("SELECT COUNT(*) as count FROM reservations")->fetch_assoc()['count'];
$donations_count = $conn->query("SELECT COUNT(*) as count FROM donations")->fetch_assoc()['count'];

// Get recent activity
$result = $conn->query("SELECT name, role, date_registered FROM users ORDER BY date_registered DESC LIMIT 5");
$recent_users = [];
while ($row = $result->fetch_assoc()) {
    $recent_users[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Waste Not Kitchen</title>
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
    <h1>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?> (Administrator)</h1>
    
    <!-- Quick Stats -->
    <div style="display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 30px; justify-content: center;">
        <div style="background: white; padding: 15px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); text-align: center; min-width: 150px;">
            <div style="font-size: 24px; font-weight: bold; color: #4CAF50;"><?php echo $users_count; ?></div>
            <div>Total Users</div>
        </div>
        <div style="background: white; padding: 15px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); text-align: center; min-width: 150px;">
            <div style="font-size: 24px; font-weight: bold; color: #4CAF50;"><?php echo $restaurants_count; ?></div>
            <div>Restaurants</div>
        </div>
        <div style="background: white; padding: 15px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); text-align: center; min-width: 150px;">
            <div style="font-size: 24px; font-weight: bold; color: #4CAF50;"><?php echo $plates_count; ?></div>
            <div>Available Plates</div>
        </div>
    </div>
    
    <!-- Quick Links -->
    <div style="display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 30px; justify-content: center;">
        <a href="admin_members.php" style="background: #4CAF50; color: white; padding: 15px 25px; text-decoration: none; border-radius: 8px; font-weight: bold; transition: 0.3s;">
            👥 Manage Members
        </a>
        <a href="admin_reports.php" style="background: #4CAF50; color: white; padding: 15px 25px; text-decoration: none; border-radius: 8px; font-weight: bold; transition: 0.3s;">
            📊 Generate Reports
        </a>
        <a href="admin_activity.php" style="background: #4CAF50; color: white; padding: 15px 25px; text-decoration: none; border-radius: 8px; font-weight: bold; transition: 0.3s;">
            📈 System Activity
        </a>
    </div>
    
    <!-- Recent Registrations -->
    <h3>Recent Member Registrations</h3>
    
    <?php if (empty($recent_users)): ?>
        <p style="text-align: center; color: #666;">No recent member registrations</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Role</th>
                    <th>Registered</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent_users as $user): ?>
                <tr>
                    <td><?php echo htmlspecialchars($user['name']); ?></td>
                    <td>
                        <span class="role-badge role-<?php echo strtolower($user['role']); ?>">
                            <?php echo $user['role']; ?>
                        </span>
                    </td>
                    <td><?php echo date('M j, Y', strtotime($user['date_registered'])); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

</body>
</html>