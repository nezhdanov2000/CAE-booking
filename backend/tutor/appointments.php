<?php
require_once '../common/auth.php';
require_once '../common/db.php';

require_tutor();

$tutor_id = get_current_user_id();

$query = "
    SELECT 
        t.Timeslot_ID,
        t.Date,
        t.Start_Time,
        t.End_Time,
        COUNT(sc.Student_ID) AS Student_Count
    FROM Timeslot t
    JOIN Tutor_Creates tc ON t.Timeslot_ID = tc.Timeslot_ID
    JOIN Student_Choice sc ON t.Timeslot_ID = sc.Timeslot_ID
    WHERE tc.Tutor_ID = ?
    GROUP BY t.Timeslot_ID, t.Date, t.Start_Time, t.End_Time
    ORDER BY t.Date, t.Start_Time
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $tutor_id);
$stmt->execute();

$result = $stmt->get_result();
$slots = $result->fetch_all(MYSQLI_ASSOC);

header('Content-Type: application/json');
echo json_encode($slots);
?> 