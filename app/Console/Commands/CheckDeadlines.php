<?php

namespace App\Console\Commands;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckDeadlines extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tasks:check-deadlines';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check tasks with approaching or expired deadlines and send notifications';

    /**
     * Execute the console command.
     */
    public function handle(NotificationService $notificationService): int
    {
        $this->info('Checking task deadlines...');

        $now = Carbon::now();
        $in24Hours = $now->copy()->addHours(24);

        // Check tasks with deadline approaching in 24 hours
        $approachingTasks = Task::whereNotNull('due_date')
            ->whereNotIn('status', [TaskStatus::DONE, TaskStatus::FOR_UNLOADING])
            ->whereBetween('due_date', [$now, $in24Hours])
            ->get()
            ->filter(function ($task) use ($now) {
                // Check if notification was already sent in the last 23 hours
                $recentNotification = \App\Models\Notification::where('type', 'deadline_approaching')
                    ->where('created_at', '>=', $now->copy()->subHours(23))
                    ->whereJsonContains('data->task_id', $task->id)
                    ->exists();

                return !$recentNotification;
            });

        foreach ($approachingTasks as $task) {
            $notificationService->notifyDeadlineApproaching($task);
            $this->line("Sent approaching deadline notification for task #{$task->id}: {$task->title}");
        }

        // Check overdue tasks
        $overdueTasks = Task::whereNotNull('due_date')
            ->whereNotIn('status', [TaskStatus::DONE, TaskStatus::FOR_UNLOADING])
            ->where('due_date', '<', $now)
            ->get()
            ->filter(function ($task) use ($now) {
                // Check if notification was already sent in the last 23 hours
                $recentNotification = \App\Models\Notification::where('type', 'deadline_expired')
                    ->where('created_at', '>=', $now->copy()->subHours(23))
                    ->whereJsonContains('data->task_id', $task->id)
                    ->exists();

                return !$recentNotification;
            });

        foreach ($overdueTasks as $task) {
            $notificationService->notifyDeadlineExpired($task);
            $this->line("Sent expired deadline notification for task #{$task->id}: {$task->title}");
        }

        $this->info("Processed {$approachingTasks->count()} approaching deadlines and {$overdueTasks->count()} expired deadlines.");

        return Command::SUCCESS;
    }
}
