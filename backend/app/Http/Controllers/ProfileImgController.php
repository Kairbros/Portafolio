<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Repositories\ProfileImgRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProfileImgController extends Controller
{
    function __construct(protected ProfileImgRepository $profile_img_repository)
    {}

    function index()
    {
        return response()->json($this->profile_img_repository->get_items(), 200);
    }

    function delete(string $id)
    {
        try {
            $this->profile_img_repository->delete_item($id);

            return response()->json([
                'message' => 'imagen de perfil eliminada',
            ], 200);
        } catch (\Exception $e) {
            Log::error('error al eliminar imagen de perfil', ['exception' => $e]);

            return response()->json([
                'message' => 'error al eliminar tipo de proyecto',
                'error' => app()->isLocal() ? $e->getMessage() : null,
            ], 500);
        }
    }

    function store(Request $request)
    {
        $request->validate([
            'img' => 'required|image|mimes:jpeg,png,jpg,gif|max:32000|dimensions:ratio=1',
        ], [
            'img.required' => 'La imagen de perfil es obligatoria.',
            'img.image' => 'El archivo debe ser una imagen.',
            'img.mimes' => 'La imagen debe ser de tipo: :values.',
            'img.max' => 'La imagen no puede superar los :max kilobytes.',
            'img.dimensions' => 'La imagen de perfil debe ser cuadrada (misma altura que anchura).',
        ]);

        try {
            $img = DB::transaction(function () use ($request) {
                $file = $request->file('img');
                $name = time() . '_' . $file->getClientOriginalName();
                $path = $file->storeAs('profile_imgs', $name, 'public');

                return $this->profile_img_repository->save_item([
                    'img_path' => $path,
                ]);
            });

            return response()->json(['message' => 'Imagen de perfil guardada exitosamente.', 'data' => $img], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al guardar la imagen de perfil.', 'error' => $e->getMessage()], 500);
        }
    }
}
