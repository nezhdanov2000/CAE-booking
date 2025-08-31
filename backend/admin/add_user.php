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

$userType = $input['user-type'] ?? '';
$firstName = $input['first-name'] ?? '';
$lastName = $input['last-name'] ?? '';
$email = $input['email'] ?? '';
$password = $input['password'] ?? '';
$adminRole = $input['admin-role'] ?? 'admin';
$tutorCourses = $input['tutor-courses'] ?? '';

// Валидация
if (!in_array($userType, ['student', 'tutor', 'admin'])) {
    send_json_error('Invalid user type', 400);
}

if (empty($firstName) || empty($lastName) || empty($email) || empty($password)) {
    send_json_error('All required fields must be filled', 400);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    send_json_error('Invalid email format', 400);
}

try {
    $conn->begin_transaction();
    
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    if ($userType === 'student') {
        // Проверяем, не существует ли уже студент с таким email
        $stmt = $conn->prepare('SELECT Student_ID FROM Student WHERE Email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            send_json_error('Student with this email already exists', 400);
        }
        $stmt->close();
        
        // Добавляем студента
        $stmt = $conn->prepare('INSERT INTO Student (Email, Password, Name, Surname) VALUES (?, ?, ?, ?)');
        $stmt->bind_param('ssss', $email, $hashedPassword, $firstName, $lastName);
        $stmt->execute();
        $studentId = $conn->insert_id;
        $stmt->close();
        
        log_admin_action('Added new student', "Student: $firstName $lastName ($email)");
        
    } elseif ($userType === 'tutor') {
        // Проверяем, не существует ли уже преподаватель с таким email
        $stmt = $conn->prepare('SELECT Tutor_ID FROM Tutor WHERE Email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            send_json_error('Tutor with this email already exists', 400);
        }
        $stmt->close();
        
        // Добавляем преподавателя
        $stmt = $conn->prepare('INSERT INTO Tutor (Email, Password, Name, Surname) VALUES (?, ?, ?, ?)');
        $stmt->bind_param('ssss', $email, $hashedPassword, $firstName, $lastName);
        $stmt->execute();
        $tutorId = $conn->insert_id;
        $stmt->close();
        
        // Добавляем курсы для преподавателя
        if (!empty($tutorCourses)) {
            $courses = array_map('trim', explode(',', $tutorCourses));
            foreach ($courses as $courseName) {
                if (!empty($courseName)) {
                    // Проверяем, существует ли курс
                    $stmt = $conn->prepare('SELECT Course_ID FROM Course WHERE Course_name = ?');
                    $stmt->bind_param('s', $courseName);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows > 0) {
                        $course = $result->fetch_assoc();
                        $courseId = $course['Course_ID'];
                        
                        // Добавляем связь преподаватель-курс
                        $stmt = $conn->prepare('INSERT INTO Tutoring (Tutor_ID, Course_ID) VALUES (?, ?)');
                        $stmt->bind_param('ii', $tutorId, $courseId);
                        $stmt->execute();
                        $stmt->close();
                    }
                }
            }
        }
        
        log_admin_action('Added new tutor', "Tutor: $firstName $lastName ($email)");
        
    } elseif ($userType === 'admin') {
        // Проверяем, не существует ли уже администратор с таким email
        $stmt = $conn->prepare('SELECT Admin_ID FROM Admin WHERE Email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            send_json_error('Administrator with this email already exists', 400);
        }
        $stmt->close();
        
        // Добавляем администратора
        $stmt = $conn->prepare('INSERT INTO Admin (Email, Password, Name, Surname, Role) VALUES (?, ?, ?, ?, ?)');
        $stmt->bind_param('sssss', $email, $hashedPassword, $firstName, $lastName, $adminRole);
        $stmt->execute();
        $stmt->close();
        
        log_admin_action('Added new administrator', "Admin: $firstName $lastName ($email) - Role: $adminRole");
    }
    
    $conn->commit();
    send_json_success(['message' => 'User added successfully']);
    
} catch (Exception $e) {
    $conn->rollback();
    send_json_error('Database error: ' . $e->getMessage(), 500);
}

$conn->close();
?>
