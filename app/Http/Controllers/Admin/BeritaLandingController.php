<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pengumuman;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;
use App\Support\Pagination;

class BeritaLandingController extends Controller
{
    public function index(Request $request)
    {
        return view('shared.berita-landing.index', [
            'layout' => Auth::user()?->hasRole('kepala_sekolah') ? 'layouts.kepala-sekolah' : 'layouts.admin',
            'routePrefix' => Auth::user()?->hasRole('kepala_sekolah') ? 'kepala-sekolah.berita-landing' : 'admin.berita-landing',
            'articles' => Pengumuman::query()
                ->when($request->filled('search'), function ($query) use ($request) {
                    $search = trim($request->string('search')->toString());
                    $query->where(function ($articleQuery) use ($search) {
                        $articleQuery->where('judul', 'like', "%{$search}%")
                            ->orWhere('isi', 'like', "%{$search}%");
                    });
                })
                ->latest('tampil_mulai')->latest()->paginate(Pagination::perPage())->withQueryString(),
        ]);
    }

    public function create()
    {
        return $this->editorResponse();
    }

    public function edit(Pengumuman $berita)
    {
        return $this->editorResponse($berita);
    }

    public function store(Request $request)
    {
        Pengumuman::create($this->payload($request) + ['dibuat_oleh' => Auth::id()]);

        return back()->with('success', 'Berita atau artikel berhasil disimpan.');
    }

    public function update(Request $request, Pengumuman $berita)
    {
        $berita->update($this->payload($request, $berita));

        return back()->with('success', 'Berita atau artikel berhasil diperbarui.');
    }

    public function destroy(Pengumuman $berita)
    {
        $berita->delete();

        return back()->with('success', 'Berita landing page dihapus.');
    }

    private function payload(Request $request, ?Pengumuman $berita = null): array
    {
        $data = $request->validate([
            'judul' => ['required', 'string', 'max:180'],
            'isi' => ['required', 'string', 'max:5000'],
            'status' => ['required', 'in:draft,publish'],
            'tampil_mulai' => ['nullable', 'date'],
            'tampil_sampai' => ['nullable', 'date', 'after_or_equal:tampil_mulai'],
            'image' => ['nullable', 'image', 'max:3072'],
            'inline_images' => ['nullable', 'array', 'max:8'],
            'inline_images.*' => ['nullable', 'image', 'max:5120'],
            'photo_placement' => ['nullable', 'in:auto,manual'],
            'inline_placement' => ['nullable', 'array'],
            'inline_placement.*' => ['nullable', 'in:intro,middle,outro'],
            'inline_after' => ['nullable', 'array'],
            'inline_after.*' => ['nullable', 'integer', 'min:1', 'max:99'],
            'inline_captions' => ['nullable', 'array'],
            'inline_captions.*' => ['nullable', 'string', 'max:180'],
            'existing_inline_paths' => ['nullable', 'array'],
            'existing_inline_paths.*' => ['nullable', 'string'],
            'existing_inline_after' => ['nullable', 'array'],
            'existing_inline_after.*' => ['nullable', 'integer', 'min:1', 'max:99'],
            'existing_inline_placement' => ['nullable', 'array'],
            'existing_inline_placement.*' => ['nullable', 'in:intro,middle,outro'],
            'existing_inline_captions' => ['nullable', 'array'],
            'existing_inline_captions.*' => ['nullable', 'string', 'max:180'],
            'remove_inline_images' => ['nullable', 'array'],
            'remove_inline_images.*' => ['nullable', 'integer', 'min:0'],
        ]);

        $gallery = $berita?->gallery ?? [];
        if ($request->hasFile('image')) {
            $gallery = ['storage/' . $request->file('image')->store('berita-landing', 'public')];
        }

        $inlineMedia = collect();
        $removed = collect($data['remove_inline_images'] ?? [])->map(fn ($index) => (int) $index)->all();

        foreach ($data['existing_inline_paths'] ?? [] as $index => $path) {
            if (in_array($index, $removed, true)) {
                continue;
            }

            $inlineMedia->push([
                'type' => 'image',
                'path' => $path,
                'after_paragraph' => (int) ($data['existing_inline_after'][$index] ?? 1),
                'placement' => $data['existing_inline_placement'][$index] ?? 'middle',
                'caption' => trim((string) ($data['existing_inline_captions'][$index] ?? '')),
            ]);
        }

        foreach ($request->file('inline_images', []) as $index => $image) {
            if (! $image) {
                continue;
            }

            $inlineMedia->push([
                'type' => 'image',
                'path' => 'storage/' . $image->store('berita-landing', 'public'),
                'after_paragraph' => (int) ($data['inline_after'][$index] ?? 1),
                'placement' => $data['inline_placement'][$index] ?? 'middle',
                'caption' => trim((string) ($data['inline_captions'][$index] ?? '')),
            ]);
        }

        $paragraphCount = max(1, count(array_filter(preg_split('/\R\s*\R/', (string) $data['isi']))));
        $inlineMedia = ($data['photo_placement'] ?? 'auto') === 'auto'
            ? $this->placePhotosAutomatically($inlineMedia, (string) $data['isi'])
            : $inlineMedia->values()->map(function (array $media) use ($paragraphCount) {
                $media['after_paragraph'] = match ($media['placement'] ?? 'middle') {
                    'intro' => 1,
                    'outro' => $paragraphCount,
                    default => max(1, (int) ceil($paragraphCount / 2)),
                };

                return $media;
            });

        unset($data['image'], $data['photo_placement'], $data['inline_images'], $data['inline_after'], $data['inline_placement'], $data['inline_captions'], $data['existing_inline_paths'], $data['existing_inline_after'], $data['existing_inline_placement'], $data['existing_inline_captions'], $data['remove_inline_images']);
        $data['gallery'] = $gallery;
        $data['content_blocks'] = $inlineMedia->values()->all();
        $data['tampil_sampai'] = null;

        return $data;
    }

