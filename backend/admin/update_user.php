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

$userId = $input['user-id'] ?? '';
$userType = $input['user-type'] ?? '';
$firstName = $input['first-name'] ?? '';
$lastName = $input['last-name'] ?? '';
$email = $input['email'] ?? '';
$password = $input['password'] ?? '';

// Валидация
if (!in_array($userType, ['student', 'tutor', 'admin'])) {
    send_json_error('Invalid user type', 400);
}

if (empty($userId) || empty($firstName) || empty($lastName) || empty($email)) {
    send_json_error('All required fields must be filled', 400);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    send_json_error('Invalid email format', 400);
}

try {
    $conn->begin_transaction();
    
    if ($userType === 'student') {
        // Проверяем, не существует ли уже студент с таким email (кроме текущего)
        $stmt = $conn->prepare('SELECT Student_ID FROM Student WHERE Email = ? AND Student_ID != ?');
        $stmt->bind_param('si', $email, $userId);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            send_json_error('Student with this email already exists', 400);
        }
        $stmt->close();
        
        // Обновляем студента
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
        
        log_admin_action('Updated student', "Student ID: $userId - $firstName $lastName ($email)");
        
    } elseif ($userType === 'tutor') {
        // Проверяем, не существует ли уже преподаватель с таким email (кроме текущего)
        $stmt = $conn->prepare('SELECT Tutor_ID FROM Tutor WHERE Email = ? AND Tutor_ID != ?');
        $stmt->bind_param('si', $email, $userId);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            send_json_error('Tutor with this email already exists', 400);
        }
        $stmt->close();
        
        // Обновляем преподавателя
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
        
        log_admin_action('Updated tutor', "Tutor ID: $userId - $firstName $lastName ($email)");
        
    } elseif ($userType === 'admin') {
        // Проверяем, не существует ли уже администратор с таким email (кроме текущего)
        $stmt = $conn->prepare('SELECT Admin_ID FROM Admin WHERE Email = ? AND Admin_ID != ?');
        $stmt->bind_param('si', $email, $userId);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            send_json_error('Administrator with this email already exists', 400);
        }
        $stmt->close();
        
        // Обновляем администратора
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
        
        log_admin_action('Updated administrator', "Admin ID: $userId - $firstName $lastName ($email)");
    }
    
    $conn->commit();
    send_json_success(['message' => 'User updated successfully']);
    
} catch (Exception $e) {
    $conn->rollback();
    send_json_error('Database error: ' . $e->getMessage(), 500);
}

$conn->close();
?>
