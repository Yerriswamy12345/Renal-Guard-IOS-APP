<?php
// Test script to verify addSchedule.php is working correctly

$url = 'http://localhost:8888/renalguard/addSchedule.php';

// Test data
$data = [
    'doctor_email' => 'raju@gmail.com',  // Using the doctor from the logs
    'available_date' => date('Y-m-d', strtotime('+1 day')),
    'start_time' => '09:00:00',
    'end_time' => '17:00:00'
];

echo "Testing addSchedule.php endpoint...\n";
echo "Sending data:\n";
print_r($data);
echo "\n";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response:\n";
echo $response . "\n\n";

$decoded = json_decode($response, true);
if ($decoded) {
    echo "Decoded Response:\n";
    print_r($decoded);
}
?>
