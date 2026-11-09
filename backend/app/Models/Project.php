<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Project extends Model
{
    protected $table = 'projects';

    protected $casts = [
        'tags' => 'array',
        'published' => 'boolean',
        'year' => 'integer',
    ];

    public function type(): BelongsTo
    {
        return $this->belongsTo(TypeProject::class, 'type_project_id');
    }

    public function links(): HasMany
    {
        return $this->hasMany(ProjectLink::class)->orderBy('position');
    }

    public function files(): HasMany
    {
        return $this->hasMany(ProjectFile::class)->orderBy('id');
    }

    protected static function booted(): void
    {
        static::deleting(function (Project $project) {
            Storage::disk('public')->deleteDirectory('projects/' . $project->id);
            Storage::disk('local')->deleteDirectory('project-files/' . $project->id);
        });
    }
}
