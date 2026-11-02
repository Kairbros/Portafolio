<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Project extends Model
{
    protected $table = 'projects';

    public function type(): BelongsTo
    {
        return $this->belongsTo(TypeProject::class, 'type_project_id');
    }

    protected static function booted(): void
    {
        static::deleting(function (Project $project) {
            $project->discard_files();
        });
    }

    private function discard_files(): void
    {
        $disk = Storage::disk('public');

        $folder = $this->project_root();

        if ($folder) {
            if ($disk->directoryExists($folder)) $disk->deleteDirectory($folder);
            else $disk->delete($folder);
        }

        if ($this->cover && $this->cover !== $this->folder) {
            $disk->delete($this->cover);
        }
    }

    private function project_root(): ?string
    {
        $folder = (string) $this->folder;

        if ($folder === '' || str_starts_with($folder, 'http')) {
            return null;
        }

        $parts = explode('/', trim($folder, '/'));

        return count($parts) >= 3
            ? implode('/', array_slice($parts, 0, 3))
            : null;
    }
}
