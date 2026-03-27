# Laravel Sail - Настройка и использование

## Обзор

Проект использует Laravel Sail для локальной разработки. Sail предоставляет легкий интерфейс командной строки для взаимодействия с Docker-окружением Laravel.

## Установленные сервисы

- **Laravel Application** (PHP 8.5) - порт 80
- **MySQL 8.4** - порт 3306
- **Redis** - порт 6379
- **Mailpit** - порты 1025 (SMTP), 8025 (Web UI)

## Быстрый старт

### Запуск контейнеров

```bash
./vendor/bin/sail up -d
```

Флаг `-d` запускает контейнеры в фоновом режиме.

### Остановка контейнеров

```bash
./vendor/bin/sail down
```

### Проверка статуса

```bash
./vendor/bin/sail ps
```

## Основные команды

### Artisan команды

```bash
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan db:seed
./vendor/bin/sail artisan make:model Project
./vendor/bin/sail artisan tinker
```

### Composer команды

```bash
./vendor/bin/sail composer install
./vendor/bin/sail composer require package/name
./vendor/bin/sail composer update
```

### NPM команды

```bash
./vendor/bin/sail npm install
./vendor/bin/sail npm run dev
./vendor/bin/sail npm run build
```

### Запуск тестов

```bash
./vendor/bin/sail test
# или
./vendor/bin/sail artisan test
# с фильтром
./vendor/bin/sail test --filter=TestName
```

## Работа с базой данных

### Подключение к MySQL

```bash
./vendor/bin/sail mysql
```

Или используйте любой MySQL клиент с параметрами:
- Host: `localhost`
- Port: `3306`
- Database: `project_management`
- Username: `sail`
- Password: `password`

### Миграции

```bash
# Запустить миграции
./vendor/bin/sail artisan migrate

# Откатить последнюю миграцию
./vendor/bin/sail artisan migrate:rollback

# Пересоздать базу данных
./vendor/bin/sail artisan migrate:fresh

# Пересоздать базу данных с сидерами
./vendor/bin/sail artisan migrate:fresh --seed
```

### Проверка подключения к БД

```bash
./vendor/bin/sail artisan db:show
```

## Работа с Redis

### Подключение к Redis CLI

```bash
./vendor/bin/sail exec redis redis-cli
```

### Проверка Redis

```bash
./vendor/bin/sail exec redis redis-cli ping
# Должно вернуть: PONG
```

## Mailpit - Тестирование email

Mailpit перехватывает все исходящие письма в режиме разработки.

- **Web интерфейс**: http://localhost:8025
- **SMTP порт**: 1025

Все письма, отправленные приложением, будут доступны в веб-интерфейсе Mailpit.

## Логи

### Просмотр логов всех контейнеров

```bash
./vendor/bin/sail logs
```

### Просмотр логов конкретного сервиса

```bash
./vendor/bin/sail logs laravel.test
./vendor/bin/sail logs mysql
./vendor/bin/sail logs redis
```

### Следить за логами в реальном времени

```bash
./vendor/bin/sail logs -f
```

## Выполнение команд в контейнерах

### Bash в контейнере приложения

```bash
./vendor/bin/sail shell
# или
./vendor/bin/sail bash
```

### Выполнение произвольной команды

```bash
./vendor/bin/sail exec laravel.test php -v
```

## Переменные окружения

Конфигурация для Sail в `.env`:

```env
# Database
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=project_management
DB_USERNAME=sail
DB_PASSWORD=password

# Redis
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

# Mail
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
```

## Полезные алиасы

Для упрощения работы можно создать алиас в `~/.bashrc` или `~/.zshrc`:

```bash
alias sail='./vendor/bin/sail'
```

После этого можно использовать просто:

```bash
sail up -d
sail artisan migrate
sail test
```

## Устранение проблем

### Контейнеры не запускаются

```bash
# Остановить все контейнеры
./vendor/bin/sail down

# Пересобрать образы
./vendor/bin/sail build --no-cache

# Запустить снова
./vendor/bin/sail up -d
```

### Порты заняты

Если порты 80, 3306, 6379 или 8025 уже заняты, можно изменить их в `.env`:

```env
APP_PORT=8000
FORWARD_DB_PORT=33060
FORWARD_REDIS_PORT=63790
FORWARD_MAILPIT_DASHBOARD_PORT=8026
```

### Очистка volumes

```bash
# Остановить и удалить volumes
./vendor/bin/sail down -v

# Запустить снова
./vendor/bin/sail up -d

# Выполнить миграции
./vendor/bin/sail artisan migrate
```

## Проверка работоспособности

После запуска Sail выполните следующие проверки:

```bash
# 1. Проверка версии Laravel
./vendor/bin/sail artisan --version

# 2. Проверка подключения к БД
./vendor/bin/sail artisan db:show

# 3. Проверка Redis
./vendor/bin/sail exec redis redis-cli ping

# 4. Проверка приложения
curl http://localhost

# 5. Проверка Mailpit
curl http://localhost:8025
```

Все команды должны выполниться успешно.

## Дополнительная информация

- [Официальная документация Laravel Sail](https://laravel.com/docs/sail)
- [Docker Compose документация](https://docs.docker.com/compose/)

## Важно

⚠️ **Всегда используйте `./vendor/bin/sail` для выполнения команд в контексте Docker-окружения!**

Не используйте прямые команды `php`, `composer`, `npm`, `artisan` без Sail, так как они будут выполняться в вашей локальной системе, а не в Docker-контейнере.
