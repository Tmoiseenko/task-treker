<?php

use App\Http\Controllers\TaskController;
use App\Http\Controllers\EstimateController;
use App\Http\Controllers\TimeEntryController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\KanbanController;
use App\Http\Controllers\ChecklistController;
use App\Http\Controllers\BugReportController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Task routes - require authentication
Route::middleware(['auth'])->group(function () {
    // Dashboard route
    Route::get('dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');
    
    // Standard resource routes
    Route::resource('tasks', TaskController::class);
    
    // Additional task actions
    Route::post('tasks/{task}/assign', [TaskController::class, 'assignTask'])
        ->name('tasks.assign');
    
    Route::post('tasks/{task}/take', [TaskController::class, 'takeTask'])
        ->name('tasks.take');
    
    Route::post('tasks/{task}/change-status', [TaskController::class, 'changeStatus'])
        ->name('tasks.change-status');
    
    // Kanban board routes
    Route::get('kanban', [KanbanController::class, 'index'])
        ->name('kanban.index');
    
    Route::patch('kanban/tasks/{task}/status', [KanbanController::class, 'updateStatus'])
        ->name('kanban.update-status');
    
    // Calendar routes
    Route::get('calendar', [CalendarController::class, 'index'])
        ->name('calendar.index');
    
    // Comment routes
    Route::post('tasks/{task}/comments', [CommentController::class, 'store'])
        ->name('comments.store');
    
    Route::delete('tasks/{task}/comments/{comment}', [CommentController::class, 'destroy'])
        ->name('comments.destroy');
    
    // Attachment routes
    Route::post('tasks/{task}/attachments', [AttachmentController::class, 'store'])
        ->name('attachments.store');
    
    Route::get('tasks/{task}/attachments/{attachment}/download', [AttachmentController::class, 'download'])
        ->name('attachments.download');
    
    Route::delete('tasks/{task}/attachments/{attachment}', [AttachmentController::class, 'destroy'])
        ->name('attachments.destroy');
    
    // Estimate routes
    Route::post('task-stages/{taskStage}/estimates', [EstimateController::class, 'store'])
        ->name('estimates.store');
    
    Route::put('estimates/{estimate}', [EstimateController::class, 'update'])
        ->name('estimates.update');
    
    Route::delete('estimates/{estimate}', [EstimateController::class, 'destroy'])
        ->name('estimates.destroy');
    
    // Time Entry routes
    Route::post('task-stages/{taskStage}/time-entries', [TimeEntryController::class, 'store'])
        ->name('time-entries.store');
    
    Route::put('time-entries/{timeEntry}', [TimeEntryController::class, 'update'])
        ->name('time-entries.update');
    
    Route::delete('time-entries/{timeEntry}', [TimeEntryController::class, 'destroy'])
        ->name('time-entries.destroy');
    
    // Timer routes
    Route::post('task-stages/{taskStage}/timer/start', [TimeEntryController::class, 'startTimer'])
        ->name('timer.start');
    
    Route::post('task-stages/{taskStage}/timer/stop', [TimeEntryController::class, 'stopTimer'])
        ->name('timer.stop');
    
    Route::get('task-stages/{taskStage}/timer/status', [TimeEntryController::class, 'timerStatus'])
        ->name('timer.status');
    
    // Report routes
    Route::get('reports/tasks/{task}/time', [ReportController::class, 'taskTimeReport'])
        ->name('reports.task-time');
    
    Route::get('reports/projects/{project}/time', [ReportController::class, 'projectTimeReport'])
        ->name('reports.project-time');
    
    Route::get('reports/my-payment', [ReportController::class, 'userPaymentReport'])
        ->name('reports.user-payment');
    
    Route::get('reports/users/{user}/payment', [ReportController::class, 'userPaymentReport'])
        ->name('reports.user-payment.specific');
    
    Route::get('reports/team/payment', [ReportController::class, 'teamPaymentReport'])
        ->name('reports.team-payment');
    
    // Checklist routes
    Route::post('tasks/{task}/checklist', [ChecklistController::class, 'store'])
        ->name('checklist.store');
    
    Route::patch('checklist/{checklistItem}/toggle', [ChecklistController::class, 'toggle'])
        ->name('checklist.toggle');
    
    Route::get('tasks/{task}/checklist/progress', [ChecklistController::class, 'progress'])
        ->name('checklist.progress');
    
    Route::delete('checklist/{checklistItem}', [ChecklistController::class, 'destroy'])
        ->name('checklist.destroy');
    
    // Bug Report routes
    Route::get('tasks/{task}/bug-reports', [BugReportController::class, 'index'])
        ->name('bug-reports.index');
    
    Route::get('tasks/{task}/bug-reports/create', [BugReportController::class, 'create'])
        ->name('bug-reports.create');
    
    Route::post('tasks/{task}/bug-reports', [BugReportController::class, 'store'])
        ->name('bug-reports.store');
    
    Route::post('bug-reports/{bugReport}/assign', [BugReportController::class, 'assign'])
        ->name('bug-reports.assign');
    
    // Document routes (Knowledge Base)
    Route::resource('documents', DocumentController::class);
    
    Route::get('documents/search', [DocumentController::class, 'search'])
        ->name('documents.search');
    
    Route::post('documents/{document}/attach-task', [DocumentController::class, 'attachToTask'])
        ->name('documents.attach-task');
    
    // Notification routes
    Route::get('notifications', [NotificationController::class, 'index'])
        ->name('notifications.index');
    
    Route::patch('notifications/{notification}/read', [NotificationController::class, 'markAsRead'])
        ->name('notifications.mark-as-read');
    
    Route::post('notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])
        ->name('notifications.mark-all-read');
});
