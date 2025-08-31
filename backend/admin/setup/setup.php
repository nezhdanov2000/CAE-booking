<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CAE System Setup</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .step { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .success { background-color: #d4edda; border-color: #c3e6cb; }
        .error { background-color: #f8d7da; border-color: #f5c6cb; }
        .info { background-color: #d1ecf1; border-color: #bee5eb; }
        .btn { padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px; display: inline-block; margin: 5px; }
        .btn:hover { background-color: #0056b3; }
    </style>
</head>
<body>
    <h1>CAE System Setup</h1>
    
    <div class="step info">
        <h2>Шаг 1: Инициализация базы данных</h2>
        <p>Нажмите кнопку ниже для создания базы данных и добавления тестовых данных:</p>
        <a href="init_database.php" class="btn">Инициализировать базу данных</a>
    </div>
    
    <div class="step info">
        <h2>Шаг 1.5: Создание администратора</h2>
        <p>Создайте администратора с простым паролем для тестирования:</p>
        <a href="create_admin.php" class="btn">Создать администратора</a>
    </div>
    
    <div class="step info">
        <h2>Шаг 2: Проверка данных</h2>
        <p>После инициализации проверьте, что данные загрузились корректно:</p>
        <a href="test_db.php" class="btn">Проверить данные</a>
    </div>
    
    <div class="step info">
        <h2>Шаг 3: Тест админ панели</h2>
        <p>Проверьте работу админ панели с реальными данными:</p>
        <a href="test_admin_data.php" class="btn">Тест API данных</a>
        <a href="frontend/admin/dashboard.html" class="btn">Открыть админ панель</a>
        <a href="frontend/admin/user_management.html" class="btn">Управление пользователями</a>
        <a href="frontend/admin/course_management.html" class="btn">Управление курсами</a>
    </div>
    
    <div class="step info">
        <h2>Информация о системе</h2>
        <p><strong>База данных:</strong> cae_database</p>
        <p><strong>Пользователь:</strong> root</p>
        <p><strong>Хост:</strong> localhost</p>
        <p><strong>Админ по умолчанию:</strong> admin@cae.com / admin123</p>
    </div>
</body>
</html>
