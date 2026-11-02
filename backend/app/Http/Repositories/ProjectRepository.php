<?php

namespace App\Http\Repositories;

use Illuminate\Database\Eloquent\Model;
use App\Models\Project;
use App\Http\Repositories\BaseRepository;

class ProjectRepository extends BaseRepository
{
    protected Model $model;

    function __construct(Project $model)
    {
        $this->model = $model;
    }
}
