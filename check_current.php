<?php
echo "<h2>Checking Current Database</h2>";

// Try to connect to wnk_database
$conn = new mysqli('localhost', 'root', 'root', 'wnk_database');

if ($conn->connect_error) {
    echo "<span style='color:red'>❌ Cannot connect to wnk_database</span><br>";
    echo "Error: " . $conn->connect_error;
} else {
    echo "<span style='color:green'>✓ Connected to wnk_database successfully!</span><br><br>";
    
    // Check tables
    $tables = ['users', 'restaurants', 'plates', 'reservations', 'donations', 'transactions'];
    $all_good = true;
    
    foreach ($tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if ($result->num_rows > 0) {
            echo "<span style='color:green'>✓ Table exists: $table</span><br>";
        } else {
            echo "<span style='color:orange'>⚠ Table missing: $table</span><br>";
            $all_good = false;
        }
    }
    
    echo "<br>";
    
    if ($all_good) {
        echo "<div style='background:#e6ffe6; padding:10px;'>";
        echo "<h3>✅ Database looks complete!</h3>";
        echo "You can use the existing wnk_database.<br>";
        echo "<a href='fix_config.php' style='display:inline-block; background:#4CAF50; color:white; padding:10px; margin:10px 0; text-decoration:none;'>Click here to fix config</a>";
        echo "</div>";
    } else {
        echo "<div style='background:#ffe6e6; padding:10px;'>";
        echo "<h3>⚠ Some tables are missing</h3>";
        echo "You might need to run setup again.";
        echo "</div>";
    }
    
    // Show sample data
    echo "<h3>Sample Data Check:</h3>";
    
    // Check users
    $result = $conn->query("SELECT COUNT(*) as count FROM users");
    $row = $result->fetch_assoc();
    echo "Total users: " . $row['count'] . "<br>";
    
    // Check admin
    $result = $conn->query("SELECT name, email, role FROM users WHERE role = 'Administrator' LIMIT 1");
    if ($row = $result->fetch_assoc()) {
        echo "Admin user: " . $row['name'] . " (" . $row['email'] . ")<br>";
    }
    
    // Check Johns
    $result = $conn->query("SELECT COUNT(*) as count FROM users WHERE name LIKE '%John%'");
    $row = $result->fetch_assoc();
    echo "Users named 'John': " . $row['count'] . "<br>";
    
    $conn->close();
}
?>