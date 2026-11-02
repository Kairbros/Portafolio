<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TypeProject extends Model
{
    protected $table = 'type_projects';

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'type_project_id');
    }
}
