<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Content-Type: application/json");

error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Asia/Kolkata');

include 'db.php';

$input = json_decode(file_get_contents("php://input"), true);
$doctor_email = $input['doctor_email'] ?? ($_POST['doctor_email'] ?? '');

if (!$doctor_email) {
    echo json_encode(["success"=>false,"message"=>"doctor_email required"]);
    exit();
}

// Get doctor_id
$stmt = $conn->prepare("SELECT doctor_id FROM doctors WHERE email=?");
if (!$stmt) {
    echo json_encode(["success"=>false,"message"=>"Prepare failed: " . $conn->error]);
    exit();
}
$stmt->bind_param("s", $doctor_email);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows == 0) {
    echo json_encode(["success"=>false,"message"=>"Doctor not found for email: $doctor_email"]);
    exit();
}
$doc = $res->fetch_assoc();
$doctor_id = $doc['doctor_id'];

// Get schedules
// Using prepared statement for safety
$sql = "SELECT * FROM doctor_schedule WHERE doctor_id=? ORDER BY available_date DESC, start_time ASC";
$stmt2 = $conn->prepare($sql);
$stmt2->bind_param("s", $doctor_id);
$stmt2->execute();
$result = $stmt2->get_result();

$schedules = [];
$currentTime = time();

while ($row = $result->fetch_assoc()) {
    $sch = [
        "schedule_id" => (string)$row['schedule_id'],
        "available_date" => $row['available_date'],
        "start_time" => $row['start_time'],
        "end_time" => $row['end_time'],
        "slot_duration" => (string)$row['slot_duration'],
        "max_patients" => (string)$row['max_patients'],
        "booked_count" => (string)$row['booked_count'],
        "remaining" => intval($row['max_patients']) - intval($row['booked_count']),
        "status" => $row['status'], 
        "patients" => []
    ];
    
    // Calculate display status
    $startStr = $row['available_date'] . ' ' . $row['start_time'];
    $endStr   = $row['available_date'] . ' ' . $row['end_time'];
    
    $start = strtotime($startStr);
    $end = strtotime($endStr);
    
    if ($currentTime > $end) {
        $sch['status'] = "Finished";
    } elseif ($currentTime >= $start) {
        $sch['status'] = "Ongoing";
    } else {
        $sch['status'] = "Upcoming";
    }

    // Get appointments for this schedule
    $sch_id = $row['schedule_id'];
    // Join with users table to get patient name and phone
    $app_sql = "SELECT a.appointment_id, a.slot_time, a.status, u.name, u.phone, u.email 
                FROM appointments a
                LEFT JOIN users u ON a.patient_email = u.email
                WHERE a.schedule_id = '$sch_id'
                ORDER BY a.slot_time ASC";
                
    $app_res = $conn->query($app_sql);
    if ($app_res) {
        while ($app = $app_res->fetch_assoc()) {
            $sch['patients'][] = [
                "appointment_id" => (string)$app['appointment_id'],
                "slot_time" => $app['slot_time'],
                "status" => ucfirst($app['status']),
                "patient_name" => $app['name'] ?? 'Unknown',
                "patient_phone" => $app['phone'] ?? '',
                "patient_email" => $app['email'] ?? $app['patient_email'] // Fallback if user not found
            ];
        }
    }
    
    $schedules[] = $sch;
}

echo json_encode(["success"=>true, "schedules"=>$schedules]);
$conn->close();
?>
