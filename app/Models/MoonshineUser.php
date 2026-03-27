<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use MoonShine\Laravel\Models\MoonshineUser as BaseMoonshineUser;
use MoonShine\Permissions\Traits\HasMoonShinePermissions;

class MoonshineUser extends BaseMoonshineUser
{
    use HasFactory, Notifiable, HasMoonShinePermissions;

    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'hourly_rate',
        'moonshine_user_role_id',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'hourly_rate' => 'decimal:2',
        ];
    }

    // Relationships
    public function projects()
    {
        return $this->belongsToMany(Project::class, 'project_user', 'moonshine_user_id', 'project_id');
    }

    public function assignedTasks()
    {
        return $this->hasMany(Task::class, 'moonshine_assignee_id');
    }

    public function createdTasks()
    {
        return $this->hasMany(Task::class, 'moonshine_author_id');
    }

    public function timeEntries()
    {
        return $this->hasMany(TimeEntry::class, 'moonshine_user_id');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class, 'moonshine_user_id');
    }

    public function attachments()
    {
        return $this->hasMany(Attachment::class, 'moonshine_user_id');
    }

    public function createdDocuments()
    {
        return $this->hasMany(Document::class, 'moonshine_author_id');
    }

    public function estimates()
    {
        return $this->hasMany(Estimate::class, 'moonshine_user_id');
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class, 'moonshine_user_id');
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): \Illuminate\Database\Eloquent\Factories\Factory
    {
        return \Database\Factories\MoonshineUserFactory::new();
    }
}
