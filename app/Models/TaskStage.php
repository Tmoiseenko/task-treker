<?php

namespace App\Models;

use App\Enums\StageStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskStage extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'stage_id',
        'status',
        'order',
    ];

    protected function casts(): array
    {
        return [
            'status' => StageStatus::class,
        ];
    }

    // Relationships
    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function stage()
    {
        return $this->belongsTo(Stage::class);
    }

    public function estimates()
    {
        return $this->hasMany(Estimate::class);
    }

    public function timeEntries()
    {
        return $this->hasMany(TimeEntry::class);
    }
}
