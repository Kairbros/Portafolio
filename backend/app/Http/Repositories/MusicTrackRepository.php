<?php

namespace App\Http\Repositories;

use Illuminate\Database\Eloquent\Model;
use App\Models\MusicTrack;
use App\Http\Repositories\BaseRepository;

class MusicTrackRepository extends BaseRepository
{
    protected Model $model;

    function __construct(MusicTrack $model)
    {
        $this->model = $model;
    }
}
