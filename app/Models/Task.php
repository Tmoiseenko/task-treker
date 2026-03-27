<?php

namespace App\Models;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'project_id',
        'moonshine_author_id',
        'moonshine_assignee_id',
        'priority',
        'status',
        'due_date',
        'parent_task_id',
    ];

    protected function casts(): array
    {
        return [
            'priority' => TaskPriority::class,
            'status' => TaskStatus::class,
            'due_date' => 'datetime',
        ];
    }

    // Relationships
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function author()
    {
        return $this->belongsTo(MoonshineUser::class, 'moonshine_author_id');
    }

    public function assignee()
    {
        return $this->belongsTo(MoonshineUser::class, 'moonshine_assignee_id');
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'task_tag');
    }

    public function taskStages()
    {
        return $this->hasMany(TaskStage::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function attachments()
    {
        return $this->hasMany(Attachment::class);
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }

    public function checklistItems()
    {
        return $this->hasMany(ChecklistItem::class);
    }

    public function bugReports()
    {
        return $this->hasMany(Task::class, 'parent_task_id');
    }

    public function parentTask()
    {
        return $this->belongsTo(Task::class, 'parent_task_id');
    }

    public function documents()
    {
        return $this->belongsToMany(Document::class, 'document_task');
    }

    // Query Scopes
    public function scopeByProject($query, $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeByAssignee($query, $assigneeId)
    {
        return $query->where('moonshine_assignee_id', $assigneeId);
    }

    public function scopeByAuthor($query, $authorId)
    {
        return $query->where('moonshine_author_id', $authorId);
    }

    public function scopeByTags($query, $tagIds)
    {
        if (is_array($tagIds)) {
            return $query->whereHas('tags', function ($q) use ($tagIds) {
                $q->whereIn('tags.id', $tagIds);
            });
        }
        
        return $query->whereHas('tags', function ($q) use ($tagIds) {
            $q->where('tags.id', $tagIds);
        });
    }

    public function scopeSearch($query, $searchTerm)
    {
        return $query->where(function ($q) use ($searchTerm) {
            $q->where('title', 'like', "%{$searchTerm}%")
              ->orWhere('description', 'like', "%{$searchTerm}%");
        });
    }
}
