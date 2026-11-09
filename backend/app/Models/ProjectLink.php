<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectLink extends Model
{
    protected $table = 'project_links';

    protected $fillable = ['label', 'url', 'position'];
}
