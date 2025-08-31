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
    if ($method === 'GET' && $action === 'get_users') {
        $type = $_GET['type'] ?? '';
        
        if (!in_array($type, ['students', 'tutors', 'admins'])) {
            send_json_response(['error' => 'Invalid user type'], false);
        }
        
        $users = [];
        
        if ($type === 'students') {
            $stmt = $conn->prepare('SELECT Student_ID, Name, Surname, Email FROM Student ORDER BY Name, Surname');
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $users[] = $row;
            }
            $stmt->close();
        }
        
        elseif ($type === 'tutors') {
            $stmt = $conn->prepare('
                SELECT t.Tutor_ID, t.Name, t.Surname, t.Email, 
                       GROUP_CONCAT(c.Course_name SEPARATOR ", ") as courses
                FROM Tutor t
                LEFT JOIN Tutoring tut ON t.Tutor_ID = tut.Tutor_ID
                LEFT JOIN Course c ON tut.Course_ID = c.Course_ID
                GROUP BY t.Tutor_ID
                ORDER BY t.Name, t.Surname
            ');
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $users[] = $row;
            }
            $stmt->close();
        }
        
        elseif ($type === 'admins') {
            $stmt = $conn->prepare('
                SELECT Admin_ID, Name, Surname, Email, Role, 
                       DATE_FORMAT(Last_Login, "%Y-%m-%d %H:%i") as Last_Login
                FROM Admin 
                ORDER BY Name, Surname
            ');
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $users[] = $row;
            }
            $stmt->close();
        }
        
        send_json_response(['users' => $users]);
    }
    
    elseif ($method === 'POST' && $action === 'add_user') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            send_json_response(['error' => 'Invalid input data'], false);
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
            send_json_response(['error' => 'Invalid user type'], false);
        }
        
        if (empty($firstName) || empty($lastName) || empty($email) || empty($password)) {
            send_json_response(['error' => 'All required fields must be filled'], false);
        }
        
        $conn->begin_transaction();
        
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        if ($userType === 'student') {
            $stmt = $conn->prepare('INSERT INTO Student (Email, Password, Name, Surname) VALUES (?, ?, ?, ?)');
            $stmt->bind_param('ssss', $email, $hashedPassword, $firstName, $lastName);
            $stmt->execute();
            $stmt->close();
            
        } elseif ($userType === 'tutor') {
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
                        $stmt = $conn->prepare('SELECT Course_ID FROM Course WHERE Course_name = ?');
                        $stmt->bind_param('s', $courseName);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        
                        if ($result->num_rows > 0) {
                            $course = $result->fetch_assoc();
                            $courseId = $course['Course_ID'];
                            
                            $stmt = $conn->prepare('INSERT INTO Tutoring (Tutor_ID, Course_ID) VALUES (?, ?)');
                            $stmt->bind_param('ii', $tutorId, $courseId);
                            $stmt->execute();
                            $stmt->close();
                        }
                    }
                }
            }
            
        } elseif ($userType === 'admin') {
            $stmt = $conn->prepare('INSERT INTO Admin (Email, Password, Name, Surname, Role) VALUES (?, ?, ?, ?, ?)');
            $stmt->bind_param('sssss', $email, $hashedPassword, $firstName, $lastName, $adminRole);
            $stmt->execute();
            $stmt->close();
        }
        
        $conn->commit();
        send_json_response(['message' => 'User added successfully']);
    }
    
    elseif ($method === 'POST' && $action === 'update_user') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            send_json_response(['error' => 'Invalid input data'], false);
        }
        
        $userId = $input['user-id'] ?? '';
        $userType = $input['user-type'] ?? '';
        $firstName = $input['first-name'] ?? '';
        $lastName = $input['last-name'] ?? '';
        $email = $input['email'] ?? '';
        $password = $input['password'] ?? '';
        
        if (empty($userId) || empty($firstName) || empty($lastName) || empty($email)) {
            send_json_response(['error' => 'All required fields must be filled'], false);
        }
        
        $conn->begin_transaction();
        
        if ($userType === 'student') {
            if (!empty($password)) {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare('UPDATE Student SET Name = ?, Surname = ?, Email = ?, Password = ? WHERE Student_ID = ?');
                $stmt->bind_param('ssssi', $firstName, $lastName, $email, $hashedPassword, $userId);
            } else {
                $stmt = $conn->prepare('UPDATE Student SET Name = ?, Surname = ?, Email = ? WHERE Student_ID = ?');
                $stmt->bind_param('sssi', $firstName, $lastName, $email, $userId);
            }
            $stmt->execute();
            $stmt->close();
            
        } elseif ($userType === 'tutor') {
            if (!empty($password)) {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare('UPDATE Tutor SET Name = ?, Surname = ?, Email = ?, Password = ? WHERE Tutor_ID = ?');
                $stmt->bind_param('ssssi', $firstName, $lastName, $email, $hashedPassword, $userId);
            } else {
                $stmt = $conn->prepare('UPDATE Tutor SET Name = ?, Surname = ?, Email = ? WHERE Tutor_ID = ?');
                $stmt->bind_param('sssi', $firstName, $lastName, $email, $userId);
            }
            $stmt->execute();
            $stmt->close();
            
        } elseif ($userType === 'admin') {
            if (!empty($password)) {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare('UPDATE Admin SET Name = ?, Surname = ?, Email = ?, Password = ? WHERE Admin_ID = ?');
                $stmt->bind_param('ssssi', $firstName, $lastName, $email, $hashedPassword, $userId);
            } else {
                $stmt = $conn->prepare('UPDATE Admin SET Name = ?, Surname = ?, Email = ? WHERE Admin_ID = ?');
                $stmt->bind_param('sssi', $firstName, $lastName, $email, $userId);
            }
            $stmt->execute();
            $stmt->close();
        }
        
        $conn->commit();
        send_json_response(['message' => 'User updated successfully']);
    }
    
    elseif ($method === 'POST' && $action === 'delete_user') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            send_json_response(['error' => 'Invalid input data'], false);
        }
        
        $userType = $input['type'] ?? '';
        $userId = $input['id'] ?? '';
        
        if (empty($userId)) {
            send_json_response(['error' => 'User ID is required'], false);
        }
        
        $conn->begin_transaction();
        
        if ($userType === 'student') {
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
            
        } elseif ($userType === 'tutor') {
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
            
        } elseif ($userType === 'admin') {
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
        }
        
        $conn->commit();
        send_json_response(['message' => 'User deleted successfully']);
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
