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

try {
    // Получаем данные администратора (ID = 1)
    $admin_id = 1;
    
    $stmt = $conn->prepare('SELECT Name, Surname, Role FROM Admin WHERE Admin_ID = ?');
    $stmt->bind_param('i', $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $admin = $result->fetch_assoc();
        $admin_data = [
            'name' => $admin['Name'] . ' ' . $admin['Surname'],
            'role' => ucfirst(str_replace('_', ' ', $admin['Role']))
        ];
    } else {
        $admin_data = [
            'name' => 'System Administrator',
            'role' => 'Super Admin'
        ];
    }
    $stmt->close();
    
    // Получаем статистику
    $stats = [];
    
    // Количество студентов
    $stmt = $conn->prepare('SELECT COUNT(*) as count FROM Student');
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stats['students'] = $row['count'];
    $stmt->close();
    
    // Количество преподавателей
    $stmt = $conn->prepare('SELECT COUNT(*) as count FROM Tutor');
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stats['tutors'] = $row['count'];
    $stmt->close();
    
    // Количество курсов
    $stmt = $conn->prepare('SELECT COUNT(*) as count FROM Course');
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stats['courses'] = $row['count'];
    $stmt->close();
    
    // Количество записей на сегодня
    $stmt = $conn->prepare('SELECT COUNT(*) as count FROM Appointment a JOIN Timeslot t ON a.Timeslot_ID = t.Timeslot_ID WHERE DATE(t.Date) = CURDATE()');
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stats['appointments'] = $row['count'];
    $stmt->close();
    
    // Получаем системные параметры
    $params = [];
    
    // Общая статистика
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
    
    $stmt = $conn->prepare('SELECT COUNT(DISTINCT Group_ID) as count FROM Student_Join');
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $params['total_groups'] = $row['count'];
    $stmt->close();
    
    // Последние действия
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
    
    // Формируем ответ
    $response = [
        'admin' => $admin_data,
        'stats' => $stats,
        'params' => $params
    ];
    
    send_json_response($response);
    
} catch (Exception $e) {
    send_json_response(['error' => $e->getMessage()], false);
}

$conn->close();
?>
