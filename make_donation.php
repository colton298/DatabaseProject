<?php
session_start();
require_once "config.php";

// Must be logged in and must be a donor
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "Donor") {
    header("Location: login.php");
    exit;
}

$donor_id = $_SESSION["user_id"];

// Validate POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: donor_dashboard.php");
    exit;
}

if (!isset($_POST["plate_id"]) || !isset($_POST["quantity"])) {
    die("Invalid donation request.");
}

$plate_id = intval($_POST["plate_id"]);
$quantity = intval($_POST["quantity"]);

// Get plate details
$plateQuery = "SELECT quantity FROM plates WHERE plate_id = ?";
$stmt = $conn->prepare($plateQuery);
$stmt->bind_param("i", $plate_id);
$stmt->execute();
$plateResult = $stmt->get_result();

if ($plateResult->num_rows === 0) {
    die("Invalid plate ID.");
}

$plate = $plateResult->fetch_assoc();

// Check quantity
if ($quantity <= 0 || $quantity > $plate["quantity"]) {
    die("Invalid quantity selected.");
}

// Insert into donations
$insertQuery = "INSERT INTO donations (donor_id, plate_id, quantity) VALUES (?, ?, ?)";
$stmt2 = $conn->prepare($insertQuery);
$stmt2->bind_param("iii", $donor_id, $plate_id, $quantity);
$stmt2->execute();

// Reduce plate quantity
$updatePlate = "UPDATE plates SET quantity = quantity - ? WHERE plate_id = ?";
$stmt3 = $conn->prepare($updatePlate);
$stmt3->bind_param("ii", $quantity, $plate_id);
$stmt3->execute();

// Redirect back to dashboard
header("Location: donor_dashboard.php?success=1");
exit;
?>

