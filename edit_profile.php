<?php
session_start();
require_once "config.php";

if (!isset($_SESSION["user_id"])) {
    die("<h3 style='color:red; text-align:center;'>Access denied. Please log in first.</h3>");
}

$user_id = $_SESSION["user_id"];
$role = $_SESSION["role"];

$message = "";
$messageClass = "error";

// Fetch current user info
$query = "SELECT * FROM users WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$userResult = $stmt->get_result();
$user = $userResult->fetch_assoc();
$stmt->close();

// Fetch restaurant info if applicable
$restaurant_name = "";
if ($role === "Restaurant") {
    $rquery = "SELECT restaurant_name FROM restaurants WHERE user_id = ?";
    $rstmt = $conn->prepare($rquery);
    $rstmt->bind_param("i", $user_id);
    $rstmt->execute();
    $rresult = $rstmt->get_result();
    if ($rrow = $rresult->fetch_assoc()) {
        $restaurant_name = $rrow["restaurant_name"];
    }
    $rstmt->close();
}

// Handle update
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);
    $address = trim($_POST["address"]);
    $phone = trim($_POST["phone"]);
    $credit_card = trim($_POST["credit_card"] ?? "");
    $new_password = trim($_POST["password"]);
    $restaurant_name = trim($_POST["restaurant_name"] ?? "");

    if (empty($name) || empty($email)) {
        $message = "Name and email cannot be empty.";
    } elseif ($role !== "Needy" && empty($phone)) {
        $message = "Phone number is required for this role.";
    } elseif (($role === "Customer" || $role === "Donor") && empty($credit_card)) {
        $message = "Credit card information is required for customers and donors.";
    } elseif ($role === "Restaurant" && empty($restaurant_name)) {
        $message = "Restaurant name is required for restaurant accounts.";
    } else {
        // Update users table
        if (!empty($new_password)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $updateQuery = "UPDATE users SET name=?, email=?, password_hash=?, address=?, phone=?, credit_card=? WHERE user_id=?";
            $stmt = $conn->prepare($updateQuery);
            $stmt->bind_param("ssssssi", $name, $email, $hashed_password, $address, $phone, $credit_card, $user_id);
        } else {
            $updateQuery = "UPDATE users SET name=?, email=?, address=?, phone=?, credit_card=? WHERE user_id=?";
            $stmt = $conn->prepare($updateQuery);
            $stmt->bind_param("sssssi", $name, $email, $address, $phone, $credit_card, $user_id);
        }

        if ($stmt->execute()) {
            // If restaurant, update restaurant name
            if ($role === "Restaurant") {
                $rquery = "UPDATE restaurants SET restaurant_name=? WHERE user_id=?";
                $rstmt = $conn->prepare($rquery);
                $rstmt->bind_param("si", $restaurant_name, $user_id);
                $rstmt->execute();
                $rstmt->close();
            }

            $message = "Profile updated successfully.";
            $messageClass = "success";
            $_SESSION["name"] = $name;
            $_SESSION["email"] = $email;
        } else {
            $message = "Error updating profile: " . $stmt->error;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Profile - Waste Not Kitchen</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f7f8;
            margin: 0;
            padding: 0;
        }

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
        }

        nav a:hover {
            text-decoration: underline;
        }

        .container {
            max-width: 600px;
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

        input {
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
</head>
<body>

<nav>
    <a href="index.php">Home</a>
    <a href="restaurant.php">Restaurant Dashboard</a>
    <a href="logout.php">Logout</a>
</nav>

<div class="container">
    <h2>Edit Profile</h2>

    <form method="POST" action="edit_profile.php">
        <label for="name">Full Name:</label>
        <input type="text" name="name" id="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>

        <label for="email">Email:</label>
        <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>

        <label for="password">New Password (leave blank to keep current):</label>
        <input type="password" name="password" id="password">

        <label for="address">Address:</label>
        <input type="text" name="address" id="address" value="<?php echo htmlspecialchars($user['address']); ?>">

        <label for="phone">Phone Number:</label>
        <input type="tel" name="phone" id="phone" value="<?php echo htmlspecialchars($user['phone']); ?>">

        <?php if ($role === "Restaurant"): ?>
            <label for="restaurant_name">Restaurant Name:</label>
            <input type="text" name="restaurant_name" id="restaurant_name" value="<?php echo htmlspecialchars($restaurant_name); ?>" required>
        <?php endif; ?>

        <?php if ($role === "Customer" || $role === "Donor"): ?>
            <label for="credit_card">Credit Card Number:</label>
            <input type="text" name="credit_card" id="credit_card" value="<?php echo htmlspecialchars($user['credit_card']); ?>" required>
        <?php endif; ?>

        <button type="submit">Save Changes</button>
    </form>

    <?php if (!empty($message)): ?>
        <div class="message <?php echo $messageClass; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
