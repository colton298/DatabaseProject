<?php
session_start();
require_once "config.php";

// Must be logged in as Needy
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

// Fetch donation details
$query = "SELECT quantity, needy_id FROM donations WHERE donation_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $donation_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Invalid donation selected.");
}

$donation = $result->fetch_assoc();

// Check if donation already assigned or quantity exceeds available
if ($donation["needy_id"] !== null) {
    die("This plate has already been reserved by another needy.");
}

if ($quantity_requested <= 0 || $quantity_requested > $donation["quantity"]) {
    die("Invalid quantity selected.");
}

// Update donation: assign needy and reduce quantity
$new_quantity = $donation["quantity"] - $quantity_requested;

if ($new_quantity === 0) {
    // Fully assigned: mark needy and fulfilled
    $updateQuery = "UPDATE donations SET needy_id = ?, fulfilled = 1, quantity = 0 WHERE donation_id = ?";
    $stmt2 = $conn->prepare($updateQuery);
    $stmt2->bind_param("ii", $needy_id, $donation_id);
} else {
    // Partially assigned: reduce quantity and create new record for this needy
    // Reduce original donation
    $updateQuery = "UPDATE donations SET quantity = ? WHERE donation_id = ?";
    $stmt2 = $conn->prepare($updateQuery);
    $stmt2->bind_param("ii", $new_quantity, $donation_id);

    // Insert new donation record for this needy
    $insertQuery = "INSERT INTO donations (donor_id, needy_id, plate_id, quantity, donated_at, fulfilled)
                    SELECT donor_id, ?, plate_id, ?, donated_at, 1 FROM donations WHERE donation_id = ?";
    $stmt3 = $conn->prepare($insertQuery);
    $stmt3->bind_param("iii", $needy_id, $quantity_requested, $donation_id);
}

// Execute updates
if (isset($stmt3)) {
    $stmt2->execute();
    $stmt3->execute();
    $stmt3->close();
} else {
    $stmt2->execute();
}
$stmt2->close();
$stmt->close();

header("Location: needy_dashboard.php?success=1");
exit;
?>
