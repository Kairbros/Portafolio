<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Repositories\MusicTrackRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class MusicTrackController extends Controller
{
    function __construct(protected MusicTrackRepository $music_track_repository)
    {}

    function index()
    {
        return response()->json($this->music_track_repository->get_items(), 200);
        
    }

    function delete(string $id)
    {
        try {
            $this->music_track_repository->delete_item($id);

            return response()->json([
                'message' => 'pista eliminada',
            ], 200);

        } catch (\Exception $e) {
            Log::error('error al eliminar la pista', ['exception' => $e]);

            return response()->json([
                'message' => 'error al eliminar la pista',
                'error' => app()->isLocal() ? $e->getMessage() : null,
            ], 500);
        }
    }

    function update(Request $request, string $id)
    {
        $track = $this->music_track_repository->get_item($id);

        $validated = $request->validate([
            'title' => 'required|string|min:1|max:120',
            'artist' => 'required|string|min:1|max:120',
            'img' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:32000|dimensions:ratio=1',
            'music' => 'nullable|mimes:mp3,wav|max:128000',
        ], [
            'title.required' => 'El título es obligatorio.',
            'title.max' => 'El título no puede superar los :max caracteres.',
            'artist.required' => 'El artista es obligatorio.',
            'artist.max' => 'El artista no puede superar los :max caracteres.',
            'img.image' => 'El archivo debe ser una imagen.',
            'img.mimes' => 'La carátula debe ser de tipo: :values.',
            'img.max' => 'La carátula no puede superar los :max kilobytes.',
            'img.dimensions' => 'La carátula debe ser cuadrada (misma altura que anchura).',
            'music.mimes' => 'El audio debe ser :values',
            'music.max' => 'El audio no puede superar los :max kilobytes.',
        ]);

        $stamp = time();
        $stored = [];
        $replaced = [];

        try {
            $data = [
                'title' => $validated['title'],
                'artist' => $validated['artist'],
            ];

            if ($request->hasFile('music')) {
                $music = $request->file('music');
                $data['path'] = $stored[] = $music->storeAs(
                    'music_tracks',
                    $stamp . '_' . $music->getClientOriginalName(),
                    'public',
                );
                $replaced[] = $track->path;
            }

            if ($request->hasFile('img')) {
                $artwork = $request->file('img');
                $data['artwork_path'] = $stored[] = $artwork->storeAs(
                    'music_tracks/artwork',
                    $stamp . '_' . $artwork->getClientOriginalName(),
                    'public',
                );
                $replaced[] = $track->artwork_path;
            }

            $track = $this->music_track_repository->update_item($id, $data);

            Storage::disk('public')->delete(array_diff(array_filter($replaced), $stored));

            return response()->json(['message' => 'Pista actualizada.', 'data' => $track], 200);
        } catch (\Exception $e) {
            Storage::disk('public')->delete($stored);

            Log::error('error al actualizar la pista', ['exception' => $e]);

            return response()->json([
                'message' => 'Error al actualizar la pista.',
                'error' => app()->isLocal() ? $e->getMessage() : null,
            ], 500);
        }
    }

    function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|min:1|max:120',
            'artist' => 'required|string|min:1|max:120',
            'img' => 'required|image|mimes:jpeg,png,jpg,gif|max:32000|dimensions:ratio=1',
            'music' => 'required|mimes:mp3,wav|max:128000',
        ], [
            'title.required' => 'El título es obligatorio.',
            'title.max' => 'El título no puede superar los :max caracteres.',
            'artist.required' => 'El artista es obligatorio.',
            'artist.max' => 'El artista no puede superar los :max caracteres.',
            'img.required' => 'La carátula es obligatoria.',
            'img.image' => 'El archivo debe ser una imagen.',
            'img.mimes' => 'La carátula debe ser de tipo: :values.',
            'img.max' => 'La carátula no puede superar los :max kilobytes.',
            'img.dimensions' => 'La carátula debe ser cuadrada (misma altura que anchura).',
            'music.required' => 'El audio es requerido',
            'music.mimes' => 'El audio debe ser :values',
            'music.max' => 'El audio no puede superar los :max kilobytes.'
        ], [
            'title' => 'título',
            'artist' => 'artista',
            'img' => 'carátula',
            'music' => 'audio',
        ]);

        try {
            $track = DB::transaction(function () use ($request, $validated) {
                $stamp = time();

                $music = $request->file('music');
                $artwork = $request->file('img');

                $path = $music->storeAs(
                    'music_tracks',
                    $stamp . '_' . $music->getClientOriginalName(),
                    'public',
                );

                $artwork_path = $artwork->storeAs(
                    'music_tracks/artwork',
                    $stamp . '_' . $artwork->getClientOriginalName(),
                    'public',
                );

                return $this->music_track_repository->save_item([
                    'title' => $validated['title'],
                    'artist' => $validated['artist'],
                    'path' => $path,
                    'artwork_path' => $artwork_path,
                ]);
            });

            return response()->json(['message' => 'Pista guardada exitosamente.', 'data' => $track], 201);
        } catch (\Exception $e) {
            Log::error('error al guardar la pista', ['exception' => $e]);

            return response()->json([
                'message' => 'Error al guardar la pista.',
                'error' => app()->isLocal() ? $e->getMessage() : null,
            ], 500);
        }
    }
}
