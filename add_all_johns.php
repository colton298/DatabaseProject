<?php
require_once 'config.php';

echo "<style>
    body { font-family: Arial; margin: 20px; }
    .role { padding: 5px 10px; border-radius: 4px; color: white; font-weight: bold; }
    .admin { background: #c53030; }
    .restaurant { background: #276749; }
    .customer { background: #2c5aa0; }
    .donor { background: #6b46c1; }
    .needy { background: #dd6b20; }
</style>";

echo "<h2>Adding John Users to All Roles</h2>";

$password_hash = password_hash('password', PASSWORD_DEFAULT);

// ====================
// JOHN USERS IN EVERY ROLE
// ====================
$john_users = [
    // ADMINISTRATOR Johns
    ['Administrator', 'John Admin', 'john.admin@wnk.com', '101 Admin St', '555-0101', NULL],
    
    // RESTAURANT Johns (owners)
    ['Restaurant', "John's Pizza Palace", 'john.pizza@wnk.com', '202 Pizza Ave', '555-0202', NULL],
    ['Restaurant', "John's Burger Joint", 'john.burger@wnk.com', '303 Burger Blvd', '555-0203', NULL],
    ['Restaurant', "John's Sushi Spot", 'john.sushi@wnk.com', '404 Sushi St', '555-0204', NULL],
    
    // DONOR Johns
    ['Donor', 'John Generous', 'john.generous@email.com', '601 Charity Ln', '555-0401', '4222222222222001'],
    ['Donor', 'John Philanthropist', 'john.philanthropist@email.com', '602 Charity Ln', '555-0402', '4222222222222002'],
    ['Donor', 'John Benefactor', 'john.benefactor@email.com', '603 Charity Ln', '555-0403', '4222222222222003'],
    ['Donor', 'John Altruist', 'john.altruist@email.com', '604 Charity Ln', '555-0404', '4222222222222004'],
    
    // NEEDY Johns
    ['Needy', 'John Recipient', 'john.recipient@email.com', '701 Help Ave', NULL, NULL],
    ['Needy', 'John Beneficiary', 'john.beneficiary@email.com', '702 Help Ave', NULL, NULL],
    ['Needy', 'John Needy', 'john.needy@email.com', '703 Help Ave', NULL, NULL],
    ['Needy', 'John Homeless', 'john.homeless@email.com', '704 Help Ave', NULL, NULL],
];

echo "<h3>Adding 20+ John Users Across All Roles:</h3>";

$added = 0;
$skipped = 0;
$stmt = $conn->prepare("INSERT INTO users (role, name, email, password_hash, address, phone, credit_card) VALUES (?, ?, ?, ?, ?, ?, ?)");

foreach ($john_users as $user) {
    // Check if email already exists
    $check = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
    $check->bind_param("s", $user[2]);
    $check->execute();
    
    if ($check->get_result()->num_rows > 0) {
        echo "⚠ <span class='role {$user[0]}'>{$user[0]}</span> {$user[1]} - Already exists<br>";
        $skipped++;
        continue;
    }
    
    $stmt->bind_param("sssssss", $user[0], $user[1], $user[2], $password_hash, $user[3], $user[4], $user[5]);
    
    if ($stmt->execute()) {
        echo "✓ <span class='role {$user[0]}'>{$user[0]}</span> {$user[1]}<br>";
        $added++;
    } else {
        echo "❌ Failed: {$user[1]} - " . $conn->error . "<br>";
    }
}

echo "<br><strong>Added: {$added} new John users</strong><br>";
echo "<strong>Skipped: {$skipped} existing users</strong><br><br>";

// ====================
// ADD RESTAURANTS FOR RESTAURANT JOHNS
// ====================
echo "<h3>Creating Restaurants for Restaurant Johns:</h3>";

// Get the restaurant Johns we just added
$result = $conn->query("SELECT user_id, name FROM users WHERE name LIKE \"John's%\" AND role = 'Restaurant'");
$restaurant_johns = [];
while ($row = $result->fetch_assoc()) {
    $restaurant_johns[] = $row;
}

foreach ($restaurant_johns as $john) {
    $restaurant_name = str_replace("John's ", "", $john['name']);
    $description = "Owned by {$john['name']} - Quality food at great prices!";
    
    $stmt = $conn->prepare("INSERT INTO restaurants (user_id, restaurant_name, description) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $john['user_id'], $restaurant_name, $description);
    
    if ($stmt->execute()) {
        echo "✓ Restaurant: {$restaurant_name} (Owner: {$john['name']})<br>";
    }
}

// ====================
// ADD PLATES FOR THESE RESTAURANTS
// ====================
echo "<h3>Adding Plates to John's Restaurants:</h3>";

// Get restaurant IDs for John's restaurants
$restaurant_data = [];
$result = $conn->query("SELECT r.restaurant_id, r.restaurant_name FROM restaurants r 
                       JOIN users u ON r.user_id = u.user_id 
                       WHERE u.name LIKE \"John's%\"");
while ($row = $result->fetch_assoc()) {
    $restaurant_data[] = $row;
}

$plates = [
    ["John's Pizza Palace" => [
        ['Pepperoni Pizza', 15.99, 8, '2025-01-20 11:00:00', '2025-01-20 14:00:00'],
        ['Cheese Pizza', 13.99, 10, '2025-01-20 11:00:00', '2025-01-20 14:00:00'],
        ['Vegetarian Pizza', 16.99, 6, '2025-01-21 11:00:00', '2025-01-21 14:00:00'],
    ]],
    ["John's Burger Joint" => [
        ['Classic Burger', 9.99, 12, '2025-01-20 12:00:00', '2025-01-20 15:00:00'],
        ['Bacon Cheeseburger', 11.99, 8, '2025-01-20 12:00:00', '2025-01-20 15:00:00'],
        ['Veggie Burger', 8.99, 10, '2025-01-21 12:00:00', '2025-01-21 15:00:00'],
    ]],
    ["John's Sushi Spot" => [
        ['California Roll Set', 14.99, 15, '2025-01-20 13:00:00', '2025-01-20 16:00:00'],
        ['Salmon Nigiri Platter', 18.99, 8, '2025-01-20 13:00:00', '2025-01-20 16:00:00'],
        ['Tempura Roll', 16.99, 10, '2025-01-21 13:00:00', '2025-01-21 16:00:00'],
    ]],
];

foreach ($plates as $restaurant_plates) {
    foreach ($restaurant_plates as $restaurant_name => $plate_list) {
        // Find restaurant ID
        $restaurant_id = null;
        foreach ($restaurant_data as $r) {
            if ($r['restaurant_name'] == $restaurant_name) {
                $restaurant_id = $r['restaurant_id'];
                break;
            }
        }
        
        if ($restaurant_id) {
            foreach ($plate_list as $plate) {
                $stmt = $conn->prepare("INSERT INTO plates (restaurant_id, description, price, quantity, available_from, available_until) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("isdiss", $restaurant_id, $plate[0], $plate[1], $plate[2], $plate[3], $plate[4]);
                if ($stmt->execute()) {
                    echo "✓ {$restaurant_name}: {$plate[0]} ($$plate[1])<br>";
                }
            }
        }
    }
}

// ====================
// ADD TRANSACTIONS FOR JOHN USERS
// ====================
echo "<h3>Adding Sample Transactions for John Users:</h3>";

// Get some John user IDs
$john_ids = [];
$result = $conn->query("SELECT user_id, role FROM users WHERE name LIKE 'John%' AND role IN ('Customer', 'Donor')");
while ($row = $result->fetch_assoc()) {
    $john_ids[] = $row;
}

// Get some plate IDs
$plate_ids = [];
$result = $conn->query("SELECT plate_id, price FROM plates LIMIT 5");
while ($row = $result->fetch_assoc()) {
    $plate_ids[] = $row;
}

$transactions_added = 0;
$stmt = $conn->prepare("INSERT INTO transactions (user_id, plate_id, amount, transaction_type, transaction_date) VALUES (?, ?, ?, ?, ?)");

foreach ($john_ids as $john) {
    if (count($plate_ids) > 0) {
        $plate = $plate_ids[array_rand($plate_ids)];
        $type = ($john['role'] == 'Donor') ? 'Donation' : 'Purchase';
        $date = date('Y-m-d H:i:s', strtotime("-" . rand(1, 30) . " days"));
        
        $stmt->bind_param("iidss", $john['user_id'], $plate['plate_id'], $plate['price'], $type, $date);
        
        if ($stmt->execute()) {
            $transactions_added++;
        }
    }
}

echo "Added {$transactions_added} sample transactions for John users<br>";

// ====================
// FINAL SUMMARY
// ====================
echo "<div style='background:#e6ffe6; padding:20px; margin:20px 0;'>";
echo "<h2>✅ John Users Added Successfully!</h2>";

// Show breakdown by role
$roles = ['Administrator', 'Restaurant', 'Customer', 'Donor', 'Needy'];
foreach ($roles as $role) {
    $result = $conn->query("SELECT COUNT(*) as count FROM users WHERE name LIKE 'John%' AND role = '$role'");
    $row = $result->fetch_assoc();
    echo "<strong><span class='role $role'>$role</span> Johns:</strong> " . $row['count'] . "<br>";
}

// Total Johns
$result = $conn->query("SELECT COUNT(*) as total FROM users WHERE name LIKE 'John%'");
$row = $result->fetch_assoc();
echo "<br><strong>Total John Users:</strong> " . $row['count'] . "<br>";

echo "</div>";

echo "<h3>🔗 Test Your Enhanced System:</h3>";
echo "<ol>";
echo "<li><a href='admin_members.php' target='_blank'>Test Search & Filter</a> - Search 'John' then filter by each role</li>";
echo "<li><a href='verify_johns.php' target='_blank'>Verify All Johns</a> - See all John users organized by role</li>";
echo "<li><a href='test_role_filter.php' target='_blank'>Role Filter Test</a> - Test filtering by each role</li>";
echo "</ol>";

$conn->close();
?>