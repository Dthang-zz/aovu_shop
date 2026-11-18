<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewsCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class NewsCategoryController extends Controller
{
    public function index(): View
    {
        return view('admin.news-categories.index', [
            'categories' => NewsCategory::query()->withCount('articles')->orderBy('name')->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('admin.news-categories.form', ['category' => new NewsCategory]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateCategory($request);
        $data['slug'] = $this->uniqueSlug($data['slug'] ?: $data['name']);
        $data['is_active'] = $request->boolean('is_active');
        NewsCategory::query()->create($data);

        return redirect()->route('admin.news-categories.index')->with('success', 'Đã tạo danh mục tin tức.');
    }

    public function edit(NewsCategory $newsCategory): View
    {
        return view('admin.news-categories.form', ['category' => $newsCategory]);
    }

    public function update(Request $request, NewsCategory $newsCategory): RedirectResponse
    {
        $data = $this->validateCategory($request, $newsCategory);
        $data['slug'] = $this->uniqueSlug($data['slug'] ?: $data['name'], $newsCategory);
        $data['is_active'] = $request->boolean('is_active');
        $newsCategory->update($data);

        return redirect()->route('admin.news-categories.index')->with('success', 'Đã cập nhật danh mục tin tức.');
    }

    public function destroy(NewsCategory $newsCategory): RedirectResponse
    {
        if ($newsCategory->articles()->exists()) {
            return redirect()->route('admin.news-categories.index')
                ->with('error', 'Không thể xóa danh mục đang được bài viết sử dụng.');
        }

        $newsCategory->delete();

        return redirect()->route('admin.news-categories.index')->with('success', 'Đã xóa danh mục tin tức.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateCategory(Request $request, ?NewsCategory $category = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => [
                'nullable',
                'string',
                'max:140',
                'alpha_dash:ascii',
                Rule::unique('news_categories', 'slug')->ignore($category),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);
    }

    private function uniqueSlug(string $value, ?NewsCategory $category = null): string
    {
        $baseSlug = Str::slug($value);
        $baseSlug = $baseSlug !== '' ? $baseSlug : 'danh-muc';
        $slug = $baseSlug;
        $suffix = 2;

        while (NewsCategory::query()->where('slug', $slug)->when($category, fn ($query) => $query->where('id', '!=', $category->getKey()))->exists()) {
            $slug = "{$baseSlug}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
