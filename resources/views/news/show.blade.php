@extends('layouts.storefront')

@section('title', $article->metaTitle())

@section('styles')
    <meta name="description" content="{{ $article->metaDescription() }}">
    <style>
        .article-content { overflow-wrap: anywhere; line-height: 1.8; }
        .article-content img { max-width: 100%; height: auto; border-radius: 6px; }
        .article-content table { width: 100%; margin-bottom: 1rem; border-collapse: collapse; }
        .article-content th, .article-content td { border: 1px solid #dee2e6; padding: .5rem; }
        .article-content blockquote { border-left: 4px solid #dee2e6; padding-left: 1rem; color: #6c757d; }
        .article-content pre { white-space: pre-wrap; background: #f5f7fb; padding: .75rem; border-radius: 6px; }
    </style>
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
    <h1 class="mt-2 mb-2">{!! $article->titleHtml() !!}</h1>
    <div class="text-muted mb-4">{{ $article->published_at?->format('d/m/Y H:i') ?? 'Bản xem trước' }}</div>
    @if ($article->featured_image)
        <img src="{{ Storage::disk('public')->url($article->featured_image) }}" alt="{{ $article->plainTitle() }}" class="img-fluid rounded w-100 mb-4">
    @endif
    @if ($article->excerpt)
        <p class="lead">{!! $article->excerptHtml() !!}</p>
    @endif
    {{-- titleHtml() / excerptHtml() / contentHtml() run the allow-list sanitizer, so they are safe to print unescaped. --}}
    <div class="article-content">{!! $article->contentHtml() !!}</div>
</article>
@endsection
