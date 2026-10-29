<?php

namespace App\Http\Controllers;

use App\Models\NewsCategory;
use App\Models\Post;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class NewsController extends Controller
{
    public function index(Request $request): View
    {
        $query = Post::published()->with('category');
        if ($request->filled('q')) {
            $term = trim((string) $request->query('q'));
            $query->where(function ($builder) use ($term) {
                $builder->where('tieu_de', 'like', "%{$term}%")
                    ->orWhere('mo_ta_ngan', 'like', "%{$term}%");
            });
        }
        if ($request->filled('category')) {
            $query->whereHas('category', fn ($builder) => $builder->where('slug', $request->query('category')));
        }

        return view('news.index', [
            'posts' => $query->orderByDesc('published_at')->paginate(9)->withQueryString(),
            'categories' => NewsCategory::where('trang_thai', true)->orderBy('ten_danh_muc')->get(),
        ]);
    }

     public function show(string $slug): View
    {
        $post = Post::published()->with('category')->where('slug', $slug)->firstOrFail();
        $post->increment('luot_xem');

        return view('news.show', [
            'post' => $post,
            'relatedPosts' => Post::published()->with('category')
                ->where('id', '!=', $post->id)
                ->when($post->danh_muc_bai_viet_id, fn ($query) => $query->where('danh_muc_bai_viet_id', $post->danh_muc_bai_viet_id))
                ->latest('published_at')->limit(3)->get(),
        ]);
    }
}