    private function editorResponse(?Pengumuman $article = null)
    {
        $isKepalaSekolah = Auth::user()?->hasRole('kepala_sekolah');
        $routePrefix = $isKepalaSekolah ? 'kepala-sekolah.berita-landing' : 'admin.berita-landing';

        return view('shared.berita-landing.editor', [
            'layout' => $isKepalaSekolah ? 'layouts.kepala-sekolah' : 'layouts.admin',
            'routePrefix' => $routePrefix,
            'article' => $article,
        ]);
    }

    private function placePhotosAutomatically(Collection $media, string $content): Collection
    {
        $paragraphs = collect(preg_split('/\R\s*\R/', $content))->filter()->values();
        $paragraphCount = max(1, $paragraphs->count());
        $photoCount = max(1, $media->count());

        return $media->values()->map(function (array $photo, int $index) use ($paragraphs, $paragraphCount, $photoCount) {
            $keywords = $this->photoKeywords(($photo['caption'] ?? '') . ' ' . pathinfo($photo['path'] ?? '', PATHINFO_FILENAME));
            $scores = $paragraphs->map(fn ($paragraph) => count(array_intersect($keywords, $this->photoKeywords($paragraph))));
            $bestScore = $scores->max() ?? 0;
            $fallbackPosition = min($paragraphCount, max(1, (int) ceil((($index + 1) * $paragraphCount) / ($photoCount + 1))));

            $photo['after_paragraph'] = $bestScore > 0
                ? ((int) $scores->search($bestScore, true) + 1)
                : $fallbackPosition;

            return $photo;
        });
    }

    private function photoKeywords(string $text): array
    {
        $ignored = ['yang', 'dan', 'atau', 'dari', 'untuk', 'dengan', 'pada', 'para', 'oleh', 'ini', 'itu', 'dalam', 'sebagai', 'agar', 'akan', 'foto', 'gambar', 'kegiatan'];

        return collect(preg_split('/[^\pL\pN]+/u', mb_strtolower($text)))
            ->filter(fn ($word) => mb_strlen($word) >= 3 && ! in_array($word, $ignored, true))
            ->unique()
            ->values()
            ->all();
    }
}
