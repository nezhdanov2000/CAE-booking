<?php
require_once 'backend/common/db.php';

echo "<h1>Тест подключения к базе данных CAE</h1>";

// Проверяем подключение
if ($conn->connect_error) {
    die("Ошибка подключения: " . $conn->connect_error);
}
echo "<p style='color: green;'>✓ Подключение к базе данных успешно</p>";

// Показываем таблицы
$result = $conn->query("SHOW TABLES");
echo "<h2>Таблицы в базе данных:</h2>";
echo "<ul>";
while ($row = $result->fetch_array()) {
    echo "<li>" . $row[0] . "</li>";
}
echo "</ul>";

// Проверяем данные в основных таблицах
echo "<h2>Статистика данных:</h2>";

// Студенты
$result = $conn->query("SELECT COUNT(*) as count FROM Student");
$row = $result->fetch_assoc();
echo "<p>Студентов: " . $row['count'] . "</p>";

// Преподаватели
$result = $conn->query("SELECT COUNT(*) as count FROM Tutor");
$row = $result->fetch_assoc();
echo "<p>Преподавателей: " . $row['count'] . "</p>";

// Курсы
$result = $conn->query("SELECT COUNT(*) as count FROM Course");
$row = $result->fetch_assoc();
echo "<p>Курсов: " . $row['count'] . "</p>";

// Администраторы
$result = $conn->query("SELECT COUNT(*) as count FROM Admin");
$row = $result->fetch_assoc();
echo "<p>Администраторов: " . $row['count'] . "</p>";

// Показываем администраторов
echo "<h2>Администраторы:</h2>";
$result = $conn->query("SELECT * FROM Admin");
echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>ID</th><th>Email</th><th>Имя</th><th>Фамилия</th><th>Роль</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['Admin_ID'] . "</td>";
    echo "<td>" . $row['Email'] . "</td>";
    echo "<td>" . $row['Name'] . "</td>";
    echo "<td>" . $row['Surname'] . "</td>";
    echo "<td>" . $row['Role'] . "</td>";
    echo "</tr>";
}
echo "</table>";

$conn->close();
?>
