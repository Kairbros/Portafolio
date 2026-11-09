<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Http\Repositories\DrawingRepository;

class DrawingController extends Controller
{
    private const MAX_COLORS = 35;

    private const DEFAULT_COLORS = 24;

    function __construct(protected DrawingRepository $drawing_repository)
    {}

    function index()
    {
        return response()->json($this->drawing_repository->get_pages(), 200);
    }

    function store(Request $request)
    {
        if ($request->filled('website')) {
            return response()->json(['message' => 'enviado'], 201);
        }

        $data = $request->validate([
            'name' => 'nullable|string|max:24',
            'comment' => 'nullable|string|max:140',
            'pixels' => ['required', 'string', 'size:1024', 'regex:/^[0-9a-z]+$/', 'not_regex:/^0+$/'],
            'colors' => 'nullable|array|max:' . self::MAX_COLORS,
            'colors.*' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ], [
            'name.max' => 'El nombre no puede superar los :max caracteres.',
            'comment.max' => 'El comentario no puede superar los :max caracteres.',
            'pixels.required' => 'Falta el dibujo.',
            'pixels.size' => 'El dibujo no es válido.',
            'pixels.regex' => 'El dibujo no es válido.',
            'pixels.not_regex' => 'El dibujo está vacío.',
            'colors.max' => 'El dibujo tiene demasiados colores.',
            'colors.*.regex' => 'El dibujo no es válido.',
        ]);

        if (isset($data['colors'])) {
            $data['colors'] = array_map('strtolower', array_values($data['colors']));
        }

        $available = isset($data['colors']) ? count($data['colors']) : self::DEFAULT_COLORS;
        $highest = max(array_map(fn (string $cell) => intval($cell, 36), str_split($data['pixels'])));

        if ($highest > $available) {
            throw ValidationException::withMessages(['pixels' => 'El dibujo no es válido.']);
        }

        $this->drawing_repository->save_item($data);

        return response()->json(['message' => 'enviado'], 201);
    }

    function pending()
    {
        return response()->json($this->drawing_repository->get_pending(), 200);
    }

    function add_page()
    {
        return response()->json($this->drawing_repository->add_page(), 201);
    }

    function delete_page(string $id)
    {
        if (! $this->drawing_repository->delete_page($id)) {
            return response()->json(['message' => 'Tiene que quedar al menos una página.'], 400);
        }

        return response()->json(['message' => 'página borrada'], 200);
    }

    function place(Request $request, string $id)
    {
        $data = $request->validate([
            'drawing_page_id' => 'required|integer|exists:drawing_pages,id',
            'x' => 'required|numeric|between:0,100',
            'y' => 'required|numeric|between:0,100',
            'rotation' => 'required|numeric|between:-180,180',
            'z' => 'nullable|integer|min:0',
        ]);

        $data['z'] ??= $this->drawing_repository->top_z($data['drawing_page_id']) + 1;

        return response()->json($this->drawing_repository->update_item($id, $data), 200);
    }

    function unplace(string $id)
    {
        return response()->json(
            $this->drawing_repository->update_item($id, ['drawing_page_id' => null, 'z' => 0]),
            200,
        );
    }

    function delete(string $id)
    {
        $this->drawing_repository->delete_item($id);

        return response()->json(['message' => 'descartado'], 200);
    }
}
