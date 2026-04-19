<?php

namespace Database\Seeders;

use App\Enums\ProjectStatus;
use App\Enums\ProjectType;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
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
    public function run(): void
    {
        // ── Пользователи ───────────────────────────────────────────────
        $users = collect([
            ['name' => 'Администратор',       'email' => 'admin@example.com',    'rate' => 100],
            ['name' => 'Иван Петров',         'email' => 'pm@example.com',       'rate' => 80],
            ['name' => 'Мария Дизайнер',      'email' => 'designer@example.com', 'rate' => 60],
            ['name' => 'Алексей Разработчик', 'email' => 'dev1@example.com',     'rate' => 70],
            ['name' => 'Елена Кодер',         'email' => 'dev2@example.com',     'rate' => 75],
            ['name' => 'Ольга Тестер',        'email' => 'tester@example.com',   'rate' => 50],
        ])->map(fn ($u) => MoonshineUser::create([
            'name'                   => $u['name'],
            'email'                  => $u['email'],
            'password'               => Hash::make('password'),
            'hourly_rate'            => $u['rate'],
            'moonshine_user_role_id' => 1,
        ]));

        // ── Теги ───────────────────────────────────────────────────────
        $tags = collect([
            Tag::create(['name' => 'Дизайн',          'color' => 'purple']),
            Tag::create(['name' => 'Бэкенд',       'color' => 'green']),
            Tag::create(['name' => 'Админка',   'color' => 'pink']),
            Tag::create(['name' => 'Мобилка',        'color' => 'yellow']),
            Tag::create(['name' => 'Тестирование', 'color' => 'info']),
        ]);

        // ── Проекты ────────────────────────────────────────────────────
        $projectDefs = [
            ['name' => 'Мобильное приложение для доставки еды', 'type' => ProjectType::MOBILE_APP],
            ['name' => 'Корпоративный сайт',                    'type' => ProjectType::WEBSITE],
            ['name' => 'Telegram бот для поддержки',            'type' => ProjectType::TELEGRAM_BOT],
        ];

        foreach ($projectDefs as $def) {
            $project = Project::create([
                'name'        => $def['name'],
                'description' => fake()->paragraph(),
                'type'        => $def['type'],
                'status'      => ProjectStatus::ACTIVE,
            ]);

            // Привязываем этапы проекта
            $stageNames = $def['type']->getDefaultStages();
            $stages = Stage::whereIn('name', $stageNames)->get();
            $project->stages()->attach($stages);

            // Все пользователи — участники проекта
            $project->members()->attach($users->pluck('id')->toArray());

            // 5–8 задач на каждый статус
            foreach (TaskStatus::cases() as $status) {
                $count = rand(5, 8);

                for ($i = 0; $i < $count; $i++) {
                    $author   = $users->random();
                    $assignee = $users->random();

                    $dueDate = match ($status) {
                        TaskStatus::DONE          => fake()->dateTimeBetween('-60 days', '-1 day'),
                        TaskStatus::FOR_UNLOADING => fake()->dateTimeBetween('-10 days', '+5 days'),
                        TaskStatus::IN_TESTING,
                        TaskStatus::TEST_FAILED   => fake()->dateTimeBetween('-5 days', '+10 days'),
                        default                   => fake()->dateTimeBetween('now', '+30 days'),
                    };

                    $task = Task::create([
                        'title'                  => fake()->sentence(rand(4, 8)),
                        'description'            => fake()->paragraph(rand(1, 3)),
                        'project_id'             => $project->id,
                        'moonshine_author_id'    => $author->id,
                        'moonshine_assignee_id'  => $assignee->id,
                        'priority'               => fake()->randomElement(TaskPriority::cases()),
                        'status'                 => $status,
                        'due_date'               => $dueDate,
                    ]);

                    // 1–2 случайных тега
                    $task->tags()->attach(
                        $tags->random(rand(1, 2))->pluck('id')->toArray()
                    );

                    // Комментарий для задач не в TODO
                    if ($status !== TaskStatus::TODO) {
                        Comment::create([
                            'task_id'           => $task->id,
                            'moonshine_user_id' => $assignee->id,
                            'content'           => fake()->sentence(),
                        ]);
                    }

                    // Записи времени для задач, по которым уже шла работа
                    if (in_array($status, [TaskStatus::IN_TESTING, TaskStatus::FOR_UNLOADING, TaskStatus::DONE])) {
                        $task->refresh();
                        $taskStage = $task->taskStages()->first();

                        if ($taskStage) {
                            $entries = rand(2, 4);
                            for ($e = 0; $e < $entries; $e++) {
                                $hours = round(rand(1, 4) + fake()->randomFloat(1, 0, 0.9), 1);
                                TimeEntry::create([
                                    'task_stage_id'     => $taskStage->id,
                                    'moonshine_user_id' => $assignee->id,
                                    'hours'             => $hours,
                                    'date'              => fake()->dateTimeBetween('-20 days', 'now'),
                                    'description'       => fake()->sentence(),
                                    'cost'              => round($hours * $assignee->hourly_rate, 2),
                                ]);
                            }
                        }
                    }
                }
            }
        }

        $total = Task::count();
        $this->command->info("Demo data created: {$total} tasks across " . count($projectDefs) . ' projects.');
    }
}
