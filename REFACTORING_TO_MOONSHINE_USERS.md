# Рефакторинг: Переход на MoonshineUser

## Выполненные изменения

### 1. Установлен пакет moonshine/permissions
```bash
./vendor/bin/sail composer require moonshine/permissions
```

### 2. Созданы миграции

#### a) `add_hourly_rate_to_moonshine_users_table.php`
- Добавляет поле `hourly_rate` в таблицу `moonshine_users`

#### b) `refactor_users_to_moonshine_users.php`
- Переименовывает все foreign keys с `user_id` на `moonshine_user_id`
- Переименовывает `author_id` и `assignee_id` на `moonshine_author_id` и `moonshine_assignee_id`
- Обновляет foreign keys для ссылки на `moonshine_users` вместо `users`

Затронутые таблицы:
- `project_user`
- `tasks`
- `time_entries`
- `estimates`
- `comments`
- `attachments`
- `audit_logs`
- `documents`
- `document_versions`

#### c) `drop_old_user_system_tables.php`
- Удаляет старые таблицы: `users`, `roles`, `permissions`, `permission_role`, `role_user`, `password_reset_tokens`
- Удаляет кастомную таблицу `notifications`
- Создает стандартную таблицу `notifications` Laravel (для использования с trait Notifiable)

### 3. Удалены старые миграции
- `0001_01_01_000000_create_users_table.php`
- `2026_03_09_161713_add_hourly_rate_to_users_table.php`
- `2026_03_09_161715_create_permissions_table.php`
- `2026_03_09_161716_create_roles_table.php`
- `2026_03_09_161717_create_permission_role_table.php`
- `2026_03_09_161718_create_role_user_table.php`
- `2026_03_09_120517_create_notifications_table.php`

### 4. Удалены старые модели
- `app/Models/User.php`
- `app/Models/Role.php`
- `app/Models/Permission.php`
- `app/Models/Notification.php`

### 5. Создана новая модель MoonshineUser
**Файл:** `app/Models/MoonshineUser.php`

Особенности:
- Наследуется от `MoonShine\Laravel\Models\MoonshineUser`
- Использует trait `HasMoonShinePermissions` из пакета moonshine/permissions
- Использует trait `Notifiable` для уведомлений Laravel
- Содержит все необходимые relationships для проекта

### 6. Обновлены модели

Все модели обновлены для использования `MoonshineUser` и новых имен полей:

- **Task**: `author_id` → `moonshine_author_id`, `assignee_id` → `moonshine_assignee_id`
- **Project**: `members()` теперь использует `moonshine_user_id`
- **TimeEntry**: `user_id` → `moonshine_user_id`
- **Comment**: `user_id` → `moonshine_user_id`
- **Attachment**: `user_id` → `moonshine_user_id`
- **Document**: `author_id` → `moonshine_author_id`
- **DocumentVersion**: `user_id` → `moonshine_user_id`
- **Estimate**: `user_id` → `moonshine_user_id`
- **AuditLog**: `user_id` → `moonshine_user_id`

### 7. Обновлены конфигурации

#### config/moonshine.php
```php
'auth' => [
    'model' => \App\Models\MoonshineUser::class,
],
```

#### config/auth.php
- Изменен default guard на `moonshine`
- Добавлен guard `moonshine`
- Изменен provider на `moonshine_users`
- Обновлены настройки password reset

## Следующие шаги

### 1. Обновить контроллеры
Необходимо обновить все контроллеры, которые используют `User`:
- `TaskController`
- `DashboardController`
- `TimeEntryController`
- `CommentController`
- `AttachmentController`
- `DocumentController`
- `ReportController`
- И другие

Заменить:
- `auth()->user()` продолжит работать (т.к. guard изменен)
- Обновить имена полей в запросах (`user_id` → `moonshine_user_id`, etc.)

### 2. Обновить сервисы
- `TaskService`
- `TimeTrackingService`
- `DocumentService`
- `NotificationService` - переписать для использования стандартных уведомлений Laravel
- `AuditService`
- `ReportService`

### 3. Обновить политики (Policies)
- `TaskPolicy`
- `ProjectPolicy`

Заменить проверки ролей на использование `HasMoonShinePermissions`

### 4. Обновить фабрики
- Удалить: `UserFactory`, `RoleFactory`, `PermissionFactory`, `NotificationFactory`
- Создать: `MoonshineUserFactory`
- Обновить все остальные фабрики для использования новых имен полей

### 5. Обновить сидеры
- Удалить: `RoleSeeder`, `PermissionSeeder`
- Обновить: `DatabaseSeeder`, `DemoDataSeeder`
- Создать пользователей через `MoonshineUser`

### 6. Обновить тесты
Все тесты нужно обновить для:
- Использования `MoonshineUser` вместо `User`
- Новых имен полей
- Новой системы прав доступа

### 7. Обновить Blade views
Проверить и обновить все view файлы, которые используют:
- `$user->roles`
- `$task->author_id` / `$task->assignee_id`
- И другие старые имена полей

### 8. Обновить MoonShine Resources
- Добавить trait `WithPermissions` в ресурсы
- Обновить поля для использования новых имен

### 9. Запустить миграции
```bash
./vendor/bin/sail artisan migrate:fresh --seed
```

### 10. Запустить тесты
```bash
./vendor/bin/sail test
```

## Преимущества нового подхода

1. **Единая система пользователей** - используется встроенная система MoonShine
2. **Упрощенная система прав** - JSON-based permissions вместо множества таблиц
3. **Стандартные уведомления Laravel** - вместо кастомной таблицы
4. **Меньше кода для поддержки** - используются проверенные решения
5. **Лучшая интеграция с MoonShine** - все возможности админ-панели доступны из коробки

## Важные замечания

- Все изменения обратимы через `down()` методы миграций
- Старые данные не будут потеряны при откате
- Необходимо обновить все места, где используется `User` модель
- Рекомендуется тщательное тестирование после применения изменений
