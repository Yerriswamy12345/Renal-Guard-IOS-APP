<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Content-Type: application/json");

// Connect to MySQL
$conn = new mysqli("localhost", "root", "", "renalguard", 3306);
if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Database connection failed"]);
    exit();
}

// First try to read params from GET/POST
$patient_id = $_GET['patient_id'] ?? $_POST['patient_id'] ?? null;
$patient_email = $_GET['email'] ?? $_POST['email'] ?? null;

// If not found, try to decode JSON body
if (!$patient_id && !$patient_email) {
    $input = json_decode(file_get_contents("php://input"), true);
    $patient_id = $input['patient_id'] ?? null;
    $patient_email = $input['email'] ?? null;
}



if (!$patient_id && !$patient_email) {
    echo json_encode(["success" => false, "message" => "Missing patient_id or email"]);
    $conn->close();
    exit();
}

// If we only have email, try to find the patient_id from the patients table first
if (!$patient_id && $patient_email) {
    $stmt_pid = $conn->prepare("SELECT patient_id FROM patients WHERE email = ?");
    $stmt_pid->bind_param("s", $patient_email);
    $stmt_pid->execute();
    $res_pid = $stmt_pid->get_result();
    if ($row_pid = $res_pid->fetch_assoc()) {
        $patient_id = $row_pid['patient_id'];
        // file_put_contents("debug_scores.txt", "Found Patient ID from email: $patient_id\n", FILE_APPEND);
    }
    $stmt_pid->close();
}

// Fetch all patient scores
$sql = "SELECT id as score_id, score, created_at 
        FROM patient_assessments 
        WHERE ";

if ($patient_id) {
    $sql .= "patient_id = ? ";
    $param = $patient_id;
} else {
    // Fallback: Case insensitive email search on assessments table directly
    $sql .= "LOWER(TRIM(patient_email)) = LOWER(TRIM(?)) ";
    $param = $patient_email;
}

$sql .= "ORDER BY created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $param);
$stmt->execute();
$result = $stmt->get_result();

$scores = [];
while ($row = $result->fetch_assoc()) {
    $scores[] = [
        "score_id" => (int)$row['score_id'],
        "score" => (int)$row['score'],
        "created_at" => $row['created_at']
    ];
}



if (count($scores) > 0) {
    echo json_encode([
        "success" => true,
        "count" => count($scores),
        "scores" => $scores
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "No scores found"
    ]);
}

$stmt->close();
$conn->close();
?>
