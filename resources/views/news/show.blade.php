@extends('layouts.storefront')

@section('title', $article->seo_title ?: $article->title)

@section('styles')
    <meta name="description" content="{{ $article->seo_description ?: $article->excerpt }}">
    <style>.article-content { white-space: pre-wrap; overflow-wrap: anywhere; line-height: 1.8; }</style>
@endsection

@section('content')
@if ($preview)
    <div class="alert alert-warning"><i class="fas fa-eye mr-1"></i>Bản xem trước — bài viết hiện chưa hiển thị công khai.</div>
@endif
<article class="storefront-card bg-white p-3 p-md-5">
    <a href="{{ route('news.index') }}" class="small text-muted"><i class="fas fa-arrow-left mr-1"></i>Tin tức</a>
    @if ($article->category)
        <div class="mt-3"><span class="badge badge-primary">{{ $article->category->name }}</span></div>
    @endif
    <h1 class="mt-2 mb-2">{{ $article->title }}</h1>
    <div class="text-muted mb-4">{{ $article->published_at?->format('d/m/Y H:i') ?? 'Bản xem trước' }}</div>
    @if ($article->featured_image)
        <img src="{{ Storage::disk('public')->url($article->featured_image) }}" alt="{{ $article->title }}" class="img-fluid rounded w-100 mb-4">
    @endif
    @if ($article->excerpt)
        <p class="lead">{{ $article->excerpt }}</p>
    @endif
    <div class="article-content">{{ $article->content }}</div>
</article>
@endsection
