<?php

namespace App\Http\Repositories;

use Illuminate\Database\Eloquent\Model;
use App\Models\TypeProject;

class TypeProjectRepository extends BaseRepository
{
    protected Model $model;

    function __construct(TypeProject $model)
    {
        $this->model = $model;
    }

    function has_related_projects(string $id): bool
    {
        return $this->model->newQuery()
            ->whereKey($id)
            ->has('projects')
            ->exists();
    }
}
