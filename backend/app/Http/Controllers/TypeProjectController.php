<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Repositories\TypeProjectRepository;
use Illuminate\Support\Facades\Log;

class TypeProjectController extends Controller
{
    function __construct(protected TypeProjectRepository $type_project_repository)
    {}

    function index()
    {
        return $this->type_project_repository->get_items()->loadCount('projects');
    }

    function delete(string $id)
    {
        try {
            if ($this->type_project_repository->has_related_projects($id)) {
                return response()->json([
                    'message' => 'No se puede eliminar el tipo de proyecto porque tiene proyectos relacionados.',
                ], 400);
            }

            $this->type_project_repository->delete_item($id);

            return response()->json([
                'message' => 'tipo de proyecto eliminado',
            ], 200);
        } catch (\Exception $e) {
            Log::error('error al eliminar tipo de proyecto', ['exception' => $e]);

            return response()->json([
                'message' => 'error al eliminar tipo de proyecto',
                'error' => app()->isLocal() ? $e->getMessage() : null,
            ], 500);
        }
    }

    function store(Request $request)
    {   
        $data = $request->validate([
            'name' => 'required|string|max:255|min:2',
            'description' => 'required|string|max:255',
            'extension' => 'nullable|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'description_en' => 'nullable|string|max:255',
        ], [
            'name.min'      => 'El nombre del proyecto debe tener al menos :min caracteres.',
            'name.required' => 'El nombre del proyecto es obligatorio.',
            'name.max'      => 'El nombre no puede superar los :max caracteres.',
            'description.required' => 'La descripción es obligatoria.',
            'description.max' => 'La descripción no puede superar los :max caracteres.',
            'extension.max' => 'La extensión no puede superar los :max caracteres.',
        ], [
            'name' => 'nombre',
            'description' => 'descripción',
            'extension' => 'extensión',
        ]);

        return $this->type_project_repository->save_item($data);
    }

    function update(Request $request, string $id)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|min:2',
            'description' => 'required|string|max:255',
            'extension' => 'nullable|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'description_en' => 'nullable|string|max:255',
        ], [
            'name.min'      => 'El nombre debe tener al menos :min caracteres.',
            'name.required' => 'El nombre es obligatorio.',
            'name.max'      => 'El nombre no puede superar los :max caracteres.',
            'description.required' => 'La descripción es obligatoria.',
            'description.max' => 'La descripción no puede superar los :max caracteres.',
            'extension.max' => 'La extensión no puede superar los :max caracteres.',
        ]);

        return response()->json([
            'message' => 'tipo de proyecto actualizado',
            'data' => $this->type_project_repository->update_item($id, $data),
        ], 200);
    }
}
