<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "Connecting to DB...\n";
$conn = new mysqli("localhost", "root", "", "renalguard", 3306);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "Connected.\n";

$patient_email = "test@test.com"; // Default for testing, or better yet, verify existing patient
// I'll define a patient email that likely exists or just use empty string to fail gracefully if not found
// But I need to pass the SQL Prepare step.

$sql = "SELECT d.doctor_id, u.name, u.specialization, u.education, u.location, u.phone, u.email 
        FROM users u
        JOIN doctors d ON d.email = u.email
        JOIN patients p ON p.doctor_email = u.email
        WHERE p.email = ?
        LIMIT 1";

echo "Preparing SQL: $sql\n";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo "Prepare failed: (" . $conn->errno . ") " . $conn->error . "\n";
    die("SQL Error");
}

echo "Bind param...\n";
$stmt->bind_param("s", $patient_email);

echo "Execute...\n";
if (!$stmt->execute()) {
    echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error . "\n";
}

$result = $stmt->get_result();
echo "Rows returned: " . $result->num_rows . "\n";

if ($row = $result->fetch_assoc()) {
    print_r($row);
} else {
    echo "No match found (expected if email is wrong, but SQL ran fine).\n";
}

$stmt->close();
$conn->close();
?>
