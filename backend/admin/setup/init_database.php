<?php
require_once 'backend/common/db.php';

echo "<h1>Инициализация базы данных CAE</h1>";

// Проверяем подключение
if ($conn->connect_error) {
    die("Ошибка подключения: " . $conn->connect_error);
}
echo "<p style='color: green;'>✓ Подключение к базе данных успешно</p>";

// Создаем базу данных если её нет
$conn->query("CREATE DATABASE IF NOT EXISTS cae_database");
$conn->select_db("cae_database");

// Читаем SQL файл и выполняем его
$sql_file = 'backend/cae_structure.sql';
if (file_exists($sql_file)) {
    $sql = file_get_contents($sql_file);
    
    // Разбиваем на отдельные запросы
    $queries = explode(';', $sql);
    
    foreach ($queries as $query) {
        $query = trim($query);
        if (!empty($query)) {
            if ($conn->query($query)) {
                echo "<p style='color: green;'>✓ Выполнен запрос: " . substr($query, 0, 50) . "...</p>";
            } else {
                echo "<p style='color: red;'>✗ Ошибка в запросе: " . $conn->error . "</p>";
            }
        }
    }
} else {
    echo "<p style='color: red;'>✗ Файл SQL не найден: " . $sql_file . "</p>";
}

// Добавляем тестовые данные
echo "<h2>Добавление тестовых данных...</h2>";

// Добавляем курсы
$courses = [
    ['Математика'],
    ['Физика'],
    ['Химия'],
    ['Программирование'],
    ['Английский язык']
];

foreach ($courses as $course) {
    $stmt = $conn->prepare("INSERT IGNORE INTO Course (Course_name) VALUES (?)");
    $stmt->bind_param('s', $course[0]);
    if ($stmt->execute()) {
        echo "<p>✓ Добавлен курс: " . $course[0] . "</p>";
    }
    $stmt->close();
}

// Add students
$students = [
    ['student1@test.com', 'password123', 'John', 'Smith'],
    ['student2@test.com', 'password123', 'Peter', 'Johnson'],
    ['student3@test.com', 'password123', 'Anna', 'Williams'],
    ['student4@test.com', 'password123', 'Mary', 'Brown'],
    ['student5@test.com', 'password123', 'Alex', 'Davis']
];

foreach ($students as $student) {
    $stmt = $conn->prepare("INSERT IGNORE INTO Student (Email, Password, Name, Surname) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('ssss', $student[0], $student[1], $student[2], $student[3]);
    if ($stmt->execute()) {
        echo "<p>✓ Added student: " . $student[2] . " " . $student[3] . "</p>";
    }
    $stmt->close();
}

// Add tutors
$tutors = [
    ['tutor1@test.com', 'password123', 'David', 'Teacher'],
    ['tutor2@test.com', 'password123', 'Elena', 'Professor'],
    ['tutor3@test.com', 'password123', 'Sergei', 'Instructor']
];

foreach ($tutors as $tutor) {
    $stmt = $conn->prepare("INSERT IGNORE INTO Tutor (Email, Password, Name, Surname) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('ssss', $tutor[0], $tutor[1], $tutor[2], $tutor[3]);
    if ($stmt->execute()) {
        echo "<p>✓ Added tutor: " . $tutor[2] . " " . $tutor[3] . "</p>";
    }
    $stmt->close();
}

// Add tutor-course relationships
$tutor_courses = [
    [1, 1], // David - Mathematics
    [1, 2], // David - Physics
    [2, 3], // Elena - Chemistry
    [2, 4], // Elena - Programming
    [3, 5]  // Sergei - English
];

foreach ($tutor_courses as $tc) {
    $stmt = $conn->prepare("INSERT IGNORE INTO Tutoring (Tutor_ID, Course_ID) VALUES (?, ?)");
    $stmt->bind_param('ii', $tc[0], $tc[1]);
    if ($stmt->execute()) {
        echo "<p>✓ Added tutor-course relationship: " . $tc[0] . "-" . $tc[1] . "</p>";
    }
    $stmt->close();
}

// Add timeslots
$timeslots = [
    [1, '2024-01-15', '09:00:00', '10:30:00'],
    [2, '2024-01-15', '11:00:00', '12:30:00'],
    [3, '2024-01-16', '14:00:00', '15:30:00'],
    [4, '2024-01-16', '16:00:00', '17:30:00'],
    [5, '2024-01-17', '10:00:00', '11:30:00']
];

foreach ($timeslots as $ts) {
    $stmt = $conn->prepare("INSERT IGNORE INTO Timeslot (Course_ID, Date, Start_Time, End_Time) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('isss', $ts[0], $ts[1], $ts[2], $ts[3]);
    if ($stmt->execute()) {
        echo "<p>✓ Added timeslot: " . $ts[1] . " " . $ts[2] . "-" . $ts[3] . "</p>";
    }
    $stmt->close();
}

// Add appointments
$appointments = [
    [1, 1, 1, 'Room 101'],
    [2, 2, 2, 'Room 102'],
    [3, 3, 3, 'Laboratory 201']
];

foreach ($appointments as $app) {
    $stmt = $conn->prepare("INSERT IGNORE INTO Appointment (Tutor_ID, Group_ID, Timeslot_ID, Location) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('iiis', $app[0], $app[1], $app[2], $app[3]);
    if ($stmt->execute()) {
        echo "<p>✓ Added appointment: " . $app[3] . "</p>";
    }
    $stmt->close();
}

// Add admin logs
$admin_logs = [
    ['System Login', 'Successful authorization'],
    ['View Statistics', 'Viewed general system statistics'],
    ['Data Check', 'Checked user data']
];

foreach ($admin_logs as $log) {
    $stmt = $conn->prepare("INSERT INTO Admin_Log (Admin_ID, Action, Details) VALUES (1, ?, ?)");
    $stmt->bind_param('ss', $log[0], $log[1]);
    if ($stmt->execute()) {
        echo "<p>✓ Добавлен лог: " . $log[0] . "</p>";
    }
    $stmt->close();
}

echo "<h2>Инициализация завершена!</h2>";
echo "<p><a href='test_db.php'>Проверить данные</a></p>";

$conn->close();
?>
