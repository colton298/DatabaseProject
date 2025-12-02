<?php
require_once 'config.php';

echo "<h2>Adding More Sample Data</h2>";

// Password hash for all new users
$password_hash = password_hash('password', PASSWORD_DEFAULT);

// ====================
// ADD MORE USERS (especially more Johns)
// ====================
echo "<h3>Adding More Users...</h3>";

$new_users = [
    // More Johns (for search testing)
    ['Customer', 'John Brown', 'john.brown@email.com', '333 Customer Ave', '555-1001', '4111111111111121'],
    ['Customer', 'John Davis', 'john.davis@email.com', '334 Customer Ave', '555-1002', '4111111111111122'],
    ['Customer', 'John Miller', 'john.miller@email.com', '335 Customer Ave', '555-1003', '4111111111111123'],
    ['Customer', 'John Wilson', 'john.wilson@email.com', '336 Customer Ave', '555-1004', '4111111111111124'],
    ['Customer', 'John Moore', 'john.moore@email.com', '337 Customer Ave', '555-1005', '4111111111111125'],
    ['Customer', 'John Taylor', 'john.taylor@email.com', '338 Customer Ave', '555-1006', '4111111111111126'],
    ['Customer', 'John Anderson', 'john.anderson@email.com', '339 Customer Ave', '555-1007', '4111111111111127'],
    ['Customer', 'John Thomas', 'john.thomas@email.com', '340 Customer Ave', '555-1008', '4111111111111128'],
    
    // More Restaurants
    ['Restaurant', 'Sushi Express', 'sushi@wnk.com', '123 Japan St', '555-2001', NULL],
    ['Restaurant', 'Taco Fiesta', 'taco@wnk.com', '456 Mexico Ave', '555-2002', NULL],
    ['Restaurant', 'Pizza Heaven', 'pizza@wnk.com', '789 Italy Blvd', '555-2003', NULL],
    
    // More Customers
    ['Customer', 'Emily Shopper', 'emily@email.com', '444 Shop Lane', '555-3001', '4222222222222221'],
    ['Customer', 'David Buyer', 'david@email.com', '445 Shop Lane', '555-3002', '4222222222222222'],
    ['Customer', 'Sarah Consumer', 'sarah.c@email.com', '446 Shop Lane', '555-3003', '4222222222222223'],
    
    // More Donors
    ['Donor', 'Michael Generous', 'michael@email.com', '777 Charity Rd', '555-4001', '4333333333333331'],
    ['Donor', 'Lisa Philanthropist', 'lisa.d@email.com', '778 Charity Rd', '555-4002', '4333333333333332'],
    
    // More Needy
    ['Needy', 'Anna Recipient', 'anna@email.com', '888 Help St', NULL, NULL],
    ['Needy', 'Chris Beneficiary', 'chris@email.com', '889 Help St', NULL, NULL],
    ['Needy', 'Jessica Needy', 'jessica@email.com', '890 Help St', NULL, NULL],
];

$users_added = 0;
$stmt = $conn->prepare("INSERT INTO users (role, name, email, password_hash, address, phone, credit_card) VALUES (?, ?, ?, ?, ?, ?, ?)");

foreach ($new_users as $user) {
    $stmt->bind_param("sssssss", $user[0], $user[1], $user[2], $password_hash, $user[3], $user[4], $user[5]);
    if ($stmt->execute()) {
        $users_added++;
        echo "✓ Added: {$user[1]} ({$user[0]})<br>";
    } else {
        echo "⚠ Skipped {$user[1]} (might already exist)<br>";
    }
}

echo "<strong>Total new users added: {$users_added}</strong><br><br>";

// ====================
// ADD MORE RESTAURANTS
// ====================
echo "<h3>Adding More Restaurants...</h3>";

// Get user IDs for restaurant owners
$restaurant_owners = [
    'Sushi Express' => 'sushi@wnk.com',
    'Taco Fiesta' => 'taco@wnk.com',
    'Pizza Heaven' => 'pizza@wnk.com'
];

foreach ($restaurant_owners as $name => $email) {
    // Get user_id for this restaurant owner
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $user_id = $row['user_id'];
        
        // Insert restaurant
        $stmt2 = $conn->prepare("INSERT INTO restaurants (user_id, restaurant_name, description) VALUES (?, ?, ?)");
        $description = "Delicious {$name} cuisine";
        $stmt2->bind_param("iss", $user_id, $name, $description);
        
        if ($stmt2->execute()) {
            echo "✓ Added restaurant: {$name}<br>";
        }
    }
}

// ====================
// ADD MORE PLATES
// ====================
echo "<h3>Adding More Plates...</h3>";

// Get restaurant IDs
$restaurant_ids = [];
$result = $conn->query("SELECT restaurant_id, restaurant_name FROM restaurants");
while ($row = $result->fetch_assoc()) {
    $restaurant_ids[$row['restaurant_name']] = $row['restaurant_id'];
}

