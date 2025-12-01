<?php
session_start();
require_once "config.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "Administrator") {
    header("Location: login.php");
    exit;
}

$reports = [];
$report_type = '';

if ($_POST) {
    $report_type = $_POST['report_type'];
    $year = $_POST['year'];
    $user_id = $_POST['user_id'] ?? null;
    
    switch ($report_type) {
        case 'Restaurant Activity':
            $sql = "SELECT r.restaurant_name, p.description, p.price, p.quantity, 
                           COUNT(res.reservation_id) as reservations_sold,
                           SUM(res.quantity) as total_plates_sold,
                           SUM(p.price * res.quantity) as total_revenue
                    FROM restaurants r
                    JOIN plates p ON r.restaurant_id = p.restaurant_id
                    LEFT JOIN reservations res ON p.plate_id = res.plate_id 
                    WHERE YEAR(p.available_from) = ? AND r.user_id = ?
                    GROUP BY p.plate_id";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $year, $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $reports[] = $row;
            }
            break;
            
        case 'Customer Purchases':
            $sql = "SELECT u.name, p.description, t.amount, t.transaction_date 
                    FROM transactions t
                    JOIN users u ON t.user_id = u.user_id
                    LEFT JOIN plates p ON t.plate_id = p.plate_id
                    WHERE YEAR(t.transaction_date) = ? AND u.user_id = ? 
                    AND t.transaction_type = 'Purchase'";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $year, $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $reports[] = $row;
            }
            break;
            
        case 'Donor Purchases':
            $sql = "SELECT u.name, p.description, t.amount, t.transaction_date 
                    FROM transactions t
                    JOIN users u ON t.user_id = u.user_id
                    LEFT JOIN plates p ON t.plate_id = p.plate_id
                    WHERE YEAR(t.transaction_date) = ? AND u.user_id = ? 
                    AND t.transaction_type = 'Donation'";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $year, $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $reports[] = $row;
            }
            break;
    }
}

// Get users for dropdowns
function getUsersByRole($conn, $role) {
    $users = [];
    $sql = "SELECT user_id, name FROM users WHERE role = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $role);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    return $users;
}

$restaurants = getUsersByRole($conn, 'Restaurant');
$customers = getUsersByRole($conn, 'Customer');
$donors = getUsersByRole($conn, 'Donor');
$needy = getUsersByRole($conn, 'Needy');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Reports - Waste Not Kitchen</title>
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
    <h1>Generate Reports</h1>
    
    <a href="admin_dashboard.php" class="back-link">← Back to Dashboard</a>
    
    <!-- Report Form -->
    <form method="POST" style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap; margin-bottom: 15px;">
            <select name="report_type" required onchange="updateUserDropdown()" id="report_type" style="padding: 8px; flex: 1;">
                <option value="">Select Report Type</option>
                <option value="Restaurant Activity" <?php echo $report_type == 'Restaurant Activity' ? 'selected' : ''; ?>>Restaurant Activity Report</option>
                <option value="Customer Purchases" <?php echo $report_type == 'Customer Purchases' ? 'selected' : ''; ?>>Customer Purchase Report</option>
                <option value="Donor Purchases" <?php echo $report_type == 'Donor Purchases' ? 'selected' : ''; ?>>Donor Purchase Report</option>
            </select>
            
            <select name="user_id" id="user_select" required style="padding: 8px; display: none; flex: 1;">
                <option value="">Select User</option>
            </select>
            
            <input type="number" name="year" placeholder="Year (e.g., 2025)" required min="2020" max="2030" 
                   value="<?php echo isset($year) ? $year : date('Y'); ?>" style="padding: 8px;">
            
            <button type="submit" class="btn">Generate Report</button>
        </div>
    </form>
    
    <!-- Report Results -->
    <?php if (!empty($reports)): ?>
    <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1);">
        <h3>Report Results</h3>
        <table>
            <thead>
                <tr>
                    <?php if ($report_type == 'Restaurant Activity'): ?>
                        <th>Restaurant</th>
                        <th>Plate Description</th>
                        <th>Price</th>
                        <th>Quantity Available</th>
                        <th>Reservations Sold</th>
                        <th>Total Plates Sold</th>
                        <th>Total Revenue</th>
                    <?php else: ?>
                        <th>User Name</th>
                        <th>Plate Description</th>
                        <th>Amount</th>
                        <th>Transaction Date</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reports as $report): ?>
                <tr>
                    <?php if ($report_type == 'Restaurant Activity'): ?>
                        <td><?php echo htmlspecialchars($report['restaurant_name']); ?></td>
                        <td><?php echo htmlspecialchars($report['description']); ?></td>
                        <td>$<?php echo number_format($report['price'], 2); ?></td>
                        <td><?php echo $report['quantity']; ?></td>
                        <td><?php echo $report['reservations_sold']; ?></td>
                        <td><?php echo $report['total_plates_sold']; ?></td>
                        <td>$<?php echo number_format($report['total_revenue'], 2); ?></td>
                    <?php else: ?>
                        <td><?php echo htmlspecialchars($report['name']); ?></td>
                        <td><?php echo htmlspecialchars($report['description']); ?></td>
                        <td>$<?php echo number_format($report['amount'], 2); ?></td>
                        <td><?php echo date('M j, Y H:i', strtotime($report['transaction_date'])); ?></td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<script>
const users = {
    'Restaurant Activity': <?php echo json_encode($restaurants); ?>,
    'Customer Purchases': <?php echo json_encode($customers); ?>,
    'Donor Purchases': <?php echo json_encode($donors); ?>,
    'Needy Free Plates': <?php echo json_encode($needy); ?>,
    'Donor Year-End Summary': <?php echo json_encode($donors); ?>
};

function updateUserDropdown() {
    const reportType = document.getElementById('report_type').value;
    const userSelect = document.getElementById('user_select');
    
    if (reportType && users[reportType]) {
        userSelect.innerHTML = '<option value="">Select User</option>';
        users[reportType].forEach(user => {
            userSelect.innerHTML += `<option value="${user.user_id}">${user.name}</option>`;
        });
        userSelect.style.display = 'block';
    } else {
        userSelect.style.display = 'none';
    }
}
</script>

</body>
</html>