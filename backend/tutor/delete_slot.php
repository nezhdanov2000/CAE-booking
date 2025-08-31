<?php
require_once '../common/auth.php';
require_once '../common/db.php';

require_tutor();

$tutor_id = get_current_user_id();
$timeslot_id = (int)($_POST['timeslot_id'] ?? 0);

if (!$timeslot_id) {
    send_json_error('timeslot_id is not passed');
}

// Check if the tutor owns this slot
$check = $conn->prepare("SELECT 1 FROM Tutor_Creates WHERE Tutor_ID = ? AND Timeslot_ID = ?");
$check->bind_param("ii", $tutor_id, $timeslot_id);
$check->execute();
$result = $check->get_result();
if ($result->num_rows === 0) {
    send_json_error('You can only delete your own slots');
}

// Delete related records first
$conn->begin_transaction();

try {
    // Delete from Student_Choice
    $delete_choice = $conn->prepare("DELETE FROM Student_Choice WHERE Timeslot_ID = ?");
    $delete_choice->bind_param("i", $timeslot_id);
    $delete_choice->execute();
    $delete_choice->close();

    // Delete from Student_Join
    $delete_join = $conn->prepare("DELETE FROM Student_Join WHERE Group_ID = ?");
    $delete_join->bind_param("i", $timeslot_id);
    $delete_join->execute();
    $delete_join->close();

    // Delete from Appointment
    $delete_appointment = $conn->prepare("DELETE FROM Appointment WHERE Timeslot_ID = ?");
    $delete_appointment->bind_param("i", $timeslot_id);
    $delete_appointment->execute();
    $delete_appointment->close();

    // Delete from Tutor_Creates
    $delete_tutor_creates = $conn->prepare("DELETE FROM Tutor_Creates WHERE Timeslot_ID = ?");
    $delete_tutor_creates->bind_param("i", $timeslot_id);
    $delete_tutor_creates->execute();
    $delete_tutor_creates->close();

    // Finally delete the timeslot
    $delete_timeslot = $conn->prepare("DELETE FROM Timeslot WHERE Timeslot_ID = ?");
    $delete_timeslot->bind_param("i", $timeslot_id);
    $delete_timeslot->execute();
    $delete_timeslot->close();

    $conn->commit();
    send_json_success();
} catch (Exception $e) {
    $conn->rollback();
    send_json_error('Error deleting slot: ' . $e->getMessage(), 500);
}
?> 