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

$courseName = $input['course-name'] ?? '';
$description = $input['course-description'] ?? '';

if (empty($courseName)) {
    send_json_error('Course name is required');
}

try {
    // Проверяем, не существует ли уже курс с таким названием
    $stmt = $conn->prepare('SELECT Course_ID FROM Course WHERE Course_name = ?');
    $stmt->bind_param('s', $courseName);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        send_json_error('Course with this name already exists');
    }
    $stmt->close();
    
    // Добавляем курс
    $stmt = $conn->prepare('INSERT INTO Course (Course_name, Description) VALUES (?, ?)');
    $stmt->bind_param('ss', $courseName, $description);
    $stmt->execute();
    $stmt->close();
    
    send_json_success('Course added successfully');
    
} catch (Exception $e) {
    send_json_error('Database error: ' . $e->getMessage(), 500);
}

$conn->close();
?>
