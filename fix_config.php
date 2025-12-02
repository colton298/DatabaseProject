<?php
// This will create/update your config.php file
$config_content = '<?php
// Database Configuration for existing wnk_database
$servername = "localhost";
$username = "root";
$password = "root"; 
$dbname = "wnk_database";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>';

if (file_put_contents('config.php', $config_content)) {
    echo "<h2 style='color:green;'>✅ Config File Updated!</h2>";
    echo "Updated config.php to use <strong>wnk_database</strong><br><br>";
    
    // Test the connection
    require_once 'config.php';
    
    if ($conn->connect_error) {
        echo "<span style='color:red'>❌ Still having connection issues</span><br>";
        echo "Error: " . $conn->connect_error;
    } else {
        echo "<span style='color:green'>✓ Connection test passed!</span><br><br>";
        
        // Quick data check
        $result = $conn->query("SELECT COUNT(*) as count FROM users");
        $row = $result->fetch_assoc();
        echo "Users in database: " . $row['count'] . "<br>";
        
        echo "<div style='background:#e6ffe6; padding:15px; margin:15px 0;'>";
        echo "<h3>Ready to Test!</h3>";
        echo "<a href='login.php' style='display:inline-block; background:#4CAF50; color:white; padding:12px 24px; text-decoration:none; font-size:18px; margin:10px;'>Test Login Page</a>";
        echo "</div>";
    }
} else {
    echo "<h2 style='color:red;'>❌ Could not update config.php</h2>";
    echo "You may need to set file permissions.";
}
?>