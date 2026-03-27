<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'color',
    ];

    // Relationships
    public function tasks()
    {
        return $this->belongsToMany(Task::class, 'task_tag');
    }
}
