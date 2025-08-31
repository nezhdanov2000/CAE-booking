<?php
session_start();

/**
 * Проверяет, авторизован ли администратор
 */
function is_admin_authenticated() {
    return isset($_SESSION['admin_id']) && isset($_SESSION['admin_role']);
}

/**
 * Проверяет роль администратора
 */
function is_admin_role($role) {
    return is_admin_authenticated() && $_SESSION['admin_role'] === $role;
}

/**
 * Проверяет, является ли пользователь супер-администратором
 */
function is_super_admin() {
    return is_admin_role('super_admin');
}

/**
 * Проверяет, является ли пользователь администратором
 */
function is_admin() {
    return is_admin_role('admin') || is_admin_role('super_admin');
}

/**
 * Проверяет, является ли пользователь модератором
 */
function is_moderator() {
    return is_admin_role('moderator') || is_admin_role('admin') || is_admin_role('super_admin');
}

/**
 * Получает ID текущего администратора
 */
function get_current_admin_id() {
    return $_SESSION['admin_id'] ?? null;
}

/**
 * Получает роль текущего администратора
 */
function get_current_admin_role() {
    return $_SESSION['admin_role'] ?? null;
}

/**
 * Получает полное имя текущего администратора
 */
function get_current_admin_name() {
    return ($_SESSION['admin_name'] ?? '') . ' ' . ($_SESSION['admin_surname'] ?? '');
}

/**
 * Проверяет, является ли запрос AJAX/Fetch
 */
function is_fetch_request() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) ||
        (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
}

/**
 * Отправляет JSON ответ с ошибкой
 */
function send_json_error($message, $code = 400) {
    http_response_code($code);
    echo json_encode(['error' => $message]);
    exit();
}

/**
 * Отправляет JSON ответ с успехом
 */
function send_json_success($data = null, $message = 'Success') {
    echo json_encode([
        'success' => true,
        'message' => $message,
        'data' => $data
    ]);
    exit();
}

/**
 * Проверяет авторизацию администратора
 */
function require_admin_auth() {
    if (!is_admin_authenticated()) {
        if (is_fetch_request()) {
            send_json_error('Unauthorized', 401);
        } else {
            header('Location: login.html');
            exit();
        }
    }
}

/**
 * Проверяет роль супер-администратора
 */
function require_super_admin() {
    require_admin_auth();
    if (!is_super_admin()) {
        send_json_error('Access denied. Super Administrator role required.', 403);
    }
}

/**
 * Проверяет роль администратора
 */
function require_admin() {
    require_admin_auth();
    if (!is_admin()) {
        send_json_error('Access denied. Administrator role required.', 403);
    }
}

/**
 * Проверяет роль модератора
 */
function require_moderator() {
    require_admin_auth();
    if (!is_moderator()) {
        send_json_error('Access denied. Moderator role required.', 403);
    }
}

/**
 * Логирует действие администратора
 */
function log_admin_action($action, $details = null) {
    global $conn;
    
    if (!is_admin_authenticated()) return;
    
    $admin_id = get_current_admin_id();
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    
    $stmt = $conn->prepare('INSERT INTO Admin_Log (Admin_ID, Action, Details, IP_Address) VALUES (?, ?, ?, ?)');
    $stmt->bind_param('isss', $admin_id, $action, $details, $ip);
    $stmt->execute();
    $stmt->close();
}
?> 