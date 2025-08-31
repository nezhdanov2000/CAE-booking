<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in and is a tutor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'tutor') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../common/db_connection.php';

try {
    // Validate input
    if (!isset($_POST['course']) || !isset($_POST['dayOfWeek']) || 
        !isset($_POST['startTime']) || !isset($_POST['endTime'])) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit;
    }
    
    $tutor_id = $_SESSION['user_id'];
    $course_name = trim($_POST['course']);
    $day_of_week = $_POST['dayOfWeek'];
    $start_time = $_POST['startTime'];
    $end_time = $_POST['endTime'];
    
    // Validate day of week
    $valid_days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
    if (!in_array($day_of_week, $valid_days)) {
        echo json_encode(['success' => false, 'message' => 'Invalid day of week']);
        exit;
    }
    
    // Validate time format
    if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $start_time) || 
        !preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $end_time)) {
        echo json_encode(['success' => false, 'message' => 'Invalid time format']);
        exit;
    }
    
    // Check if end time is after start time
    if ($start_time >= $end_time) {
        echo json_encode(['success' => false, 'message' => 'End time must be after start time']);
        exit;
    }
    
    // Validate course name
    if (empty($course_name) || strlen($course_name) > 100) {
        echo json_encode(['success' => false, 'message' => 'Course name must be between 1 and 100 characters']);
        exit;
    }
    
    // Find or create the course
    $stmt = $pdo->prepare("
        SELECT Course_ID FROM Course WHERE Course_name = ?
    ");
    $stmt->execute([$course_name]);
    $course = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($course) {
        $course_id = $course['Course_ID'];
    } else {
        // Create new course
        $stmt = $pdo->prepare("
            INSERT INTO Course (Course_name) VALUES (?)
        ");
        $stmt->execute([$course_name]);
        $course_id = $pdo->lastInsertId();
    }
    
    // Check if this recurring slot already exists
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM Recurring_Slots 
        WHERE Tutor_ID = ? AND Course_ID = ? AND Day_Of_Week = ? AND Start_Time = ?
    ");
    $stmt->execute([$tutor_id, $course_id, $day_of_week, $start_time]);
    
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'This recurring slot already exists']);
        exit;
    }
    
    // Check for conflicts with existing timeslots on the same day
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM Timeslot ts
        JOIN Tutor_Creates tc ON ts.Timeslot_ID = tc.Timeslot_ID
        WHERE tc.Tutor_ID = ? 
        AND DAYOFWEEK(ts.Date) = CASE 
            WHEN ? = 'monday' THEN 2
            WHEN ? = 'tuesday' THEN 3
            WHEN ? = 'wednesday' THEN 4
            WHEN ? = 'thursday' THEN 5
            WHEN ? = 'friday' THEN 6
            WHEN ? = 'saturday' THEN 7
            WHEN ? = 'sunday' THEN 1
        END
        AND (
            (ts.Start_Time < ? AND ts.End_Time > ?) OR
            (ts.Start_Time < ? AND ts.End_Time > ?) OR
            (ts.Start_Time >= ? AND ts.End_Time <= ?)
        )
    ");
    $stmt->execute([
        $tutor_id, 
        $day_of_week, $day_of_week, $day_of_week, $day_of_week, 
        $day_of_week, $day_of_week, $day_of_week,
        $end_time, $start_time,
        $end_time, $end_time,
        $start_time, $end_time
    ]);
    
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'This recurring slot conflicts with existing timeslots on the same day']);
        exit;
    }
    
    // Check for conflicts with other recurring slots on the same day
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM Recurring_Slots 
        WHERE Tutor_ID = ? AND Day_Of_Week = ? AND Is_Active = 1
        AND (
            (Start_Time < ? AND End_Time > ?) OR
            (Start_Time < ? AND End_Time > ?) OR
            (Start_Time >= ? AND End_Time <= ?)
        )
    ");
    $stmt->execute([
        $tutor_id, $day_of_week,
        $end_time, $start_time,
        $end_time, $end_time,
        $start_time, $end_time
    ]);
    
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'This recurring slot conflicts with other recurring slots on the same day']);
        exit;
    }
    
    // Insert the recurring slot
    $stmt = $pdo->prepare("
        INSERT INTO Recurring_Slots (Tutor_ID, Course_ID, Day_Of_Week, Start_Time, End_Time) 
        VALUES (?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([$tutor_id, $course_id, $day_of_week, $start_time, $end_time]);
    
    echo json_encode(['success' => true, 'message' => 'Recurring slot created successfully']);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
