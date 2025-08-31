<?php
require_once 'auth.php';
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        send_json_error('Please fill in all fields.', 400);
    }

    // Проверяем учетную запись администратора
    $stmt = $conn->prepare('SELECT Admin_ID, Password, Name, Surname, Role, Is_Active FROM Admin WHERE Email = ?');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows === 1) {
        $stmt->bind_result($id, $hash, $name, $surname, $role, $is_active);
        $stmt->fetch();
        
        // Проверяем активность аккаунта
        if (!$is_active) {
            log_admin_action('failed_login', 'Account disabled');
            send_json_error('Account is disabled.', 403);
        }
        
        if (password_verify($password, $hash)) {
            // Успешный вход
            $_SESSION['admin_id'] = $id;
            $_SESSION['admin_role'] = $role;
            $_SESSION['admin_name'] = $name;
            $_SESSION['admin_surname'] = $surname;
            
            // Обновляем время последнего входа
            $update_stmt = $conn->prepare('UPDATE Admin SET Last_Login = CURRENT_TIMESTAMP WHERE Admin_ID = ?');
            $update_stmt->bind_param('i', $id);
            $update_stmt->execute();
            $update_stmt->close();
            
            // Логируем успешный вход
            log_admin_action('login_success', 'Successful login');
            
            $stmt->close();
            send_json_success(['redirect' => '/CAE/frontend/admin/dashboard.html'], 'Login successful');
        } else {
            log_admin_action('failed_login', 'Invalid password');
            send_json_error('Invalid email or password.', 401);
        }
    } else {
        log_admin_action('failed_login', 'User not found');
        send_json_error('Invalid email or password.', 401);
    }
    
    $stmt->close();
}

$conn->close();
?> 