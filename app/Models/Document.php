<?php

namespace App\Models;

use App\Enums\DocumentCategory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'content',
        'category',
        'project_id',
        'moonshine_author_id',
        'version',
    ];

    protected function casts(): array
    {
        return [
            'category' => DocumentCategory::class,
            'version' => 'integer',
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

    public function tasks()
    {
        return $this->belongsToMany(Task::class);
    }

    public function versions()
    {
        return $this->hasMany(DocumentVersion::class);
    }
}
