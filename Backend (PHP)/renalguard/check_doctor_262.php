<?php
$conn = new mysqli("localhost", "root", "", "renalguard", 3306);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check for doctor_id = '262'
$sql = "SELECT * FROM doctors WHERE doctor_id = '262'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "Doctor with ID 262 FOUND in database.\n";
    while($row = $result->fetch_assoc()) {
        echo "Name: " . $row["name"] . " - Email: " . $row["email"] . "\n";
    }
} else {
    echo "Doctor with ID 262 NOT found in database.\n";
}

// Check for doctor_id = 'DOC03'
$sql2 = "SELECT * FROM doctors WHERE doctor_id = 'DOC03'";
$result2 = $conn->query($sql2);

if ($result2->num_rows > 0) {
    echo "Doctor with ID DOC03 FOUND in database.\n";
     while($row = $result2->fetch_assoc()) {
        echo "Name: " . $row["name"] . " - Email: " . $row["email"] . "\n";
    }
} else {
    echo "Doctor with ID DOC03 NOT found in database.\n";
}

$conn->close();
?>
