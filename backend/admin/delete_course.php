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

$courseId = $input['course_id'] ?? '';

if (empty($courseId)) {
    send_json_error('Course ID is required');
}

try {
    $conn->begin_transaction();
    
    // Проверяем, есть ли активные записи на этот курс
    $stmt = $conn->prepare('
        SELECT COUNT(*) as appointment_count 
        FROM Appointment a
        JOIN Timeslot t ON a.Timeslot_ID = t.Timeslot_ID
        WHERE t.Course_ID = ?
    ');
    $stmt->bind_param('i', $courseId);
    $stmt->execute();
    $result = $stmt->get_result();
    $appointmentCount = $result->fetch_assoc()['appointment_count'];
    $stmt->close();
    
    if ($appointmentCount > 0) {
        $conn->rollback();
        send_json_error('Cannot delete course with active appointments. Please remove all appointments first.');
    }
    
    // Получаем информацию о курсе перед удалением
    $stmt = $conn->prepare('SELECT Course_name FROM Course WHERE Course_ID = ?');
    $stmt->bind_param('i', $courseId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $conn->rollback();
        send_json_error('Course not found');
    }
    
    $course = $result->fetch_assoc();
    $stmt->close();
    
    // Удаляем связанные записи в правильном порядке
    // 1. Удаляем записи студентов на курсы
    $stmt = $conn->prepare('DELETE FROM Studying WHERE Course_ID = ?');
    $stmt->bind_param('i', $courseId);
    $stmt->execute();
    $stmt->close();
    
    // 2. Удаляем связи преподавателей с курсами
    $stmt = $conn->prepare('DELETE FROM Tutoring WHERE Course_ID = ?');
    $stmt->bind_param('i', $courseId);
    $stmt->execute();
    $stmt->close();
    
    // 3. Удаляем таймслоты курса
    $stmt = $conn->prepare('DELETE FROM Timeslot WHERE Course_ID = ?');
    $stmt->bind_param('i', $courseId);
    $stmt->execute();
    $stmt->close();
    
    // 4. Удаляем сам курс
    $stmt = $conn->prepare('DELETE FROM Course WHERE Course_ID = ?');
    $stmt->bind_param('i', $courseId);
    $stmt->execute();
    $stmt->close();
    
    $conn->commit();
    send_json_success('Course deleted successfully');
    
} catch (Exception $e) {
    if (isset($conn) && $conn->connect_error === false) {
        $conn->rollback();
    }
    send_json_error('Database error: ' . $e->getMessage(), 500);
}

$conn->close();
?>
