<?php
require_once 'backend/common/db.php';

echo "<h1>Обновление таблицы Course</h1>";

try {
    // Проверяем, существует ли поле Description
    $result = $conn->query("DESCRIBE Course");
    $hasDescription = false;
    
    while ($row = $result->fetch_assoc()) {
        if ($row['Field'] === 'Description') {
            $hasDescription = true;
            break;
        }
    }
    
    if (!$hasDescription) {
        // Добавляем поле Description
        $sql = "ALTER TABLE Course ADD COLUMN Description TEXT";
        if ($conn->query($sql)) {
            echo "<p style='color: green;'>✓ Поле Description успешно добавлено в таблицу Course</p>";
        } else {
            echo "<p style='color: red;'>✗ Ошибка при добавлении поля Description: " . $conn->error . "</p>";
        }
    } else {
        echo "<p style='color: blue;'>ℹ Поле Description уже существует в таблице Course</p>";
    }
    
    // Показываем текущую структуру таблицы
    echo "<h2>Структура таблицы Course:</h2>";
    $result = $conn->query("DESCRIBE Course");
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Ошибка: " . $e->getMessage() . "</p>";
}

$conn->close();
?>
