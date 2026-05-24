<?php
header("Content-Type: text/plain");
date_default_timezone_set('Asia/Kolkata');

$conn = new mysqli("localhost", "root", "", "renalguard", 3306);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check for schedules that should be ongoing NOW
$currentTime = time();
$currentDate = date("Y-m-d");

echo "Checking for ongoing schedules at " . date("Y-m-d H:i:s") . "\n";
echo "Timestamp: $currentTime\n\n";

$sql = "SELECT * FROM doctor_schedule WHERE available_date = '$currentDate' AND status = 'active'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $startStr = $row['available_date'] . ' ' . $row['start_time'];
        $endStr   = $row['available_date'] . ' ' . $row['end_time'];
        
        $start = strtotime($startStr);
        $end = strtotime($endStr);
        
        echo "ID: " . $row['schedule_id'] . "\n";
        echo "Doctor: " . $row['doctor_id'] . "\n";
        echo "Time: " . $row['start_time'] . " - " . $row['end_time'] . "\n";
        echo "Start TS: $start, End TS: $end\n";
        
        if ($currentTime >= $start && $currentTime <= $end) {
            echo "Status: ONGOING (Matched!)\n";
        } elseif ($currentTime > $end) {
            echo "Status: FINISHED\n";
        } else {
            echo "Status: UPCOMING\n";
        }
        echo "--------------------------\n";
    }
} else {
    echo "No active schedules found for today ($currentDate).\n";
}
$conn->close();
?>
