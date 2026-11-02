<?php

namespace App\Http\Repositories;

use Illuminate\Database\Eloquent\Model;
use App\Models\TypeSkill;

class TypeSkillRepository extends BaseRepository
{
    protected Model $model;

    function __construct(TypeSkill $model)
    {
        $this->model = $model;
    }

    function has_related_skills(string $id): bool
    {
        return $this->model->newQuery()
            ->whereKey($id)
            ->has('skills')
            ->exists();
    }
}
