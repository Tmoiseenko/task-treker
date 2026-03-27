<?php

namespace Database\Seeders;

use App\Enums\ProjectStatus;
use App\Enums\ProjectType;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Attachment;
use App\Models\Comment;
use App\Models\MoonshineUser;
use App\Models\Project;
use App\Models\Stage;
use App\Models\Tag;
use App\Models\Task;
use App\Models\TimeEntry;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Создаем тестовых пользователей
        $admin = $this->createUser('Администратор', 'admin@example.com', 100);
        $pm = $this->createUser('Иван Петров', 'pm@example.com', 80);
        $designer = $this->createUser('Мария Дизайнер', 'designer@example.com', 60);
        $developer1 = $this->createUser('Алексей Разработчик', 'dev1@example.com', 70);
        $developer2 = $this->createUser('Елена Кодер', 'dev2@example.com', 75);
        $tester = $this->createUser('Ольга Тестер', 'tester@example.com', 50);

        // Создаем теги
        $tags = $this->createTags();

        // Создаем проекты с задачами
        $this->createMobileAppProject($pm, $designer, $developer1, $developer2, $tester, $tags);
        $this->createWebsiteProject($pm, $designer, $developer1, $developer2, $tester, $tags);
        $this->createTelegramBotProject($pm, $developer1, $tester, $tags);

        $this->command->info('Demo data created successfully!');
    }

    /**
     * Создать пользователя
     */
    private function createUser(string $name, string $email, float $hourlyRate): MoonshineUser
    {
        return MoonshineUser::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make('password'),
            'hourly_rate' => $hourlyRate,
            'moonshine_user_role_id' => 1, // Admin role
        ]);
    }

    /**
     * Создать теги
     */
    private function createTags(): array
    {
        return [
            'bug' => Tag::create(['name' => 'Bug', 'color' => '#ef4444']),
            'feature' => Tag::create(['name' => 'Feature', 'color' => '#3b82f6']),
            'enhancement' => Tag::create(['name' => 'Enhancement', 'color' => '#10b981']),
            'urgent' => Tag::create(['name' => 'Urgent', 'color' => '#f59e0b']),
            'documentation' => Tag::create(['name' => 'Documentation', 'color' => '#8b5cf6']),
        ];
    }

    /**
     * Создать проект мобильного приложения
     */
    private function createMobileAppProject(MoonshineUser $pm, MoonshineUser $designer, MoonshineUser $dev1, MoonshineUser $dev2, MoonshineUser $tester, array $tags): void
    {
        $project = Project::create([
            'name' => 'Мобильное приложение для доставки еды',
            'description' => 'Разработка iOS и Android приложения для заказа еды с доставкой',
            'type' => ProjectType::MOBILE_APP,
            'status' => ProjectStatus::ACTIVE,
        ]);

        // Добавляем этапы проекта
        $stageNames = ProjectType::MOBILE_APP->getDefaultStages();
        $stages = Stage::whereIn('name', $stageNames)->get();
        $project->stages()->attach($stages);

        // Добавляем участников
        $project->members()->attach([$pm->id, $designer->id, $dev1->id, $dev2->id, $tester->id]);

        // Создаем задачи
        $this->createTaskWithDetails(
            $project,
            $pm,
            $designer,
            'Дизайн главного экрана',
            'Создать дизайн главного экрана с каталогом ресторанов',
            TaskPriority::HIGH,
            TaskStatus::DONE,
            now()->subDays(10),
            [$tags['feature']],
            true,
            8
        );

        $this->createTaskWithDetails(
            $project,
            $pm,
            $dev1,
            'API для каталога ресторанов',
            'Разработать REST API для получения списка ресторанов с фильтрацией',
            TaskPriority::HIGH,
            TaskStatus::DONE,
            now()->subDays(5),
            [$tags['feature']],
            true,
            12
        );

        $this->createTaskWithDetails(
            $project,
            $pm,
            $dev2,
            'Экран корзины',
            'Реализовать функционал корзины с добавлением/удалением товаров',
            TaskPriority::MEDIUM,
            TaskStatus::IN_TESTING,
            now()->addDays(2),
            [$tags['feature']],
            true,
            10
        );

        $this->createTaskWithDetails(
            $project,
            $pm,
            $dev1,
            'Интеграция с платежной системой',
            'Подключить Stripe для приема платежей',
            TaskPriority::HIGH,
            TaskStatus::IN_PROGRESS,
            now()->addDays(7),
            [$tags['feature'], $tags['urgent']],
            false
        );

        $this->createTaskWithDetails(
            $project,
            $pm,
            $designer,
            'Дизайн профиля пользователя',
            'Создать дизайн экрана профиля с историей заказов',
            TaskPriority::MEDIUM,
            TaskStatus::TODO,
            now()->addDays(14),
            [$tags['feature']],
            false
        );
    }

    /**
     * Создать проект веб-сайта
     */
    private function createWebsiteProject(MoonshineUser $pm, MoonshineUser $designer, MoonshineUser $dev1, MoonshineUser $dev2, MoonshineUser $tester, array $tags): void
    {
        $project = Project::create([
            'name' => 'Корпоративный сайт',
            'description' => 'Разработка корпоративного сайта с блогом и формой обратной связи',
            'type' => ProjectType::WEBSITE,
            'status' => ProjectStatus::ACTIVE,
        ]);

        $stageNames = ProjectType::WEBSITE->getDefaultStages();
        $stages = Stage::whereIn('name', $stageNames)->get();
        $project->stages()->attach($stages);

        $project->members()->attach([$pm->id, $designer->id, $dev1->id, $dev2->id, $tester->id]);

        $this->createTaskWithDetails(
            $project,
            $pm,
            $designer,
            'Дизайн главной страницы',
            'Создать современный дизайн главной страницы с анимациями',
            TaskPriority::HIGH,
            TaskStatus::DONE,
            now()->subDays(15),
            [$tags['feature']],
            true,
            6
        );

        $this->createTaskWithDetails(
            $project,
            $pm,
            $dev2,
            'Верстка главной страницы',
            'Адаптивная верстка главной страницы по макету',
            TaskPriority::HIGH,
            TaskStatus::DONE,
            now()->subDays(8),
            [$tags['feature']],
            true,
            8
        );

        $this->createTaskWithDetails(
            $project,
            $pm,
            $dev1,
            'Модуль блога',
            'Разработать систему управления блогом с категориями и тегами',
            TaskPriority::MEDIUM,
            TaskStatus::IN_PROGRESS,
            now()->addDays(5),
            [$tags['feature']],
            false
        );

        $this->createTaskWithDetails(
            $project,
            $pm,
            $dev2,
            'Форма обратной связи не отправляет email',
            'При отправке формы обратной связи письмо не приходит на почту',
            TaskPriority::HIGH,
            TaskStatus::IN_PROGRESS,
            now()->addDays(1),
            [$tags['bug'], $tags['urgent']],
            false
        );

        $this->createTaskWithDetails(
            $project,
            $pm,
            $dev1,
            'SEO оптимизация',
            'Настроить мета-теги, sitemap.xml, robots.txt',
            TaskPriority::LOW,
            TaskStatus::TODO,
            now()->addDays(20),
            [$tags['enhancement']],
            false
        );
    }

    /**
     * Создать проект Telegram бота
     */
    private function createTelegramBotProject(MoonshineUser $pm, MoonshineUser $dev1, MoonshineUser $tester, array $tags): void
    {
        $project = Project::create([
            'name' => 'Telegram бот для поддержки',
            'description' => 'Бот для автоматизации ответов на часто задаваемые вопросы',
            'type' => ProjectType::TELEGRAM_BOT,
            'status' => ProjectStatus::ACTIVE,
        ]);

        $stageNames = ProjectType::TELEGRAM_BOT->getDefaultStages();
        $stages = Stage::whereIn('name', $stageNames)->get();
        $project->stages()->attach($stages);

        $project->members()->attach([$pm->id, $dev1->id, $tester->id]);

        $this->createTaskWithDetails(
            $project,
            $pm,
            $dev1,
            'Базовая структура бота',
            'Настроить webhook, обработку команд /start, /help',
            TaskPriority::HIGH,
            TaskStatus::DONE,
            now()->subDays(7),
            [$tags['feature']],
            true,
            5
        );

        $this->createTaskWithDetails(
            $project,
            $pm,
            $dev1,
            'База знаний FAQ',
            'Реализовать систему вопросов-ответов с поиском',
            TaskPriority::HIGH,
            TaskStatus::IN_TESTING,
            now()->addDays(3),
            [$tags['feature']],
            true,
            8
        );

        $this->createTaskWithDetails(
            $project,
            $pm,
            $dev1,
            'Интеграция с CRM',
            'Подключить бота к CRM системе для создания тикетов',
            TaskPriority::MEDIUM,
            TaskStatus::TODO,
            now()->addDays(10),
            [$tags['feature'], $tags['enhancement']],
            false
        );
    }

    /**
     * Создать задачу с деталями
     */
    private function createTaskWithDetails(
        Project $project,
        MoonshineUser $author,
        MoonshineUser $assignee,
        string $title,
        string $description,
        TaskPriority $priority,
        TaskStatus $status,
        $dueDate,
        array $tags,
        bool $addTimeEntries = false,
        ?int $hoursSpent = null
    ): Task {
        $task = Task::create([
            'title' => $title,
            'description' => $description,
            'project_id' => $project->id,
            'moonshine_author_id' => $author->id,
            'moonshine_assignee_id' => $assignee->id,
            'priority' => $priority,
            'status' => $status,
            'due_date' => $dueDate,
        ]);

        // Добавляем теги
        $task->tags()->attach(array_map(fn($tag) => $tag->id, $tags));

        // Добавляем комментарии
        if (in_array($status, [TaskStatus::IN_PROGRESS, TaskStatus::IN_TESTING, TaskStatus::DONE])) {
            Comment::create([
                'task_id' => $task->id,
                'moonshine_user_id' => $assignee->id,
                'content' => 'Начал работу над задачей',
            ]);

            if ($status !== TaskStatus::IN_PROGRESS) {
                Comment::create([
                    'task_id' => $task->id,
                    'moonshine_user_id' => $assignee->id,
                    'content' => 'Основная функциональность реализована',
                ]);
            }
        }

        // Добавляем записи времени
        if ($addTimeEntries && $hoursSpent) {
            // Обновляем задачу чтобы получить созданные TaskStages
            $task->refresh();
            $taskStage = $task->taskStages()->first();
            
            if ($taskStage) {
                $entries = rand(2, 4);
                $hoursPerEntry = $hoursSpent / $entries;
                
                for ($i = 0; $i < $entries; $i++) {
                    TimeEntry::create([
                        'task_stage_id' => $taskStage->id,
                        'moonshine_user_id' => $assignee->id,
                        'hours' => round($hoursPerEntry, 2),
                        'date' => now()->subDays($entries - $i),
                        'description' => 'Работа над задачей',
                        'cost' => round($hoursPerEntry * $assignee->hourly_rate, 2),
                    ]);
                }
            }
        }

        return $task;
    }
}
