<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"));
$doctor_id = $data->doctor_id ?? '';

if (!$doctor_id) {
    echo json_encode(["success"=>false,"message"=>"Doctor ID is required"]);
    exit();
}

$conn = new mysqli("localhost", "root", "", "renalguard", 3306);
if ($conn->connect_error) {
    echo json_encode(["success"=>false,"message"=>"Database connection failed"]);
    exit();
}

// get doctor email first
$res = $conn->query("SELECT email FROM doctors WHERE doctor_id='$doctor_id' LIMIT 1");
if (!$res || $res->num_rows === 0) {
    echo json_encode(["success"=>false,"message"=>"Doctor not found"]);
    $conn->close();
    exit();
}
$row = $res->fetch_assoc();
$email = $row['email'];

// start transaction
$conn->begin_transaction();

try {
    // 1. Delete from appointments (linked via doctor_schedule)
    $conn->query("DELETE FROM appointments WHERE schedule_id IN (SELECT schedule_id FROM doctor_schedule WHERE doctor_id='$doctor_id')");

    // 2. Delete from doctor_schedule
    $conn->query("DELETE FROM doctor_schedule WHERE doctor_id='$doctor_id'");

    // 3. Delete from doctors
    $sql3 = "DELETE FROM doctors WHERE doctor_id='$doctor_id'";
    if (!$conn->query($sql3)) {
        throw new Exception("Failed to delete from doctors: " . $conn->error);
    }

    // 4. Delete from users
    $sql4 = "DELETE FROM users WHERE email='$email'";
    if (!$conn->query($sql4)) {
        throw new Exception("Failed to delete from users: " . $conn->error);
    }

    $conn->commit();
    echo json_encode(["success"=>true,"message"=>"Doctor and all related data deleted successfully"]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(["success"=>false,"message"=>$e->getMessage()]);
}

$conn->close();
?>
