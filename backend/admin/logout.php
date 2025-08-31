<?php
require_once 'auth.php';

// Логируем выход
if (is_admin_authenticated()) {
    log_admin_action('logout', 'Admin logged out');
}

// Уничтожаем сессию
session_destroy();

// Отправляем ответ
send_json_success(null, 'Logged out successfully');
?> 