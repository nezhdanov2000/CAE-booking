<?php
/**
 * Автоматическая очистка старых таймслотов
 * Этот скрипт предназначен для запуска через cron job на Linux сервере
 * Удаляет таймслоты старше одной недели
 */

// Включаем логирование ошибок
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/auto_cleanup.log');
error_reporting(E_ALL);

// Подключаемся к базе данных
require_once '../common/db.php';

// Функция для логирования
function log_message($message) {
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[$timestamp] $message\n";
    echo $log_message;
    error_log($log_message, 3, __DIR__ . '/auto_cleanup.log');
}

// Функция для отправки JSON ответа
function send_json_response($success, $message, $data = null) {
    $response = [
        'success' => $success,
        'message' => $message,
        'timestamp' => date('Y-m-d H:i:s'),
        'data' => $data
    ];
    
    if (php_sapi_name() === 'cli') {
        // Если запущен из командной строки
        echo json_encode($response, JSON_PRETTY_PRINT) . "\n";
    } else {
        // Если запущен через веб
        header('Content-Type: application/json');
        echo json_encode($response);
    }
}

try {
    log_message("=== Starting automatic timeslot cleanup ===");
    
    // Вычисляем дату неделю назад
    $one_week_ago = date('Y-m-d', strtotime('-1 week'));
    log_message("Cutoff date: $one_week_ago");
    
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
    
    log_message("Found " . count($timeslot_ids) . " old timeslots to delete");
    
    if (empty($timeslot_ids)) {
        log_message("No old timeslots found to delete");
        send_json_response(true, 'No old timeslots found to delete', [
            'deleted_timeslots' => 0,
            'deleted_student_choices' => 0,
            'deleted_appointments' => 0,
            'deleted_group_joins' => 0,
            'deleted_tutor_creates' => 0,
            'cutoff_date' => $one_week_ago,
            'total_found' => 0
        ]);
        exit();
    }
    
    $total_timeslots = count($timeslot_ids);
    
    // Начинаем транзакцию
    $conn->begin_transaction();
    log_message("Started database transaction");
    
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
        log_message("Deleted {$deleted_counts['student_choices']} student choices");
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
        log_message("Deleted {$deleted_counts['student_joins']} group joins");
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
        log_message("Deleted {$deleted_counts['appointments']} appointments");
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
        log_message("Deleted {$deleted_counts['tutor_creates']} tutor creates");
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
        log_message("Deleted {$deleted_counts['timeslots']} timeslots");
    }
    
    // Шаг 7: Логируем действие в Admin_Log (используем системный ID)
    $action_details = "Auto-cleanup: Deleted {$deleted_counts['timeslots']} old timeslots (before $one_week_ago). " .
                     "Affected: {$deleted_counts['student_choices']} student choices, " .
                     "{$deleted_counts['appointments']} appointments, " .
                     "{$deleted_counts['student_joins']} group joins, " .
                     "{$deleted_counts['tutor_creates']} tutor creates.";
    
    $stmt = $conn->prepare("INSERT INTO Admin_Log (Admin_ID, Action, Details, IP_Address) VALUES (?, ?, ?, ?)");
    if (!$stmt) {
        throw new Exception("Failed to prepare admin log insert: " . $conn->error);
    }
    
    $action = "Auto Cleanup Timeslots";
    $ip_address = 'SYSTEM';
    $system_admin_id = 1; // ID системного администратора
    $stmt->bind_param('isss', $system_admin_id, $action, $action_details, $ip_address);
    $stmt->execute();
    $stmt->close();
    
    // Подтверждаем транзакцию
    $conn->commit();
    log_message("Database transaction committed successfully");
    
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
    
    log_message("=== Automatic cleanup completed successfully ===");
    send_json_response(true, "Successfully deleted {$deleted_counts['timeslots']} old timeslots", $response_data);
    
} catch (Exception $e) {
    if (isset($conn) && $conn->connect_error === false) {
        $conn->rollback();
        log_message("Database transaction rolled back due to error");
    }
    
    log_message("ERROR: " . $e->getMessage());
    log_message("Stack trace: " . $e->getTraceAsString());
    
    send_json_response(false, 'Database error: ' . $e->getMessage(), null);
}

$conn->close();
?>
