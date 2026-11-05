<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Repositories\TypeSkillRepository;
use Illuminate\Support\Facades\Log;

class TypeSkillController extends Controller
{
    function __construct(protected TypeSkillRepository $type_skill_repository)
    {}

    function index()
    {
        return $this->type_skill_repository->get_items()->loadCount('skills');
    }

    function delete(string $id)
    {
        try {
            if ($this->type_skill_repository->has_related_skills($id)) {
                return response()->json([
                    'message' => 'No se puede eliminar el tipo porque tiene skills relacionadas.',
                ], 400);
            }

            $this->type_skill_repository->delete_item($id);

            return response()->json([
                'message' => 'tipo de skill eliminado',
            ], 200);
        } catch (\Exception $e) {
            Log::error('error al eliminar tipo de skill', ['exception' => $e]);

            return response()->json([
                'message' => 'error al eliminar tipo de skill',
                'error' => app()->isLocal() ? $e->getMessage() : null,
            ], 500);
        }
    }

    function store(Request $request)
    {   
        $data = $request->validate([
            'name' => 'required|string|max:255|min:2',
        ], [
            'name.min'      => 'El nombre del skill debe tener al menos :min caracteres.',
            'name.required' => 'El nombre del skill es obligatorio.',
            'name.max'      => 'El nombre no puede superar los :max caracteres.',
        ], [
            'name' => 'nombre',
        ]);

        return response()->json([
            'message' => 'tipo de skill creado',
            'data' => $this->type_skill_repository->save_item($data),
        ], 201);
    }

    function update(Request $request, string $id)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|min:2',
        ], [
            'name.min'      => 'El nombre debe tener al menos :min caracteres.',
            'name.required' => 'El nombre es obligatorio.',
            'name.max'      => 'El nombre no puede superar los :max caracteres.',
        ]);

        return response()->json([
            'message' => 'tipo de skill actualizado',
            'data' => $this->type_skill_repository->update_item($id, $data),
        ], 200);
    }
}
