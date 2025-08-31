<?php
// Включаем отображение ошибок для отладки
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Начинаем сессию
session_start();

// Проверяем авторизацию администратора
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_role'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated as admin']);
    exit();
}

// Подключаемся к базе данных
require_once '../common/db.php';

header('Content-Type: application/json');

function send_json_success($data = null, $message = 'Success') {
    echo json_encode([
        'success' => true,
        'message' => $message,
        'data' => $data
    ]);
    exit();
}

function send_json_error($message, $code = 400) {
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'error' => $message
    ]);
    exit();
}

// Проверяем метод запроса
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json_error('Method not allowed', 405);
}

try {
    // Получаем ID администратора для логирования
    $admin_id = $_SESSION['admin_id'];
    
    // Вычисляем дату неделю назад
    $one_week_ago = date('Y-m-d', strtotime('-1 week'));
    
    // Шаг 1: Получаем список ID таймслотов для удаления
    $stmt = $conn->prepare("SELECT Timeslot_ID FROM Timeslot WHERE Date < ?");
    if (!$stmt) {
        throw new Exception("Failed to prepare timeslot query: " . $conn->error);
    }
    
    $stmt->bind_param('s', $one_week_ago);
    $stmt->execute();
    $result = $stmt->get_result();
    $timeslot_ids = [];
    
    while ($row = $result->fetch_assoc()) {
        $timeslot_ids[] = $row['Timeslot_ID'];
    }
    $stmt->close();
    
    if (empty($timeslot_ids)) {
        $response_data = [
            'deleted_timeslots' => 0,
            'deleted_student_choices' => 0,
            'deleted_appointments' => 0,
            'deleted_group_joins' => 0,
            'deleted_tutor_creates' => 0,
            'cutoff_date' => $one_week_ago,
            'total_found' => 0
        ];
        send_json_success($response_data, 'No old timeslots found to delete');
    }
    
    $total_timeslots = count($timeslot_ids);
    
    // Начинаем транзакцию
    $conn->begin_transaction();
    
    $deleted_counts = [
        'student_choices' => 0,
        'student_joins' => 0,
        'appointments' => 0,
        'tutor_creates' => 0,
        'timeslots' => 0
    ];
    
    // Шаг 2: Удаляем выборы студентов
    if (!empty($timeslot_ids)) {
        $placeholders = str_repeat('?,', count($timeslot_ids) - 1) . '?';
        $stmt = $conn->prepare("DELETE FROM Student_Choice WHERE Timeslot_ID IN ($placeholders)");
        if (!$stmt) {
            throw new Exception("Failed to prepare student choice delete: " . $conn->error);
        }
        
        $stmt->bind_param(str_repeat('i', count($timeslot_ids)), ...$timeslot_ids);
        $stmt->execute();
        $deleted_counts['student_choices'] = $stmt->affected_rows;
        $stmt->close();
    }
    
    // Шаг 3: Удаляем записи студентов в группах
    if (!empty($timeslot_ids)) {
        $placeholders = str_repeat('?,', count($timeslot_ids) - 1) . '?';
        $stmt = $conn->prepare("DELETE FROM Student_Join WHERE Group_ID IN ($placeholders)");
        if (!$stmt) {
            throw new Exception("Failed to prepare student join delete: " . $conn->error);
        }
        
        $stmt->bind_param(str_repeat('i', count($timeslot_ids)), ...$timeslot_ids);
        $stmt->execute();
        $deleted_counts['student_joins'] = $stmt->affected_rows;
        $stmt->close();
    }
    
    // Шаг 4: Удаляем записи на занятия
    if (!empty($timeslot_ids)) {
        $placeholders = str_repeat('?,', count($timeslot_ids) - 1) . '?';
        $stmt = $conn->prepare("DELETE FROM Appointment WHERE Timeslot_ID IN ($placeholders)");
        if (!$stmt) {
            throw new Exception("Failed to prepare appointment delete: " . $conn->error);
        }
        
        $stmt->bind_param(str_repeat('i', count($timeslot_ids)), ...$timeslot_ids);
        $stmt->execute();
        $deleted_counts['appointments'] = $stmt->affected_rows;
        $stmt->close();
    }
    
    // Шаг 5: Удаляем связи преподавателей с таймслотами
    if (!empty($timeslot_ids)) {
        $placeholders = str_repeat('?,', count($timeslot_ids) - 1) . '?';
        $stmt = $conn->prepare("DELETE FROM Tutor_Creates WHERE Timeslot_ID IN ($placeholders)");
        if (!$stmt) {
            throw new Exception("Failed to prepare tutor creates delete: " . $conn->error);
        }
        
        $stmt->bind_param(str_repeat('i', count($timeslot_ids)), ...$timeslot_ids);
        $stmt->execute();
        $deleted_counts['tutor_creates'] = $stmt->affected_rows;
        $stmt->close();
    }
    
    // Шаг 6: Удаляем сами таймслоты
    if (!empty($timeslot_ids)) {
        $placeholders = str_repeat('?,', count($timeslot_ids) - 1) . '?';
        $stmt = $conn->prepare("DELETE FROM Timeslot WHERE Timeslot_ID IN ($placeholders)");
        if (!$stmt) {
            throw new Exception("Failed to prepare timeslot delete: " . $conn->error);
        }
        
        $stmt->bind_param(str_repeat('i', count($timeslot_ids)), ...$timeslot_ids);
        $stmt->execute();
        $deleted_counts['timeslots'] = $stmt->affected_rows;
        $stmt->close();
    }
    
    // Шаг 7: Логируем действие администратора
    $action_details = "Deleted {$deleted_counts['timeslots']} old timeslots (before $one_week_ago). " .
                     "Affected: {$deleted_counts['student_choices']} student choices, " .
                     "{$deleted_counts['appointments']} appointments, " .
                     "{$deleted_counts['student_joins']} group joins, " .
                     "{$deleted_counts['tutor_creates']} tutor creates.";
    
    $stmt = $conn->prepare("INSERT INTO Admin_Log (Admin_ID, Action, Details, IP_Address) VALUES (?, ?, ?, ?)");
    if (!$stmt) {
        throw new Exception("Failed to prepare admin log insert: " . $conn->error);
    }
    
    $action = "Delete Old Timeslots";
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $stmt->bind_param('isss', $admin_id, $action, $action_details, $ip_address);
    $stmt->execute();
    $stmt->close();
    
    // Подтверждаем транзакцию
    $conn->commit();
    
    // Формируем ответ
    $response_data = [
        'deleted_timeslots' => $deleted_counts['timeslots'],
        'deleted_student_choices' => $deleted_counts['student_choices'],
        'deleted_appointments' => $deleted_counts['appointments'],
        'deleted_group_joins' => $deleted_counts['student_joins'],
        'deleted_tutor_creates' => $deleted_counts['tutor_creates'],
        'cutoff_date' => $one_week_ago,
        'total_found' => $total_timeslots
    ];
    
    send_json_success($response_data, "Successfully deleted {$deleted_counts['timeslots']} old timeslots");
    
} catch (Exception $e) {
    if (isset($conn) && $conn->connect_error === false) {
        $conn->rollback();
    }
    
    // Логируем ошибку для отладки
    error_log("Timeslot cleanup error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    send_json_error('Database error: ' . $e->getMessage(), 500);
}

$conn->close();
?>
