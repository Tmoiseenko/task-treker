<?php

namespace App\Models;

use App\Enums\ProjectType;
use App\Enums\ProjectStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'type',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'type' => ProjectType::class,
            'status' => ProjectStatus::class,
        ];
    }

    // Relationships
    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function stages()
    {
        return $this->belongsToMany(Stage::class, 'project_stage');
    }

    public function members()
    {
        return $this->belongsToMany(MoonshineUser::class, 'project_user', 'project_id', 'moonshine_user_id');
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }
}
