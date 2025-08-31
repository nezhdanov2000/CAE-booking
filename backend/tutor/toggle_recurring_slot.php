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
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['slotId']) || !isset($input['isActive'])) {
        echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
        exit;
    }
    
    $tutor_id = $_SESSION['user_id'];
    $slot_id = $input['slotId'];
    $is_active = $input['isActive'];
    
    // Verify the slot belongs to this tutor
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM Recurring_Slots 
        WHERE Recurring_ID = ? AND Tutor_ID = ?
    ");
    $stmt->execute([$slot_id, $tutor_id]);
    
    if ($stmt->fetchColumn() == 0) {
        echo json_encode(['success' => false, 'message' => 'Slot not found or access denied']);
        exit;
    }
    
    // Update the slot status
    $stmt = $pdo->prepare("
        UPDATE Recurring_Slots 
        SET Is_Active = ?, Updated_At = CURRENT_TIMESTAMP 
        WHERE Recurring_ID = ? AND Tutor_ID = ?
    ");
    
    $stmt->execute([$is_active, $slot_id, $tutor_id]);
    
    echo json_encode(['success' => true, 'message' => 'Slot status updated successfully']);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
