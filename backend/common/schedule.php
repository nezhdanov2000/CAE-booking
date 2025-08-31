<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Database connection
$host = 'localhost';
$dbname = 'cae_database';
$username = 'root';
$password = 'mega55555';

try {
    // First try to connect to MySQL without specifying database
    $pdo = new PDO("mysql:host=$host;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname`");
    
    // Now connect to the specific database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

// Get week parameters
$week_start = isset($_GET['week_start']) ? $_GET['week_start'] : date('Y-m-d');
$week_end = isset($_GET['week_end']) ? $_GET['week_end'] : date('Y-m-d', strtotime('+4 days'));

// Get user role
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'] ?? 'student';

try {
    // Create tables from cae_structure.sql if they don't exist
    $createTablesSQL = "
    -- Students
    CREATE TABLE IF NOT EXISTS Student (
        Student_ID INT PRIMARY KEY AUTO_INCREMENT,
        Email VARCHAR(100) UNIQUE,
        Password VARCHAR(100),
        Name VARCHAR(100),
        Surname VARCHAR(100)
    );

    -- Courses
    CREATE TABLE IF NOT EXISTS Course (
        Course_ID INT PRIMARY KEY AUTO_INCREMENT,
        Course_name VARCHAR(100)
    );

    -- Tutors
    CREATE TABLE IF NOT EXISTS Tutor (
        Tutor_ID INT PRIMARY KEY AUTO_INCREMENT,
        Email VARCHAR(100) UNIQUE,
        Password VARCHAR(100),
        Name VARCHAR(100),
        Surname VARCHAR(100),
        Student_ID_opt INT,
        FOREIGN KEY (Student_ID_opt) REFERENCES Student(Student_ID)
    );

    -- Timeslots (linked to course)
    CREATE TABLE IF NOT EXISTS Timeslot (
        Timeslot_ID INT PRIMARY KEY AUTO_INCREMENT,
        Course_ID INT,
        Date DATE,
        Start_Time TIME,
        End_Time TIME,
        FOREIGN KEY (Course_ID) REFERENCES Course(Course_ID)
    );

    -- Tutor creates timeslot
    CREATE TABLE IF NOT EXISTS Tutor_Creates (
        Tutor_ID INT,
        Timeslot_ID INT,
        PRIMARY KEY (Tutor_ID, Timeslot_ID),
        FOREIGN KEY (Tutor_ID) REFERENCES Tutor(Tutor_ID),
        FOREIGN KEY (Timeslot_ID) REFERENCES Timeslot(Timeslot_ID)
    );
    
    -- Recurring slots table
    CREATE TABLE IF NOT EXISTS Recurring_Slots (
        Recurring_ID INT PRIMARY KEY AUTO_INCREMENT,
        Tutor_ID INT NOT NULL,
        Course_ID INT NOT NULL,
        Day_Of_Week ENUM('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday') NOT NULL,
        Start_Time TIME NOT NULL,
        End_Time TIME NOT NULL,
        Is_Active BOOLEAN DEFAULT TRUE,
        Created_At TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        Updated_At TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (Tutor_ID) REFERENCES Tutor(Tutor_ID) ON DELETE CASCADE,
        FOREIGN KEY (Course_ID) REFERENCES Course(Course_ID) ON DELETE CASCADE,
        UNIQUE KEY unique_tutor_course_day_time (Tutor_ID, Course_ID, Day_Of_Week, Start_Time)
    );
    ";
    
    $pdo->exec($createTablesSQL);
    
    // Check if we have sample data, if not insert some
    $checkData = $pdo->query("SELECT COUNT(*) FROM Course");
    $count = $checkData->fetchColumn();
    
    if ($count == 0) {
        // Insert sample data
        $pdo->exec("INSERT INTO Course (Course_name) VALUES 
            ('Basic Science'),
            ('Mathematics'),
            ('English'),
            ('Physics'),
            ('Chemistry'),
            ('Biology'),
            ('Computer Science'),
            ('History'),
            ('Geography'),
            ('Literature'),
            ('Art History'),
            ('Music Theory'),
            ('Psychology'),
            ('Sociology'),
            ('Economics')
        ");
        
        $pdo->exec("INSERT INTO Tutor (Name, Surname, Email, Password) VALUES 
            ('John', 'Smith', 'john.smith@cae.edu', 'password'),
            ('Mary', 'Johnson', 'mary.johnson@cae.edu', 'password'),
            ('David', 'Brown', 'david.brown@cae.edu', 'password'),
            ('Sarah', 'Wilson', 'sarah.wilson@cae.edu', 'password'),
            ('Michael', 'Davis', 'michael.davis@cae.edu', 'password')
        ");
        
        // Insert sample timeslots for this week
        $weekStart = date('Y-m-d', strtotime('monday this week'));
        $sampleTimeslots = [
            [1, $weekStart, '09:00:00', '10:30:00'], // Monday
            [2, $weekStart, '11:00:00', '12:30:00'], // Monday
            [3, $weekStart, '14:00:00', '15:30:00'], // Monday
            [4, date('Y-m-d', strtotime('tuesday this week')), '08:30:00', '10:00:00'], // Tuesday
            [5, date('Y-m-d', strtotime('tuesday this week')), '10:15:00', '11:45:00'], // Tuesday
            [6, date('Y-m-d', strtotime('tuesday this week')), '13:00:00', '14:30:00'], // Tuesday
            [7, date('Y-m-d', strtotime('wednesday this week')), '09:00:00', '10:30:00'], // Wednesday
            [8, date('Y-m-d', strtotime('wednesday this week')), '11:00:00', '12:30:00'], // Wednesday
            [9, date('Y-m-d', strtotime('wednesday this week')), '14:00:00', '15:30:00'], // Wednesday
            [10, date('Y-m-d', strtotime('thursday this week')), '08:30:00', '10:00:00'], // Thursday
            [11, date('Y-m-d', strtotime('thursday this week')), '10:15:00', '11:45:00'], // Thursday
            [12, date('Y-m-d', strtotime('thursday this week')), '13:00:00', '14:30:00'], // Thursday
            [13, date('Y-m-d', strtotime('friday this week')), '09:00:00', '10:30:00'], // Friday
            [14, date('Y-m-d', strtotime('friday this week')), '11:00:00', '12:30:00'], // Friday
            [15, date('Y-m-d', strtotime('friday this week')), '14:00:00', '15:30:00']  // Friday
        ];
        
        $insertTimeslotSQL = "INSERT INTO Timeslot (Course_ID, Date, Start_Time, End_Time) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($insertTimeslotSQL);
        
        foreach ($sampleTimeslots as $data) {
            $stmt->execute($data);
        }
        
        // Link tutors to timeslots
        $tutorTimeslots = [
            [1, 1], [2, 2], [3, 3], [4, 4], [5, 5], // Assign tutors to timeslots
            [1, 6], [2, 7], [3, 8], [4, 9], [5, 10],
            [1, 11], [2, 12], [3, 13], [4, 14], [5, 15]
        ];
        
        $insertTutorSQL = "INSERT INTO Tutor_Creates (Tutor_ID, Timeslot_ID) VALUES (?, ?)";
        $stmt = $pdo->prepare($insertTutorSQL);
        
        foreach ($tutorTimeslots as $data) {
            $stmt->execute($data);
        }
    }
    
    // Get schedule data for the specified week using the actual database structure
    $sql = "
    SELECT 
        ts.Timeslot_ID,
        c.Course_name,
        CONCAT(t.Name, ' ', t.Surname) as tutor_name,
        ts.Date,
        TIME_FORMAT(ts.Start_Time, '%H:%i') as start_time,
        TIME_FORMAT(ts.End_Time, '%H:%i') as end_time,
        CASE 
            WHEN DAYOFWEEK(ts.Date) = 1 THEN 6  -- Sunday becomes 6 (last day of week)
            WHEN DAYOFWEEK(ts.Date) = 2 THEN 0  -- Monday becomes 0
            WHEN DAYOFWEEK(ts.Date) = 3 THEN 1  -- Tuesday becomes 1
            WHEN DAYOFWEEK(ts.Date) = 4 THEN 2  -- Wednesday becomes 2
            WHEN DAYOFWEEK(ts.Date) = 5 THEN 3  -- Thursday becomes 3
            WHEN DAYOFWEEK(ts.Date) = 6 THEN 4  -- Friday becomes 4
            WHEN DAYOFWEEK(ts.Date) = 7 THEN 5  -- Saturday becomes 5
        END as day_of_week,
        'regular' as slot_type
    FROM Timeslot ts
    JOIN Course c ON ts.Course_ID = c.Course_ID
    JOIN Tutor_Creates tc ON ts.Timeslot_ID = tc.Timeslot_ID
    JOIN Tutor t ON tc.Tutor_ID = t.Tutor_ID
    WHERE ts.Date BETWEEN ? AND ?
    
    UNION ALL
    
    -- Get recurring slots for the week
    SELECT 
        rs.Recurring_ID as Timeslot_ID,
        c.Course_name,
        CONCAT(t.Name, ' ', t.Surname) as tutor_name,
        ? as Date, -- We'll calculate the actual date for each day
        TIME_FORMAT(rs.Start_Time, '%H:%i') as start_time,
        TIME_FORMAT(rs.End_Time, '%H:%i') as end_time,
        CASE 
            WHEN rs.Day_Of_Week = 'monday' THEN 0
            WHEN rs.Day_Of_Week = 'tuesday' THEN 1
            WHEN rs.Day_Of_Week = 'wednesday' THEN 2
            WHEN rs.Day_Of_Week = 'thursday' THEN 3
            WHEN rs.Day_Of_Week = 'friday' THEN 4
            WHEN rs.Day_Of_Week = 'saturday' THEN 5
            WHEN rs.Day_Of_Week = 'sunday' THEN 6
        END as day_of_week,
        'recurring' as slot_type
    FROM Recurring_Slots rs
    JOIN Course c ON rs.Course_ID = c.Course_ID
    JOIN Tutor t ON rs.Tutor_ID = t.Tutor_ID
    WHERE rs.Is_Active = 1
    AND rs.Day_Of_Week IN ('monday', 'tuesday', 'wednesday', 'thursday', 'friday')
    
    ORDER BY day_of_week, start_time
    ";
    
    // Debug: Log the date range being searched
    error_log("Searching for schedule between: $week_start and $week_end");
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$week_start, $week_end, $week_start]);
    $schedule = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Debug: Log the results
    error_log("Found " . count($schedule) . " schedule entries");
    
    // Transform data for frontend and handle conflicts
    $transformedSchedule = [];
    $conflicts = [];
    
    // First, process regular slots
    $regularSlots = array_filter($schedule, function($slot) {
        return $slot['slot_type'] === 'regular';
    });
    
    // Then, process recurring slots and check for conflicts
    $recurringSlots = array_filter($schedule, function($slot) {
        return $slot['slot_type'] === 'recurring';
    });
    
    // Add regular slots first
    foreach ($regularSlots as $class) {
        $transformedSchedule[] = [
            'id' => $class['Timeslot_ID'],
            'day' => (int)$class['day_of_week'],
            'start_time' => $class['start_time'],
            'end_time' => $class['end_time'],
            'course' => $class['Course_name'],
            'course_name' => $class['Course_name'],
            'tutor_name' => $class['tutor_name'],
            'color' => getColorForCourse($class['Course_name']),
            'is_recurring' => false,
            'slot_type' => 'regular'
        ];
    }
    
    // Process recurring slots and check for conflicts
    foreach ($recurringSlots as $class) {
        $hasConflict = false;
        
        // Check if this recurring slot conflicts with any regular slot
        foreach ($regularSlots as $regularSlot) {
            if ($regularSlot['day_of_week'] == $class['day_of_week']) {
                // Check time overlap
                $recurringStart = strtotime($class['start_time']);
                $recurringEnd = strtotime($class['end_time']);
                $regularStart = strtotime($regularSlot['start_time']);
                $regularEnd = strtotime($regularSlot['end_time']);
                
                // Check if times overlap
                if (($recurringStart < $regularEnd) && ($recurringEnd > $regularStart)) {
                    $hasConflict = true;
                    $conflicts[] = [
                        'recurring' => $class,
                        'regular' => $regularSlot,
                        'day' => $class['day_of_week']
                    ];
                    break;
                }
            }
        }
        
        // Only add recurring slot if there's no conflict
        if (!$hasConflict) {
            $transformedSchedule[] = [
                'id' => $class['Timeslot_ID'],
                'day' => (int)$class['day_of_week'],
                'start_time' => $class['start_time'],
                'end_time' => $class['end_time'],
                'course' => $class['Course_name'],
                'course_name' => $class['Course_name'],
                'tutor_name' => $class['tutor_name'],
                'color' => '#9b59b6', // Purple for recurring slots
                'is_recurring' => true,
                'slot_type' => 'recurring'
            ];
        }
    }
    
    // Log conflicts for debugging
    if (!empty($conflicts)) {
        error_log("Found " . count($conflicts) . " conflicts between regular and recurring slots");
        foreach ($conflicts as $conflict) {
            error_log("Conflict: Recurring slot '{$conflict['recurring']['Course_name']}' conflicts with regular slot '{$conflict['regular']['Course_name']}' on day {$conflict['day']}");
        }
    }
    
    echo json_encode($transformedSchedule);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'General error: ' . $e->getMessage()]);
}

// Add error logging for debugging
error_log("Schedule.php error: " . (isset($e) ? $e->getMessage() : 'Unknown error'));

// Function to assign colors to courses
function getColorForCourse($courseCode) {
    $colors = [
        '#4a90e2', // Blue
        '#e74c3c', // Red
        '#27ae60', // Green
        '#f39c12', // Orange
        '#9b59b6', // Purple
        '#e67e22', // Dark Orange
        '#3498db', // Light Blue
        '#1abc9c', // Turquoise
        '#f1c40f', // Yellow
        '#e91e63'  // Pink
    ];
    
    $hash = crc32($courseCode);
    return $colors[$hash % count($colors)];
}
?> 