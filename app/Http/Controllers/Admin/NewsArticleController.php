<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewsArticle;
use App\Models\NewsCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class NewsArticleController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'category_id' => ['nullable', 'integer', 'exists:news_categories,id'],
            'status' => ['nullable', Rule::in(array_keys(NewsArticle::STATUSES))],
        ]);

        $articles = NewsArticle::query()
            ->with('category')
            ->when($filters['q'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('excerpt', 'like', "%{$search}%");
                });
            })
            ->when($filters['category_id'] ?? null, fn (Builder $query, int $categoryId) => $query->where('category_id', $categoryId))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.news.index', [
            'articles' => $articles,
            'categories' => NewsCategory::query()->orderBy('name')->get(),
            'statuses' => NewsArticle::STATUSES,
            'counts' => NewsArticle::query()->selectRaw('status, COUNT(*) as aggregate')->groupBy('status')->pluck('aggregate', 'status'),
        ]);
    }

    public function create(): View
    {
        return view('admin.news.form', [
            'article' => new NewsArticle,
            'categories' => NewsCategory::query()->orderBy('name')->get(),
            'statuses' => NewsArticle::STATUSES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateArticle($request);
        $data['slug'] = $this->uniqueSlug($data['slug'] ?: $data['title']);
        $imagePath = $this->storeImage($request->file('featured_image'));
        $data['featured_image'] = $imagePath;
        $data['published_at'] = $data['status'] === NewsArticle::STATUS_PUBLISHED ? now() : null;

        try {
            $article = NewsArticle::query()->create($data);
        } catch (Throwable $exception) {
            if ($imagePath !== null) {
                Storage::disk('public')->delete($imagePath);
            }

            throw $exception;
        }

        return redirect()->route('admin.news.index')->with('success', 'Đã tạo bài viết.');
    }

    public function edit(NewsArticle $article): View
    {
        return view('admin.news.form', [
            'article' => $article,
            'categories' => NewsCategory::query()->orderBy('name')->get(),
            'statuses' => NewsArticle::STATUSES,
        ]);
    }

    public function update(Request $request, NewsArticle $article): RedirectResponse
    {
        $data = $this->validateArticle($request, $article);
        $previousImage = $article->featured_image;
        $data['slug'] = $this->uniqueSlug($data['slug'] ?: $data['title'], $article);
        $imagePath = $this->storeImage($request->file('featured_image'));
        $data['featured_image'] = $imagePath ?? $previousImage;
        $data['published_at'] = $data['status'] === NewsArticle::STATUS_PUBLISHED
            ? ($article->published_at ?? now())
            : null;

        try {
            DB::transaction(fn () => $article->update($data));
        } catch (Throwable $exception) {
            if ($imagePath !== null) {
                Storage::disk('public')->delete($imagePath);
            }

            throw $exception;
        }

        if ($imagePath !== null && $previousImage !== null) {
            Storage::disk('public')->delete($previousImage);
        }

        return redirect()->route('admin.news.index')->with('success', 'Đã cập nhật bài viết.');
    }

    public function destroy(NewsArticle $article): RedirectResponse
    {
        $imagePath = $article->featured_image;
        $article->delete();

        if ($imagePath !== null) {
            Storage::disk('public')->delete($imagePath);
        }

        return redirect()->route('admin.news.index')->with('success', 'Đã xóa bài viết.');
    }

    public function preview(NewsArticle $article): View
    {
        return view('news.show', [
            'article' => $article->load('category'),
            'preview' => true,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateArticle(Request $request, ?NewsArticle $article = null): array
    {
        return $request->validate([
            'category_id' => ['nullable', 'integer', 'exists:news_categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'alpha_dash:ascii',
                Rule::unique('news_articles', 'slug')->ignore($article),
            ],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'content' => ['required', 'string'],
            'featured_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:500'],
            'status' => ['required', Rule::in(array_keys(NewsArticle::STATUSES))],
        ]);
    }

    private function storeImage(?UploadedFile $image): ?string
    {
        if ($image === null) {
            return null;
        }

        $path = $image->store('news', 'public');

        if ($path === false) {
            throw new RuntimeException('Không thể lưu ảnh bài viết.');
        }

        return $path;
    }

    private function uniqueSlug(string $value, ?NewsArticle $article = null): string
    {
        $baseSlug = Str::slug($value);
        $baseSlug = $baseSlug !== '' ? $baseSlug : 'bai-viet';
        $slug = $baseSlug;
        $suffix = 2;

        while (NewsArticle::query()->where('slug', $slug)->when($article, fn (Builder $query) => $query->where('id', '!=', $article->getKey()))->exists()) {
            $slug = "{$baseSlug}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
