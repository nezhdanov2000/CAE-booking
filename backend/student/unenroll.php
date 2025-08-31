<?php
require_once '../common/auth.php';
require_once '../common/db.php';

require_student();

$student_id = get_current_user_id();
$timeslot_id = (int)($_POST['timeslot_id'] ?? 0);

if (!$timeslot_id) {
    send_json_error('timeslot_id is not passed');
}

// Remove the student from the slot
$delete = $conn->prepare("DELETE FROM Student_Choice WHERE Student_ID = ? AND Timeslot_ID = ?");
$delete->bind_param("ii", $student_id, $timeslot_id);
$delete->execute();
$delete->close();

// Remove the student from the group
$delete_join = $conn->prepare("DELETE FROM Student_Join WHERE Student_ID = ? AND Group_ID = ?");
$delete_join->bind_param("ii", $student_id, $timeslot_id);
$delete_join->execute();
$delete_join->close();

send_json_success();
?> 