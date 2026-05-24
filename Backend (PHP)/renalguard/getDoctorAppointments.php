<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Content-Type: application/json");

$conn = new mysqli("localhost", "root", "", "renalguard", 3306);
if ($conn->connect_error) {
    echo json_encode(["success"=>false,"message"=>"Database connection failed"]);
    exit();
}

$data = json_decode(file_get_contents("php://input"));
$doctor_email  = $conn->real_escape_string($data->doctor_email ?? '');
$patient_email = $conn->real_escape_string($data->patient_email ?? '');

// 🛑 Validate at least one email
if (empty($doctor_email) && empty($patient_email)) {
    echo json_encode(["success"=>false,"message"=>"Email required"]);
    exit();
}

$appointments = [];

// ✅ Case 1: Doctor wants to see all appointments
if (!empty($doctor_email) && empty($patient_email)) {

    // Get doctor_id
    $resDoc = $conn->query("SELECT doctor_id FROM doctors WHERE email='$doctor_email' LIMIT 1");
    if (!$resDoc || $resDoc->num_rows == 0) {
        echo json_encode(["success"=>false,"message"=>"Doctor not found"]);
        exit();
    }
    $rowDoc = $resDoc->fetch_assoc();
    $doctor_id = $rowDoc['doctor_id'];

    $sql = "SELECT a.appointment_id, a.schedule_id, a.patient_email, a.slot_time, a.status AS appointment_status, a.booking_date,
                   s.available_date, s.start_time AS schedule_start, s.end_time AS schedule_end,
                   u.name AS patient_name, u.phone AS patient_phone
            FROM appointments a
            JOIN doctor_schedule s ON a.schedule_id = s.schedule_id
            JOIN users u ON a.patient_email = u.email
            WHERE s.doctor_id = '$doctor_id'
            ORDER BY s.available_date DESC, a.slot_time ASC";

    $res = $conn->query($sql);

// ✅ Case 2: Patient wants to see only their appointments
} elseif (!empty($patient_email)) {

    $sql = "SELECT a.appointment_id, a.schedule_id, a.patient_email, a.slot_time, a.status AS appointment_status, a.booking_date,
                   s.available_date, s.start_time AS schedule_start, s.end_time AS schedule_end,
                   d.name AS doctor_name, d.email AS doctor_email
            FROM appointments a
            JOIN doctor_schedule s ON a.schedule_id = s.schedule_id
            JOIN doctors d ON s.doctor_id = d.doctor_id
            WHERE a.patient_email = '$patient_email'
            ORDER BY s.available_date DESC, a.slot_time ASC";

    $res = $conn->query($sql);

} else {
    echo json_encode(["success"=>false,"message"=>"Invalid request"]);
    exit();
}

// ✅ Handle query result
if (!$res) {
    echo json_encode(["success"=>false,"message"=>$conn->error]);
    exit();
}

while ($row = $res->fetch_assoc()) {
    $appointments[] = [
        "appointment_id"     => $row['appointment_id'],
        "schedule_id"        => $row['schedule_id'],
        "date"               => $row['available_date'],
        "slot_time"          => $row['slot_time'],
        "appointment_status" => $row['appointment_status'],
        "booking_date"       => $row['booking_date'],
        "patient_email"      => $row['patient_email'] ?? null,
        "patient_name"       => $row['patient_name'] ?? null,
        "patient_phone"      => $row['patient_phone'] ?? null,
        "doctor_name"        => $row['doctor_name'] ?? null,
        "doctor_email"       => $row['doctor_email'] ?? null,
        "schedule_start"     => $row['schedule_start'],
        "schedule_end"       => $row['schedule_end']
    ];
}

// ✅ Response
echo json_encode([
    "success" => true,
    "appointments" => $appointments,
    "fetched_for" => !empty($patient_email) ? "patient" : "doctor"
]);

$conn->close();
?>
