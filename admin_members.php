<?php
session_start();
require_once "config.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "Administrator") {
    header("Location: login.php");
    exit;
}

// Handle search
$search = $_GET['search'] ?? '';
$role_filter = $_GET['role'] ?? '';

// Build query
$where = [];
if (!empty($search)) {
    $where[] = "(name LIKE '%" . $conn->real_escape_string($search) . "%' OR email LIKE '%" . $conn->real_escape_string($search) . "%')";
}
if (!empty($role_filter)) {
    $where[] = "role = '" . $conn->real_escape_string($role_filter) . "'";
}

$sql = "SELECT * FROM users";
if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY date_registered DESC";

$result = $conn->query($sql);
$members = [];
while ($row = $result->fetch_assoc()) {
    $members[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Members - Waste Not Kitchen</title>
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
    <h1>Manage Members</h1>
    
    <a href="admin_dashboard.php" class="back-link">← Back to Dashboard</a>
    
    <!-- Search Form -->
    <form method="GET" style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <input type="text" name="search" placeholder="Search by name or email" 
                   value="<?php echo htmlspecialchars($search); ?>" 
                   style="flex: 1; min-width: 200px; padding: 8px;">
            
            <select name="role" style="padding: 8px;">
                <option value="">All Roles</option>
                <option value="Administrator" <?php echo $role_filter == 'Administrator' ? 'selected' : ''; ?>>Administrator</option>
                <option value="Restaurant" <?php echo $role_filter == 'Restaurant' ? 'selected' : ''; ?>>Restaurant</option>
                <option value="Customer" <?php echo $role_filter == 'Customer' ? 'selected' : ''; ?>>Customer</option>
                <option value="Donor" <?php echo $role_filter == 'Donor' ? 'selected' : ''; ?>>Donor</option>
                <option value="Needy" <?php echo $role_filter == 'Needy' ? 'selected' : ''; ?>>Needy</option>
            </select>
            
            <button type="submit" class="btn">Search</button>
        </div>
    </form>
    
    <!-- Members Table -->
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Phone</th>
                <th>Registered</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($members)): ?>
                <tr>
                    <td colspan="6" style="text-align: center; padding: 20px;">No members found</td>
                </tr>
            <?php else: ?>
                <?php foreach ($members as $member): ?>
                <tr>
                    <td><?php echo $member['user_id']; ?></td>
                    <td><?php echo htmlspecialchars($member['name']); ?></td>
                    <td><?php echo htmlspecialchars($member['email']); ?></td>
                    <td>
                        <span class="role-badge role-<?php echo strtolower($member['role']); ?>">
                            <?php echo $member['role']; ?>
                        </span>
                    </td>
                    <td><?php echo $member['phone'] ? htmlspecialchars($member['phone']) : 'N/A'; ?></td>
                    <td><?php echo date('M j, Y', strtotime($member['date_registered'])); ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>