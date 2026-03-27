# Прогресс рефакторинга на MoonshineUser

## ✅ Выполнено

### 1. Установка и настройка
- ✅ Установлен пакет `moonshine/permissions`
- ✅ Созданы миграции для рефакторинга БД
- ✅ Удалены старые миграции (users, roles, permissions, notifications)
- ✅ Обновлены конфигурации (moonshine.php, auth.php)

### 2. Модели
- ✅ Удалены старые модели: User, Role, Permission, Notification
- ✅ Создана модель MoonshineUser с trait HasMoonShinePermissions
- ✅ Обновлены все модели для использования MoonshineUser:
  - Task
  - Project
  - TimeEntry
  - Comment
  - Attachment
  - Document
  - DocumentVersion
  - Estimate
  - AuditLog

### 3. Сервисы
- ✅ NotificationService - переписан для использования Laravel Notifications
- ✅ Созданы Notification классы:
  - TaskAssignedNotification
  - TaskStatusChangedNotification
  - CommentAddedNotification
  - DeadlineApproachingNotification
- ✅ AuditService - обновлен для moonshine_user_id
- ✅ TaskService - обновлен для MoonshineUser
- ✅ TimeTrackingService - обновлен для MoonshineUser
- ✅ DocumentService - обновлен для MoonshineUser

### 4. Политики (Policies)
- ✅ TaskPolicy - обновлена для MoonshineUser
- ✅ ProjectPolicy - обновлена для MoonshineUser
- ⚠️ TODO: Добавить проверки прав через HasMoonShinePermissions

## 🔄 В процессе / Следующие шаги

### 5. Контроллеры
Необходимо обновить все контроллеры:
- [ ] TaskController
- [ ] DashboardController
- [ ] TimeEntryController
- [ ] CommentController
- [ ] AttachmentController
- [ ] DocumentController
- [ ] ReportController
- [ ] CalendarController
- [ ] KanbanController
- [ ] NotificationController
- [ ] ChecklistController
- [ ] EstimateController
- [ ] BugReportController

**Что нужно изменить:**
- Заменить `User` на `MoonshineUser`
- Обновить имена полей (`user_id` → `moonshine_user_id`, etc.)
- Обновить валидацию в Request классах

### 6. Request классы
- [ ] StoreTaskRequest - обновить поля
- [ ] UpdateTaskRequest - обновить поля
- [ ] StoreCommentRequest - обновить поля
- [ ] StoreAttachmentRequest - обновить поля
- [ ] StoreDocumentRequest - обновить поля
- [ ] UpdateDocumentRequest - обновить поля
- [ ] ChangeTaskStatusRequest - обновить поля

### 7. Observers
- [ ] TaskObserver - обновить для новых имен полей

### 8. Events & Listeners
- [ ] TaskAssigned - обновить
- [ ] TaskStatusChanged - обновить
- [ ] CommentAdded - обновить
- [ ] DeadlineApproaching - обновить
- [ ] SendTaskAssignedNotification - обновить
- [ ] SendTaskStatusChangedNotification - обновить
- [ ] SendCommentAddedNotification - обновить
- [ ] SendDeadlineApproachingNotification - обновить

### 9. Фабрики
- [ ] Удалить: UserFactory, RoleFactory, PermissionFactory, NotificationFactory
- [ ] Создать: MoonshineUserFactory
- [ ] Обновить все остальные фабрики для новых имен полей:
  - TaskFactory
  - TimeEntryFactory
  - CommentFactory
  - AttachmentFactory
  - DocumentFactory
  - DocumentVersionFactory
  - EstimateFactory
  - AuditLogFactory

### 10. Сидеры
- [ ] Удалить: RoleSeeder, PermissionSeeder
- [ ] Обновить: DatabaseSeeder, DemoDataSeeder
- [ ] Создать пользователей через MoonshineUser
- [ ] Настроить permissions через moonshine/permissions

### 11. Blade Views
Проверить и обновить все view файлы:
- [ ] tasks/* - обновить поля author/assignee
- [ ] dashboard/* - обновить отображение пользователей
- [ ] calendar/* - обновить
- [ ] kanban/* - обновить
- [ ] documents/* - обновить author
- [ ] reports/* - обновить
- [ ] components/notification-dropdown.blade.php - обновить

### 12. MoonShine Resources
- [ ] TaskResource - обновить поля
- [ ] ProjectResource - обновить поля
- [ ] DocumentResource - обновить поля
- [ ] Добавить trait WithPermissions в ресурсы

### 13. Тесты
Обновить все тесты:
- [ ] Feature/TaskControllerTest
- [ ] Feature/TimeEntryControllerTest
- [ ] Feature/CommentControllerTest
- [ ] Feature/DocumentControllerTest
- [ ] Feature/NotificationServiceTest
- [ ] Feature/TaskServiceTest
- [ ] Feature/TimeTrackingServiceTest
- [ ] Feature/AuditServiceTest
- [ ] Feature/PolicyTest
- [ ] И все остальные тесты

### 14. Финальные шаги
- [ ] Запустить миграции: `./vendor/bin/sail artisan migrate:fresh`
- [ ] Запустить сидеры: `./vendor/bin/sail artisan db:seed`
- [ ] Запустить тесты: `./vendor/bin/sail test`
- [ ] Проверить работу в браузере
- [ ] Настроить permissions для ролей через MoonShine админку

## 📝 Важные замечания

### Изменения в именах полей
```
user_id → moonshine_user_id
author_id → moonshine_author_id
assignee_id → moonshine_assignee_id
```

### Использование auth()
```php
// Старый код
$user = auth()->user(); // User

// Новый код
$user = auth()->user(); // MoonshineUser (т.к. изменен default guard)
```

### Notifications
```php
// Старый код
Notification::create([...]);

// Новый код
$user->notify(new TaskAssignedNotification($task));
// или
Notification::send($users, new TaskAssignedNotification($task));
```

### Permissions (TODO)
```php
// Будущая реализация через HasMoonShinePermissions
if ($user->moonShinePermissions()->contains('manage_tasks')) {
    // ...
}
```

## 🎯 Приоритет следующих задач

1. **Высокий**: Обновить контроллеры и Request классы
2. **Высокий**: Обновить фабрики и сидеры
3. **Средний**: Обновить Blade views
4. **Средний**: Обновить MoonShine Resources
5. **Низкий**: Обновить тесты (после того как все заработает)
6. **Низкий**: Настроить детальные permissions
