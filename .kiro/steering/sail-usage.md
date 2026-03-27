---
inclusion: always
---

# Laravel Sail Usage

## Важно

Этот проект использует Laravel Sail для локальной разработки. Все команды Laravel, Composer, Artisan, NPM и тесты должны выполняться через Sail.

## Основные команды

### Запуск контейнеров
```bash
./vendor/bin/sail up -d
```

### Остановка контейнеров
```bash
./vendor/bin/sail down
```

### Artisan команды
```bash
./vendor/bin/sail artisan [command]
```

### Composer команды
```bash
./vendor/bin/sail composer [command]
```

### NPM команды
```bash
./vendor/bin/sail npm [command]
```

### Запуск тестов
```bash
./vendor/bin/sail test
./vendor/bin/sail artisan test
```

### Миграции
```bash
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan migrate:fresh --seed
```

### Доступ к базе данных
```bash
./vendor/bin/sail mysql
# или
./vendor/bin/sail psql
```

### Логи
```bash
./vendor/bin/sail logs
```

## Правила для AI ассистента

1. **ВСЕГДА** используй `./vendor/bin/sail` перед командами artisan, composer, npm, php
2. **НИКОГДА** не используй прямые команды `php artisan`, `composer`, `npm` без Sail
3. При выполнении миграций: `./vendor/bin/sail artisan migrate`
4. При запуске тестов: `./vendor/bin/sail test` или `./vendor/bin/sail artisan test`
5. При установке пакетов: `./vendor/bin/sail composer require [package]`
6. При работе с БД используй параметры подключения из docker-compose.yml

## Переменные окружения для Sail

```env
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=project_management
DB_USERNAME=sail
DB_PASSWORD=password

REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_HOST=mailpit
MAIL_PORT=1025
```

## Порты

- Приложение: http://localhost
- Mailpit: http://localhost:8025
- MySQL: localhost:3306
- Redis: localhost:6379
