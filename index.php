<?php
// index.php
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Waste Not Kitchen</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f8f8;
            margin: 0;
            padding: 0;
        }

        header {
            background-color: #4CAF50;
            color: white;
            padding: 20px;
            text-align: center;
        }

        main {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-top: 40px;
        }

        .nav-links {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 20px;
        }

        .nav-links a {
            background-color: white;
            border: 2px solid #4CAF50;
            color: #4CAF50;
            padding: 15px 25px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            transition: 0.3s;
        }

        .nav-links a:hover {
            background-color: #4CAF50;
            color: white;
        }
    </style>
</head>
<body>

<header>
    <h1>Waste Not Kitchen</h1>
</header>

<main>
    <h2>Welcome to WNK</h2>
    <p>Select a section to continue:</p>
    <div class="nav-links">
        <a href="register.php">Register</a>
        <a href="login.php">Login</a>
        <a href="edit_profile.php">Edit Profile</a>
        <a href="logout.php">Logout</a>
        <!-- Add more as needed -->
    </div>
</main>


</body>
</html>
