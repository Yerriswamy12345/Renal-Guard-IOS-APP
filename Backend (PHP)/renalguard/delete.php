<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

// Connect to MySQL
$conn = new mysqli("localhost", "root", "", "renalguard", 3306);

// Check connection
if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Database connection failed"]);
    exit();
}

// Get raw POST body
$rawData = file_get_contents("php://input");
$data = json_decode($rawData, true);

// Get patient_id from JSON
$patient_id = $data['patient_id'] ?? '';

// Validate input
if (empty($patient_id)) {
    echo json_encode(["success" => false, "message" => "Patient ID is required in JSON body"]);
    $conn->close();
    exit();
}

// start transaction
$conn->begin_transaction();

try {
    // 1. Delete assessments
    $stmt1 = $conn->prepare("DELETE FROM patient_assessments WHERE patient_id = ?");
    $stmt1->bind_param("s", $patient_id);
    $stmt1->execute();
    $stmt1->close();

    // 2. Delete patient
    $stmt2 = $conn->prepare("DELETE FROM patients WHERE patient_id = ?");
    $stmt2->bind_param("s", $patient_id);
    $stmt2->execute();
    
    if ($stmt2->affected_rows > 0) {
        $conn->commit();
        echo json_encode(["success" => true, "message" => "Patient deleted successfully"]);
    } else {
        $conn->rollback();
        echo json_encode(["success" => false, "message" => "Patient not found"]);
    }
    $stmt2->close();

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(["success" => false, "message" => "Error deleting patient: " . $e->getMessage()]);
}

$conn->close();
exit();
?>