$plates = [
    // Pasta Palace
    ['Pasta Palace', 'Fettuccine Alfredo', 11.99, 8, '2025-01-18 11:00:00', '2025-01-18 14:00:00'],
    ['Pasta Palace', 'Lasagna Bolognese', 12.99, 6, '2025-01-19 11:00:00', '2025-01-19 14:00:00'],
    
    // Burger Barn  
    ['Burger Barn', 'BBQ Bacon Burger', 10.99, 10, '2025-01-18 12:00:00', '2025-01-18 15:00:00'],
    ['Burger Barn', 'Veggie Deluxe Burger', 9.99, 5, '2025-01-19 12:00:00', '2025-01-19 15:00:00'],
    
    // Sushi Express
    ['Sushi Express', 'California Roll Set', 14.99, 15, '2025-01-18 13:00:00', '2025-01-18 16:00:00'],
    ['Sushi Express', 'Salmon Sashimi Platter', 16.99, 8, '2025-01-19 13:00:00', '2025-01-19 16:00:00'],
    
    // Taco Fiesta
    ['Taco Fiesta', 'Taco Platter (5 tacos)', 15.99, 12, '2025-01-18 14:00:00', '2025-01-18 17:00:00'],
    ['Taco Fiesta', 'Nachos Supreme', 13.99, 10, '2025-01-19 14:00:00', '2025-01-19 17:00:00'],
    
    // Pizza Heaven
    ['Pizza Heaven', 'Pepperoni Pizza', 18.99, 6, '2025-01-18 15:00:00', '2025-01-18 18:00:00'],
    ['Pizza Heaven', 'Margherita Pizza', 16.99, 8, '2025-01-19 15:00:00', '2025-01-19 18:00:00'],
];

$plates_added = 0;
$stmt = $conn->prepare("INSERT INTO plates (restaurant_id, description, price, quantity, available_from, available_until) VALUES (?, ?, ?, ?, ?, ?)");

foreach ($plates as $plate) {
    $restaurant_name = $plate[0];
    if (isset($restaurant_ids[$restaurant_name])) {
        $stmt->bind_param("isdiss", 
            $restaurant_ids[$restaurant_name], 
            $plate[1], 
            $plate[2], 
            $plate[3], 
            $plate[4], 
            $plate[5]
        );
        
        if ($stmt->execute()) {
            $plates_added++;
            echo "✓ Added: {$plate[1]} (${$plate[2]})<br>";
        }
    }
}

echo "<strong>Total new plates added: {$plates_added}</strong><br><br>";

// ====================
// ADD MORE TRANSACTIONS
// ====================
echo "<h3>Adding More Transactions...</h3>";

// Get some user IDs for transactions
$user_ids = [];
$result = $conn->query("SELECT user_id, name FROM users WHERE role IN ('Customer', 'Donor') LIMIT 10");
while ($row = $result->fetch_assoc()) {
    $user_ids[] = $row['user_id'];
}

// Get some plate IDs
$plate_ids = [];
$result = $conn->query("SELECT plate_id, price FROM plates LIMIT 10");
while ($row = $result->fetch_assoc()) {
    $plate_ids[] = ['id' => $row['plate_id'], 'price' => $row['price']];
}

// Add sample transactions
$transactions_added = 0;
$stmt = $conn->prepare("INSERT INTO transactions (user_id, plate_id, amount, transaction_type, transaction_date) VALUES (?, ?, ?, ?, ?)");

$transaction_types = ['Purchase', 'Donation', 'Pickup'];
$dates = ['2025-01-15 10:30:00', '2025-01-15 14:45:00', '2025-01-16 11:20:00', '2025-01-16 16:30:00', '2025-01-17 09:15:00'];

for ($i = 0; $i < 15; $i++) {
    $user_id = $user_ids[array_rand($user_ids)];
    $plate = $plate_ids[array_rand($plate_ids)];
    $type = $transaction_types[array_rand($transaction_types)];
    $date = $dates[array_rand($dates)];
    
    // For pickup transactions, amount is 0
    $amount = ($type == 'Pickup') ? 0 : $plate['price'];
    
    $stmt->bind_param("iidss", $user_id, $plate['id'], $amount, $type, $date);
    
    if ($stmt->execute()) {
        $transactions_added++;
    }
}

echo "<strong>Total new transactions added: {$transactions_added}</strong><br><br>";

// ====================
// FINAL SUMMARY
// ====================
echo "<div style='background:#e6ffe6; padding:20px; margin:20px 0;'>";
echo "<h2>✅ Data Added Successfully!</h2>";

// Show updated counts
$result = $conn->query("SELECT COUNT(*) as count FROM users");
$row = $result->fetch_assoc();
echo "<strong>Total Users Now:</strong> " . $row['count'] . "<br>";

$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE name LIKE '%John%'");
$row = $result->fetch_assoc();
echo "<strong>Users named 'John':</strong> " . $row['count'] . " (search test ready!)<br>";

$result = $conn->query("SELECT COUNT(*) as count FROM plates");
$row = $result->fetch_assoc();
echo "<strong>Total Plates:</strong> " . $row['count'] . "<br>";

$result = $conn->query("SELECT COUNT(*) as count FROM transactions");
$row = $result->fetch_assoc();
echo "<strong>Total Transactions:</strong> " . $row['count'] . "<br>";

echo "</div>";

echo "<h3>🔗 Test Your Enhanced System:</h3>";
echo "<ol>";
echo "<li><a href='admin_members.php' target='_blank'>Test Search</a> - Search for 'John' (should find 8+ users)</li>";
echo "<li><a href='admin_activity.php' target='_blank'>Check Activity</a> - More transactions and data</li>";
echo "<li><a href='admin_reports.php' target='_blank'>Generate Reports</a> - More restaurant options</li>";
echo "</ol>";

$conn->close();
?>