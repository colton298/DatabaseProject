<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Waste Not Kitchen</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f8f8;
            margin: 0;
            padding: 0;
        }

        header {
            background-color: #4CAF50;
            color: white;
            padding: 20px;
            text-align: center;
        }

        main {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-top: 40px;
        }

        .nav-links {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 20px;
        }

        .nav-links a {
            background-color: white;
            border: 2px solid #4CAF50;
            color: #4CAF50;
            padding: 15px 25px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            transition: 0.3s;
        }

        .nav-links a:hover {
            background-color: #4CAF50;
            color: white;
        }
    </style>
</head>
<body>

<header>
    <h1>Waste Not Kitchen</h1>
</header>

<main>
    <h2>Welcome to WNK</h2>
    <p>Select a section to continue:</p>
    <div class="nav-links">
        <?php if (!isset($_SESSION['user_id'])): ?>
            <!-- Not logged in -->
            <a href="register.php">Register</a>
            <a href="login.php">Login</a>
        <?php else: ?>
            <!-- Logged in: show role-specific dashboards -->
            <?php if ($_SESSION['role'] === 'Administrator'): ?>
                <a href="admin_dashboard.php">Admin Dashboard</a>
                <a href="admin_members.php">Manage Members</a>
                <a href="admin_reports.php">Reports</a>
                <a href="admin_activity.php">Activity</a>
            <?php elseif ($_SESSION['role'] === 'Needy'): ?>
                <a href="needy_dashboard.php">Needy Dashboard</a>
            <?php elseif ($_SESSION['role'] === 'Restaurant'): ?>
                <a href="restaurant.php">Restaurant Dashboard</a>
                <a href="edit_profile.php">Edit Profile</a>
            <?php elseif ($_SESSION['role'] === 'Customer'): ?>
                <a href="customer_dashboard.php">Customer Dashboard</a>
                <a href="customer_orders.php">My Orders</a>
            <?php elseif ($_SESSION['role'] === 'Donor'): ?>
                <a href="donor_dashboard.php">Donor Dashboard</a>
                <a href="donate.php">Make Donation</a>
            <?php endif; ?>

            <a href="logout.php">Logout</a>
        <?php endif; ?>
    </div>
</main>

</body>
</html>
