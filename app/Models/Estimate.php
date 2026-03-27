<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Estimate extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_stage_id',
        'moonshine_user_id',
        'hours',
    ];

    protected function casts(): array
    {
        return [
            'hours' => 'decimal:2',
        ];
    }

    // Relationships
    public function taskStage()
    {
        return $this->belongsTo(TaskStage::class);
    }

    public function user()
    {
        return $this->belongsTo(MoonshineUser::class, 'moonshine_user_id');
    }
}
