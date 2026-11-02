<?php

namespace App\Http\Repositories;

use Illuminate\Database\Eloquent\Model;
use App\Models\Project;

class BaseRepository
{
    protected Model $model;
    
    function __construct(Project $model)
    {
        $this->model = $model;
    }

    function delete_item(string $id)
    {
        $item = $this->model::findOrFail($id);
        $item->delete();
    }

    function get_items()
    {
        return $this->model::all();
    }
    
    function get_item(string $id)
    {
        return $this->model::findOrFail($id);
    }

    function save_item(array $data)
    {
        $item = $this->model::newModelInstance();

        foreach ($data as $key => $value) {
            $item[$key] = $value;
        }

        $item->save();

        return $item;
    }
    
    function update_item(string $id, array $data)
    {
        $item = $this->model::findOrFail($id);

        foreach ($data as $key => $value) {
            $item[$key] = $value;
        }

        $item->save();

        return $item;
    }
}
