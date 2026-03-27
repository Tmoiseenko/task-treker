<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TimeEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_stage_id',
        'moonshine_user_id',
        'hours',
        'date',
        'description',
        'cost',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'hours' => 'decimal:2',
            'cost' => 'decimal:2',
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

    // Calculated fields
    public function calculateCost(): float
    {
        return (float) ($this->hours * $this->user->hourly_rate);
    }
}
