# Руководство по развертыванию

Это руководство описывает процесс развертывания Системы управления проектами на production сервере.

## ⚠️ Важно: Разработка vs Production

- **Локальная разработка**: используйте Laravel Sail (Docker) - см. [README.md](README.md)
- **Production сервер**: используйте стандартный PHP/веб-сервер - см. это руководство

**НЕ используйте Sail на production серверах!** Sail предназначен только для локальной разработки.

---

## Требования к production серверу

### Минимальные требования

- **ОС**: Ubuntu 20.04+ / Debian 11+ / CentOS 8+ или аналогичная
- **PHP**: 8.1 или выше
- **База данных**: MySQL 8.0+ или PostgreSQL 13+
- **Веб-сервер**: Nginx 1.18+ или Apache 2.4+
- **Composer**: 2.x
- **Node.js**: 18+ и NPM
- **Память**: минимум 2GB RAM
- **Диск**: минимум 10GB свободного места

### Необходимые расширения PHP

```bash
php-cli
php-fpm
php-mysql (или php-pgsql для PostgreSQL)
php-mbstring
php-xml
php-bcmath
php-curl
php-zip
php-gd
php-redis (опционально, для кэширования)
```

### Установка зависимостей (Ubuntu/Debian)

```bash
# Обновление системы
sudo apt update && sudo apt upgrade -y

# Установка PHP и расширений
sudo apt install -y php8.1 php8.1-fpm php8.1-mysql php8.1-mbstring \
    php8.1-xml php8.1-bcmath php8.1-curl php8.1-zip php8.1-gd php8.1-redis

# Установка Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Установка Node.js и NPM
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install -y nodejs

# Установка Nginx
sudo apt install -y nginx

# Установка MySQL
sudo apt install -y mysql-server

# Установка Redis (опционально)
sudo apt install -y redis-server
```

---

## Процесс установки на production

### Шаг 1: Подготовка сервера

```bash
# Создание директории для приложения
sudo mkdir -p /var/www/project-management
sudo chown -R $USER:$USER /var/www/project-management
```

### Шаг 2: Клонирование репозитория

```bash
cd /var/www
git clone <repository-url> project-management
cd project-management
```

### Шаг 3: Установка зависимостей

```bash
# Установка PHP зависимостей (БЕЗ Sail!)
composer install --no-dev --optimize-autoloader

# Установка и сборка frontend зависимостей
npm install
npm run build
```

### Шаг 4: Настройка окружения

```bash
# Копирование файла окружения
cp .env.example .env

# Генерация ключа приложения
php artisan key:generate
```

### Шаг 5: Настройка .env файла

Отредактируйте `.env` файл:

```env
APP_NAME="Project Management System"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# База данных
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=project_management
DB_USERNAME=your_db_user
DB_PASSWORD=your_secure_password

# Кэширование (используйте Redis для production)
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Email
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-email@domain.com
MAIL_PASSWORD=your-email-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@your-domain.com
MAIL_FROM_NAME="${APP_NAME}"

# Логирование
LOG_CHANNEL=stack
LOG_LEVEL=error
```

### Шаг 6: Настройка базы данных

```bash
# Создание базы данных
mysql -u root -p
```

```sql
CREATE DATABASE project_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'pm_user'@'localhost' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON project_management.* TO 'pm_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### Шаг 7: Запуск миграций и сидеров

```bash
# Запуск миграций (БЕЗ Sail!)
php artisan migrate --force

# Запуск сидеров для создания ролей и разрешений
php artisan db:seed --force
```

### Шаг 8: Установка MoonShine

```bash
# Установка MoonShine (БЕЗ Sail!)
php artisan moonshine:install

# Создание администратора
php artisan moonshine:user
```

### Шаг 9: Настройка прав доступа

```bash
# Установка владельца (замените www-data на вашего веб-сервер пользователя)
sudo chown -R www-data:www-data /var/www/project-management

# Установка прав
sudo chmod -R 775 /var/www/project-management/storage
sudo chmod -R 775 /var/www/project-management/bootstrap/cache
```

### Шаг 10: Оптимизация для production

```bash
# Запуск скрипта оптимизации
./optimize-production.sh

# Или вручную:
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

### Шаг 11: Настройка веб-сервера

#### Для Nginx:

```bash
# Копирование конфигурации
sudo cp nginx.conf.example /etc/nginx/sites-available/project-management

# Редактирование конфигурации (замените your-domain.com)
sudo nano /etc/nginx/sites-available/project-management

# Создание символической ссылки
sudo ln -s /etc/nginx/sites-available/project-management /etc/nginx/sites-enabled/

# Проверка конфигурации
sudo nginx -t

# Перезагрузка Nginx
sudo systemctl reload nginx
```

#### Для Apache:

```bash
# Создание конфигурации виртуального хоста
sudo nano /etc/apache2/sites-available/project-management.conf
```

Добавьте конфигурацию из `.htaccess.example`, затем:

```bash
# Включение необходимых модулей
sudo a2enmod rewrite headers deflate expires

# Включение сайта
sudo a2ensite project-management.conf

# Перезагрузка Apache
sudo systemctl reload apache2
```

### Шаг 12: Настройка SSL (Let's Encrypt)

```bash
# Установка Certbot
sudo apt install -y certbot python3-certbot-nginx

# Получение SSL сертификата (для Nginx)
sudo certbot --nginx -d your-domain.com -d www.your-domain.com

# Для Apache:
# sudo certbot --apache -d your-domain.com -d www.your-domain.com
```

### Шаг 13: Настройка Cron для scheduled tasks

