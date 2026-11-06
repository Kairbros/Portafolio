<?php

namespace App\Http\Repositories;

use Illuminate\Database\Eloquent\Model;
use App\Models\Drawing;
use App\Models\DrawingPage;

class DrawingRepository extends BaseRepository
{
    protected Model $model;

    function __construct(Drawing $model)
    {
        $this->model = $model;
    }

    // todas las páginas, con sus dibujos pegados de abajo hacia arriba
    function get_pages()
    {
        return DrawingPage::query()->orderBy('id')->with('drawings')->get();
    }

    function get_pending()
    {
        return $this->model::query()->whereNull('drawing_page_id')->orderBy('id')->get();
    }

    function add_page()
    {
        return DrawingPage::create();
    }

    function delete_page(string $id): bool
    {
        if (DrawingPage::query()->count() <= 1) {
            return false;
        }

        $page = DrawingPage::findOrFail($id);

        $this->model::query()
            ->where('drawing_page_id', $page->id)
            ->update(['drawing_page_id' => null, 'z' => 0]);

        $page->delete();

        return true;
    }

    function top_z(int $page_id): int
    {
        return (int) $this->model::query()->where('drawing_page_id', $page_id)->max('z');
    }
}
