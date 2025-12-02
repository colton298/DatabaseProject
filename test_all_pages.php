<?php
echo "<h2>Page Testing Guide</h2>";

$pages = [
    'login.php' => 'Login Page - Test with admin@wnk.com / password',
    'admin_dashboard.php' => 'Admin Dashboard (login required)',
    'admin_members.php' => 'Manage Members - Search for "John"',
    'admin_reports.php' => 'Generate Reports',
    'admin_activity.php' => 'System Activity',
    'logout.php' => 'Logout'
];

echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Page</th><th>Description</th><th>Test Action</th></tr>";

foreach ($pages as $page => $description) {
    echo "<tr>";
    echo "<td><a href='$page' target='_blank'>$page</a></td>";
    echo "<td>$description</td>";
    
    if ($page == 'login.php') {
        echo "<td><strong>Test Credentials:</strong><br>admin@wnk.com / password</td>";
    } elseif ($page == 'admin_members.php') {
        echo "<td>Search for 'John' to test search functionality</td>";
    } else {
        echo "<td>Click link to test</td>";
    }
    
    echo "</tr>";
}

echo "</table>";

echo "<hr>";
echo "<h3>If pages don't work:</h3>";
echo "<ol>";
echo "<li>Make sure config.php points to wnk_database</li>";
echo "<li>Try <a href='fix_config.php'>fix_config.php</a></li>";
echo "<li>Check MAMP is running</li>";
echo "</ol>";
?>