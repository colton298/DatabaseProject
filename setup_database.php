<?php
// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';
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

// Drop existing tables (for fresh start)
$conn->query("DROP TABLE IF EXISTS transactions");
$conn->query("DROP TABLE IF EXISTS donations");
$conn->query("DROP TABLE IF EXISTS reservations");
$conn->query("DROP TABLE IF EXISTS plates");
$conn->query("DROP TABLE IF EXISTS restaurants");
$conn->query("DROP TABLE IF EXISTS users");
$conn->query("DROP TABLE IF EXISTS reports");

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

echo "Inserting extensive sample data...<br>";

// Insert users with hashed passwords (password for all is 'password')
$password_hash = password_hash('password', PASSWORD_DEFAULT);

// INSERT EXTENSIVE USER DATA - Multiple Johns for searching
$conn->query("
    INSERT INTO users (role, name, email, password_hash, address, phone, credit_card) VALUES
    -- Administrators (1)
    ('Administrator', 'Admin User', 'admin@wnk.com', '$password_hash', '123 Admin St, Orlando, FL', '407-555-0001', NULL),
    
    -- Restaurants (6)
    ('Restaurant', 'Pasta Palace', 'pasta@wnk.com', '$password_hash', '456 Restaurant Row, Orlando, FL', '407-555-0002', NULL),
    ('Restaurant', 'Burger Barn', 'burger@wnk.com', '$password_hash', '789 Food Ave, Orlando, FL', '407-555-0003', NULL),
    ('Restaurant', 'Sushi Express', 'sushi@wnk.com', '$password_hash', '321 Japan St, Orlando, FL', '407-555-0010', NULL),
    ('Restaurant', 'Taco Fiesta', 'taco@wnk.com', '$password_hash', '654 Mexico Ave, Orlando, FL', '407-555-0011', NULL),
    ('Restaurant', 'Pizza Heaven', 'pizza@wnk.com', '$password_hash', '987 Italy Blvd, Orlando, FL', '407-555-0012', NULL),
    ('Restaurant', 'Veggie Delight', 'veggie@wnk.com', '$password_hash', '147 Green St, Orlando, FL', '407-555-0013', NULL),
    
    -- Customers - MULTIPLE JOHNS (8)
    ('Customer', 'John Smith', 'john.smith@email.com', '$password_hash', '321 Customer Dr, Orlando, FL', '407-555-0004', '4111111111111111'),
    ('Customer', 'John Johnson', 'john.johnson@email.com', '$password_hash', '322 Customer Dr, Orlando, FL', '407-555-0104', '4111111111111112'),
    ('Customer', 'John Williams', 'john.williams@email.com', '$password_hash', '323 Customer Dr, Orlando, FL', '407-555-0204', '4111111111111113'),
    ('Customer', 'John Brown', 'john.brown@email.com', '$password_hash', '324 Customer Dr, Orlando, FL', '407-555-0304', '4111111111111114'),
    ('Customer', 'John Davis', 'john.davis@email.com', '$password_hash', '325 Customer Dr, Orlando, FL', '407-555-0404', '4111111111111115'),
    ('Customer', 'John Miller', 'john.miller@email.com', '$password_hash', '326 Customer Dr, Orlando, FL', '407-555-0504', '4111111111111116'),
    ('Customer', 'John Wilson', 'john.wilson@email.com', '$password_hash', '327 Customer Dr, Orlando, FL', '407-555-0604', '4111111111111117'),
    ('Customer', 'John Moore', 'john.moore@email.com', '$password_hash', '328 Customer Dr, Orlando, FL', '407-555-0704', '4111111111111118'),
    
    -- Other Customers (7)
    ('Customer', 'Lisa Shopper', 'lisa@wnk.com', '$password_hash', '555 Shopping Ave, Orlando, FL', '407-555-0006', '4333333333333333'),
    ('Customer', 'Robert Buyer', 'robert@email.com', '$password_hash', '556 Shopping Ave, Orlando, FL', '407-555-0008', '4333333333333334'),
    ('Customer', 'Sarah Consumer', 'sarah.c@email.com', '$password_hash', '557 Shopping Ave, Orlando, FL', '407-555-0009', '4333333333333335'),
    ('Customer', 'Michael Patron', 'michael@email.com', '$password_hash', '558 Shopping Ave, Orlando, FL', '407-555-0014', '4333333333333336'),
    ('Customer', 'Emily Customer', 'emily@email.com', '$password_hash', '559 Shopping Ave, Orlando, FL', '407-555-0015', '4333333333333337'),
    ('Customer', 'David Client', 'david.c@email.com', '$password_hash', '560 Shopping Ave, Orlando, FL', '407-555-0016', '4333333333333338'),
    ('Customer', 'Jennifer Shopper', 'jennifer@email.com', '$password_hash', '561 Shopping Ave, Orlando, FL', '407-555-0017', '4333333333333339'),
    
    -- Donors (8)
    ('Donor', 'Sarah Donor', 'sarah@wnk.com', '$password_hash', '654 Donor Ln, Orlando, FL', '407-555-0005', '4222222222222222'),
    ('Donor', 'David Generous', 'david@wnk.com', '$password_hash', '777 Charity Rd, Orlando, FL', '407-555-0007', '4444444444444444'),
    ('Donor', 'John Philanthropist', 'john.philanthropist@email.com', '$password_hash', '778 Charity Rd, Orlando, FL', '407-555-0008', '4444444444444445'),
    ('Donor', 'Emma Giver', 'emma@email.com', '$password_hash', '779 Charity Rd, Orlando, FL', '407-555-0009', '4444444444444446'),
    ('Donor', 'Thomas Benefactor', 'thomas@email.com', '$password_hash', '780 Charity Rd, Orlando, FL', '407-555-0018', '4444444444444447'),
    ('Donor', 'Olivia Contributor', 'olivia@email.com', '$password_hash', '781 Charity Rd, Orlando, FL', '407-555-0019', '4444444444444448'),
    ('Donor', 'James Altruist', 'james@email.com', '$password_hash', '782 Charity Rd, Orlando, FL', '407-555-0020', '4444444444444449'),
    ('Donor', 'Sophia Humanitarian', 'sophia@email.com', '$password_hash', '783 Charity Rd, Orlando, FL', '407-555-0021', '4444444444444450'),
    
    -- Needy (8)
    ('Needy', 'Mike Needy', 'mike@wnk.com', '$password_hash', '987 Needy St, Orlando, FL', NULL, NULL),
    ('Needy', 'John Needy', 'john.needy@email.com', '$password_hash', '988 Needy St, Orlando, FL', NULL, NULL),
    ('Needy', 'Anna Recipient', 'anna@email.com', '$password_hash', '989 Needy St, Orlando, FL', NULL, NULL),
    ('Needy', 'Chris Recipient', 'chris@email.com', '$password_hash', '990 Needy St, Orlando, FL', NULL, NULL),
    ('Needy', 'Jessica Beneficiary', 'jessica@email.com', '$password_hash', '991 Needy St, Orlando, FL', NULL, NULL),
    ('Needy', 'Daniel Recipient', 'daniel@email.com', '$password_hash', '992 Needy St, Orlando, FL', NULL, NULL),
    ('Needy', 'Laura Beneficiary', 'laura@email.com', '$password_hash', '993 Needy St, Orlando, FL', NULL, NULL),
    ('Needy', 'Kevin Recipient', 'kevin@email.com', '$password_hash', '994 Needy St, Orlando, FL', NULL, NULL)
");
echo "✓ 38 Users inserted (including 10+ Johns)!<br>";

// Insert restaurants (6 restaurants)
$conn->query("
    INSERT INTO restaurants (user_id, restaurant_name, description) VALUES
    (2, 'Pasta Palace', 'Authentic Italian cuisine with fresh pasta'),
    (3, 'Burger Barn', 'Gourmet burgers and American classics'),
    (4, 'Sushi Express', 'Fresh sushi and Japanese cuisine'),
    (5, 'Taco Fiesta', 'Mexican street food and tacos'),
    (6, 'Pizza Heaven', 'Wood-fired pizzas and Italian classics'),
    (7, 'Veggie Delight', 'Vegetarian and vegan friendly meals')
");
echo "✓ 6 Restaurants inserted!<br>";

// Insert plates (30 plates across 6 restaurants)
$conn->query("
    INSERT INTO plates (restaurant_id, description, price, quantity, available_from, available_until, status) VALUES
    -- Pasta Palace (5 plates)
    (1, 'Spaghetti Carbonara - Creamy pasta with bacon and cheese', 8.99, 10, '2025-01-15 11:00:00', '2025-01-15 14:00:00', 'Available'),
    (1, 'Chicken Alfredo - Grilled chicken with creamy Alfredo sauce', 9.99, 8, '2025-01-15 11:00:00', '2025-01-15 14:00:00', 'Available'),
    (1, 'Lasagna - Layered pasta with meat and cheese', 10.99, 6, '2025-01-16 11:00:00', '2025-01-16 14:00:00', 'Available'),
    (1, 'Fettuccine Bolognese - Classic meat sauce pasta', 8.49, 12, '2025-01-16 11:00:00', '2025-01-16 14:00:00', 'Available'),
    (1, 'Penne Arrabbiata - Spicy tomato sauce pasta', 7.99, 15, '2025-01-17 11:00:00', '2025-01-17 14:00:00', 'Available'),
    
    -- Burger Barn (5 plates)
    (2, 'Classic Cheeseburger - Beef patty with cheese and veggies', 7.99, 15, '2025-01-15 12:00:00', '2025-01-15 15:00:00', 'Available'),
    (2, 'Veggie Burger - Plant-based patty with fresh vegetables', 6.99, 5, '2025-01-15 12:00:00', '2025-01-15 15:00:00', 'Available'),
    (2, 'BBQ Bacon Burger - Burger with BBQ sauce and crispy bacon', 8.99, 12, '2025-01-16 12:00:00', '2025-01-16 15:00:00', 'Available'),
    (2, 'Mushroom Swiss Burger - Burger with mushrooms and Swiss cheese', 9.49, 8, '2025-01-16 12:00:00', '2025-01-16 15:00:00', 'Available'),
    (2, 'Double Cheeseburger - Two beef patties with double cheese', 10.99, 10, '2025-01-17 12:00:00', '2025-01-17 15:00:00', 'Available'),
    
    -- Sushi Express (5 plates)
    (3, 'California Roll - Crab, avocado, cucumber', 5.99, 20, '2025-01-15 13:00:00', '2025-01-15 16:00:00', 'Available'),
    (3, 'Salmon Nigiri - Fresh salmon over rice', 6.99, 15, '2025-01-15 13:00:00', '2025-01-15 16:00:00', 'Available'),
    (3, 'Spicy Tuna Roll - Tuna with spicy mayo', 7.49, 18, '2025-01-16 13:00:00', '2025-01-16 16:00:00', 'Available'),
    (3, 'Dragon Roll - Eel, cucumber, avocado', 8.99, 12, '2025-01-16 13:00:00', '2025-01-16 16:00:00', 'Available'),
    (3, 'Tempura Shrimp Roll - Crispy shrimp tempura', 7.99, 14, '2025-01-17 13:00:00', '2025-01-17 16:00:00', 'Available'),
    
    -- Taco Fiesta (5 plates)
    (4, 'Chicken Tacos (3 pcs) - Grilled chicken with salsa', 6.99, 25, '2025-01-15 14:00:00', '2025-01-15 17:00:00', 'Available'),
    (4, 'Beef Tacos (3 pcs) - Seasoned ground beef tacos', 7.49, 20, '2025-01-15 14:00:00', '2025-01-15 17:00:00', 'Available'),
    (4, 'Vegetarian Tacos (3 pcs) - Black bean and corn tacos', 5.99, 15, '2025-01-16 14:00:00', '2025-01-16 17:00:00', 'Available'),
    (4, 'Carnitas Tacos (3 pcs) - Slow-cooked pork tacos', 8.99, 18, '2025-01-16 14:00:00', '2025-01-16 17:00:00', 'Available'),
    (4, 'Fish Tacos (3 pcs) - Beer-battered fish with slaw', 9.49, 12, '2025-01-17 14:00:00', '2025-01-17 17:00:00', 'Available'),
    
    -- Pizza Heaven (5 plates)
    (5, 'Margherita Pizza - Classic tomato and mozzarella', 12.99, 8, '2025-01-15 15:00:00', '2025-01-15 18:00:00', 'Available'),
    (5, 'Pepperoni Pizza - Pepperoni with extra cheese', 14.99, 10, '2025-01-15 15:00:00', '2025-01-15 18:00:00', 'Available'),
    (5, 'Vegetarian Pizza - Mixed vegetable pizza', 13.99, 6, '2025-01-16 15:00:00', '2025-01-16 18:00:00', 'Available'),
    (5, 'BBQ Chicken Pizza - Chicken with BBQ sauce', 15.99, 7, '2025-01-16 15:00:00', '2025-01-16 18:00:00', 'Available'),
    (5, 'Hawaiian Pizza - Ham and pineapple pizza', 13.49, 9, '2025-01-17 15:00:00', '2025-01-17 18:00:00', 'Available'),
    
    -- Veggie Delight (5 plates)
    (6, 'Quinoa Bowl - Quinoa with roasted vegetables', 8.99, 12, '2025-01-15 16:00:00', '2025-01-15 19:00:00', 'Available'),
    (6, 'Veggie Stir Fry - Mixed vegetables with tofu', 9.49, 10, '2025-01-15 16:00:00', '2025-01-15 19:00:00', 'Available'),
    (6, 'Falafel Plate - Falafel with hummus and pita', 7.99, 15, '2025-01-16 16:00:00', '2025-01-16 19:00:00', 'Available'),
    (6, 'Vegetable Curry - Mixed vegetables in curry sauce', 10.99, 8, '2025-01-16 16:00:00', '2025-01-16 19:00:00', 'Available'),
    (6, 'Stuffed Bell Peppers - Rice and vegetable stuffed peppers', 9.99, 6, '2025-01-17 16:00:00', '2025-01-17 19:00:00', 'Available')
");
echo "✓ 30 Plates inserted!<br>";

// Insert reservations (25 reservations from multiple customers including Johns)
$conn->query("
    INSERT INTO reservations (plate_id, user_id, quantity, reserved_at, confirmed, pickup_time) VALUES
    -- John Smith reservations (3)
    (1, 8, 2, '2025-01-14 10:30:00', TRUE, '2025-01-15 12:00:00'),
    (7, 8, 1, '2025-01-14 11:15:00', TRUE, '2025-01-15 13:00:00'),
    (15, 8, 3, '2025-01-15 09:45:00', FALSE, NULL),
    
    -- John Johnson reservations (2)
    (3, 9, 1, '2025-01-14 14:20:00', TRUE, '2025-01-16 12:30:00'),
    (12, 9, 2, '2025-01-15 10:10:00', TRUE, '2025-01-16 14:00:00'),
    
    -- John Williams reservations (2)
    (5, 10, 2, '2025-01-13 16:45:00', TRUE, '2025-01-17 11:45:00'),
    (18, 10, 1, '2025-01-14 12:30:00', FALSE, NULL),
    
    -- Lisa Shopper reservations (3)
    (2, 16, 1, '2025-01-14 09:15:00', TRUE, '2025-01-15 11:30:00'),
    (8, 16, 2, '2025-01-14 15:40:00', TRUE, '2025-01-16 13:15:00'),
    (21, 16, 1, '2025-01-15 11:20:00', TRUE, '2025-01-17 16:00:00'),
    
    -- Robert Buyer reservations (2)
    (6, 17, 2, '2025-01-13 17:10:00', TRUE, '2025-01-15 12:45:00'),
    (14, 17, 1, '2025-01-14 13:25:00', TRUE, '2025-01-16 15:30:00'),
    
    -- Sarah Consumer reservations (2)
    (9, 18, 1, '2025-01-14 10:50:00', TRUE, '2025-01-16 14:45:00'),
    (20, 18, 2, '2025-01-15 14:35:00', FALSE, NULL),
    
    -- Michael Patron reservations (2)
    (11, 19, 3, '2025-01-13 12:20:00', TRUE, '2025-01-15 13:30:00'),
    (23, 19, 1, '2025-01-14 16:15:00', TRUE, '2025-01-17 17:00:00'),
    
    -- Emily Customer reservations (2)
    (13, 20, 2, '2025-01-14 11:40:00', TRUE, '2025-01-16 16:15:00'),
    (25, 20, 1, '2025-01-15 09:20:00', FALSE, NULL),
    
    -- David Client reservations (2)
    (4, 21, 1, '2025-01-13 14:55:00', TRUE, '2025-01-16 12:15:00'),
    (17, 21, 2, '2025-01-14 13:10:00', TRUE, '2025-01-16 17:30:00'),
    
    -- Jennifer Shopper reservations (2)
    (10, 22, 1, '2025-01-14 10:05:00', TRUE, '2025-01-17 13:45:00'),
    (22, 22, 2, '2025-01-15 15:25:00', TRUE, '2025-01-17 18:15:00'),
    
    -- John Brown reservation (1)
    (19, 11, 1, '2025-01-14 12:15:00', TRUE, '2025-01-17 14:30:00'),
    
    -- John Davis reservation (1)
    (24, 12, 2, '2025-01-15 08:45:00', FALSE, NULL)
");
echo "✓ 25 Reservations inserted!<br>";

// Insert donations (20 donations from multiple donors including Johns)
$conn->query("
    INSERT INTO donations (donor_id, needy_id, plate_id, quantity, donated_at, fulfilled) VALUES
    -- Sarah Donor donations (3)
    (23, 31, 2, 2, '2025-01-14 10:00:00', TRUE),
    (23, NULL, 4, 3, '2025-01-14 14:30:00', FALSE),
    (23, 32, 8, 1, '2025-01-15 09:15:00', TRUE),
    
    -- David Generous donations (3)
    (24, 33, 5, 1, '2025-01-14 11:20:00', TRUE),
    (24, NULL, 6, 4, '2025-01-14 16:45:00', FALSE),
    (24, 34, 12, 2, '2025-01-15 10:40:00', TRUE),
    
    -- John Philanthropist donations (3)
    (25, 35, 9, 2, '2025-01-13 13:10:00', TRUE),
    (25, NULL, 11, 3, '2025-01-14 15:25:00', FALSE),
    (25, 36, 16, 1, '2025-01-15 11:55:00', TRUE),
    
    -- Emma Giver donations (2)
    (26, 37, 14, 1, '2025-01-14 12:40:00', TRUE),
    (26, NULL, 18, 2, '2025-01-15 08:30:00', FALSE),
    
    -- Thomas Benefactor donations (2)
    (27, 38, 20, 3, '2025-01-14 14:15:00', TRUE),
    (27, NULL, 22, 1, '2025-01-15 12:20:00', FALSE),
    
    -- Olivia Contributor donations (2)
    (28, 31, 23, 2, '2025-01-13 16:30:00', TRUE),
    (28, NULL, 25, 1, '2025-01-14 17:45:00', FALSE),
    
    -- James Altruist donations (2)
    (29, 32, 26, 1, '2025-01-14 10:50:00', TRUE),
    (29, NULL, 28, 3, '2025-01-15 13:35:00', FALSE),
    
    -- Sophia Humanitarian donations (3)
    (30, 33, 29, 2, '2025-01-13 15:20:00', TRUE),
    (30, NULL, 30, 2, '2025-01-14 18:10:00', FALSE),
    (30, 34, 1, 1, '2025-01-15 14:25:00', TRUE)
");
echo "✓ 20 Donations inserted!<br>";

// Insert transactions (40 transactions)
$conn->query("
    INSERT INTO transactions (user_id, plate_id, amount, transaction_type, transaction_date) VALUES
    -- Purchase transactions (20)
    (8, 1, 17.98, 'Purchase', '2025-01-14 10:30:00'),
    (8, 7, 7.99, 'Purchase', '2025-01-14 11:15:00'),
    (8, 15, 20.97, 'Purchase', '2025-01-15 09:45:00'),
    (9, 3, 10.99, 'Purchase', '2025-01-14 14:20:00'),
    (9, 12, 13.98, 'Purchase', '2025-01-15 10:10:00'),
    (10, 5, 15.98, 'Purchase', '2025-01-13 16:45:00'),
    (10, 18, 8.99, 'Purchase', '2025-01-14 12:30:00'),
    (16, 2, 9.99, 'Purchase', '2025-01-14 09:15:00'),
    (16, 8, 18.98, 'Purchase', '2025-01-14 15:40:00'),
    (16, 21, 12.99, 'Purchase', '2025-01-15 11:20:00'),
    (17, 6, 15.98, 'Purchase', '2025-01-13 17:10:00'),
    (17, 14, 7.49, 'Purchase', '2025-01-14 13:25:00'),
    (18, 9, 9.49, 'Purchase', '2025-01-14 10:50:00'),
    (18, 20, 18.98, 'Purchase', '2025-01-15 14:35:00'),
    (19, 11, 22.47, 'Purchase', '2025-01-13 12:20:00'),
    (19, 23, 13.99, 'Purchase', '2025-01-14 16:15:00'),
    (20, 13, 14.98, 'Purchase', '2025-01-14 11:40:00'),
    (20, 25, 9.99, 'Purchase', '2025-01-15 09:20:00'),
    (21, 4, 8.49, 'Purchase', '2025-01-13 14:55:00'),
    (21, 17, 13.98, 'Purchase', '2025-01-14 13:10:00'),
    
    -- Donation transactions (10)
    (23, 2, 19.98, 'Donation', '2025-01-14 10:00:00'),
    (23, 4, 25.47, 'Donation', '2025-01-14 14:30:00'),
    (23, 8, 8.99, 'Donation', '2025-01-15 09:15:00'),
    (24, 5, 10.99, 'Donation', '2025-01-14 11:20:00'),
    (24, 6, 31.96, 'Donation', '2025-01-14 16:45:00'),
    (25, 9, 18.98, 'Donation', '2025-01-13 13:10:00'),
    (25, 11, 22.47, 'Donation', '2025-01-14 15:25:00'),
    (26, 14, 6.99, 'Donation', '2025-01-14 12:40:00'),
    (26, 18, 17.98, 'Donation', '2025-01-15 08:30:00'),
    (27, 20, 28.47, 'Donation', '2025-01-14 14:15:00'),
    
    -- Pickup transactions (10)
    (31, 2, 0.00, 'Pickup', '2025-01-14 11:30:00'),
    (32, 8, 0.00, 'Pickup', '2025-01-15 10:45:00'),
    (33, 5, 0.00, 'Pickup', '2025-01-14 12:45:00'),
    (34, 12, 0.00, 'Pickup', '2025-01-15 12:10:00'),
    (35, 9, 0.00, 'Pickup', '2025-01-13 14:30:00'),
    (36, 16, 0.00, 'Pickup', '2025-01-15 13:25:00'),
    (37, 14, 0.00, 'Pickup', '2025-01-14 13:50:00'),
    (38, 20, 0.00, 'Pickup', '2025-01-14 15:45:00'),
    (31, 23, 0.00, 'Pickup', '2025-01-13 17:30:00'),
    (32, 26, 0.00, 'Pickup', '2025-01-14 12:20:00')
");
echo "✓ 40 Transactions inserted!<br>";

echo "<h2 style='color: green;'>Database setup COMPLETED SUCCESSFULLY!</h2>";
echo "<div style='background: #e6ffe6; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
echo "<h3>📊 Data Summary:</h3>";
echo "<p><strong>38 Users</strong> (including 10+ users named 'John' for searching)</p>";
echo "<p><strong>6 Restaurants</strong> with diverse cuisines</p>";
echo "<p><strong>30 Plates</strong> available for purchase/donation</p>";
echo "<p><strong>25 Reservations</strong> from various customers</p>";
echo "<p><strong>20 Donations</strong> from generous donors</p>";
echo "<p><strong>40 Transactions</strong> (purchases, donations, pickups)</p>";
echo "</div>";

echo "<div style='background: #f0f8ff; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
echo "<h3>🔑 Login Credentials:</h3>";
echo "<p><strong>Admin:</strong> admin@wnk.com / password</p>";
echo "<p><strong>Search Test:</strong> Search 'John' in admin members page to see multiple results</p>";