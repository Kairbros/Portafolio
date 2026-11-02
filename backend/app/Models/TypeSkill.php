<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TypeSkill extends Model
{
    protected $table = "type_skill";
    protected $fillable = ['name'];

    public function skills(): HasMany
    {
        return $this->hasMany(Skill::class, 'id_type_skill');
    }
}
