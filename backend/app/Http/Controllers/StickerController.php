<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Repositories\StickerRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StickerController extends Controller
{
    function __construct(protected StickerRepository $sticker_repository)
    {}

    function index()
    {
        return response()->json($this->sticker_repository->get_items(), 200);
    }


    function delete(string $id)
    {
        try {
            $this->sticker_repository->delete_item($id);

            return response()->json([
                'message' => 'sticker eliminado',
            ], 200);

        } catch (\Exception $e) {
            Log::error('error al eliminar sticker', ['exception' => $e]);

            return response()->json([
                'message' => 'error al eliminar sticker',
                'error' => app()->isLocal() ? $e->getMessage() : null,
            ], 500);
        }
    }

    function store(Request $request)
    {
        $request->validate([
            'img' => 'required|image|mimes:jpeg,png,jpg,gif,svg,webp|max:32000',
        ], [
            'img.required' => 'El sticker es obligatorio.',
            'img.image' => 'El archivo debe ser una imagen.',
            'img.mimes' => 'La imagen debe ser de tipo: :values.',
            'img.max' => 'La imagen no puede superar los :max kilobytes.',
        ]);

        try {
            $img = DB::transaction(function () use ($request) {
                $file = $request->file('img');
                $name = time() . '_' . $file->getClientOriginalName();
                $path = $file->storeAs('stickers', $name, 'public');

                return $this->sticker_repository->save_item([
                    'img_path' => $path,
                ]);
            });

            return response()->json(['message' => 'Sticker guardado exitosamente.', 'data' => $img], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al guardar el sticker.', 'error' => $e->getMessage()], 500);
        }

    }
}
