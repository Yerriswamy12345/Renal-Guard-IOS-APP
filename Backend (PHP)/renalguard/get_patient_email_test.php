<?php
include 'db.php';
// Get any patient email from appointments table to test
$res = $conn->query("SELECT patient_email FROM appointments LIMIT 1");
if ($row = $res->fetch_assoc()) {
    echo "Patient Email: " . $row['patient_email'];
} else {
    echo "No appointments found to get patient email from.";
}
?>
