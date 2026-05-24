<?php
/**
 * RenalGuard — Personalized IJV Line Reminder Notifications
 * Sends push notifications to both doctor & patient with different messages.
 */

$conn = new mysqli("localhost", "root", "", "renalguard", 3306);
if ($conn->connect_error) die("Database connection failed");


function sendFCM($token, $title, $body) {
    if (empty($token)) return;

    $serviceAccountPath = __DIR__ . '/fcm-service-account.json';
    $serviceAccount = json_decode(file_get_contents($serviceAccountPath), true);
    $projectId = $serviceAccount['project_id'];

    // ---- Create JWT ----
    $header = base64_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
    $now = time();
    $claims = base64_encode(json_encode([
        'iss' => $serviceAccount['client_email'],
        'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
        'aud' => 'https://oauth2.googleapis.com/token',
        'exp' => $now + 3600,
        'iat' => $now
    ]));

    $jwt = $header . '.' . $claims;
    openssl_sign($jwt, $signature, $serviceAccount['private_key'], 'sha256');
    $jwt .= '.' . base64_encode($signature);

    // ---- Get access token ----
    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        'assertion' => $jwt
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = json_decode(curl_exec($ch), true);
    curl_close($ch);
    $accessToken = $response['access_token'] ?? '';

    if (!$accessToken) {
        file_put_contents("fcm_log.txt", date("Y-m-d H:i:s") . " => Failed to generate access token\n", FILE_APPEND);
        return;
    }

    // ---- Send push message ----
    $url = "https://fcm.googleapis.com/v1/projects/$projectId/messages:send";
    $data = [
        "message" => [
            "token" => $token,
            'notification' => [
                'title' => $title,
                'body' => $body,
                'sound' => 'default'
            ],
            'data' => [
                'title' => $title,
                'body' => $body,
                'custom_key' => 'renalguard'
            ]

        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $accessToken",
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    $result = curl_exec($ch);
    curl_close($ch);

    file_put_contents("fcm_log.txt", date("Y-m-d H:i:s") . " => " . $result . PHP_EOL, FILE_APPEND);
}

$res = $conn->query("
SELECT pa.*, 
       u1.fcm_token AS doctor_token, 
       u1.name AS doctor_name,
       u2.fcm_token AS patient_token, 
       u2.name AS patient_name
FROM patient_assessments pa
JOIN users u1 ON pa.doctor_email = u1.email
JOIN users u2 ON pa.patient_email = u2.email
WHERE pa.notified = 0
");

$today = new DateTime();
$notifications = 0;

while ($r = $res->fetch_assoc()) {
    $created = new DateTime($r['created_at']);
    $days = $created->diff($today)->days;

    $patientName = htmlspecialchars($r['patient_name']);
    $doctorName  = htmlspecialchars($r['doctor_name']);
    $lineDuration = strtolower($r['line_duration']);
    $doctorMsg = "";
    $patientMsg = "";

    // --- Week-based reminder logic ---
    switch ($lineDuration) {
        case "week 1":
            if ($days >= 20) {
                $doctorMsg  = "Patient {$patientName}'s IJV line is due for replacement tomorrow.";
                $patientMsg = "Your IJV line needs to be reviewed or replaced tomorrow.";
            }
            break;

        case "week 2":
            if ($days >= 13) {
                $doctorMsg  = "Patient {$patientName}'s IJV line is 2 weeks old — review next week.";
                $patientMsg = "Your IJV line is 2 weeks old — review scheduled next week.";
            }
            break;

        case "week 3":
            if ($days >= 0) {
                $doctorMsg  = "Immediate: {$patientName}'s IJV line needs replacement today!";
                $patientMsg = "Urgent: Your IJV line must be replaced today. Visit your dialysis unit.";
            }
            break;
    }

    // Send to doctor
    if (!empty($r['doctor_token']) && !empty($doctorMsg)) {
        sendFCM($r['doctor_token'], "Line Replacement Alert", $doctorMsg);
    }

    // Send to patient
    if (!empty($r['patient_token']) && !empty($patientMsg)) {
        sendFCM($r['patient_token'], "Line Change Reminder", $patientMsg);
    }

    if ($doctorMsg || $patientMsg) {
        $conn->query("UPDATE patient_assessments SET notified = 1 WHERE id = {$r['id']}");
        $notifications++;
    }
}

echo json_encode(["success" => true, "notifications_sent" => $notifications]);
$conn->close();
file_put_contents("cron_log.txt", date("Y-m-d H:i:s") . " => Cron executed\n", FILE_APPEND);

?>
