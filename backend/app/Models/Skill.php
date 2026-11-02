<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class Skill extends Model
{
    protected $table = "skill";
    protected $fillable = ['id_type_skill', 'name', 'icon', 'mastery'];

    public function type(): BelongsTo
    {
        return $this->belongsTo(TypeSkill::class, 'id_type_skill');
    }
}
