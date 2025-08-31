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
    
    // Get courses that the tutor teaches
    $stmt = $pdo->prepare("
        SELECT c.Course_ID, c.Course_name 
        FROM Course c 
        INNER JOIN Tutoring t ON c.Course_ID = t.Course_ID 
        WHERE t.Tutor_ID = ?
        ORDER BY c.Course_name
    ");
    
    $stmt->execute([$tutor_id]);
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($courses);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
