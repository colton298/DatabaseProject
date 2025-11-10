<?php
require_once "config.php";

$message = "";
$messageClass = "error";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $role = $_POST["role"];
    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];
    $address = trim($_POST["address"]);
    $phone = trim($_POST["phone"]);
    $credit_card = trim($_POST["credit_card"]);
    $restaurant_name = trim($_POST["restaurant_name"]);

    // Input validation
    if (empty($role) || empty($name) || empty($email) || empty($password)) {
        $message = "Please fill in all required fields.";
    } else {
        // Role-based validation
        if ($role !== "Needy" && empty($phone)) {
            $message = "Phone number is required for this role.";
        } elseif (($role === "Customer" || $role === "Donor") && empty($credit_card)) {
            $message = "Credit card information is required for customers and donors.";
        } elseif ($role === "Restaurant" && empty($restaurant_name)) {
            $message = "Restaurant name is required for restaurant accounts.";
        } else {
            // Check if email already exists
            $checkQuery = "SELECT * FROM users WHERE email = ?";
            $checkStmt = $conn->prepare($checkQuery);
            $checkStmt->bind_param("s", $email);
            $checkStmt->execute();
            $result = $checkStmt->get_result();

            if ($result->num_rows > 0) {
                $message = "An account with this email already exists.";
            } else {
                // Insert new user
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $insertQuery = "INSERT INTO users (role, name, email, password_hash, address, phone, credit_card)
                                VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($insertQuery);
                $stmt->bind_param("sssssss", $role, $name, $email, $hashed_password, $address, $phone, $credit_card);

                if ($stmt->execute()) {
                    $user_id = $stmt->insert_id;

                    // If role is Restaurant, add to restaurants table
                    if ($role === "Restaurant") {
                        $restQuery = "INSERT INTO restaurants (user_id, restaurant_name) VALUES (?, ?)";
                        $restStmt = $conn->prepare($restQuery);
                        $restStmt->bind_param("is", $user_id, $restaurant_name);
                        $restStmt->execute();
                        $restStmt->close();
                    }

                    $messageClass = "success";
                    if ($role === "Restaurant") {
                        $message = "Restaurant registered successfully! You can now <a href='login.php'>log in</a>.";
                    } else {
                        $message = "Registration successful! You can now <a href='login.php'>log in</a>.";
                    }
                } else {
                    $message = "❌ Error: " . $stmt->error;
                }
                $stmt->close();
            }
            $checkStmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - Waste Not Kitchen</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f7f8;
            margin: 0;
            padding: 0;
        }

        /* Navigation Bar */
        nav {
            background-color: #4CAF50;
            padding: 15px;
            display: flex;
            justify-content: center;
            gap: 30px;
        }

        nav a {
            color: white;
            text-decoration: none;
            font-weight: bold;
            font-size: 16px;
            transition: 0.3s;
        }

        nav a:hover {
            text-decoration: underline;
        }

        .container {
            max-width: 500px;
            background: white;
            margin: 50px auto;
            padding: 30px 40px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        h2 {
            text-align: center;
            color: #333;
        }

        label {
            display: block;
            margin-top: 15px;
            color: #333;
        }

        input, select {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 6px;
        }

        button {
            margin-top: 20px;
            width: 100%;
            background: #4CAF50;
            color: white;
            padding: 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
        }

        button:hover {
            background: #45a049;
        }

        .message {
            margin-top: 20px;
            text-align: center;
            padding: 10px;
            border-radius: 6px;
        }

        .error {
            color: #b00020;
            background-color: #ffe6e6;
        }

        .success {
            color: #0c750c;
            background-color: #e6ffe6;
        }
    </style>

    <script>
        function toggleFields() {
            const role = document.getElementById("role").value;
            const phoneInput = document.getElementById("phone");
            const creditCardSection = document.getElementById("creditCardSection");
            const restaurantSection = document.getElementById("restaurantSection");

            // Phone optional for Needy
            if (role === "Needy") {
                phoneInput.removeAttribute("required");
            } else {
                phoneInput.setAttribute("required", "true");
            }

            // Credit card required for Customer or Donor
            if (role === "Customer" || role === "Donor") {
                creditCardSection.style.display = "block";
                document.getElementById("credit_card").setAttribute("required", "true");
            } else {
                creditCardSection.style.display = "none";
                document.getElementById("credit_card").removeAttribute("required");
            }

            // Restaurant name required for Restaurants
            if (role === "Restaurant") {
                restaurantSection.style.display = "block";
                document.getElementById("restaurant_name").setAttribute("required", "true");
            } else {
                restaurantSection.style.display = "none";
                document.getElementById("restaurant_name").removeAttribute("required");
            }
        }
    </script>
</head>
<body>

<!-- Navigation Bar -->
<nav>
    <a href="index.php">Home</a>
    <a href="edit_profile.php">Edit Profile</a>
    <a href="login.php">Login</a>
</nav>

<div class="container">
    <h2>Register - Waste Not Kitchen</h2>

    <form method="POST" action="register.php">
        <label for="role">Select Role:</label>
        <select name="role" id="role" required onchange="toggleFields()">
            <option value="">-- Select Role --</option>
            <option value="Administrator">Administrator</option>
            <option value="Restaurant">Restaurant</option>
            <option value="Customer">Customer</option>
            <option value="Donor">Donor</option>
            <option value="Needy">Needy</option>
        </select>

        <div id="restaurantSection" style="display:none;">
            <label for="restaurant_name">Restaurant Name:</label>
            <input type="text" name="restaurant_name" id="restaurant_name">
        </div>

        <label for="name">Full Name:</label>
        <input type="text" name="name" id="name" required>

        <label for="email">Email:</label>
        <input type="email" name="email" id="email" required>

        <label for="password">Password:</label>
        <input type="password" name="password" id="password" required>

        <label for="address">Address:</label>
        <input type="text" name="address" id="address">

        <label for="phone">Phone Number:</label>
        <input type="tel" name="phone" id="phone">

        <div id="creditCardSection" style="display:none;">
            <label for="credit_card">Credit Card Number:</label>
            <input type="text" name="credit_card" id="credit_card" maxlength="20">
        </div>

        <button type="submit">Register</button>
    </form>

    <?php if (!empty($message)): ?>
        <div class="message <?php echo $messageClass; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

</div>

</body>
</html>
