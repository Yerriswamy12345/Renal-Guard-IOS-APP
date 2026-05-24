<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Content-Type: application/json");

define('FCM_SERVER_KEY', 'YOUR_FIREBASE_SERVER_KEY'); // ⚠️ Replace with your Firebase Cloud Messaging key

function sendFCM($tokens, $title, $body) {
    $url = 'https://fcm.googleapis.com/fcm/send';
    $data = [
        'registration_ids' => $tokens,
        'notification' => ['title' => $title, 'body' => $body, 'sound' => 'default']
    ];
    $headers = ['Authorization: key=' . FCM_SERVER_KEY, 'Content-Type: application/json'];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}

$data = json_decode(file_get_contents("php://input"), true);
$doc = $data['doctor_email'] ?? '';
$pat = $data['patient_email'] ?? '';
$dur = $data['line_duration'] ?? '';

$conn = new mysqli("localhost", "root", "", "renalguard", 3306);
$resDoc = $conn->query("SELECT fcm_token FROM users WHERE email='$doc'");
$resPat = $conn->query("SELECT fcm_token FROM users WHERE email='$pat'");

$tokens = [];
if ($resDoc && $r=$resDoc->fetch_assoc()) $tokens[]=$r['fcm_token'];
if ($resPat && $r=$resPat->fetch_assoc()) $tokens[]=$r['fcm_token'];

$msg = match($dur) {
    "Week 1" => "3 weeks since IJV insertion — line change tomorrow.",
    "Week 2" => "2 weeks completed — line change next week.",
    "Week 3" => "Immediate attention — line change required today!",
    default => "Check patient’s line status."
};

sendFCM($tokens, "Line Change Reminder", $msg);
echo json_encode(["success"=>true,"message"=>"Notification sent"]);
$conn->close();
?>
