<?php
// Database configuration
$host = 'localhost';
$username = 'root';
$password = 'root';
$database = 'wnk_database';

// Connect to MySQL without selecting database
$conn = new mysqli($host, $username, $password);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if it doesn't exist
$conn->query("CREATE DATABASE IF NOT EXISTS $database");
$conn->select_db($database);

echo "Database created successfully!<br>";

// Create users table
$conn->query("
    CREATE TABLE IF NOT EXISTS users (
        user_id INT AUTO_INCREMENT PRIMARY KEY,
        role ENUM('Administrator', 'Restaurant', 'Customer', 'Donor', 'Needy') NOT NULL,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        address VARCHAR(255),
        phone VARCHAR(20),
        credit_card VARCHAR(20),
        date_registered DATETIME DEFAULT CURRENT_TIMESTAMP
    )
");
echo "Users table created successfully!<br>";

// Create restaurants table
$conn->query("
    CREATE TABLE IF NOT EXISTS restaurants (
        restaurant_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        restaurant_name VARCHAR(100) NOT NULL,
        description TEXT,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
    )
");
echo "Restaurants table created successfully!<br>";

// Create plates table
$conn->query("
    CREATE TABLE IF NOT EXISTS plates (
        plate_id INT AUTO_INCREMENT PRIMARY KEY,
        restaurant_id INT NOT NULL,
        description TEXT NOT NULL,
        price DECIMAL(8,2) NOT NULL,
        quantity INT NOT NULL CHECK (quantity >= 0),
        available_from DATETIME NOT NULL,
        available_until DATETIME NOT NULL,
        status ENUM('Available', 'Sold Out', 'Closed') DEFAULT 'Available',
        FOREIGN KEY (restaurant_id) REFERENCES restaurants(restaurant_id) ON DELETE CASCADE
    )
");
echo "Plates table created successfully!<br>";

// Create reservations table
$conn->query("
    CREATE TABLE IF NOT EXISTS reservations (
        reservation_id INT AUTO_INCREMENT PRIMARY KEY,
        plate_id INT NOT NULL,
        user_id INT NOT NULL,
        quantity INT NOT NULL CHECK (quantity > 0),
        reserved_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        confirmed BOOLEAN DEFAULT FALSE,
        pickup_time DATETIME,
        FOREIGN KEY (plate_id) REFERENCES plates(plate_id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
    )
");
echo "Reservations table created successfully!<br>";

// Create donations table
$conn->query("
    CREATE TABLE IF NOT EXISTS donations (
        donation_id INT AUTO_INCREMENT PRIMARY KEY,
        donor_id INT NOT NULL,
        needy_id INT,
        plate_id INT NOT NULL,
        quantity INT NOT NULL CHECK (quantity > 0),
        donated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        fulfilled BOOLEAN DEFAULT FALSE,
        FOREIGN KEY (donor_id) REFERENCES users(user_id) ON DELETE CASCADE,
        FOREIGN KEY (needy_id) REFERENCES users(user_id) ON DELETE SET NULL,
        FOREIGN KEY (plate_id) REFERENCES plates(plate_id) ON DELETE CASCADE
    )
");
echo "Donations table created successfully!<br>";

// Create transactions table
$conn->query("
    CREATE TABLE IF NOT EXISTS transactions (
        transaction_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        plate_id INT,
        amount DECIMAL(8,2),
        transaction_type ENUM('Purchase', 'Donation', 'Pickup') NOT NULL,
        transaction_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
        FOREIGN KEY (plate_id) REFERENCES plates(plate_id) ON DELETE SET NULL
    )
");
echo "Transactions table created successfully!<br>";

// Create reports table
$conn->query("
    CREATE TABLE IF NOT EXISTS reports (
        report_id INT AUTO_INCREMENT PRIMARY KEY,
        admin_id INT NOT NULL,
        report_type ENUM(
            'Restaurant Activity',
            'Customer Purchases',
            'Donor Purchases',
            'Needy Free Plates',
            'Donor Year-End Summary'
        ),
        year INT,
        generated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        report_data TEXT,
        FOREIGN KEY (admin_id) REFERENCES users(user_id) ON DELETE CASCADE
    )
");
echo "Reports table created successfully!<br>";

// Insert sample data
echo "Inserting sample data...<br>";

// Insert users with hashed passwords (password for all is 'password')
$password_hash = password_hash('password', PASSWORD_DEFAULT);

$conn->query("
    INSERT INTO users (role, name, email, password_hash, address, phone, credit_card) VALUES
    ('Administrator', 'Admin User', 'admin@wnk.com', '$password_hash', '123 Admin St, Orlando, FL', '407-555-0001', NULL),
    ('Restaurant', 'Pasta Palace', 'pasta@wnk.com', '$password_hash', '456 Restaurant Row, Orlando, FL', '407-555-0002', NULL),
    ('Restaurant', 'Burger Barn', 'burger@wnk.com', '$password_hash', '789 Food Ave, Orlando, FL', '407-555-0003', NULL),
    ('Customer', 'John Customer', 'john@wnk.com', '$password_hash', '321 Customer Dr, Orlando, FL', '407-555-0004', '4111111111111111'),
    ('Donor', 'Sarah Donor', 'sarah@wnk.com', '$password_hash', '654 Donor Ln, Orlando, FL', '407-555-0005', '4222222222222222'),
    ('Needy', 'Mike Needy', 'mike@wnk.com', '$password_hash', '987 Needy St, Orlando, FL', NULL, NULL),
    ('Customer', 'Lisa Shopper', 'lisa@wnk.com', '$password_hash', '555 Shopping Ave, Orlando, FL', '407-555-0006', '4333333333333333'),
    ('Donor', 'David Generous', 'david@wnk.com', '$password_hash', '777 Charity Rd, Orlando, FL', '407-555-0007', '4444444444444444')
");
echo "Sample users inserted!<br>";

// Insert restaurants
$conn->query("
    INSERT INTO restaurants (user_id, restaurant_name, description) VALUES
    (2, 'Pasta Palace', 'Authentic Italian cuisine with fresh pasta'),
    (3, 'Burger Barn', 'Gourmet burgers and American classics'),
    (2, 'Pasta Palace Express', 'Quick Italian meals for busy people')
");
echo "Sample restaurants inserted!<br>";

// Insert plates
$conn->query("
    INSERT INTO plates (restaurant_id, description, price, quantity, available_from, available_until, status) VALUES
    (1, 'Spaghetti Carbonara - Creamy pasta with bacon and cheese', 8.99, 10, '2025-11-01 11:00:00', '2025-11-01 14:00:00', 'Available'),
    (1, 'Chicken Alfredo - Grilled chicken with creamy Alfredo sauce', 9.99, 8, '2025-11-01 11:00:00', '2025-11-01 14:00:00', 'Available'),
    (2, 'Classic Cheeseburger - Beef patty with cheese and veggies', 7.99, 15, '2025-11-01 12:00:00', '2025-11-01 15:00:00', 'Available'),
    (2, 'Veggie Burger - Plant-based patty with fresh vegetables', 6.99, 5, '2025-11-01 12:00:00', '2025-11-01 15:00:00', 'Available'),
    (1, 'Lasagna - Layered pasta with meat and cheese', 10.99, 6, '2025-11-02 11:00:00', '2025-11-02 14:00:00', 'Available'),
    (2, 'BBQ Bacon Burger - Burger with BBQ sauce and crispy bacon', 8.99, 12, '2025-11-02 12:00:00', '2025-11-02 15:00:00', 'Available')
");
echo "Sample plates inserted!<br>";

// Insert reservations
$conn->query("
    INSERT INTO reservations (plate_id, user_id, quantity, reserved_at, confirmed, pickup_time) VALUES
    (1, 4, 2, NOW(), TRUE, '2025-11-01 13:00:00'),
    (3, 4, 1, NOW(), FALSE, NULL),
    (2, 7, 3, NOW(), TRUE, '2025-11-01 13:30:00'),
    (4, 7, 2, NOW(), TRUE, '2025-11-01 14:00:00')
");
echo "Sample reservations inserted!<br>";

// Insert donations
$conn->query("
    INSERT INTO donations (donor_id, needy_id, plate_id, quantity, donated_at, fulfilled) VALUES
    (5, 6, 2, 2, NOW(), TRUE),
    (5, NULL, 4, 3, NOW(), FALSE),
    (8, 6, 5, 1, NOW(), TRUE),
    (8, NULL, 6, 4, NOW(), FALSE)
");
echo "Sample donations inserted!<br>";

// Insert transactions
$conn->query("
    INSERT INTO transactions (user_id, plate_id, amount, transaction_type, transaction_date) VALUES
    (4, 1, 17.98, 'Purchase', NOW()),
    (5, 2, 19.98, 'Donation', NOW()),
    (6, 2, 0.00, 'Pickup', NOW()),
    (7, 3, 7.99, 'Purchase', NOW()),
    (7, 4, 13.98, 'Purchase', NOW()),
    (8, 5, 10.99, 'Donation', NOW()),
    (6, 5, 0.00, 'Pickup', NOW()),
    (8, 6, 35.96, 'Donation', '2025-01-15 10:30:00'),
    (4, 2, 9.99, 'Purchase', '2025-03-20 14:15:00')
");
echo "Sample transactions inserted!<br>";

echo "<h2>Database setup completed successfully!</h2>";
echo "<p>You can now access the admin system:</p>";
echo "<p><a href='admin_login.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>Go to Admin Login</a></p>";
echo "<h3>Login Credentials:</h3>";
echo "<p><strong>Admin:</strong> admin@wnk.com / password</p>";
echo "<p><strong>All other users:</strong> Use their email with password 'password'</p>";

$conn->close();
?>