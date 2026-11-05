<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sticker extends Model
{
    protected $table = 'stickers';
    protected $fillable = ['img_path'];
}
