<?php
require_once 'auth.php';
require_once 'db.php';

require_admin_auth();

header('Content-Type: application/json');

function send_json_success($data) {
    echo json_encode([
        'success' => true,
        'message' => $data
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

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    send_json_error('Invalid input data');
}

$courseId = $input['course-id'] ?? '';
$courseName = $input['course-name'] ?? '';
$description = $input['course-description'] ?? '';

if (empty($courseId) || empty($courseName)) {
    send_json_error('Course ID and name are required');
}

try {
    // Проверяем, не существует ли уже курс с таким названием (кроме текущего)
    $stmt = $conn->prepare('SELECT Course_ID FROM Course WHERE Course_name = ? AND Course_ID != ?');
    $stmt->bind_param('si', $courseName, $courseId);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        send_json_error('Course with this name already exists');
    }
    $stmt->close();
    
    // Обновляем курс
    $stmt = $conn->prepare('UPDATE Course SET Course_name = ?, Description = ? WHERE Course_ID = ?');
    $stmt->bind_param('ssi', $courseName, $description, $courseId);
    $stmt->execute();
    $stmt->close();
    
    send_json_success('Course updated successfully');
    
} catch (Exception $e) {
    send_json_error('Database error: ' . $e->getMessage(), 500);
}

$conn->close();
?>
