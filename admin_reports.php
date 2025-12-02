<?php
session_start();
require_once "config.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "Administrator") {
    header("Location: login.php");
    exit;
}

$reports = [];
$report_type = '';
$year = '';
$user_id = '';
$needy_id = '';
$donor_id = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $report_type = trim($_POST['report_type'] ?? '');
    $year = trim($_POST['year'] ?? '');
    $user_id = trim($_POST['user_id'] ?? '');
    $needy_id = trim($_POST['needy_id'] ?? '');
    $donor_id = trim($_POST['donor_id'] ?? '');
    
    // Validate year
    if (!is_numeric($year) || (int)$year < 2020 || (int)$year > 2030) {
        $_SESSION['error'] = "Invalid year selected.";
        header("Location: admin_reports.php");
        exit;
    }
    
    // Validate report type
    $valid_report_types = [
        'Restaurant Activity', 
        'Customer Purchases', 
        'Donor Purchases', 
        'Needy Free Plates', 
        'Donor Year-End Summary'
    ];
    
    if (!in_array($report_type, $valid_report_types)) {
        $_SESSION['error'] = "Invalid report type selected.";
        header("Location: admin_reports.php");
        exit;
    }
    
    switch ($report_type) {
        case 'Restaurant Activity':
            if (empty($user_id) || !is_numeric($user_id)) {
                $_SESSION['error'] = "Please select a restaurant.";
                header("Location: admin_reports.php");
                exit;
            }
            
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
            break;
            
        case 'Customer Purchases':
            if (empty($user_id) || !is_numeric($user_id)) {
                $_SESSION['error'] = "Please select a customer.";
                header("Location: admin_reports.php");
                exit;
            }
            
            $sql = "SELECT u.name, p.description, t.amount, t.transaction_date 
                    FROM transactions t
                    JOIN users u ON t.user_id = u.user_id
                    LEFT JOIN plates p ON t.plate_id = p.plate_id
                    WHERE YEAR(t.transaction_date) = ? AND u.user_id = ? 
                    AND t.transaction_type = 'Purchase'";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $year, $user_id);
            break;
            
        case 'Donor Purchases':
            if (empty($user_id) || !is_numeric($user_id)) {
                $_SESSION['error'] = "Please select a donor.";
                header("Location: admin_reports.php");
                exit;
            }
            
            $sql = "SELECT u.name, p.description, t.amount, t.transaction_date 
                    FROM transactions t
                    JOIN users u ON t.user_id = u.user_id
                    LEFT JOIN plates p ON t.plate_id = p.plate_id
                    WHERE YEAR(t.transaction_date) = ? AND u.user_id = ? 
                    AND t.transaction_type = 'Donation'";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $year, $user_id);
            break;
            
        case 'Needy Free Plates':
            if (empty($needy_id) || !is_numeric($needy_id)) {
                $_SESSION['error'] = "Please select a needy person.";
                header("Location: admin_reports.php");
                exit;
            }
            
            $sql = "SELECT 
                        u.name as needy_name,
                        p.description as plate_description,
                        r.restaurant_name,
                        d.quantity,
                        d.donated_at,
                        don.name as donor_name,
                        COALESCE(t.amount, 0) as plate_value
                    FROM donations d
                    JOIN users u ON d.needy_id = u.user_id
                    JOIN plates p ON d.plate_id = p.plate_id
                    JOIN restaurants r ON p.restaurant_id = r.restaurant_id
                    JOIN users don ON d.donor_id = don.user_id
                    LEFT JOIN transactions t ON t.plate_id = p.plate_id AND t.user_id = d.donor_id
                    WHERE YEAR(d.donated_at) = ? AND d.needy_id = ? AND d.fulfilled = 1
                    ORDER BY d.donated_at DESC";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $year, $needy_id);
            break;
            
        case 'Donor Year-End Summary':
            if (empty($donor_id) || !is_numeric($donor_id)) {
                $_SESSION['error'] = "Please select a donor.";
                header("Location: admin_reports.php");
                exit;
            }
            
            $sql = "SELECT 
                        d.donation_id,
                        p.description as plate_description,
                        r.restaurant_name,
                        d.quantity,
                        d.donated_at,
                        COALESCE(u.name, 'Not Assigned') as needy_recipient,
                        COALESCE(t.amount, 0) as donation_amount,
                        t.transaction_date,
                        CASE 
                            WHEN d.fulfilled = 1 THEN 'Fulfilled'
                            ELSE 'Pending'
                        END as status
                    FROM donations d
                    JOIN plates p ON d.plate_id = p.plate_id
                    JOIN restaurants r ON p.restaurant_id = r.restaurant_id
                    LEFT JOIN users u ON d.needy_id = u.user_id
                    LEFT JOIN transactions t ON t.plate_id = p.plate_id AND t.user_id = d.donor_id AND t.transaction_type = 'Donation'
                    WHERE YEAR(d.donated_at) = ? AND d.donor_id = ?
                    ORDER BY d.donated_at DESC";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $year, $donor_id);
            break;
    }
    
    // Execute the prepared statement
    if (isset($stmt)) {
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $reports[] = $row;
        }
        $stmt->close();
    }
}

