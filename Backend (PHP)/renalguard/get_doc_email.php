<?php
include 'db.php';
$res = $conn->query("SELECT email FROM doctors WHERE doctor_id='DOC03'");
if ($row = $res->fetch_assoc()) {
    echo "Email: " . $row['email'];
} else {
    echo "Doctor not found";
}
?>
