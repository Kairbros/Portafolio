<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Repositories\AboutRepository;

class AboutController extends Controller
{
    function __construct(protected AboutRepository $about_repository)
    {}

    function show()
    {
        $about = $this->about_repository->get_current();

        return response()->json([
            'body' => $about?->body ?? '',
            'body_en' => $about?->body_en ?? '',
        ], 200);
    }

    function update(Request $request)
    {
        $data = $request->validate([
            'body' => 'required|string|max:10000',
            'body_en' => 'nullable|string|max:10000',
        ], [
            'body.required' => 'El texto no puede quedar vacío.',
            'body.max' => 'El texto no puede superar los :max caracteres.',
            'body_en.max' => 'El texto en inglés no puede superar los :max caracteres.',
        ]);

        $about = $this->about_repository->save_current($data);

        return response()->json([
            'message' => 'texto guardado',
            'body' => $about->body,
            'body_en' => $about->body_en ?? '',
        ], 200);
    }
}
