<?php
require_once 'auth.php';
require_once 'db.php';

// Проверяем авторизацию
require_admin_auth();

// Получаем параметры системы
$params = [];

// Общая статистика системы
$stmt = $conn->prepare('SELECT COUNT(*) as count FROM Student');
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$params['total_students'] = $row['count'];
$stmt->close();

$stmt = $conn->prepare('SELECT COUNT(*) as count FROM Tutor');
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$params['total_tutors'] = $row['count'];
$stmt->close();

$stmt = $conn->prepare('SELECT COUNT(*) as count FROM Course');
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$params['total_courses'] = $row['count'];
$stmt->close();

// Статистика по таймслотам
$stmt = $conn->prepare('SELECT COUNT(*) as count FROM Timeslot');
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$params['total_timeslots'] = $row['count'];
$stmt->close();

$stmt = $conn->prepare('SELECT COUNT(*) as count FROM Timeslot WHERE Date >= CURDATE()');
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$params['future_timeslots'] = $row['count'];
$stmt->close();

// Статистика по записям
$stmt = $conn->prepare('SELECT COUNT(*) as count FROM Appointment');
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$params['total_appointments'] = $row['count'];
$stmt->close();

$stmt = $conn->prepare('SELECT COUNT(*) as count FROM Appointment a JOIN Timeslot t ON a.Timeslot_ID = t.Timeslot_ID WHERE DATE(t.Date) = CURDATE()');
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$params['today_appointments'] = $row['count'];
$stmt->close();

// Статистика по группам
$stmt = $conn->prepare('SELECT COUNT(DISTINCT Group_ID) as count FROM Student_Join');
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$params['total_groups'] = $row['count'];
$stmt->close();

// Последние действия администраторов
$stmt = $conn->prepare('
    SELECT al.Action, al.Details, al.Created_At, a.Name, a.Surname 
    FROM Admin_Log al 
    JOIN Admin a ON al.Admin_ID = a.Admin_ID 
    ORDER BY al.Created_At DESC 
    LIMIT 5
');
$stmt->execute();
$result = $stmt->get_result();
$recent_actions = [];
while ($row = $result->fetch_assoc()) {
    $recent_actions[] = $row;
}
$params['recent_actions'] = $recent_actions;
$stmt->close();

// Информация о системе
$params['system_info'] = [
    'php_version' => PHP_VERSION,
    'mysql_version' => $conn->server_info,
    'server_time' => date('Y-m-d H:i:s'),
    'timezone' => date_default_timezone_get()
];

send_json_success(['params' => $params]);

$conn->close();
?> 