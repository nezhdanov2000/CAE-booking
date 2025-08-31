<?php
require_once 'backend/common/db.php';

header('Content-Type: application/json');

// Функция для отправки JSON ответа
function send_json_response($data, $success = true) {
    echo json_encode([
        'success' => $success,
        'data' => $data
    ]);
    exit();
}

// Получаем метод запроса и параметры
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    if ($method === 'GET' && $action === 'get_courses') {
        $courses = [];
        
        $stmt = $conn->prepare('
            SELECT 
                c.Course_ID,
                c.Course_name,
                c.Description,
                COALESCE(s.student_count, 0) as students,
                COALESCE(t.tutor_count, 0) as tutors,
                COALESCE(ts.timeslot_count, 0) as timeslots,
                COALESCE(a.appointment_count, 0) as appointments
            FROM Course c
            LEFT JOIN (
                SELECT Course_ID, COUNT(*) as student_count 
                FROM Studying 
                GROUP BY Course_ID
            ) s ON c.Course_ID = s.Course_ID
            LEFT JOIN (
                SELECT Course_ID, COUNT(*) as tutor_count 
                FROM Tutoring 
                GROUP BY Course_ID
            ) t ON c.Course_ID = t.Course_ID
            LEFT JOIN (
                SELECT Course_ID, COUNT(*) as timeslot_count 
                FROM Timeslot 
                GROUP BY Course_ID
            ) ts ON c.Course_ID = ts.Course_ID
            LEFT JOIN (
                SELECT t.Course_ID, COUNT(*) as appointment_count 
                FROM Appointment a
                JOIN Timeslot t ON a.Timeslot_ID = t.Timeslot_ID
                GROUP BY t.Course_ID
            ) a ON c.Course_ID = a.Course_ID
            ORDER BY c.Course_name
        ');
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $courses[] = $row;
        }
        $stmt->close();
        
        send_json_response(['courses' => $courses]);
    }
    
    elseif ($method === 'POST' && $action === 'add_course') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            send_json_response(['error' => 'Invalid input data'], false);
        }
        
        $courseName = $input['course-name'] ?? '';
        $description = $input['course-description'] ?? '';
        
        if (empty($courseName)) {
            send_json_response(['error' => 'Course name is required'], false);
        }
        
        // Проверяем, не существует ли уже курс с таким названием
        $stmt = $conn->prepare('SELECT Course_ID FROM Course WHERE Course_name = ?');
        $stmt->bind_param('s', $courseName);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            send_json_response(['error' => 'Course with this name already exists'], false);
        }
        $stmt->close();
        
        // Добавляем курс
        $stmt = $conn->prepare('INSERT INTO Course (Course_name, Description) VALUES (?, ?)');
        $stmt->bind_param('ss', $courseName, $description);
        $stmt->execute();
        $stmt->close();
        
        send_json_response(['message' => 'Course added successfully']);
    }
    
    elseif ($method === 'POST' && $action === 'update_course') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            send_json_response(['error' => 'Invalid input data'], false);
        }
        
        $courseId = $input['course-id'] ?? '';
        $courseName = $input['course-name'] ?? '';
        $description = $input['course-description'] ?? '';
        
        if (empty($courseId) || empty($courseName)) {
            send_json_response(['error' => 'Course ID and name are required'], false);
        }
        
        // Проверяем, не существует ли уже курс с таким названием (кроме текущего)
        $stmt = $conn->prepare('SELECT Course_ID FROM Course WHERE Course_name = ? AND Course_ID != ?');
        $stmt->bind_param('si', $courseName, $courseId);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            send_json_response(['error' => 'Course with this name already exists'], false);
        }
        $stmt->close();
        
        // Обновляем курс
        $stmt = $conn->prepare('UPDATE Course SET Course_name = ?, Description = ? WHERE Course_ID = ?');
        $stmt->bind_param('ssi', $courseName, $description, $courseId);
        $stmt->execute();
        $stmt->close();
        
        send_json_response(['message' => 'Course updated successfully']);
    }
    
    elseif ($method === 'POST' && $action === 'delete_course') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            send_json_response(['error' => 'Invalid input data'], false);
        }
        
        $courseId = $input['course_id'] ?? '';
        
        if (empty($courseId)) {
            send_json_response(['error' => 'Course ID is required'], false);
        }
        
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
            send_json_response(['error' => 'Cannot delete course with active appointments. Please remove all appointments first.'], false);
        }
        
        // Получаем информацию о курсе перед удалением
        $stmt = $conn->prepare('SELECT Course_name FROM Course WHERE Course_ID = ?');
        $stmt->bind_param('i', $courseId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $conn->rollback();
            send_json_response(['error' => 'Course not found'], false);
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
        send_json_response(['message' => 'Course deleted successfully']);
    }
    
    else {
        send_json_response(['error' => 'Invalid action'], false);
    }
    
} catch (Exception $e) {
    if (isset($conn) && $conn->connect_error === false) {
        $conn->rollback();
    }
    send_json_response(['error' => 'Database error: ' . $e->getMessage()], false);
}

if (isset($conn)) {
    $conn->close();
}
?>
