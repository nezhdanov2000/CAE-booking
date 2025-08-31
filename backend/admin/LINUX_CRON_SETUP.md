# Автоматическая очистка таймслотов - Настройка для Linux

## 📋 Описание

Система автоматически удаляет таймслоты старше одной недели каждое воскресенье в 02:00 через cron job.

## 🛠️ Файлы

- `auto_cleanup_timeslots.php` - Основной PHP скрипт очистки
- `auto_cleanup.log` - Лог файл (создается автоматически)

## ⚙️ Настройка Cron Job

### 1. Откройте crontab для редактирования:

```bash
crontab -e
```

### 2. Добавьте строку для еженедельного запуска:

```bash
# Автоматическая очистка таймслотов каждое воскресенье в 02:00
0 2 * * 0 cd /path/to/your/cae/backend/admin && /usr/bin/php auto_cleanup_timeslots.php >> auto_cleanup.log 2>&1
```

### 3. Альтернативные варианты расписания:

```bash
# Ежедневно в 02:00
0 2 * * * cd /path/to/your/cae/backend/admin && /usr/bin/php auto_cleanup_timeslots.php >> auto_cleanup.log 2>&1

# Каждые 6 часов
0 */6 * * * cd /path/to/your/cae/backend/admin && /usr/bin/php auto_cleanup_timeslots.php >> auto_cleanup.log 2>&1

# Каждую неделю в понедельник в 03:00
0 3 * * 1 cd /path/to/your/cae/backend/admin && /usr/bin/php auto_cleanup_timeslots.php >> auto_cleanup.log 2>&1
```

## 🔧 Тестирование

### Ручной запуск:

```bash
cd /path/to/your/cae/backend/admin
php auto_cleanup_timeslots.php
```

### Проверка логов:

```bash
tail -f auto_cleanup.log
```

### Проверка cron job:

```bash
# Посмотреть все cron jobs
crontab -l

# Проверить логи cron
sudo tail -f /var/log/cron
```

## 📊 Мониторинг

### Логи

- **PHP лог:** `auto_cleanup.log` - детальные логи выполнения
- **Cron лог:** `/var/log/cron` - логи cron daemon

### Проверка в базе данных

```sql
-- Последние автоматические очистки
SELECT * FROM Admin_Log 
WHERE Action = 'Auto Cleanup Timeslots' 
ORDER BY Timestamp DESC 
LIMIT 10;

-- Статистика по дням
SELECT 
    DATE(Timestamp) as Date,
    COUNT(*) as CleanupCount,
    SUM(CASE WHEN Details LIKE '%Successfully%' THEN 1 ELSE 0 END) as SuccessCount
FROM Admin_Log 
WHERE Action = 'Auto Cleanup Timeslots'
GROUP BY DATE(Timestamp)
ORDER BY Date DESC;
```

## ⚠️ Важные моменты

1. **Пути:** Убедитесь, что пути к PHP и файлам корректны
2. **Права доступа:** Убедитесь, что пользователь cron имеет доступ к файлам
3. **Время:** Рекомендуется запуск в нерабочее время (02:00)
4. **Резервное копирование:** Рекомендуется настроить резервное копирование базы данных
5. **Мониторинг:** Регулярно проверяйте логи на наличие ошибок

## 🔄 Изменение расписания

### Изменить на ежедневный запуск:

```bash
crontab -e
# Замените строку на:
0 2 * * * cd /path/to/your/cae/backend/admin && /usr/bin/php auto_cleanup_timeslots.php >> auto_cleanup.log 2>&1
```

### Изменить время запуска:

```bash
crontab -e
# Замените строку на:
0 3 * * 0 cd /path/to/your/cae/backend/admin && /usr/bin/php auto_cleanup_timeslots.php >> auto_cleanup.log 2>&1
```

## 🚨 Устранение неполадок

### Проблема: Cron job не запускается
**Решение:**
1. Проверьте синтаксис cron job: `crontab -l`
2. Проверьте логи cron: `sudo tail -f /var/log/cron`
3. Убедитесь, что cron daemon запущен: `sudo systemctl status cron`

### Проблема: Ошибки в логах
**Решение:**
1. Проверьте подключение к базе данных
2. Убедитесь, что таблицы существуют
3. Проверьте права доступа к файлам логов

### Проблема: Не удаляется ничего
**Решение:**
1. Проверьте дату cutoff (неделю назад)
2. Убедитесь, что есть старые таймслоты
3. Проверьте SQL запросы в логах

### Проблема: PHP не найден
**Решение:**
1. Найдите путь к PHP: `which php`
2. Обновите cron job с правильным путем
3. Проверьте, что PHP CLI установлен

## 📝 Пример полной настройки

```bash
# 1. Перейдите в директорию проекта
cd /var/www/html/cae/backend/admin

# 2. Сделайте скрипт исполняемым
chmod +x auto_cleanup_timeslots.php

# 3. Откройте crontab
crontab -e

# 4. Добавьте строку
0 2 * * 0 cd /var/www/html/cae/backend/admin && /usr/bin/php auto_cleanup_timeslots.php >> auto_cleanup.log 2>&1

# 5. Сохраните и выйдите
# (Ctrl+X, затем Y, затем Enter в nano)

# 6. Проверьте, что cron job добавлен
crontab -l

# 7. Протестируйте вручную
php auto_cleanup_timeslots.php

# 8. Проверьте логи
tail -f auto_cleanup.log
```

## 🔐 Безопасность

1. **Ограничьте доступ к логам:**
```bash
chmod 600 auto_cleanup.log
```

2. **Настройте ротацию логов:**
```bash
# Добавьте в /etc/logrotate.d/cae_cleanup
/var/www/html/cae/backend/admin/auto_cleanup.log {
    daily
    missingok
    rotate 7
    compress
    notifempty
}
```
