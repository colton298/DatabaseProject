<?php
// Database Configuration for existing wnk_database
$servername = "localhost";
$username = "root";
$password = "root"; 
$dbname = "wnk_database";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>