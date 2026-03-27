<?php

namespace App\Services;

use App\Enums\TaskStatus;
use App\Models\Comment;
use App\Models\MoonshineUser;
use App\Models\Task;
use App\Notifications\CommentAddedNotification;
use App\Notifications\DeadlineApproachingNotification;
use App\Notifications\TaskAssignedNotification;
use App\Notifications\TaskStatusChangedNotification;
use Illuminate\Support\Facades\Notification;

class NotificationService
{
    /**
     * Notify user when a task is assigned to them
     */
    public function notifyTaskAssigned(Task $task): void
    {
        if (!$task->moonshine_assignee_id) {
            return;
        }

        $assignee = MoonshineUser::find($task->moonshine_assignee_id);
        if ($assignee) {
            $assignee->notify(new TaskAssignedNotification($task));
        }
    }

    /**
     * Notify relevant users when task status changes
     */
    public function notifyStatusChanged(Task $task, TaskStatus $oldStatus): void
    {
        $users = collect();

        // Notify assignee
        if ($task->moonshine_assignee_id) {
            $assignee = MoonshineUser::find($task->moonshine_assignee_id);
            if ($assignee) {
                $users->push($assignee);
            }
        }

        // Notify author
        if ($task->moonshine_author_id && $task->moonshine_author_id !== $task->moonshine_assignee_id) {
            $author = MoonshineUser::find($task->moonshine_author_id);
            if ($author) {
                $users->push($author);
            }
        }

        Notification::send($users, new TaskStatusChangedNotification($task, $oldStatus));
    }

    /**
     * Notify relevant users when a comment is added
     */
    public function notifyCommentAdded(Comment $comment): void
    {
        $task = $comment->task;
        $users = collect();

        // Notify assignee (if not the commenter)
        if ($task->moonshine_assignee_id && $task->moonshine_assignee_id !== $comment->moonshine_user_id) {
            $assignee = MoonshineUser::find($task->moonshine_assignee_id);
            if ($assignee) {
                $users->push($assignee);
            }
        }

        // Notify author (if not the commenter and not already notified)
        if ($task->moonshine_author_id 
            && $task->moonshine_author_id !== $comment->moonshine_user_id 
            && $task->moonshine_author_id !== $task->moonshine_assignee_id) {
            $author = MoonshineUser::find($task->moonshine_author_id);
            if ($author) {
                $users->push($author);
            }
        }

        Notification::send($users, new CommentAddedNotification($comment));
    }

    /**
     * Notify when deadline is approaching (24 hours)
     */
    public function notifyDeadlineApproaching(Task $task): void
    {
        $users = collect();

        // Notify assignee
        if ($task->moonshine_assignee_id) {
            $assignee = MoonshineUser::find($task->moonshine_assignee_id);
            if ($assignee) {
                $users->push($assignee);
            }
        }

        // Notify project managers
        $projectManagers = $this->getProjectManagers($task);
        foreach ($projectManagers as $manager) {
            $users->push($manager);
        }

        Notification::send($users->unique('id'), new DeadlineApproachingNotification($task));
    }

    /**
     * Notify when deadline has expired
     */
    public function notifyDeadlineExpired(Task $task): void
    {
        $users = collect();

        // Notify assignee
        if ($task->moonshine_assignee_id) {
            $assignee = MoonshineUser::find($task->moonshine_assignee_id);
            if ($assignee) {
                $users->push($assignee);
            }
        }

        // Notify project managers
        $projectManagers = $this->getProjectManagers($task);
        foreach ($projectManagers as $manager) {
            $users->push($manager);
        }

        $notification = new DeadlineApproachingNotification($task);
        Notification::send($users->unique('id'), $notification);
    }

    /**
     * Notify testers when task is ready for testing
     */
    public function notifyTaskReadyForTesting(Task $task): void
    {
        // Get all testers in the project
        $testers = $this->getProjectTesters($task);

        $notification = new TaskStatusChangedNotification($task, TaskStatus::IN_PROGRESS);
        Notification::send($testers, $notification);
    }

    /**
     * Notify assignee when a bug report is created
     */
    public function notifyBugReportCreated(Task $bugReport, Task $originalTask): void
    {
        if (!$originalTask->moonshine_assignee_id) {
            return;
        }

        $assignee = MoonshineUser::find($originalTask->moonshine_assignee_id);
        if ($assignee) {
            $assignee->notify(new TaskAssignedNotification($bugReport));
        }
    }

    /**
     * Notify testers when all bugs are fixed
     */
    public function notifyAllBugsFixed(Task $task): void
    {
        // Get all testers in the project
        $testers = $this->getProjectTesters($task);

        $notification = new TaskStatusChangedNotification($task, TaskStatus::TEST_FAILED);
        Notification::send($testers, $notification);
    }

    /**
     * Get project managers for a task's project
     */
    private function getProjectManagers(Task $task): \Illuminate\Support\Collection
    {
        // Используем MoonShine permissions для определения менеджеров
        // Пока возвращаем пустую коллекцию, позже можно добавить логику через permissions
        return $task->project->members()
            ->where(function($query) {
                // TODO: Добавить проверку прав через HasMoonShinePermissions
                // Например: $query->whereHas('permissions', ...)
            })
            ->get();
    }

    /**
     * Get testers for a task's project
     */
    private function getProjectTesters(Task $task): \Illuminate\Support\Collection
    {
        // Используем MoonShine permissions для определения тестеров
        // Пока возвращаем пустую коллекцию, позже можно добавить логику через permissions
        return $task->project->members()
            ->where(function($query) {
                // TODO: Добавить проверку прав через HasMoonShinePermissions
            })
            ->get();
    }
}
