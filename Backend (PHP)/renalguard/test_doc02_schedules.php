<?php
// Test fetching schedules for DOC02 manually
include "db.php";

$_POST['doctor_id'] = 'DOC02';

include "get_doctor_schedules.php";
?>
