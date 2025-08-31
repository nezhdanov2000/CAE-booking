<?php
require_once 'auth.php';
require_once 'db.php';

// Проверяем авторизацию
require_admin_auth();

// Получаем данные текущего администратора
$admin_id = get_current_admin_id();

$stmt = $conn->prepare('SELECT Name, Surname, Role FROM Admin WHERE Admin_ID = ?');
$stmt->bind_param('i', $admin_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    send_json_success([
        'admin' => [
            'name' => $row['Name'] . ' ' . $row['Surname'],
            'role' => ucfirst(str_replace('_', ' ', $row['Role']))
        ]
    ]);
} else {
    send_json_error('Admin data not found', 404);
}

$stmt->close();
$conn->close();
?> 