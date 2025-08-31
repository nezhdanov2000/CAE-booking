<?php
require_once 'auth.php';
require_once 'db.php';

// Проверяем авторизацию
require_admin_auth();

// Получаем тип пользователей
$type = $_GET['type'] ?? '';

if (!in_array($type, ['students', 'tutors', 'admins'])) {
    send_json_error('Invalid user type', 400);
}

try {
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
    
    send_json_success(['users' => $users]);
    
} catch (Exception $e) {
    send_json_error('Database error: ' . $e->getMessage(), 500);
}

$conn->close();
?>
