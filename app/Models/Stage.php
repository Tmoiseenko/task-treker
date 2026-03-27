<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stage extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'order',
    ];

    // Relationships
    public function projects()
    {
        return $this->belongsToMany(Project::class, 'project_stage');
    }

    public function taskStages()
    {
        return $this->hasMany(TaskStage::class);
    }
}
