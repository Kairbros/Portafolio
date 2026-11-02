<?php

namespace App\Http\Repositories;

use Illuminate\Database\Eloquent\Model;
use App\Models\Skill;

class SkillRepository extends BaseRepository
{
    protected Model $model;

    function __construct(Skill $model)
    {
        $this->model = $model;
    }
}
