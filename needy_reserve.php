<?php
session_start();
require_once "config.php";

// Only Needy users
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "Needy") {
    header("Location: login.php");
    exit;
}

$needy_id = $_SESSION["user_id"];

// Validate POST
if ($_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_POST["donation_id"], $_POST["quantity"])) {
    header("Location: needy_dashboard.php");
    exit;
}

$donation_id = intval($_POST["donation_id"]);
$quantity_requested = intval($_POST["quantity"]);

// Fetch donation
$query = "SELECT donor_id, plate_id, quantity, needy_id, fulfilled FROM donations WHERE donation_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $donation_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Invalid donation selected.");
}

$donation = $result->fetch_assoc();

// Already reserved?
if ($donation["needy_id"] !== null) {
    die("This donation has already been reserved.");
}

// Invalid quantity?
if ($quantity_requested <= 0 || $quantity_requested > $donation["quantity"]) {
    die("Invalid quantity selected.");
}

$new_quantity = $donation["quantity"] - $quantity_requested;

if ($new_quantity === 0) {
    // Fully reserve
    $updateQuery = "UPDATE donations SET needy_id = ?, fulfilled = 1 WHERE donation_id = ?";
    $stmt2 = $conn->prepare($updateQuery);
    $stmt2->bind_param("ii", $needy_id, $donation_id);
    $stmt2->execute();
    $stmt2->close();
} else {
    // Partially reserve: reduce original donation
    $updateQuery = "UPDATE donations SET quantity = ? WHERE donation_id = ?";
    $stmt2 = $conn->prepare($updateQuery);
    $stmt2->bind_param("ii", $new_quantity, $donation_id);
    $stmt2->execute();
    $stmt2->close();

    // Create a new donation record for the reserved quantity
    $insertQuery = "INSERT INTO donations (donor_id, needy_id, plate_id, quantity, donated_at, fulfilled)
                    VALUES (?, ?, ?, ?, NOW(), 1)";
    $stmt3 = $conn->prepare($insertQuery);
    $stmt3->bind_param("iiii", $donation["donor_id"], $needy_id, $donation["plate_id"], $quantity_requested);
    $stmt3->execute();
    $stmt3->close();
}

$stmt->close();

// Redirect back
header("Location: needy_dashboard.php?success=1");
exit;
?>
