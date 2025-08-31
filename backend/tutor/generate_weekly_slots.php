<?php
/**
 * Script to generate weekly timeslots from recurring slots
 * This should be run weekly (e.g., every Sunday) via cron job
 * Example cron: 0 0 * * 0 /usr/bin/php /path/to/generate_weekly_slots.php
 */

require_once '../common/db_connection.php';

try {
    // Get the start of next week (Monday)
    $next_monday = new DateTime('next monday');
    $week_start = $next_monday->format('Y-m-d');
    
    // Get all active recurring slots
    $stmt = $pdo->prepare("
        SELECT rs.*, c.Course_name, t.Name as Tutor_Name, t.Surname as Tutor_Surname
        FROM Recurring_Slots rs
        INNER JOIN Course c ON rs.Course_ID = c.Course_ID
        INNER JOIN Tutor t ON rs.Tutor_ID = t.Tutor_ID
        WHERE rs.Is_Active = 1
        ORDER BY rs.Day_Of_Week, rs.Start_Time
    ");
    
    $stmt->execute();
    $recurring_slots = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $created_slots = 0;
    $errors = [];
    
    foreach ($recurring_slots as $slot) {
        // Calculate the date for this slot in the next week
        $day_mapping = [
            'monday' => 1,
            'tuesday' => 2,
            'wednesday' => 3,
            'thursday' => 4,
            'friday' => 5,
            'saturday' => 6,
            'sunday' => 7
        ];
        
        $day_number = $day_mapping[$slot['Day_Of_Week']];
        $slot_date = new DateTime($week_start);
        $slot_date->modify('+' . ($day_number - 1) . ' days');
        $date = $slot_date->format('Y-m-d');
        
        // Check if this timeslot already exists or conflicts with existing slots
        $check_stmt = $pdo->prepare("
            SELECT COUNT(*) FROM Timeslot 
            WHERE Course_ID = ? AND Date = ? AND Start_Time = ? AND End_Time = ?
        ");
        $check_stmt->execute([
            $slot['Course_ID'], 
            $date, 
            $slot['Start_Time'], 
            $slot['End_Time']
        ]);
        
        // Also check for time conflicts with existing slots on the same date
        $conflict_stmt = $pdo->prepare("
            SELECT COUNT(*) FROM Timeslot 
            WHERE Date = ? 
            AND (
                (Start_Time < ? AND End_Time > ?) OR
                (Start_Time < ? AND End_Time > ?) OR
                (Start_Time >= ? AND End_Time <= ?)
            )
        ");
        $conflict_stmt->execute([
            $date,
            $slot['End_Time'], $slot['Start_Time'],
            $slot['End_Time'], $slot['End_Time'],
            $slot['Start_Time'], $slot['End_Time']
        ]);
        
        if ($check_stmt->fetchColumn() == 0 && $conflict_stmt->fetchColumn() == 0) {
            // Create the timeslot
            $pdo->beginTransaction();
            
            try {
                // Insert timeslot
                $timeslot_stmt = $pdo->prepare("
                    INSERT INTO Timeslot (Course_ID, Date, Start_Time, End_Time) 
                    VALUES (?, ?, ?, ?)
                ");
                $timeslot_stmt->execute([
                    $slot['Course_ID'], 
                    $date, 
                    $slot['Start_Time'], 
                    $slot['End_Time']
                ]);
                
                $timeslot_id = $pdo->lastInsertId();
                
                // Link timeslot to tutor
                $tutor_stmt = $pdo->prepare("
                    INSERT INTO Tutor_Creates (Tutor_ID, Timeslot_ID) 
                    VALUES (?, ?)
                ");
                $tutor_stmt->execute([$slot['Tutor_ID'], $timeslot_id]);
                
                $pdo->commit();
                $created_slots++;
                
                echo "Created slot: {$slot['Course_name']} on {$date} at {$slot['Start_Time']}-{$slot['End_Time']} with {$slot['Tutor_Name']} {$slot['Tutor_Surname']}\n";
                
            } catch (Exception $e) {
                $pdo->rollBack();
                $errors[] = "Error creating slot for {$slot['Course_name']} on {$date}: " . $e->getMessage();
            }
        } else {
            echo "Slot already exists: {$slot['Course_name']} on {$date} at {$slot['Start_Time']}-{$slot['End_Time']}\n";
        }
    }
    
    echo "\nSummary:\n";
    echo "Created {$created_slots} new timeslots for week starting {$week_start}\n";
    
    if (!empty($errors)) {
        echo "\nErrors encountered:\n";
        foreach ($errors as $error) {
            echo "- {$error}\n";
        }
    }
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
