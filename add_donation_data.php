<?php
require_once 'config.php';

echo "<h2>Adding Sample Donation Data for Reports</h2>";

// Get some needy and donor users
$needy_ids = [];
$donor_ids = [];
$plate_ids = [];

// Get needy users
$result = $conn->query("SELECT user_id FROM users WHERE role = 'Needy' LIMIT 5");
while ($row = $result->fetch_assoc()) {
    $needy_ids[] = $row['user_id'];
}

// Get donor users
$result = $conn->query("SELECT user_id FROM users WHERE role = 'Donor' LIMIT 5");
while ($row = $result->fetch_assoc()) {
    $donor_ids[] = $row['user_id'];
}

// Get plates
$result = $conn->query("SELECT plate_id, price FROM plates LIMIT 10");
while ($row = $result->fetch_assoc()) {
    $plate_ids[] = $row;
}

echo "<h3>Adding Donations...</h3>";

$donations_added = 0;
$transactions_added = 0;

// Add donations for 2024 and 2025
$years = [2024, 2025];

foreach ($years as $year) {
    for ($i = 0; $i < 5; $i++) {
        // Random needy (sometimes null for unfulfilled)
        $needy_id = (rand(0, 3) > 0) ? $needy_ids[array_rand($needy_ids)] : null;
        $donor_id = $donor_ids[array_rand($donor_ids)];
        $plate = $plate_ids[array_rand($plate_ids)];
        $quantity = rand(1, 3);
        $fulfilled = $needy_id ? 1 : 0;
        
        // Random date in the year
        $month = rand(1, 12);
        $day = rand(1, 28);
        $date = "$year-$month-$day " . rand(10, 16) . ":" . rand(10, 59) . ":00";
        
        // Insert donation
        $stmt = $conn->prepare("INSERT INTO donations (donor_id, needy_id, plate_id, quantity, donated_at, fulfilled) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iiiisi", $donor_id, $needy_id, $plate['plate_id'], $quantity, $date, $fulfilled);
        
        if ($stmt->execute()) {
            $donations_added++;
            
            // Also add transaction for donation
            $amount = $plate['price'] * $quantity;
            $tran_stmt = $conn->prepare("INSERT INTO transactions (user_id, plate_id, amount, transaction_type, transaction_date) VALUES (?, ?, ?, 'Donation', ?)");
            $tran_stmt->bind_param("iids", $donor_id, $plate['plate_id'], $amount, $date);
            
            if ($tran_stmt->execute()) {
                $transactions_added++;
            }
            
            echo "✓ Donation added: Donor ID $donor_id → " . 
                 ($needy_id ? "Needy ID $needy_id" : "Unassigned") . 
                 " ($$amount on " . date('M j, Y', strtotime($date)) . ")<br>";
        }
    }
}

echo "<div style='background:#e6ffe6; padding:15px; margin:15px 0;'>";
echo "<h3>✅ Data Added Successfully!</h3>";
echo "<strong>Donations added:</strong> $donations_added<br>";
echo "<strong>Transactions added:</strong> $transactions_added<br>";
echo "<strong>Years covered:</strong> 2024 and 2025<br>";
echo "</div>";

echo "<h3>🔗 Test the New Reports:</h3>";
echo "<ol>";
echo "<li><a href='admin_reports.php'>Go to Reports Page</a></li>";
echo "<li>Select 'Needy Free Plates' report</li>";
echo "<li>Choose a needy person and year 2025</li>";
echo "<li>Select 'Donor Year-End Summary' report</li>";
echo "<li>Choose a donor and year 2025</li>";
echo "</ol>";

$conn->close();
?>