<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewsCategory;
use App\Models\Post;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PostController extends Controller
{
    public function index(Request $request): View
    {
        $query = Post::query()->with('category');

        if ($request->filled('q')) {
            $term = trim((string) $request->query('q'));
            $query->where(function (Builder $builder) use ($term) {
                $builder->where('tieu_de', 'like', "%{$term}%")
                    ->orWhere('mo_ta_ngan', 'like', "%{$term}%");
            });
        }
        
        if (in_array($request->query('status'), ['draft', 'published', 'hidden'], true)) {
            $query->where('trang_thai', $request->query('status'));
        }

         if ($request->filled('category_id')) {
            $query->where('danh_muc_bai_viet_id', $request->integer('category_id'));
        }

        return view('admin.posts.index', [
            'posts' => $query->latest()->paginate(10)->withQueryString(),
            'categories' => NewsCategory::query()->orderBy('ten_danh_muc')->get(),
            'totalCount' => Post::count(),
            'publishedCount' => Post::where('trang_thai', 'published')->count(),
            'draftCount' => Post::where('trang_thai', 'draft')->count(),
            'hiddenCount' => Post::where('trang_thai', 'hidden')->count(),
        ]);
    }

     public function create(): View
    {
        return view('admin.posts.form', [
            'post' => new Post(['trang_thai' => 'draft', 'tac_gia' => 'Ban biên tập AOVU']),
            'categories' => NewsCategory::where('trang_thai', true)->orderBy('ten_danh_muc')->get(),
            'formTitle' => 'Thêm bài viết',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        $data['anh_dai_dien'] = $request->file('anh_dai_dien')?->store('news', 'public');
        $data['slug'] = $this->uniqueSlug($data['slug'] ?: $data['tieu_de']);
        $data['published_at'] = $data['trang_thai'] === 'published'
            ? ($request->input('published_at') ?: now()) : null;
        Post::create($data);

        return redirect()->route('admin.posts.index')->with('success', 'Đã tạo bài viết thành công.');
    }

    public function edit(Post $post): View
    {
        return view('admin.posts.form', [
            'post' => $post,
            'categories' => NewsCategory::where('trang_thai', true)->orderBy('ten_danh_muc')->get(),
            'formTitle' => 'Chỉnh sửa bài viết',
        ]);
    }

    public function update(Request $request, Post $post): RedirectResponse
    {
        $data = $this->validatedData($request, $post);
        $data['slug'] = $this->uniqueSlug($data['slug'] ?: $data['tieu_de'], $post->id);
        $newImage = $request->file('anh_dai_dien');
        if ($newImage) {
            $newPath = $newImage->store('news', 'public');
            if ($post->anh_dai_dien) {
                Storage::disk('public')->delete($post->anh_dai_dien);
            }
            $data['anh_dai_dien'] = $newPath;
        } else {
            unset($data['anh_dai_dien']);
        }
        $data['published_at'] = $data['trang_thai'] === 'published'
            ? ($request->input('published_at') ?: ($post->published_at ?: now())) : null;
        $post->update($data);

        return redirect()->route('admin.posts.index')->with('success', 'Đã cập nhật bài viết.');
    }

    public function destroy(Post $post): RedirectResponse
    {
        if ($post->anh_dai_dien) {
            Storage::disk('public')->delete($post->anh_dai_dien);
        }
        $post->delete();

        return redirect()->route('admin.posts.index')->with('success', 'Đã xóa bài viết.');
    }

    private function validatedData(Request $request, ?Post $post = null): array
    {
        return $request->validate([
            'tieu_de' => ['required', 'string', 'max:200'],
            'slug' => ['nullable', 'string', 'max:220', Rule::unique('bai_viets', 'slug')->ignore($post?->id)],
            'mo_ta_ngan' => ['nullable', 'string', 'max:500'],
            'noi_dung' => ['required', 'string', 'min:20'],
            'anh_dai_dien' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'danh_muc_bai_viet_id' => ['nullable', 'integer', 'exists:danh_muc_bai_viets,id'],
            'tac_gia' => ['nullable', 'string', 'max:120'],
            'tu_khoa_seo' => ['nullable', 'string', 'max:255'],
            'mo_ta_seo' => ['nullable', 'string', 'max:320'],
            'trang_thai' => ['required', Rule::in(['draft', 'published', 'hidden'])],
            'published_at' => ['nullable', 'date'],
        ], [
            'tieu_de.required' => 'Vui lòng nhập tiêu đề bài viết.',
            'noi_dung.required' => 'Vui lòng nhập nội dung bài viết.',
            'noi_dung.min' => 'Nội dung bài viết cần có ít nhất 20 ký tự.',
            'anh_dai_dien.image' => 'Ảnh đại diện phải là một tệp hình ảnh.',
            'anh_dai_dien.max' => 'Ảnh đại diện không được vượt quá 4 MB.',
        ]);
    }

    private function uniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: 'bai-viet';
        $slug = $base;
        $suffix = 2;
        while (Post::where('slug', $slug)->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base . '-' . $suffix++;
        }
        return $slug;
    }
}