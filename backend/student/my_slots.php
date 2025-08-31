<?php
require_once '../common/auth.php';
require_once '../common/db.php';

require_student();

$student_id = get_current_user_id();

$query = "
    SELECT t.Timeslot_ID, t.Date, t.Start_Time, t.End_Time, c.Course_name,
           CONCAT(u.Name, ' ', u.Surname) AS Tutor_Name
    FROM Timeslot t
    JOIN Student_Choice sc ON t.Timeslot_ID = sc.Timeslot_ID
    JOIN Course c ON t.Course_ID = c.Course_ID
    JOIN Tutor_Creates tc ON t.Timeslot_ID = tc.Timeslot_ID
    JOIN Tutor u ON tc.Tutor_ID = u.Tutor_ID
    WHERE sc.Student_ID = ?
    ORDER BY t.Date, t.Start_Time
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $student_id);
$stmt->execute();

$result = $stmt->get_result();
$slots = $result->fetch_all(MYSQLI_ASSOC);

header('Content-Type: application/json');
echo json_encode($slots);
?> 