```bash
# Открыть crontab
sudo crontab -e

# Добавить строку (замените путь на ваш):
* * * * * cd /var/www/project-management && php artisan schedule:run >> /dev/null 2>&1
```

Это обеспечит выполнение:
- Проверки дедлайнов задач (каждый час)
- Отправки уведомлений о приближающихся дедлайнах
- Очистки старых уведомлений (ежедневно)

### Шаг 14: Настройка Queue Worker (опционально)

Для обработки фоновых задач:

```bash
# Создание systemd сервиса
sudo nano /etc/systemd/system/project-management-worker.service
```

Добавьте:

```ini
[Unit]
Description=Project Management Queue Worker
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/project-management
ExecStart=/usr/bin/php /var/www/project-management/artisan queue:work --sleep=3 --tries=3 --max-time=3600
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
```

Затем:

```bash
# Включение и запуск сервиса
sudo systemctl enable project-management-worker
sudo systemctl start project-management-worker

# Проверка статуса
sudo systemctl status project-management-worker
```

---

## Обновление приложения

```bash
# Перейти в директорию приложения
cd /var/www/project-management

# Включить режим обслуживания
php artisan down

# Получить последние изменения
git pull origin main

# Обновить зависимости (БЕЗ Sail!)
composer install --no-dev --optimize-autoloader
npm install && npm run build

# Выполнить миграции
php artisan migrate --force

# Очистить и пересоздать кэш
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Выключить режим обслуживания
php artisan up
```

---

## Мониторинг и обслуживание

### Логи

```bash
# Логи приложения
tail -f /var/www/project-management/storage/logs/laravel.log

# Логи Nginx
tail -f /var/log/nginx/project-management-error.log

# Логи Apache
tail -f /var/log/apache2/project-management-error.log
```

### Ротация логов

Создайте `/etc/logrotate.d/project-management`:

```
/var/www/project-management/storage/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0640 www-data www-data
    sharedscripts
}
```

### Резервное копирование

```bash
#!/bin/bash
# backup.sh

BACKUP_DIR="/var/backups/project-management"
DATE=$(date +%Y%m%d_%H%M%S)

# Создание директории для бэкапов
mkdir -p $BACKUP_DIR

# Бэкап базы данных
mysqldump -u pm_user -p project_management > $BACKUP_DIR/db_$DATE.sql

# Бэкап файлов (загруженные документы и вложения)
tar -czf $BACKUP_DIR/storage_$DATE.tar.gz /var/www/project-management/storage/app

# Удаление старых бэкапов (старше 30 дней)
find $BACKUP_DIR -type f -mtime +30 -delete

echo "Backup completed: $DATE"
```

Добавьте в crontab для ежедневного бэкапа:

```bash
0 2 * * * /path/to/backup.sh >> /var/log/project-management-backup.log 2>&1
```

---

## Производительность

### Рекомендации по оптимизации

1. **OPcache**: Включите в `php.ini`
```ini
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.validate_timestamps=0
```

2. **Redis**: Используйте для кэша и сессий

3. **HTTP/2**: Включите в Nginx/Apache

4. **CDN**: Используйте для статических ресурсов

5. **Database**: Настройте индексы и оптимизируйте запросы

### Мониторинг производительности

```bash
# Проверка использования памяти
php artisan about

# Список scheduled tasks
php artisan schedule:list

# Статус очередей
php artisan queue:monitor
```

---

## Безопасность

### Чеклист безопасности

- ✅ `APP_DEBUG=false` в production
- ✅ Используйте HTTPS (SSL сертификат)
- ✅ Регулярно обновляйте зависимости
- ✅ Настройте firewall (UFW)
- ✅ Ограничьте доступ к `.env` файлу
- ✅ Используйте сильные пароли для БД
- ✅ Настройте fail2ban для защиты от брутфорса
- ✅ Регулярно создавайте резервные копии
- ✅ Мониторьте логи на подозрительную активность

### Настройка Firewall (UFW)

```bash
# Установка UFW
sudo apt install -y ufw

# Разрешить SSH
sudo ufw allow 22/tcp

# Разрешить HTTP и HTTPS
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# Включить firewall
sudo ufw enable

# Проверить статус
sudo ufw status
```

---

## Troubleshooting

### Проблема: 500 Internal Server Error

```bash
# Проверьте права доступа
sudo chown -R www-data:www-data /var/www/project-management
sudo chmod -R 775 storage bootstrap/cache

# Проверьте логи
tail -f storage/logs/laravel.log
```

### Проблема: Страница не загружается

```bash
# Проверьте конфигурацию веб-сервера
sudo nginx -t  # для Nginx
sudo apache2ctl configtest  # для Apache

# Проверьте статус сервиса
sudo systemctl status nginx
sudo systemctl status php8.1-fpm
```

### Проблема: База данных не подключается

```bash
# Проверьте подключение к MySQL
mysql -u pm_user -p project_management

# Проверьте настройки в .env
cat .env | grep DB_
```

---

## Поддержка

Для получения помощи:
- Проверьте [документацию Laravel](https://laravel.com/docs)
- Проверьте [документацию MoonShine](https://moonshine-laravel.com/docs)
- Откройте issue в репозитории проекта

---

## Важные напоминания

⚠️ **НЕ используйте команды Sail на production!**
- ❌ `./vendor/bin/sail artisan migrate`
- ✅ `php artisan migrate`

⚠️ **Всегда создавайте резервные копии перед обновлением!**

⚠️ **Используйте `APP_DEBUG=false` на production!**

⚠️ **Регулярно обновляйте зависимости для безопасности!**
