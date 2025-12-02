<?php
session_start();
require_once 'config.php';

echo "<h2>Quick Login Test</h2>";

// Test database connection
echo "1. Database: " . ($conn->connect_error ? "❌ FAILED" : "✓ Connected") . "<br>";

// Test admin credentials
$test_email = 'admin@wnk.com';
$test_password = 'password';

$stmt = $conn->prepare("SELECT user_id, name, role, password_hash FROM users WHERE email = ?");
$stmt->bind_param("s", $test_email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    
    if (password_verify($test_password, $user['password_hash'])) {
        echo "2. Admin login: <span style='color:green'>✓ WORKS</span><br>";
        echo "   Name: " . $user['name'] . "<br>";
        echo "   Role: " . $user['role'] . "<br>";
    } else {
        echo "2. Admin login: <span style='color:red'>❌ Password incorrect</span><br>";
    }
} else {
    echo "2. Admin login: <span style='color:red'>❌ Admin user not found</span><br>";
}

// Test search functionality
$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE name LIKE '%John%'");
$row = $result->fetch_assoc();
echo "3. Search test (Johns): " . $row['count'] . " users found<br>";

echo "<hr>";
echo "<h3>Test Links:</h3>";
echo "<ul>";
echo "<li><a href='login.php'>Login Page</a></li>";
echo "<li><a href='admin_members.php'>Members Page (will redirect to login)</a></li>";
echo "<li><a href='test_all_pages.php'>Test All Pages</a></li>";
echo "</ul>";
?>