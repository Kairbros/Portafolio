<?php

namespace App\Http\Repositories;

use Illuminate\Database\Eloquent\Model;
use App\Models\ProfileImg;
use App\Http\Repositories\BaseRepository;

class ProfileImgRepository extends BaseRepository
{
    protected Model $model;

    function __construct(ProfileImg $model)
    {
        $this->model = $model;
    }
}
