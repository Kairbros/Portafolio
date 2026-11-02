<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Repositories\ProjectRepository;
use App\Http\Repositories\TypeProjectRepository;
use App\Models\TypeProject;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use ZipArchive;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ProjectController extends Controller
{
    function __construct(
        protected ProjectRepository $project_repository,
        protected TypeProjectRepository $type_project_repository,
    )
    {}

    function delete(string $id)
    {
        try {
            $this->project_repository->delete_item($id);

            return response()->json([
                'message' => 'proyecto eliminado',
            ], 200);
        } catch (\Exception $e) {
            Log::error('error al eliminar proyecto', ['exception' => $e]);

            return response()->json([
                'message' => 'error al eliminar proyecto',
                'error' => app()->isLocal() ? $e->getMessage() : null,
            ], 500);
        }
    }

    function index()
    {
        return $this->project_repository->get_items()->load('type');
    }

    function show(string $id)
    {
        return $this->project_repository->get_item($id)->load('type');
    }

    function store(Request $request)
    {
        // si se pasa de post_max_size PHP descarta el body entero
        if (empty($request->all()) && (int) $request->server('CONTENT_LENGTH') > 0) {
            return response()->json([
                'message' => 'El envío pesa más de lo que admite el servidor (' . ini_get('post_max_size') . '). Sube un archivo más pequeño.',
            ], 413);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|min:3',
            'type' => 'required|integer|exists:type_projects,id',
            'description' => 'required|string|max:1024'
        ], [
            'name.min'      => 'El nombre del proyecto debe tener al menos :min caracteres.',
            'name.required' => 'El nombre del proyecto es obligatorio.',
            'name.max'      => 'El nombre no puede superar los :max caracteres.',
            'type.required' => 'El tipo de proyecto es obligatorio.',
            'type.integer'  => 'El tipo de proyecto no es válido.',
            'type.exists'   => 'Ese tipo de proyecto no existe.',
            'description.max' => 'La descripción no puede superar los :max caracteres.',
            'description.required' => 'La descripción es obligatoria.',
        ], [
            'name' => 'nombre',
            'type' => 'tipo',
            'description' => 'descripcion',
        ]);

        $type = $this->type_project_repository->get_item($validated['type']);
        $extensions = $this->allowed_extensions($type);

        $is_link = in_array('url', $extensions, true) && ! $request->hasFile('file');

        $is_zip = ! $is_link
            && $request->hasFile('file')
            && strtolower($request->file('file')->getClientOriginalExtension()) === 'zip';

        $payload = $is_link
            ? $this->validate_link($request)
            : $this->validate_file($request, $extensions, $type);

        $this->validate_cover($request, $is_link ? null : $request->file('file'));

        $extracted = null;

        try {
            $project = DB::transaction(function () use ($request, $validated, $type, $is_link, $payload, $is_zip, &$extracted) {
                $project = $this->project_repository->save_item([
                    'name' => $validated['name'],
                    'description' => $validated['description'],
                    'type_project_id' => $type->id,
                    'folder' => 'default',
                ]);

                $sub_folder = Str::slug($type->name);

                $stamp = time() . '_' . Str::slug($validated['name']);

                if ($is_link) {
                    $folder = $payload['url'];
                } elseif ($is_zip) {
                    $extracted = 'projects/' . $sub_folder . '/' . $stamp;

                    $folder = $this->extract_zip($request->file('file'), $extracted);
                } else {
                    $file = $request->file('file');

                    $folder = $file->storeAs(
                        'projects/' . $sub_folder,
                        $stamp . '.' . strtolower($file->getClientOriginalExtension()),
                        'public',
                    );
                }

                return $this->project_repository->update_item($project->id, [
                    'folder' => $folder,
                    'cover' => $this->store_cover($request, $sub_folder, $stamp, $is_link ? null : $folder),
                ]);
            });

            return response()->json([
                'message' => 'proyecto creado',
                'project' => $project,
            ], 201);
        } catch (ValidationException $e) {
            $this->clean_up($extracted);

            throw $e;
        } catch (\Exception $e) {
            $this->clean_up($extracted);

            Log::error('error al crear proyecto', ['exception' => $e]);

            return response()->json([
                'message' => 'error al crear proyecto',
                'error' => app()->isLocal() ? $e->getMessage() : null,
            ], 500);
        }
    }

    private function clean_up(?string $folder): void
    {
        // ojo: deleteDirectory('') borraría todo el disco
        if ($folder) {
            Storage::disk('public')->deleteDirectory($folder);
        }
    }

    private function extract_zip(UploadedFile $file, string $destination): string
    {
        $disk = Storage::disk('public');
        $zip = new ZipArchive();

        if ($zip->open($file->getRealPath()) !== true) {
            throw ValidationException::withMessages([
                'file' => 'No se pudo abrir el .zip: puede estar corrupto o no ser un zip.',
            ]);
        }

        try {
            $entry_point = null;

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entry = str_replace('\\', '/', (string) $zip->getNameIndex($i));

                // zip slip
                if ($entry === '' || str_starts_with($entry, '/')
                    || preg_match('#(^|/)\.\.(/|$)#', $entry)) {
                    throw ValidationException::withMessages([
                        'file' => "El .zip trae una ruta que no se puede escribir: {$entry}",
                    ]);
                }

                if (! $entry_point
                    && strtolower(basename($entry)) === 'index.html'
                    && substr_count($entry, '/') <= 1) {
                    $entry_point = $entry;
                }
            }

            if (! $entry_point) {
                throw ValidationException::withMessages([
                    'file' => 'El .zip no trae ningún index.html a la vista, así que no hay nada que abrir. Tiene que estar en la raíz del zip o dentro de una sola carpeta.',
                ]);
            }

            if (! $zip->extractTo($disk->path($destination))) {
                $disk->deleteDirectory($destination);

                throw new \RuntimeException('no se pudo descomprimir el zip del proyecto');
            }
        } finally {
            $zip->close();
        }

        return $destination . '/' . $entry_point;
    }

    private const COVER_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'];

    private function validate_cover(Request $request, $file): void
    {
        $rules = ['image', 'mimes:' . implode(',', self::COVER_EXTENSIONS), 'max:8192'];

        array_unshift($rules, $this->file_is_image($file) ? 'nullable' : 'required');

        $request->validate([
            'cover' => $rules,
        ], [
            'cover.required' => 'Este proyecto necesita una portada: es lo que se ve en la carpeta.',
            'cover.image'    => 'La portada tiene que ser una imagen.',
            'cover.mimes'    => 'La portada debe ser de tipo: :values.',
            'cover.max'      => 'La portada no puede pesar más de 8 MB.',
        ], [
            'cover' => 'portada',
        ]);
    }

    private function store_cover(Request $request, string $sub_folder, string $stamp, ?string $folder): ?string
    {
        if ($request->hasFile('cover')) {
            $cover = $request->file('cover');

            return $cover->storeAs(
                'projects/' . $sub_folder . '/covers',
                $stamp . '_cover.' . strtolower($cover->getClientOriginalExtension()),
                'public',
            );
        }

        return $this->file_is_image($request->file('file')) ? $folder : null;
    }

    private function file_is_image($file): bool
    {
        return $file
            && in_array(strtolower($file->getClientOriginalExtension()), self::COVER_EXTENSIONS, true);
    }

    private function allowed_extensions(TypeProject $type): array
    {
        return collect(explode(',', (string) $type->extension))
            ->map(fn ($extension) => strtolower(trim($extension, " \t\n\r\0\x0B.")))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function validate_link(Request $request): array
    {
        return $request->validate([
            'url' => 'required|string|url|max:2048',
        ], [
            'url.required' => 'Este tipo de proyecto necesita un enlace.',
            'url.url'      => 'El enlace no es una URL válida.',
            'url.max'      => 'El enlace no puede superar los :max caracteres.',
        ], [
            'url' => 'enlace',
        ]);
    }

    private function validate_file(Request $request, array $extensions, TypeProject $type): array
    {
        $file_extensions = array_values(array_diff($extensions, ['url']));

        $max = $this->max_upload_kilobytes();

        $rules = ['required', 'file', 'max:' . $max];

        if ($file_extensions) {
            $rules[] = 'extensions:' . implode(',', $file_extensions);
        }

        $listed = collect($file_extensions)->map(fn ($e) => '.' . $e)->implode(', ');

        return $request->validate([
            'file' => $rules,
        ], [
            'file.required'   => 'Debes subir el archivo del proyecto.',
            'file.max'        => 'El archivo no puede pesar más de ' . $this->human_size($max) . '.',
            'file.extensions' => $file_extensions
                ? "Un proyecto de tipo {$type->name} solo acepta: {$listed}."
                : 'El archivo no es válido.',
        ], [
            'file' => 'archivo',
        ]);
    }

    private const MAX_UPLOAD_BYTES = 512 * 1024 * 1024;

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
