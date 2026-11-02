<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BackgroundImg extends Model
{
    protected $table = 'background_imgs';
    protected $fillable = ['img_path'];
}
