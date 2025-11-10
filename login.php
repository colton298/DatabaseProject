<?php
session_start();
require_once "config.php";

$message = "";
$messageClass = "error";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    if (empty($email) || empty($password)) {
        $message = "Please enter both email and password.";
    } else {
        // Check if the email exists
        $query = "SELECT user_id, role, name, email, password_hash FROM users WHERE email = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();

            // Verify password
            if (password_verify($password, $row["password_hash"])) {
                // Store session data
                $_SESSION["user_id"] = $row["user_id"];
                $_SESSION["role"] = $row["role"];
                $_SESSION["name"] = $row["name"];
                $_SESSION["email"] = $row["email"];

                // Redirect user based on role
                if ($row["role"] === "Administrator") {
                    header("Location: admin_dashboard.php");
                } elseif ($row["role"] === "Restaurant") {
                    header("Location: restaurant.php");
                } elseif ($row["role"] === "Customer") {
                    header("Location: customer_dashboard.php");
                } elseif ($row["role"] === "Donor") {
                    header("Location: donor_dashboard.php");
                } elseif ($row["role"] === "Needy") {
                    header("Location: needy_dashboard.php");
                } else {
                    header("Location: index.php");
                }
                exit;
            } else {
                $message = "Incorrect password. Please try again.";
            }
        } else {
            $message = "No account found with that email address.";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Waste Not Kitchen</title>
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
            max-width: 450px;
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

<!-- Navigation Bar -->
<nav>
    <a href="index.php">Home</a>
    <a href="register.php">Register</a>
    <a href="edit_profile.php">Edit Profile</a>
</nav>

<div class="container">
    <h2>Login - Waste Not Kitchen</h2>

    <form method="POST" action="login.php">
        <label for="email">Email:</label>
        <input type="email" name="email" id="email" required>

        <label for="password">Password:</label>
        <input type="password" name="password" id="password" required>

        <button type="submit">Login</button>
    </form>

    <?php if (!empty($message)): ?>
        <div class="message <?php echo $messageClass; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
