<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Drawing extends Model
{
    protected $table = 'drawings';
    protected $fillable = ['name', 'comment', 'pixels', 'drawing_page_id', 'x', 'y', 'rotation', 'z'];

    protected $casts = [
        'x' => 'float',
        'y' => 'float',
        'rotation' => 'float',
        'z' => 'integer',
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(DrawingPage::class, 'drawing_page_id');
    }
}
