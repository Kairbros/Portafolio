<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ProjectFile extends Model
{
    protected $table = 'project_files';

    protected $fillable = ['label', 'path', 'original_name', 'size'];

    protected $hidden = ['path'];

    protected $casts = ['size' => 'integer'];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    protected static function booted(): void
    {
        static::deleting(function (ProjectFile $file) {
            Storage::disk('local')->delete($file->path);
        });
    }
}
