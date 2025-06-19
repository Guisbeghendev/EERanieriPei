<?php

namespace App\Http\Controllers\Fotografo;

use App\Http\Controllers\Controller;
use App\Models\Gallery;
use App\Models\Group;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Jobs\ProcessImageWithGd;
use App\Models\Image;

class GalleryController extends Controller
{
    /**
     * Exibe a lista de galerias com filtros.
     */
    public function index(Request $request)
    {
        $filters = $request->only(['group_id', 'event_date']);

        $galleriesQuery = Gallery::where('user_id', Auth::id())
            ->with('groups');

        if (!empty($filters['group_id'])) {
            $galleriesQuery->whereHas('groups', function ($query) use ($filters) {
                $query->where('groups.id', $filters['group_id']);
            });
        }

        if (!empty($filters['event_date'])) {
            $galleriesQuery->whereDate('event_date', $filters['event_date']);
        }

        $galleries = $galleriesQuery->orderBy('event_date', 'desc')->get();

        $groups = Group::orderBy('name')->get(['id', 'name']);

        return Inertia::render('Fotografo/Galleries/ListGalleries', [ // Mantido ListGalleries
            'galleries' => $galleries,
            'groups' => $groups,
            'filters' => $filters,
        ]);
    }

    /**
     * Exibe o formulário para criar uma nova galeria.
     */
    public function create()
    {
        Gate::authorize('create', Gallery::class);
        $groups = Group::orderBy('name')->get(['id', 'name']);
        return Inertia::render('Fotografo/Galleries/Create', [
            'groups' => $groups,
        ]);
    }

