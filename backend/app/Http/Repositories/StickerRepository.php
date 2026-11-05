<?php

namespace App\Http\Repositories;

use Illuminate\Database\Eloquent\Model;
use App\Models\Sticker;
use App\Http\Repositories\BaseRepository;

class StickerRepository extends BaseRepository
{
    protected Model $model;

    function __construct(Sticker $model)
    {
        $this->model = $model;
    }
}
