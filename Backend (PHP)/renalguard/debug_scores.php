<?php
header("Content-Type: text/plain");
$conn = new mysqli("localhost", "root", "", "renalguard", 3306);
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

echo "--- PATIENT ASSESSMENTS ---\n";
$result = $conn->query("SELECT * FROM patient_assessments");
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo "ID: " . $row["id"] . " | PatientID: [" . $row["patient_id"] . "] | Email: [" . $row["patient_email"] . "] | Score: " . $row["score"] . "\n";
    }
} else {
    echo "0 results in patient_assessments table.\n";
}

echo "\n--- USERS ---\n";
$result = $conn->query("SELECT id, email, name FROM users");
while($row = $result->fetch_assoc()) {
    echo "ID: " . $row["id"] . " | Email: [" . $row["email"] . "] | Name: " . $row["name"] . "\n";
}
$conn->close();
?>
