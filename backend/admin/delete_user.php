<?php
require_once 'auth.php';
require_once 'db.php';

// Проверяем авторизацию
require_admin_auth();

// Получаем данные из POST запроса
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    send_json_error('Invalid input data', 400);
}

$userType = $input['type'] ?? '';
$userId = $input['id'] ?? '';

// Валидация
if (!in_array($userType, ['student', 'tutor', 'admin'])) {
    send_json_error('Invalid user type', 400);
}

if (empty($userId)) {
    send_json_error('User ID is required', 400);
}

try {
    $conn->begin_transaction();
    
    if ($userType === 'student') {
        // Получаем информацию о студенте перед удалением
        $stmt = $conn->prepare('SELECT Name, Surname, Email FROM Student WHERE Student_ID = ?');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            send_json_error('Student not found', 404);
        }
        
        $student = $result->fetch_assoc();
        $stmt->close();
        
        // Удаляем связанные записи
        $stmt = $conn->prepare('DELETE FROM Student_Choice WHERE Student_ID = ?');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->close();
        
        $stmt = $conn->prepare('DELETE FROM Student_Join WHERE Student_ID = ?');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->close();
        
        $stmt = $conn->prepare('DELETE FROM Studying WHERE Student_ID = ?');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->close();
        
        // Удаляем студента
        $stmt = $conn->prepare('DELETE FROM Student WHERE Student_ID = ?');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->close();
        
        log_admin_action('Deleted student', "Student: {$student['Name']} {$student['Surname']} ({$student['Email']})");
        
    } elseif ($userType === 'tutor') {
        // Получаем информацию о преподавателе перед удалением
        $stmt = $conn->prepare('SELECT Name, Surname, Email FROM Tutor WHERE Tutor_ID = ?');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            send_json_error('Tutor not found', 404);
        }
        
        $tutor = $result->fetch_assoc();
        $stmt->close();
        
        // Удаляем связанные записи
        $stmt = $conn->prepare('DELETE FROM Tutoring WHERE Tutor_ID = ?');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->close();
        
        $stmt = $conn->prepare('DELETE FROM Tutor_Creates WHERE Tutor_ID = ?');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->close();
        
        $stmt = $conn->prepare('DELETE FROM Appointment WHERE Tutor_ID = ?');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->close();
        
        // Удаляем преподавателя
        $stmt = $conn->prepare('DELETE FROM Tutor WHERE Tutor_ID = ?');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->close();
        
        log_admin_action('Deleted tutor', "Tutor: {$tutor['Name']} {$tutor['Surname']} ({$tutor['Email']})");
        
    } elseif ($userType === 'admin') {
        // Получаем информацию об администраторе перед удалением
        $stmt = $conn->prepare('SELECT Name, Surname, Email FROM Admin WHERE Admin_ID = ?');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            send_json_error('Administrator not found', 404);
        }
        
        $admin = $result->fetch_assoc();
        $stmt->close();
        
        // Проверяем, не пытается ли администратор удалить сам себя
        $currentAdminId = get_current_admin_id();
        if ($userId == $currentAdminId) {
            send_json_error('You cannot delete your own account', 400);
        }
        
        // Удаляем связанные записи
        $stmt = $conn->prepare('DELETE FROM Admin_Log WHERE Admin_ID = ?');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->close();
        
        // Удаляем администратора
        $stmt = $conn->prepare('DELETE FROM Admin WHERE Admin_ID = ?');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->close();
        
        log_admin_action('Deleted administrator', "Admin: {$admin['Name']} {$admin['Surname']} ({$admin['Email']})");
    }
    
    $conn->commit();
    send_json_success(['message' => 'User deleted successfully']);
    
} catch (Exception $e) {
    $conn->rollback();
    send_json_error('Database error: ' . $e->getMessage(), 500);
}

$conn->close();
?>
