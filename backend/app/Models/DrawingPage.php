<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DrawingPage extends Model
{
    protected $table = 'drawing_pages';

    public function drawings(): HasMany
    {
        return $this->hasMany(Drawing::class, 'drawing_page_id')->orderBy('z');
    }
}
