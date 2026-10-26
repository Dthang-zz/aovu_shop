@extends('layouts.storefront')

@section('title', 'Tin tức')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h2 mb-1">Tin tức &amp; cảm hứng</h1>
        <p class="text-muted mb-0">Cập nhật xu hướng, mẹo phối đồ và tin tức từ AOVU Shop.</p>
    </div>
    <form method="GET" action="{{ route('news.index') }}" class="mt-3 mt-md-0">
        <label for="category" class="sr-only">Lọc theo danh mục</label>
        <select id="category" name="category" class="form-control" onchange="this.form.submit()">
            <option value="">Tất cả chủ đề</option>
            @foreach ($categories as $category)
                <option value="{{ $category->slug }}" @selected($selectedCategory === $category->slug)>{{ $category->name }}</option>
            @endforeach
        </select>
    </form>
</div>

@if ($articles->isEmpty())
    <div class="storefront-card bg-white text-center p-5 text-muted">Chưa có bài viết được xuất bản trong chủ đề này.</div>
@else
    <div class="row">
        @foreach ($articles as $article)
            <div class="col-md-6 col-lg-4 mb-4">
                <article class="storefront-card bg-white h-100 overflow-hidden">
                    <a href="{{ route('news.show', $article->slug) }}" class="d-block">
                        @if ($article->featured_image)
                            <img src="{{ Storage::disk('public')->url($article->featured_image) }}" alt="{{ $article->title }}" class="storefront-image w-100">
                        @else
                            <div class="storefront-image bg-light d-flex align-items-center justify-content-center text-muted"><i class="far fa-newspaper fa-3x"></i></div>
                        @endif
                    </a>
                    <div class="p-3">
                        @if ($article->category)
                            <a class="small text-primary font-weight-bold" href="{{ route('news.index', ['category' => $article->category->slug]) }}">{{ $article->category->name }}</a>
                        @endif
                        <div class="small text-muted mt-1">{{ $article->published_at?->format('d/m/Y') }}</div>
                        <h2 class="h5 mt-2"><a class="text-dark" href="{{ route('news.show', $article->slug) }}">{{ $article->title }}</a></h2>
                        @if ($article->excerpt)
                            <p class="text-muted mb-0">{{ $article->excerpt }}</p>
                        @endif
                    </div>
                </article>
            </div>
        @endforeach
    </div>
    <div class="d-flex justify-content-center">{{ $articles->links() }}</div>
@endif
@endsection
