<?php

namespace App\Http\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Models\Project;
use App\Http\Repositories\BaseRepository;

class ProjectRepository extends BaseRepository
{
    protected Model $model;

    function __construct(Project $model)
    {
        $this->model = $model;
    }

    function published_items()
    {
        return $this->model::query()
            ->where('published', true)
            ->with('type')
            ->latest()
            ->get()
            ->makeHidden(['body', 'body_en']);
    }

    function all_items()
    {
        return $this->model::query()
            ->with('type')
            ->latest()
            ->get()
            ->makeHidden(['body', 'body_en']);
    }

    function published_by_slug(string $slug)
    {
        return $this->model::query()
            ->where('published', true)
            ->where('slug', $slug)
            ->with(['type', 'links', 'files'])
            ->firstOrFail();
    }

    function full_item(string $id)
    {
        return $this->model::query()
            ->with(['type', 'links', 'files'])
            ->findOrFail($id);
    }

    function unique_slug(string $name): string
    {
        $base = Str::limit(Str::slug($name), 200, '') ?: 'proyecto';
        $slug = $base;
        $suffix = 2;

        while ($this->model::query()->where('slug', $slug)->exists()) {
            $slug = $base . '-' . $suffix++;
        }

        return $slug;
    }

    function sync_links(Project $project, array $links): void
    {
        $project->links()->delete();

        foreach (array_values($links) as $position => $link) {
            $project->links()->create([
                'label' => $link['label'],
                'url' => $link['url'],
                'position' => $position,
            ]);
        }
    }
}
