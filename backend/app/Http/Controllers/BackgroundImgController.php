<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Repositories\BackgroundImgRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BackgroundImgController extends Controller
{
    function __construct(protected BackgroundImgRepository $background_img_repository)
    {}

    function index()
    {
        return response()->json($this->background_img_repository->get_items(), 200);
    }


    function delete(string $id)
    {
        try {
            $this->background_img_repository->delete_item($id);

            return response()->json([
                'message' => 'imagen de fondo eliminada',
            ], 200);

        } catch (\Exception $e) {
            Log::error('error al eliminar imagen de fondo', ['exception' => $e]);

            return response()->json([
                'message' => 'error al eliminar imagen de fondo',
                'error' => app()->isLocal() ? $e->getMessage() : null,
            ], 500);
        }
    }

    function store(Request $request)
    {
        $request->validate([
            'img' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:32000',
        ], [
            'img.required' => 'La imagen de fondo es obligatoria.',
            'img.image' => 'El archivo debe ser una imagen.',
            'img.mimes' => 'La imagen debe ser de tipo: :values.',
            'img.max' => 'La imagen no puede superar los :max kilobytes.',
        ]);

        try {
            $img = DB::transaction(function () use ($request) {
                $file = $request->file('img');
                $name = time() . '_' . $file->getClientOriginalName();
                $path = $file->storeAs('background_imgs', $name, 'public');

                return $this->background_img_repository->save_item([
                    'img_path' => $path,
                ]);
            });

            return response()->json(['message' => 'Imagen de fondo guardada exitosamente.', 'data' => $img], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al guardar la imagen de fondo.', 'error' => $e->getMessage()], 500);
        }

    }
}
