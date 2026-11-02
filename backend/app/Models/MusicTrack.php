<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MusicTrack extends Model
{
    protected $table = 'music_track';
    protected $fillable = ['title', 'artist', 'path', 'artwork_path'];
}
