<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in and is a tutor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'tutor') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once '../common/db_connection.php';

try {
    $tutor_id = $_SESSION['user_id'];
    
    // Get recurring slots for the tutor
    $stmt = $pdo->prepare("
        SELECT rs.*, c.Course_name 
        FROM Recurring_Slots rs
        INNER JOIN Course c ON rs.Course_ID = c.Course_ID
        WHERE rs.Tutor_ID = ?
        ORDER BY rs.Day_Of_Week, rs.Start_Time
    ");
    
    $stmt->execute([$tutor_id]);
    $slots = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($slots);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
