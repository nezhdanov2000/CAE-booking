<?php
require_once '../common/auth.php';
require_once '../common/db.php';

require_tutor();

$tutor_id = get_current_user_id();

$course_name = trim($_POST['course_name'] ?? '');
$date = $_POST['date'] ?? '';
$start_time = $_POST['start_time'] ?? '';
$end_time = $_POST['end_time'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = $_POST['date'] ?? '';
    $start_time = $_POST['start_time'] ?? '';
    $end_time = $_POST['end_time'] ?? '';
    $course_name = trim($_POST['course_name'] ?? '');

    if (!$date || !$start_time || !$end_time || !$course_name) {
        if (is_fetch_request()) {
            send_json_error('Please fill in all fields.', 400);
        } else {
            echo 'Please fill in all fields.';
        }
        exit();
    }

    // Check for time slot conflicts - check all slots on the same date
    $stmt = $conn->prepare("
        SELECT t.Start_Time, t.End_Time, c.Course_name, CONCAT(tut.Name, ' ', tut.Surname) as Tutor_Name
        FROM Timeslot t 
        JOIN Tutor_Creates tc ON t.Timeslot_ID = tc.Timeslot_ID 
        JOIN Tutor tut ON tc.Tutor_ID = tut.Tutor_ID
        JOIN Course c ON t.Course_ID = c.Course_ID
        WHERE t.Date = ?
        ORDER BY t.Start_Time
    ");
    $stmt->bind_param("s", $date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $conflicts = false;
    $conflict_details = [];
    
    while ($row = $result->fetch_assoc()) {
        $existing_start = $row['Start_Time'];
        $existing_end = $row['End_Time'];
        $course_name = $row['Course_name'];
        $tutor_name = $row['Tutor_Name'];
        
        // Check if new slot overlaps with existing slot
        // Overlap occurs when: new_start < existing_end AND new_end > existing_start
        if ($start_time < $existing_end && $end_time > $existing_start) {
            $conflicts = true;
            $conflict_details[] = "$course_name with $tutor_name ($existing_start - $existing_end)";
        }
    }
    $stmt->close();
    
    // Check for conflicts with recurring slots on the same day of week
    $day_of_week = date('l', strtotime($date)); // Get day name (Monday, Tuesday, etc.)
    $day_mapping = [
        'Monday' => 'monday',
        'Tuesday' => 'tuesday', 
        'Wednesday' => 'wednesday',
        'Thursday' => 'thursday',
        'Friday' => 'friday',
        'Saturday' => 'saturday',
        'Sunday' => 'sunday'
    ];
    
    if (isset($day_mapping[$day_of_week])) {
        $recurring_day = $day_mapping[$day_of_week];
        
        $stmt = $conn->prepare("
            SELECT rs.Start_Time, rs.End_Time, c.Course_name, CONCAT(t.Name, ' ', t.Surname) as Tutor_Name
            FROM Recurring_Slots rs
            JOIN Course c ON rs.Course_ID = c.Course_ID
            JOIN Tutor t ON rs.Tutor_ID = t.Tutor_ID
            WHERE rs.Day_Of_Week = ? AND rs.Is_Active = 1
            ORDER BY rs.Start_Time
        ");
        $stmt->bind_param("s", $recurring_day);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $existing_start = $row['Start_Time'];
            $existing_end = $row['End_Time'];
            $course_name = $row['Course_name'];
            $tutor_name = $row['Tutor_Name'];
            
            // Check if new slot overlaps with recurring slot
            if ($start_time < $existing_end && $end_time > $existing_start) {
                $conflicts = true;
                $conflict_details[] = "Recurring $course_name with $tutor_name ($existing_start - $existing_end)";
            }
        }
        $stmt->close();
    }
    
    if ($conflicts) {
        $conflict_message = 'Time slot conflicts with existing slots: ' . implode(', ', $conflict_details);
        if (is_fetch_request()) {
            send_json_error($conflict_message, 400);
        } else {
            echo $conflict_message;
        }
        exit();
    }

    // 1. Check if the course exists
    $stmt = $conn->prepare("SELECT Course_ID FROM Course WHERE Course_name = ?");
    $stmt->bind_param("s", $course_name);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $course_id = $row['Course_ID'];
    } else {
        // 2. Add the course
        $insert_course = $conn->prepare("INSERT INTO Course (Course_name) VALUES (?)");
        $insert_course->bind_param("s", $course_name);
        $insert_course->execute();
        $course_id = $conn->insert_id;
        $insert_course->close();
    }
    $stmt->close();

    // 3. Add the timeslot
    $stmt = $conn->prepare('INSERT INTO Timeslot (Date, Start_Time, End_Time, Course_ID) VALUES (?, ?, ?, ?)');
    $stmt->bind_param('sssi', $date, $start_time, $end_time, $course_id);
    $success = $stmt->execute();
    $timeslot_id = $conn->insert_id;
    $stmt->close();

    if ($success) {
        // 4. Link the slot with the tutor
        $stmt2 = $conn->prepare('INSERT INTO Tutor_Creates (Tutor_ID, Timeslot_ID) VALUES (?, ?)');
        $stmt2->bind_param('ii', $tutor_id, $timeslot_id);
        $success2 = $stmt2->execute();
        $stmt2->close();
        if ($success2) {
            if (is_fetch_request()) {
                send_json_success(null, 'Timeslot successfully created!');
            } else {
                echo 'Timeslot successfully created!';
            }
        } else {
            if (is_fetch_request()) {
                send_json_error('Error linking the tutor.', 500);
            } else {
                echo 'Error linking the tutor.';
            }
        }
    } else {
        if (is_fetch_request()) {
            send_json_error('Error creating the timeslot.', 500);
        } else {
            echo 'Error creating the timeslot.';
        }
    }
    exit();
}

$conn->close();
?> 