<?php
// Mock input
$_POST['doctor_id'] = 'DOC03';
$_POST['date'] = '2026-02-02';

// Capture output
ob_start();
include 'get_doctor_schedules.php';
$output = ob_get_clean();

echo "--- OUTPUT ---\n";
echo $output;
echo "\n--------------\n";

// Decode and debug
$json = json_decode($output, true);
if ($json) {
    echo "Success: " . ($json['success'] ? 'YES' : 'NO') . "\n";
    echo "Count: " . count($json['schedules']) . "\n";
    print_r($json['schedules']);
} else {
    echo "Failed to decode JSON.\n";
}
?>
