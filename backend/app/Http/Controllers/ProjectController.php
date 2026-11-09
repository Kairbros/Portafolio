<?php

namespace App\Http\Controllers;

use App\Http\Repositories\ProjectRepository;
use App\Models\Project;
use App\Models\ProjectFile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProjectController extends Controller
{
    private const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'];

    private const DOWNLOAD_EXTENSIONS = ['zip', 'rar', '7z', 'tar', 'gz', 'exe', 'msi', 'apk', 'dmg', 'appimage', 'deb', 'jar', 'pdf'];

    private const STATUSES = ['in_progress', 'finished', 'paused'];

    private const MAX_UPLOAD_BYTES = 512 * 1024 * 1024;

    function __construct(protected ProjectRepository $project_repository)
    {}

    function index()
    {
        return $this->project_repository->published_items();
    }

    function admin_index()
    {
        return $this->project_repository->all_items();
    }

    function show(string $slug)
    {
        return $this->project_repository->published_by_slug($slug);
    }

    function admin_show(string $id)
    {
        return $this->project_repository->full_item($id);
    }

    function store(Request $request)
    {
        if ($too_big = $this->too_big($request)) {
            return $too_big;
        }

        $validated = $this->validate_project($request);

        $project = DB::transaction(function () use ($request, $validated) {
            $project = $this->project_repository->save_item([
                ...$this->attributes($validated),
                'slug' => $this->project_repository->unique_slug($validated['name']),
            ]);

            $this->project_repository->sync_links($project, $validated['links'] ?? []);

            if ($request->hasFile('cover')) {
                $project->cover = $this->store_cover($request->file('cover'), $project);
                $project->save();
            }

            return $project;
        });

        return response()->json(['message' => 'proyecto creado', 'project' => $project], 201);
    }

    function update(Request $request, string $id)
    {
        if ($too_big = $this->too_big($request)) {
            return $too_big;
        }

        $project = $this->project_repository->get_item($id);
        $validated = $this->validate_project($request);
        $old_cover = $project->cover;

        DB::transaction(function () use ($request, $validated, $project) {
            foreach ($this->attributes($validated) as $key => $value) {
                $project[$key] = $value;
            }

            if ($request->hasFile('cover')) {
                $project->cover = $this->store_cover($request->file('cover'), $project);
            }

            $project->save();

            $this->project_repository->sync_links($project, $validated['links'] ?? []);
        });

        if ($old_cover && $old_cover !== $project->cover) {
            Storage::disk('public')->delete($old_cover);
        }

        return response()->json([
            'message' => 'proyecto actualizado',
            'project' => $this->project_repository->full_item($id),
        ], 200);
    }

    function delete(string $id)
    {
        $this->project_repository->delete_item($id);

        return response()->json(['message' => 'proyecto eliminado'], 200);
    }

    function store_file(Request $request, string $id)
    {
        if ($too_big = $this->too_big($request)) {
            return $too_big;
        }

        $project = $this->project_repository->get_item($id);
        $limit = $this->max_upload_kilobytes();

        $data = $request->validate([
            'label' => 'required|string|max:80',
            'file' => ['required', 'file', 'extensions:' . implode(',', self::DOWNLOAD_EXTENSIONS), 'max:' . $limit],
        ], [
            'label.required' => 'Ponle un nombre a la descarga.',
            'label.max' => 'El nombre no puede superar los :max caracteres.',
            'file.required' => 'Elige un archivo.',
            'file.uploaded' => 'El archivo no se pudo subir; el límite es ' . $this->human_size($limit) . '.',
            'file.extensions' => 'Tipo de archivo no permitido. Se aceptan: ' . implode(', ', self::DOWNLOAD_EXTENSIONS) . '.',
            'file.max' => 'El archivo no puede pesar más de ' . $this->human_size($limit) . '.',
        ]);

        $file = $data['file'];
        $extension = strtolower($file->getClientOriginalExtension());

        return response()->json($project->files()->create([
            'label' => $data['label'],
            'path' => $file->storeAs('project-files/' . $project->id, Str::random(32) . '.' . $extension, 'local'),
            'original_name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
        ]), 201);
    }

    function delete_file(string $id)
    {
        ProjectFile::query()->findOrFail($id)->delete();

        return response()->json(['message' => 'descarga eliminada'], 200);
    }

    function download(string $id)
    {
        $file = ProjectFile::query()->with('project')->findOrFail($id);

        abort_unless($file->project->published, 404);

        return Storage::disk('local')->download($file->path, $file->original_name);
    }

    function store_image(Request $request, string $id)
    {
        $project = $this->project_repository->get_item($id);

        $data = $request->validate([
            'image' => ['required', 'image', 'mimes:' . implode(',', self::IMAGE_EXTENSIONS), 'max:8192'],
        ], [
            'image.required' => 'Elige una imagen.',
            'image.image' => 'El archivo tiene que ser una imagen.',
            'image.mimes' => 'La imagen debe ser de tipo: :values.',
            'image.max' => 'La imagen no puede pesar más de 8 MB.',
        ]);

        $path = $data['image']->storeAs(
            'projects/' . $project->id . '/images',
            Str::random(32) . '.' . $data['image']->extension(),
            'public',
        );

        return response()->json(['path' => $path], 201);
    }

    private function validate_project(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|min:3|max:255',
            'type' => 'required|integer|exists:type_projects,id',
            'description' => 'required|string|max:1024',
            'description_en' => 'nullable|string|max:1024',
            'body' => 'nullable|string|max:100000',
            'body_en' => 'nullable|string|max:100000',
            'status' => ['required', Rule::in(self::STATUSES)],
            'year' => 'nullable|integer|between:1990,2100',
            'tags' => 'nullable|array|max:12',
            'tags.*' => 'string|max:30',
            'published' => 'required|boolean',
            'links' => 'nullable|array|max:12',
            'links.*.label' => 'required|string|max:60',
            'links.*.url' => 'required|string|url:http,https|max:2048',
            'cover' => ['nullable', 'image', 'mimes:' . implode(',', self::IMAGE_EXTENSIONS), 'max:8192'],
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'name.min' => 'El nombre debe tener al menos :min caracteres.',
            'name.max' => 'El nombre no puede superar los :max caracteres.',
            'type.required' => 'Elige un tipo.',
            'type.exists' => 'Ese tipo no existe.',
            'description.required' => 'El resumen es obligatorio.',
            'description.max' => 'El resumen no puede superar los :max caracteres.',
            'description_en.max' => 'El resumen no puede superar los :max caracteres.',
            'body.max' => 'El artículo es demasiado largo.',
            'body_en.max' => 'El artículo es demasiado largo.',
            'status.in' => 'Ese estado no existe.',
            'year.integer' => 'El año no es válido.',
            'year.between' => 'El año no es válido.',
            'tags.max' => 'Como máximo :max etiquetas.',
            'tags.*.max' => 'Cada etiqueta puede tener hasta :max caracteres.',
            'links.max' => 'Como máximo :max enlaces.',
            'links.*.label.required' => 'Cada enlace necesita un nombre.',
            'links.*.label.max' => 'El nombre del enlace no puede superar los :max caracteres.',
            'links.*.url.required' => 'Cada enlace necesita una dirección.',
            'links.*.url.url' => 'El enlace debe empezar por http:// o https://.',
            'links.*.url.max' => 'El enlace es demasiado largo.',
            'cover.image' => 'La portada tiene que ser una imagen.',
            'cover.mimes' => 'La portada debe ser de tipo: :values.',
            'cover.max' => 'La portada no puede pesar más de 8 MB.',
        ]);
    }

    private function attributes(array $validated): array
    {
        return [
            'name' => $validated['name'],
            'type_project_id' => $validated['type'],
            'description' => $validated['description'],
            'description_en' => $validated['description_en'] ?? null,
            'body' => $validated['body'] ?? null,
            'body_en' => $validated['body_en'] ?? null,
            'status' => $validated['status'],
            'year' => $validated['year'] ?? null,
            'tags' => $validated['tags'] ?? [],
            'published' => (bool) $validated['published'],
        ];
    }

    private function store_cover(UploadedFile $cover, Project $project): string
    {
        return $cover->storeAs(
            'projects/' . $project->id,
            'cover-' . Str::random(12) . '.' . $cover->extension(),
            'public',
        );
    }

    private function too_big(Request $request): ?JsonResponse
    {
        if (! empty($request->all()) || (int) $request->server('CONTENT_LENGTH') === 0) {
            return null;
        }

        return response()->json([
            'message' => 'El envío pesa más de lo que admite el servidor (' . ini_get('post_max_size') . ').',
        ], 413);
    }

    private function max_upload_kilobytes(): int
    {
        $limits = array_filter([
            $this->ini_bytes(ini_get('upload_max_filesize')),
            $this->ini_bytes(ini_get('post_max_size')),
            self::MAX_UPLOAD_BYTES,
        ], fn ($bytes) => $bytes > 0);

        return (int) floor(min($limits) / 1024);
    }

    private function ini_bytes(string|false $value): int
    {
        $value = trim((string) $value);

        return (int) $value * match (strtolower(substr($value, -1))) {
            'g' => 1024 ** 3,
            'm' => 1024 ** 2,
            'k' => 1024,
            default => 1,
        };
    }

    private function human_size(int $kilobytes): string
    {
        return $kilobytes >= 1024
            ? round($kilobytes / 1024) . ' MB'
            : $kilobytes . ' KB';
    }
}
