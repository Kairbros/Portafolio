<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Repositories\SkillRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SkillsController extends Controller
{
    function __construct(protected SkillRepository $skill_repository)
    {}

    function index()
    {
        return response()->json(
            $this->skill_repository->get_items()->load('type'),
            200,
        );
    }

    function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|min:2|max:120',
            'type' => 'required|integer|exists:type_skill,id',
            'icon' => 'required|image:allow_svg|mimes:svg,png,webp,jpg,jpeg,gif|max:2048',
            'mastery' => 'required|integer|between:0,100',
        ], [
            'name.required'    => 'El nombre de la skill es obligatorio.',
            'name.min'         => 'El nombre debe tener al menos :min caracteres.',
            'name.max'         => 'El nombre no puede superar los :max caracteres.',
            'type.required'    => 'La skill necesita un tipo.',
            'type.integer'     => 'El tipo de skill no es válido.',
            'type.exists'      => 'Ese tipo de skill no existe.',
            'icon.required'    => 'El icono es obligatorio.',
            'icon.image'       => 'El icono tiene que ser una imagen.',
            'icon.mimes'       => 'El icono debe ser de tipo: :values.',
            'icon.max'         => 'El icono no puede pesar más de 2 MB.',
            'mastery.required' => 'Falta el nivel de dominio.',
            'mastery.integer'  => 'El dominio se mide en números enteros.',
            'mastery.between'  => 'El dominio va de :min a :max.',
        ], [
            'name' => 'nombre',
            'type' => 'tipo',
            'icon' => 'icono',
            'mastery' => 'dominio',
        ]);

        try {
            $skill = DB::transaction(function () use ($request, $validated) {
                $icon = $request->file('icon');

                $path = $icon->storeAs(
                    'skills',
                    time() . '_' . Str::slug($validated['name'])
                        . '.' . strtolower($icon->getClientOriginalExtension()),
                    'public',
                );

                return $this->skill_repository->save_item([
                    'id_type_skill' => $validated['type'],
                    'name' => $validated['name'],
                    'icon' => $path,
                    'mastery' => $validated['mastery'],
                ]);
            });

            return response()->json([
                'message' => 'skill guardada',
                'data' => $skill->load('type'),
            ], 201);
        } catch (\Exception $e) {
            Log::error('error al guardar la skill', ['exception' => $e]);

            return response()->json([
                'message' => 'error al guardar la skill',
                'error' => app()->isLocal() ? $e->getMessage() : null,
            ], 500);
        }
    }

    function delete(string $id)
    {
        try {
            $skill = $this->skill_repository->get_item($id);
            $icon = $skill->icon;

            $this->skill_repository->delete_item($id);

            if ($icon) Storage::disk('public')->delete($icon);

            return response()->json([
                'message' => 'skill eliminada',
            ], 200);
        } catch (\Exception $e) {
            Log::error('error al eliminar la skill', ['exception' => $e]);

            return response()->json([
                'message' => 'error al eliminar la skill',
                'error' => app()->isLocal() ? $e->getMessage() : null,
            ], 500);
        }
    }
}
