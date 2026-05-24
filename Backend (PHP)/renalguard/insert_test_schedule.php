<?php
$conn = new mysqli("localhost", "root", "", "renalguard", 3306);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$date = date("Y-m-d"); // Today
$startTime = "22:30:00";
$endTime = "23:30:00";
$doctorId = "DOC03"; // Using DOC03 as seen in previous logs

$sql = "INSERT INTO doctor_schedule (doctor_id, available_date, start_time, end_time, slot_duration, max_patients, booked_count, status)
        VALUES ('$doctorId', '$date', '$startTime', '$endTime', 15, 5, 0, 'active')";

if ($conn->query($sql) === TRUE) {
    echo "New record created successfully: $date $startTime - $endTime\n";
    echo "This schedule should be ONGOING right now.\n";
} else {
    echo "Error: " . $sql . "\n" . $conn->error;
}

$conn->close();
?>
