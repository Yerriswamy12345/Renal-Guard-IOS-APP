<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Content-Type: application/json");

$conn = new mysqli("localhost", "root", "", "renalguard", 3306);
$log_file = __DIR__ . '/debug_get_schedules.log';
$raw_input = file_get_contents("php://input");
file_put_contents($log_file, "--- NEW REQUEST ---\n", FILE_APPEND);
file_put_contents($log_file, "Raw Input: ".$raw_input."\n", FILE_APPEND);
file_put_contents($log_file, "GET: ".print_r($_GET, true)."\n", FILE_APPEND);
file_put_contents($log_file, "POST: ".print_r($_POST, true)."\n", FILE_APPEND);

$doctor_id = trim($_GET['doctor_id'] ?? ($_POST['doctor_id'] ?? ''));
$date      = trim($_GET['date'] ?? ($_POST['date'] ?? ''));

if (!$doctor_id || !$date) {
    // Try JSON input
    $input = json_decode(file_get_contents("php://input"), true);
    if ($input) {
        $doctor_id = trim($input['doctor_id'] ?? $doctor_id);
        $date      = trim($input['date'] ?? $date);
    }
}

if (!$doctor_id || !$date) {
    echo json_encode(["success"=>false,"message"=>"doctor_id and date required"]);
    exit();
}

$sql = "SELECT schedule_id, doctor_id, available_date, start_time, end_time, 
               slot_duration, max_patients, booked_count, status
        FROM doctor_schedule
        WHERE LOWER(TRIM(doctor_id)) = LOWER('$doctor_id') 
          AND available_date >= '$date'
          AND status='active'
        ORDER BY available_date ASC, start_time ASC";

$debug_info = [
    "received_doctor_id" => $doctor_id,
    "received_date" => $date,
    "sql" => $sql
];

$result = $conn->query($sql);
if (!$result) {
    echo json_encode(["success"=>false, "message"=>"Query Error: " . $conn->error, "debug"=>$debug_info]);
    exit();
}

$debug_info["rows_found"] = $result->num_rows;

$data = [];
date_default_timezone_set('Asia/Kolkata');
$currentTime = time();

while ($row = $result->fetch_assoc()) {
    // Construct full datetime strings to ensure accurate comparison across dates
    $startDateTimeStr = $row['available_date'] . ' ' . $row['start_time'];
    $endDateTimeStr   = $row['available_date'] . ' ' . $row['end_time'];

    $start = strtotime($startDateTimeStr);
    $end   = strtotime($endDateTimeStr);

    // Determine status (ongoing / finished / upcoming)
    if ($currentTime >= $start && $currentTime <= $end) {
        $displayStatus = "ongoing";
    } elseif ($currentTime > $end) {
        $displayStatus = "finished";
    } else {
        $displayStatus = "upcoming";
    }

    $remainingSlots = $row['max_patients'] - $row['booked_count'];

    // Keep if valid
    if ($remainingSlots > 0 && $displayStatus !== 'finished') {
        $data[] = [
            "schedule_id"     => intval($row['schedule_id']),
            "doctor_id"       => $row['doctor_id'],
            "date"            => $row['available_date'],
            "start_time"      => $row['start_time'],
            "end_time"        => $row['end_time'],
            "slot_duration"   => intval($row['slot_duration']),
            "max_patients"    => intval($row['max_patients']),
            "booked_count"    => intval($row['booked_count']),
            "status"          => $displayStatus,
            "remaining_slots" => $remainingSlots
        ];
    }
}

echo json_encode([
    "success" => true,
    "doctor_id" => $doctor_id,
    "schedules" => $data,
    "debug" => $debug_info
]);

$conn->close();
?>