// Get users for dropdowns
function getUsersByRole($conn, $role) {
    $users = [];
    $sql = "SELECT user_id, name FROM users WHERE role = ? ORDER BY name";
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
    <style>
        .report-summary {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #4CAF50;
        }
        .total-amount {
            font-size: 20px;
            font-weight: bold;
            color: #4CAF50;
        }
        .tax-info {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
    </style>
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
                <option value="Needy Free Plates" <?php echo $report_type == 'Needy Free Plates' ? 'selected' : ''; ?>>Needy Free Plates Report</option>
                <option value="Donor Year-End Summary" <?php echo $report_type == 'Donor Year-End Summary' ? 'selected' : ''; ?>>Donor Year-End Summary (Tax Report)</option>
            </select>
            
            <!-- User selection will be populated by JavaScript -->
            <div id="user_select_container" style="display: none; flex: 1;">
                <select name="user_id" id="user_select" style="padding: 8px; width: 100%;">
                    <option value="">Select User</option>
                </select>
            </div>
            
            <div id="needy_select_container" style="display: none; flex: 1;">
                <select name="needy_id" id="needy_select" style="padding: 8px; width: 100%;">
                    <option value="">Select Needy Person</option>
                </select>
            </div>
            
            <div id="donor_select_container" style="display: none; flex: 1;">
                <select name="donor_id" id="donor_select" style="padding: 8px; width: 100%;">
                    <option value="">Select Donor</option>
                </select>
            </div>
            
            <input type="number" name="year" placeholder="Year (e.g., 2025)" required min="2020" max="2030" 
                   value="<?php echo isset($year) ? $year : date('Y'); ?>" style="padding: 8px;">
            
            <button type="submit" class="btn">Generate Report</button>
        </div>
    </form>
    
    <!-- Report Results -->
    <?php if (!empty($reports)): ?>
    <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1);">
        <h3>
            <?php 
            $report_titles = [
                'Restaurant Activity' => 'Restaurant Activity Report',
                'Customer Purchases' => 'Customer Purchase Report',
                'Donor Purchases' => 'Donor Purchase Report',
                'Needy Free Plates' => 'Free Plates Received by Needy',
                'Donor Year-End Summary' => 'Donor Year-End Tax Summary'
            ];
            echo $report_titles[$report_type] ?? $report_type . ' Report';
            ?>
        </h3>
        
        <?php if ($report_type == 'Donor Year-End Summary'): ?>
        <div class="tax-info">
            <strong>📋 Tax Deduction Information:</strong><br>
            This report can be used for tax purposes. Keep for your records.<br>
            Total deductible amount for <?php echo $year; ?>: 
            <span class="total-amount">
                $<?php 
                    $total = 0;
                    foreach ($reports as $report) {
                        $total += $report['donation_amount'] ?? 0;
                    }
                    echo number_format($total, 2);
                ?>
            </span>
        </div>
        <?php endif; ?>
        
        <?php if ($report_type == 'Needy Free Plates'): ?>
        <div class="report-summary">
            <strong>Report Summary:</strong><br>
            Total free plates received in <?php echo $year; ?>: <strong><?php echo count($reports); ?></strong><br>
            Total value: 
            <span class="total-amount">
                $<?php 
                    $total = 0;
                    foreach ($reports as $report) {
                        $total += $report['plate_value'] ?? 0;
                    }
                    echo number_format($total, 2);
                ?>
            </span>
        </div>
        <?php endif; ?>
        
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
                    <?php elseif ($report_type == 'Needy Free Plates'): ?>
                        <th>Needy Person</th>
                        <th>Plate Description</th>
                        <th>Restaurant</th>
                        <th>Quantity</th>
                        <th>Value</th>
                        <th>Donor</th>
                        <th>Date Received</th>
                    <?php elseif ($report_type == 'Donor Year-End Summary'): ?>
                        <th>Donation ID</th>
                        <th>Plate Description</th>
                        <th>Restaurant</th>
                        <th>Quantity</th>
                        <th>Amount</th>
                        <th>Recipient</th>
                        <th>Date</th>
                        <th>Status</th>
                    <?php else: ?>
                        <th>User Name</th>
                        <th>Plate Description</th>
                        <th>Amount</th>
                        <th>Transaction Date</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($reports)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 20px; color: #666;">
                            No data found for this report.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($reports as $report): ?>
                    <tr>
                        <?php if ($report_type == 'Restaurant Activity'): ?>
                            <td><?php echo htmlspecialchars($report['restaurant_name'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($report['description'] ?? ''); ?></td>
                            <td>$<?php echo number_format($report['price'] ?? 0, 2); ?></td>
                            <td><?php echo $report['quantity'] ?? 0; ?></td>
                            <td><?php echo $report['reservations_sold'] ?? 0; ?></td>
                            <td><?php echo $report['total_plates_sold'] ?? 0; ?></td>
                            <td>$<?php echo number_format($report['total_revenue'] ?? 0, 2); ?></td>
                        <?php elseif ($report_type == 'Needy Free Plates'): ?>
                            <td><?php echo htmlspecialchars($report['needy_name'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($report['plate_description'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($report['restaurant_name'] ?? ''); ?></td>
                            <td><?php echo $report['quantity'] ?? 0; ?></td>
                            <td>$<?php echo number_format($report['plate_value'] ?? 0, 2); ?></td>
                            <td><?php echo htmlspecialchars($report['donor_name'] ?? ''); ?></td>
                            <td><?php echo isset($report['donated_at']) ? date('M j, Y', strtotime($report['donated_at'])) : ''; ?></td>
                        <?php elseif ($report_type == 'Donor Year-End Summary'): ?>
                            <td>#<?php echo $report['donation_id'] ?? ''; ?></td>
                            <td><?php echo htmlspecialchars($report['plate_description'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($report['restaurant_name'] ?? ''); ?></td>
                            <td><?php echo $report['quantity'] ?? 0; ?></td>
                            <td>$<?php echo number_format($report['donation_amount'] ?? 0, 2); ?></td>
                            <td><?php echo htmlspecialchars($report['needy_recipient'] ?? 'Not Assigned'); ?></td>
                            <td><?php echo isset($report['donated_at']) ? date('M j, Y', strtotime($report['donated_at'])) : ''; ?></td>
                            <td>
                                <?php if (isset($report['status'])): ?>
                                <span style="padding: 3px 8px; border-radius: 10px; font-size: 12px; 
                                      background: <?php echo $report['status'] == 'Fulfilled' ? '#c6f6d5' : '#fed7d7'; ?>; 
                                      color: <?php echo $report['status'] == 'Fulfilled' ? '#276749' : '#c53030'; ?>;">
                                    <?php echo $report['status']; ?>
                                </span>
                                <?php endif; ?>
                            </td>
                        <?php else: ?>
                            <td><?php echo htmlspecialchars($report['name'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($report['description'] ?? ''); ?></td>
                            <td>$<?php echo number_format($report['amount'] ?? 0, 2); ?></td>
                            <td>
                                <?php if (isset($report['transaction_date'])): ?>
                                    <?php echo date('M j, Y H:i', strtotime($report['transaction_date'])); ?>
                                <?php endif; ?>
                            </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        
        <?php if ($report_type == 'Donor Year-End Summary' && !empty($reports)): ?>
        <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
            <h4>Year-End Tax Summary for <?php echo $year; ?></h4>
            <p><strong>Total Donations:</strong> <?php echo count($reports); ?></p>
            <p><strong>Total Deductible Amount:</strong> 
                <span style="color: #4CAF50; font-weight: bold;">
                    $<?php 
                        $total = 0;
                        foreach ($reports as $report) {
                            $total += $report['donation_amount'] ?? 0;
                        }
                        echo number_format($total, 2);
                    ?>
                </span>
            </p>
            <p><strong>Fulfilled Donations:</strong> 
                <?php 
                    $fulfilled = 0;
                    foreach ($reports as $report) {
                        if (($report['status'] ?? '') == 'Fulfilled') $fulfilled++;
                    }
                    echo $fulfilled . ' of ' . count($reports);
                ?>
            </p>
            <p style="font-size: 12px; color: #666; margin-top: 10px;">
                * This report can be used for tax deduction purposes. Consult with your tax advisor.
            </p>
        </div>
        <?php endif; ?>
        
        <!-- Print Button -->
        <div style="margin-top: 20px; text-align: center;">
            <button onclick="window.print()" class="btn" style="background: #666;">
                🖨️ Print Report
            </button>
        </div>
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
    
    // Hide all select containers first
    document.getElementById('user_select_container').style.display = 'none';
    document.getElementById('needy_select_container').style.display = 'none';
    document.getElementById('donor_select_container').style.display = 'none';
    
    if (reportType && users[reportType]) {
        let selectElement, containerId;
        
        if (reportType === 'Needy Free Plates') {
            selectElement = document.getElementById('needy_select');
            containerId = 'needy_select_container';
        } else if (reportType === 'Donor Year-End Summary') {
            selectElement = document.getElementById('donor_select');
            containerId = 'donor_select_container';
        } else {
            selectElement = document.getElementById('user_select');
            containerId = 'user_select_container';
        }
        
        // Populate dropdown
        selectElement.innerHTML = '<option value="">Select ' + 
            (reportType === 'Needy Free Plates' ? 'Needy Person' : 
             reportType === 'Donor Year-End Summary' ? 'Donor' : 'User') + 
            '</option>';
        
        users[reportType].forEach(user => {
            selectElement.innerHTML += `<option value="${user.user_id}">${user.name}</option>`;
        });
        
        // Show the appropriate container
        document.getElementById(containerId).style.display = 'block';
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    updateUserDropdown();
});
</script>

</body>
</html>