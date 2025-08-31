<?php
require_once 'auth.php';
require_once 'db.php';

require_admin_auth();

header('Content-Type: application/json');

function send_json_success($data) {
    echo json_encode([
        'success' => true,
        'courses' => $data
    ]);
    exit();
}

function send_json_error($message, $code = 400) {
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'error' => $message
    ]);
    exit();
}

try {
    $courses = [];
    
    $stmt = $conn->prepare('
        SELECT 
            c.Course_ID,
            c.Course_name,
            c.Description,
            COALESCE(s.student_count, 0) as students,
            COALESCE(t.tutor_count, 0) as tutors,
            COALESCE(ts.timeslot_count, 0) as timeslots,
            COALESCE(a.appointment_count, 0) as appointments
        FROM Course c
        LEFT JOIN (
            SELECT Course_ID, COUNT(*) as student_count 
            FROM Studying 
            GROUP BY Course_ID
        ) s ON c.Course_ID = s.Course_ID
        LEFT JOIN (
            SELECT Course_ID, COUNT(*) as tutor_count 
            FROM Tutoring 
            GROUP BY Course_ID
        ) t ON c.Course_ID = t.Course_ID
        LEFT JOIN (
            SELECT Course_ID, COUNT(*) as timeslot_count 
            FROM Timeslot 
            GROUP BY Course_ID
        ) ts ON c.Course_ID = ts.Course_ID
        LEFT JOIN (
            SELECT t.Course_ID, COUNT(*) as appointment_count 
            FROM Appointment a
            JOIN Timeslot t ON a.Timeslot_ID = t.Timeslot_ID
            GROUP BY t.Course_ID
        ) a ON c.Course_ID = a.Course_ID
        ORDER BY c.Course_name
    ');
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $courses[] = $row;
    }
    $stmt->close();
    
    send_json_success($courses);
    
} catch (Exception $e) {
    send_json_error('Database error: ' . $e->getMessage(), 500);
}

$conn->close();
?>
