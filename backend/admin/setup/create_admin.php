<?php
require_once 'backend/common/db.php';

echo "<h1>Создание администратора для тестирования</h1>";

// Проверяем подключение
if ($conn->connect_error) {
    die("Ошибка подключения: " . $conn->connect_error);
}
echo "<p style='color: green;'>✓ Подключение к базе данных успешно</p>";

// Create simple password for testing
$email = 'admin@cae.com';
$password = 'admin123'; // Simple password for testing
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
$name = 'System';
$surname = 'Administrator';
$role = 'super_admin';

// Проверяем, существует ли уже администратор
$stmt = $conn->prepare('SELECT Admin_ID FROM Admin WHERE Email = ?');
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Обновляем существующего администратора
    $stmt = $conn->prepare('UPDATE Admin SET Password = ?, Name = ?, Surname = ?, Role = ? WHERE Email = ?');
    $stmt->bind_param('sssss', $hashed_password, $name, $surname, $role, $email);
    if ($stmt->execute()) {
        echo "<p style='color: green;'>✓ Администратор обновлен</p>";
    } else {
        echo "<p style='color: red;'>✗ Ошибка обновления: " . $stmt->error . "</p>";
    }
} else {
    // Создаем нового администратора
    $stmt = $conn->prepare('INSERT INTO Admin (Email, Password, Name, Surname, Role) VALUES (?, ?, ?, ?, ?)');
    $stmt->bind_param('sssss', $email, $hashed_password, $name, $surname, $role);
    if ($stmt->execute()) {
        echo "<p style='color: green;'>✓ Администратор создан</p>";
    } else {
        echo "<p style='color: red;'>✗ Ошибка создания: " . $stmt->error . "</p>";
    }
}
$stmt->close();

echo "<h2>Данные для входа:</h2>";
echo "<p><strong>Email:</strong> " . $email . "</p>";
echo "<p><strong>Пароль:</strong> " . $password . "</p>";
echo "<p><strong>Роль:</strong> " . $role . "</p>";

echo "<h2>Ссылки:</h2>";
echo "<p><a href='frontend/admin/login.html'>Страница входа</a></p>";
echo "<p><a href='frontend/admin/dashboard.html'>Админ панель</a></p>";

$conn->close();
?>
