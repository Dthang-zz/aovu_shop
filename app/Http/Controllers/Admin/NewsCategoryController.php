<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewsCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class NewsCategoryController extends Controller
{
    public function index(): View
    {
        return view('admin.posts.categories', [
            'categories' => NewsCategory::withCount('posts')->orderBy('ten_danh_muc')->paginate(15),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['slug'] = $this->makeSlug($data['slug'] ?: $data['ten_danh_muc']);
        NewsCategory::create($data);
        return back()->with('success', 'Đã thêm danh mục tin tức.');
    }

    public function update(Request $request, NewsCategory $newsCategory): RedirectResponse
    {
        $data = $this->validated($request, $newsCategory);
        $data['slug'] = $this->makeSlug($data['slug'] ?: $data['ten_danh_muc'], $newsCategory->id);
        $newsCategory->update($data);
        return back()->with('success', 'Đã cập nhật danh mục tin tức.');
    }

    public function destroy(NewsCategory $newsCategory): RedirectResponse
    {
        if ($newsCategory->posts()->exists()) {
            return back()->with('error', 'Danh mục đang có bài viết. Hãy chuyển bài viết sang danh mục khác trước khi xóa.');
        }
        $newsCategory->delete();
        return back()->with('success', 'Đã xóa danh mục tin tức.');
    }

    private function validated(Request $request, ?NewsCategory $category = null): array
    {
        return $request->validate([
            'ten_danh_muc' => ['required', 'string', 'max:120', Rule::unique('danh_muc_bai_viets', 'ten_danh_muc')->ignore($category?->id)],
            'slug' => ['nullable', 'string', 'max:150', Rule::unique('danh_muc_bai_viets', 'slug')->ignore($category?->id)],
            'mo_ta' => ['nullable', 'string', 'max:1000'],
            'trang_thai' => ['required', 'boolean'],
        ]);
    }

    private function makeSlug(string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: 'danh-muc';
        $slug = $base;
        $suffix = 2;
        while (NewsCategory::where('slug', $slug)->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base . '-' . $suffix++;
        }
        return $slug;
    }
}