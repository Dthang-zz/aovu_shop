<?php

namespace App\Http\Controllers;

use App\Models\NewsArticle;
use App\Models\NewsCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NewsController extends Controller
{
    public function index(Request $request): View
    {
        $categorySlug = $request->query('category');
        $articles = NewsArticle::query()
            ->with('category')
            ->where('status', NewsArticle::STATUS_PUBLISHED)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->when($categorySlug, function (Builder $query, string $slug): void {
                $query->whereHas('category', fn (Builder $categoryQuery) => $categoryQuery
                    ->where('slug', $slug)
                    ->where('is_active', true));
            })
            ->orderByDesc('published_at')
            ->paginate(12)
            ->withQueryString();

        return view('news.index', [
            'articles' => $articles,
            'categories' => NewsCategory::query()->where('is_active', true)->orderBy('name')->get(),
            'selectedCategory' => $categorySlug,
        ]);
    }

    public function show(string $slug): View
    {
        $article = NewsArticle::query()
            ->with('category')
            ->where('slug', $slug)
            ->where('status', NewsArticle::STATUS_PUBLISHED)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->firstOrFail();

        return view('news.show', ['article' => $article, 'preview' => false]);
    }
}
