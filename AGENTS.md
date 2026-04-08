# AGENTS.md — AI Coding Agent Guide

## Project Overview
Self-hosted project-management system built on **Laravel 12** + **MoonShine 4** admin panel, PHP 8.5, MySQL 8.4, Redis. Development runs inside **Laravel Sail** (Docker); production runs without Docker.

---

## Developer Workflows

```bash
# Start / stop dev environment
./vendor/bin/sail up -d
./vendor/bin/sail down

# Run all tests (PestPHP)
./vendor/bin/sail test

# Run a single test file
./vendor/bin/sail test tests/Feature/TaskServiceTest.php

# Artisan / Composer / NPM inside containers
./vendor/bin/sail artisan migrate
./vendor/bin/sail composer require <pkg>
./vendor/bin/sail npm run dev

# Seed fresh DB (roles + demo data)
./vendor/bin/sail artisan migrate:fresh --seed

# Demo data only
./vendor/bin/sail artisan db:seed --class=DemoDataSeeder
```

Default admin credentials (created by `DatabaseSeeder`): `admin@admin.com` / `admin`

---

## Architecture: Two Parallel UI Layers

The app has **two separate frontends** that must be kept in sync:

| Layer | Path | Auth guard |
|---|---|---|
| MoonShine admin panel | `app/MoonShine/` + `/admin` routes | MoonShine session |
| Custom web UI | `app/Http/Controllers/` + `resources/views/` + `routes/web.php` | Standard `auth` middleware |

Both layers work against the same models/services. Business logic lives **only** in `app/Services/` — controllers and MoonShine resources delegate to services.

---

## Critical: User Model Is Not `App\Models\User`

The system uses **`App\Models\MoonshineUser`** (extends `MoonShine\Laravel\Models\MoonshineUser`). There is no `App\Models\User`. Every foreign key referencing a user is named `moonshine_user_id`, `moonshine_author_id`, or `moonshine_assignee_id` — **never** `user_id`.

```php
// Correct
$task->author()    // FK: moonshine_author_id
$task->assignee()  // FK: moonshine_assignee_id
$project->members() // pivot: project_user, moonshine_user_id
```

---

## Task Status State Machine

`TaskStatus` enforces allowed transitions via `canTransitionTo()`. Always call `TaskService::changeStatus()` — never update `status` directly in controllers.

```
TO_WORK → IN_TESTING
IN_PROGRESS → IN_TESTING | TO_WORK
IN_TESTING → TEST_FAILED | FOR_UNLOADING
TEST_FAILED → IN_PROGRESS
FOR_UNLOADING → DONE
DONE → (terminal)
```

---

## TaskObserver Side Effects (automatic, not manual)

`App\Observers\TaskObserver` is registered in `AppServiceProvider` and does two things automatically:

- **`created`**: Creates a `TaskStage` record for every `Stage` attached to the task's project. Do **not** call `TaskService::createTaskStages()` manually — it is a no-op kept for reference only.
- **`updated`**: Logs every changed field to `audit_logs` via `AuditService::logChange()`.

---

## MoonShine Resource Pattern

Each MoonShine resource follows a mandatory directory structure:

```
app/MoonShine/Resources/<Entity>/
    <Entity>Resource.php      ← ModelResource subclass, declares pages() and search()
    Pages/
        <Entity>IndexPage.php ← fields() + filters()
        <Entity>FormPage.php  ← form fields
```

Resources are registered once in `MoonShineServiceProvider::boot()`. To add a new resource, create the files above **and** add the class to the `->resources([...])` call there.

MoonShine UI components use `#[Group(...)]` and `#[Order(...)]` attributes for sidebar navigation ordering.

---

## Key Patterns & Conventions

**Enums** — all status/type/priority values are PHP 8.1 backed string enums in `app/Enums/`. Always cast enum columns in model `casts()`:
```php
'status' => TaskStatus::class,
```

**Query scopes on Task** — use named scopes instead of raw `where` in controllers:
`byProject`, `byStatus`, `byPriority`, `byAssignee`, `byAuthor`, `byTags`, `search`.

**Time tracking timer** — active timers are stored in **Redis cache** (not DB) by `TimeTrackingService`. The key is `timer:{taskStage_id}:{user_id}`. Timers expire after 7 days.

**Bug reports** — implemented as child `Task` records via `parent_task_id` (self-referential on the `tasks` table).

**SoftDeletes** — enabled on `Project` and `MoonshineUser`; **not** on `Task`.

**Policies** — `TaskPolicy` and `ProjectPolicy` are registered in `AppServiceProvider`. Many policy methods contain `// TODO` stubs and return permissive defaults; implement using `HasMoonShinePermissions` trait on `MoonshineUser`.

---

## Events & Notifications

Four events with corresponding listeners (registered in `EventServiceProvider`):

| Event | Trigger | Listener |
|---|---|---|
| `TaskAssigned` | assignee set | `SendTaskAssignedNotification` |
| `TaskStatusChanged` | status updated | `SendTaskStatusChangedNotification` |
| `CommentAdded` | comment stored | `SendCommentAddedNotification` |
| `DeadlineApproaching` | scheduler | `SendDeadlineApproachingNotification` |

---

## Scheduled Commands

| Command | Schedule | Purpose |
|---|---|---|
| `tasks:check-deadlines` | Hourly | Fires `DeadlineApproaching` for tasks due in ≤24 h |
| `notifications:cleanup` | Daily | Purges old notifications |
| `reports:generate-monthly` | 1st of month | Generates monthly reports |

---

## Key Files Reference

| Purpose | Path |
|---|---|
| Model: user | `app/Models/MoonshineUser.php` |
| Model: task (scopes + relations) | `app/Models/Task.php` |
| Status state machine | `app/Enums/TaskStatus.php` |
| Task business logic | `app/Services/TaskService.php` |
| Audit logging | `app/Services/AuditService.php` |
| Timer (Redis) | `app/Services/TimeTrackingService.php` |
| Observer (auto TaskStage + audit) | `app/Observers/TaskObserver.php` |
| MoonShine registration | `app/Providers/MoonShineServiceProvider.php` |
| Policy registration + observer boot | `app/Providers/AppServiceProvider.php` |
| Event → listener map | `app/Providers/EventServiceProvider.php` |
| All web routes | `routes/web.php` |

