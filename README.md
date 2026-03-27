# Система управления проектами

Self-hosted веб-приложение для управления задачами, проектами и командой разработки на базе Laravel 12 и MoonShine 4.

## Технологический стек

- **Backend**: Laravel 12.x
- **Admin Panel**: MoonShine 4.x
- **Database**: MySQL 8.4
- **Cache**: Redis
- **PHP**: 8.5
- **Development Environment**: Laravel Sail (Docker)

## 📖 Документация

- **[Локальная разработка](#быстрый-старт)** - используйте Laravel Sail (Docker)
- **[Production развертывание](DEPLOYMENT.md)** - полное руководство по установке на сервер
- [Требования к системе](.kiro/specs/project-management-system/requirements.md)
- [Документ проектирования](.kiro/specs/project-management-system/design.md)
- [План реализации](.kiro/specs/project-management-system/tasks.md)

## Быстрый старт (Локальная разработка)

### Требования

- Docker Desktop
- Git

⚠️ **Важно**: Для production развертывания см. [DEPLOYMENT.md](DEPLOYMENT.md)

### Установка

1. Клонируйте репозиторий:
```bash
git clone <repository-url>
cd project-management-system
```

2. Скопируйте файл окружения:
```bash
cp .env.example .env
```

3. Запустите Docker контейнеры:
```bash
./vendor/bin/sail up -d
```

4. Установите зависимости (если необходимо):
```bash
./vendor/bin/sail composer install
```

5. Сгенерируйте ключ приложения:
```bash
./vendor/bin/sail artisan key:generate
```

6. Выполните миграции:
```bash
./vendor/bin/sail artisan migrate
```

7. Заполните базу начальными данными (роли и разрешения):
```bash
./vendor/bin/sail artisan db:seed
```

Это создаст:
- 6 базовых разрешений (управление пользователями, проектами, задачами, этапами, просмотр финансов, управление базой знаний)
- 5 встроенных ролей (администратор, проект-менеджер, дизайнер, разработчик, тестировщик)
- Связи между ролями и разрешениями по умолчанию

### Доступ к приложению

- **Приложение**: http://localhost
- **Mailpit (тестирование email)**: http://localhost:8025
- **База данных**: localhost:3306 (sail/password)

## Разработка

### Основные команды

```bash
# Запуск контейнеров
./vendor/bin/sail up -d

# Остановка контейнеров
./vendor/bin/sail down

# Artisan команды
./vendor/bin/sail artisan [command]

# Composer
./vendor/bin/sail composer [command]

# NPM
./vendor/bin/sail npm [command]

# Тесты
./vendor/bin/sail test

# Доступ к MySQL
./vendor/bin/sail mysql

# Логи
./vendor/bin/sail logs -f
```

### Алиас для упрощения

Добавьте в `~/.bashrc` или `~/.zshrc`:

```bash
alias sail='./vendor/bin/sail'
```

После этого можно использовать просто `sail` вместо `./vendor/bin/sail`.

## Система ролей и разрешений

Система использует гибкую модель ролей и разрешений для контроля доступа.

### Встроенные роли

1. **Администратор** (`administrator`)
   - Полный доступ ко всем функциям системы
   - Разрешения: управление пользователями, проектами, задачами, этапами, просмотр финансов, управление базой знаний

2. **Проект-менеджер** (`project_manager`)
   - Управление проектами и задачами
   - Разрешения: управление проектами, задачами, просмотр финансов, управление базой знаний

3. **Дизайнер** (`designer`)
   - Доступ к назначенным проектам
   - Разрешения: управление базой знаний

4. **Разработчик** (`developer`)
   - Доступ к назначенным проектам
   - Разрешения: управление базой знаний

5. **Тестировщик** (`tester`)
   - Тестирование задач
   - Разрешения: управление базой знаний

### Базовые разрешения

- `manage_users` - Управление пользователями
- `manage_projects` - Управление проектами
- `manage_tasks` - Управление задачами
- `manage_stages` - Управление этапами
- `view_finances` - Просмотр финансов
- `manage_knowledge_base` - Управление базой знаний

## Production развертывание

Для развертывания на production сервере см. **[DEPLOYMENT.md](DEPLOYMENT.md)**.

Краткий обзор:
- Требования: PHP 8.1+, MySQL 8.0+, Nginx/Apache
- Установка без Docker (стандартный PHP хостинг)
- Конфигурация веб-сервера: `nginx.conf.example` и `.htaccess.example`
- Оптимизация: запустите `./optimize-production.sh`
- Настройка cron для scheduled tasks
- SSL сертификаты через Let's Encrypt

**⚠️ НЕ используйте Sail на production серверах!**

## Демо-данные

Для тестирования системы можно загрузить демо-данные:

```bash
./vendor/bin/sail artisan db:seed --class=DemoDataSeeder
```

Это создаст:
- Тестовых пользователей с разными ролями
- Несколько проектов с задачами
- Комментарии, файлы, записи времени
- Уведомления и документы

**Внимание**: Не используйте демо-данные на production!

## Scheduled Tasks

Система использует Laravel Scheduler для автоматических задач:

- **tasks:check-deadlines** - проверка дедлайнов (каждый час)
  - Отправка уведомлений о задачах с дедлайном через 24 часа
  - Отправка уведомлений о просроченных задачах

- **notifications:cleanup** - очистка старых уведомлений (ежедневно)

- **reports:generate-monthly** - генерация месячных отчетов (1-го числа каждого месяца)

Для локальной разработки scheduled tasks запускаются автоматически через Sail.
Для production см. [DEPLOYMENT.md](DEPLOYMENT.md#шаг-13-настройка-cron-для-scheduled-tasks).

## Документация

- [Подробная инструкция по Sail](SAIL_SETUP.md)
- [Требования к системе](.kiro/specs/project-management-system/requirements.md)
- [Документ проектирования](.kiro/specs/project-management-system/design.md)
- [План реализации](.kiro/specs/project-management-system/tasks.md)

## Сервисы

Проект использует следующие Docker сервисы (для разработки):

- **Laravel Application** (PHP 8.5)
- **MySQL 8.4** - реляционная база данных
- **Redis** - кэширование и очереди
- **Mailpit** - перехват и просмотр email в разработке

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
