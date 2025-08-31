<?php
session_start();

/**
 * Проверяет, авторизован ли пользователь
 */
function is_authenticated() {
    return isset($_SESSION['user_id']);
}

/**
 * Проверяет, является ли пользователь студентом
 */
function is_student() {
    return is_authenticated() && $_SESSION['role'] === 'student';
}

/**
 * Проверяет, является ли пользователь преподавателем
 */
function is_tutor() {
    return is_authenticated() && $_SESSION['role'] === 'tutor';
}

/**
 * Получает ID текущего пользователя
 */
function get_current_user_id() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Получает роль текущего пользователя
 */
function get_current_user_role() {
    return $_SESSION['role'] ?? null;
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
 * Проверяет авторизацию и возвращает ошибку если не авторизован
 */
function require_auth() {
    if (!is_authenticated()) {
        send_json_error('Unauthorized', 401);
    }
}

/**
 * Проверяет роль студента и возвращает ошибку если не студент
 */
function require_student() {
    require_auth();
    if (!is_student()) {
        send_json_error('Access denied. Student role required.', 403);
    }
}

/**
 * Проверяет роль преподавателя и возвращает ошибку если не преподаватель
 */
function require_tutor() {
    require_auth();
    if (!is_tutor()) {
        send_json_error('Access denied. Tutor role required.', 403);
    }
}
?> 