<?php
require_once '../common/auth.php';
require_once '../common/db.php';

require_student();

$student_id = get_current_user_id();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $timeslot_id = $_POST['timeslot_id'] ?? '';

    if (!$timeslot_id) {
        echo 'Please select a slot.';
        exit();
    }

    // Check: is the student already enrolled in this slot
    $stmt = $conn->prepare('SELECT 1 FROM Student_Choice WHERE Student_ID = ? AND Timeslot_ID = ?');
    $stmt->bind_param('ii', $student_id, $timeslot_id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        echo 'You are already enrolled in this slot.';
        exit();
    }
    $stmt->close();

    // Enroll the student in the slot
    $stmt = $conn->prepare('INSERT INTO Student_Choice (Student_ID, Timeslot_ID) VALUES (?, ?)');
    $stmt->bind_param('ii', $student_id, $timeslot_id);
    $success = $stmt->execute();
    $stmt->close();

    if ($success) {
        echo 'You have successfully enrolled in the slot!';
    } else {
        echo 'Error enrolling in the slot.';
    }
    exit();
}

echo 'Invalid request method.';
?> 