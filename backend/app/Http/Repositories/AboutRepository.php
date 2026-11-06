<?php

namespace App\Http\Repositories;

use Illuminate\Database\Eloquent\Model;
use App\Models\About;
use App\Http\Repositories\BaseRepository;

class AboutRepository extends BaseRepository
{
    protected Model $model;

    function __construct(About $model)
    {
        $this->model = $model;
    }

    // la tabla guarda un solo texto
    function get_current()
    {
        return $this->model::query()->orderBy('id')->first();
    }

    function save_current(array $data)
    {
        $current = $this->get_current();

        return $current
            ? $this->update_item((string) $current->id, $data)
            : $this->save_item($data);
    }
}