    /**
     * Armazena uma nova galeria no banco de dados.
     */
    public function store(Request $request)
    {
        Gate::authorize('create', Gallery::class);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'event_date' => ['nullable', 'date'],
            'selected_group_ids' => ['required', 'array'],
            'selected_group_ids.*' => ['integer', 'exists:groups,id'],
            'selected_watermark' => ['nullable', 'string', 'max:255'],
        ]);

        $gallery = new Gallery();
        $gallery->user_id = Auth::id();
        $gallery->title = $validated['title'];
        $gallery->description = $validated['description'];
        $gallery->event_date = $validated['event_date'];
        $gallery->watermark_file_used = $validated['selected_watermark'];
        $gallery->save();

        $gallery->groups()->attach($validated['selected_group_ids']);

        // Respostas JSON para Inertia geralmente não precisam de ->withStatus(303)
        // O Inertia.js lida com respostas JSON automaticamente.
        return response()->json([
            'success_message' => 'Galeria "' . $gallery->title . '" criada com sucesso!',
            'gallery_id' => $gallery->id
        ], 201);
    }

    /**
     * Exibe a página de upload de imagens para uma galeria específica.
     * Esta é a view para o componente UploadImg.vue.
     */
    public function uploadImages(Gallery $gallery)
    {
        Gate::authorize('manage-gallery', $gallery);
        return Inertia::render('Fotografo/Galleries/UploadImg', [
            'gallery' => $gallery,
        ]);
    }

    /**
     * Lida com o upload de imagens individuais para uma galeria específica.
     *
     * @param Request $request
     * @param Gallery $gallery
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeImage(Request $request, Gallery $gallery)
    {
        Gate::authorize('manage-gallery', $gallery);

        $request->validate([
            'file' => ['required', 'image', 'max:20480'], // Valida o arquivo recebido (20MB)
        ]);

        $uploadedFile = $request->file('file');
        $originalFileName = $uploadedFile->getClientOriginalName();

        $uniqueFilename = Str::uuid() . '_' . Str::slug(pathinfo($originalFileName, PATHINFO_FILENAME)) . '.' . $uploadedFile->getClientOriginalExtension();

        $tempDir = 'uploads/temp_images';
        Storage::disk('local')->makeDirectory($tempDir);

        $tempRelativePath = Storage::disk('local')->putFileAs($tempDir, $uploadedFile, $uniqueFilename);

        $watermarkFile = $request->header('X-Watermark-File');

        ProcessImageWithGd::dispatch($tempRelativePath, (int) $gallery->id, $originalFileName, $watermarkFile);

        return response()->json(['success' => true, 'message' => 'Imagem enfileirada para processamento!']);
    }


    /**
     * Exibe o formulário para editar uma galeria existente.
     */
    public function edit(Gallery $gallery)
    {
        Gate::authorize('update', $gallery);
        $gallery->load('groups');
        $groups = Group::orderBy('name')->get(['id', 'name']);
        return Inertia::render('Fotografo/Galleries/Edit', [
            'gallery' => $gallery,
            'groups' => $groups,
            'selectedGroupIds' => $gallery->groups->pluck('id')->toArray(),
        ]);
    }

    /**
     * Atualiza uma galeria existente no banco de dados.
     */
    public function update(Request $request, Gallery $gallery)
    {
        Gate::authorize('update', $gallery);
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'event_date' => ['nullable', 'date'],
            'selected_group_ids' => ['required', 'array'],
            'selected_group_ids.*' => ['integer', 'exists:groups,id'],
        ]);

        $gallery->title = $validated['title'];
        $gallery->description = $validated['description'];
        $gallery->event_date = $validated['event_date'];
        $gallery->save();
        $gallery->groups()->sync($validated['selected_group_ids']);

        // Adicionado ->withStatus(303) para que o Inertia.js lide corretamente com o redirecionamento após PUT.
        return redirect()->route('fotografo.dashboard')
            ->with('success', 'Galeria atualizada com sucesso!')->withStatus(303);
    }

    /**
     * Exibe os detalhes de uma galeria e suas imagens para preview.
     * Mapeia para a rota GET fotografo/galleries/{gallery}/preview
     */
    public function previewImages(Gallery $gallery) // Renomeado de 'show' para 'previewImages'
    {
        // Garante que o fotógrafo logado é o dono da galeria
        if ($gallery->user_id !== Auth::id()) {
            abort(403); // Ação não autorizada
        }

        // Carrega as imagens relacionadas à galeria
        $gallery->load('images');

        // Renderiza o componente PreviewImages.vue
        return Inertia::render('Fotografo/Galleries/PreviewImages', [
            'gallery' => $gallery,
        ]);
    }

    /**
     * Remove uma galeria do banco de dados.
     */
    public function destroy(Gallery $gallery)
    {
        Gate::authorize('delete', $gallery);

        // TODO: Adicionar lógica para deletar imagens e pastas fisicamente
        // Isso pode ser feito aqui ou em um Observer para a Model Gallery.
        // Se onDelete('cascade') estiver na migração de 'images', os registros do DB serão deletados.
        // Para arquivos físicos, você precisaria iterar sobre gallery->images e deletar de Storage::disk('public')
        // Exemplo:
        // foreach ($gallery->images as $image) {
        //     Storage::disk('public')->delete([
        //         $image->path_original,
        //         $image->path_thumb,
        //         $image->metadata['watermarked_path'] ?? null
        //     ]);
        // }
        // Storage::disk('public')->deleteDirectory('galleries/' . $gallery->id); // Deleta a pasta da galeria

        $gallery->delete();
        // Adicionado ->withStatus(303) para que o Inertia.js lide corretamente com o redirecionamento após DELETE.
        return redirect()->route('fotografo.galleries.index')
            ->with('success', 'Galeria excluída com sucesso!')->withStatus(303);
    }

    /**
     * Remove uma imagem específica de uma galeria.
     *
     * @param Gallery $gallery
     * @param Image $image
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroyImage(Gallery $gallery, Image $image)
    {
        // Autoriza que o usuário pode gerenciar a galeria à qual a imagem pertence.
        // Isso implica que ele pode deletar as imagens dela.
        Gate::authorize('manage-gallery', $gallery);

        // Garante que a imagem pertence à galeria correta
        if ($image->gallery_id !== $gallery->id) {
            abort(404); // Imagem não encontrada na galeria especificada
        }

        // Deleta os arquivos físicos da imagem
        $disk = Storage::disk('public');
        $filesToDelete = [];

        if ($image->path_original) {
            $filesToDelete[] = $image->path_original;
        }
        if ($image->path_thumb) {
            $filesToDelete[] = $image->path_thumb;
        }
        if (isset($image->metadata['watermarked_path']) && $image->metadata['watermarked_path']) {
            $filesToDelete[] = $image->metadata['watermarked_path'];
        }

        if (!empty($filesToDelete)) {
            $disk->delete($filesToDelete);
        }

        // Deleta o registro da imagem no banco de dados
        $image->delete();

        // Adiciona uma flash message para o Inertia.js exibir
        session()->flash('success', 'Imagem excluída com sucesso!');

        // Retorna um redirecionamento para a página anterior (PreviewImages.vue)
        // Inertia.js lida com o redirecionamento e a flash message automaticamente.
        return redirect()->back(); // Sem ->withStatus(303) aqui, pois redirect()->back() já é um 302/303 pelo Inertia.
    }


    /**
     * Retorna a lista de marcas d'água disponíveis.
     */
    public function getAvailableWatermarks(Request $request)
    {
        $watermarks = collect(Storage::disk('public')->files('watermarks'))
            ->map(function ($file) {
                return ['name' => basename($file), 'path' => Storage::url($file)];
            })->toArray();

        return response()->json($watermarks);
    }
}
