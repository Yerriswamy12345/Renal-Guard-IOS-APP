<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Content-Type: application/json");

include 'db.php';

$input = json_decode(file_get_contents("php://input"), true);
$patient_email = $input['patient_email'] ?? ($_POST['patient_email'] ?? '');

if (!$patient_email) {
    echo json_encode(["success"=>false, "message"=>"patient_email required"]);
    exit();
}

// Join appointments with doctor_schedule to get doctor slot details
$sql = "SELECT a.appointment_id, a.slot_time, a.status, 
               ds.available_date, ds.start_time, ds.end_time
        FROM appointments a
        JOIN doctor_schedule ds ON a.schedule_id = ds.schedule_id
        WHERE a.patient_email = ?
        ORDER BY ds.available_date DESC, a.slot_time ASC";

$stmt = $conn->prepare($sql);
if(!$stmt) {
    echo json_encode(["success"=>false, "message"=>"Prepare failed"]);
    exit();
}

$stmt->bind_param("s", $patient_email);
$stmt->execute();
$result = $stmt->get_result();

$appointments = [];
while($row = $result->fetch_assoc()) {
    $appointments[] = [
        "appointment_id" => (string)$row['appointment_id'],
        "slot" => $row['start_time'] . " - " . $row['end_time'],
        "date" => $row['available_date'],
        "slotTime" => $row['slot_time'],
        "status" => $row['status']
    ];
}

echo json_encode(["success"=>true, "appointments"=>$appointments]);

$stmt->close();
$conn->close();
?>
