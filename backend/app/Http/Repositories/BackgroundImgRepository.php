<?php

namespace App\Http\Repositories;

use Illuminate\Database\Eloquent\Model;
use App\Models\BackgroundImg;
use App\Http\Repositories\BaseRepository;

class BackgroundImgRepository extends BaseRepository
{
    protected Model $model;

    function __construct(BackgroundImg $model)
    {
        $this->model = $model;
    }
}
