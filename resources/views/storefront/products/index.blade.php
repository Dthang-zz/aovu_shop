@extends('layouts.storefront')

@section('title', 'Sản phẩm')

@section('content')
    @if ($banners->isNotEmpty())
        <div id="storefront-banners" class="carousel slide mb-4 overflow-hidden rounded shadow-sm" data-ride="carousel">
            <ol class="carousel-indicators">
                @foreach ($banners as $banner)
                    <li data-target="#storefront-banners" data-slide-to="{{ $loop->index }}" @class(['active' => $loop->first])></li>
                @endforeach
            </ol>
            <div class="carousel-inner">
                @foreach ($banners as $banner)
                    <div @class(['carousel-item', 'active' => $loop->first])>
                        @if ($banner->link_url)<a href="{{ $banner->link_url }}">@endif
                        <img src="{{ Storage::disk('public')->url($banner->image_path) }}" class="d-block w-100 storefront-banner" alt="{{ $banner->title }}">
                        @if ($banner->link_url)</a>@endif
                    </div>
                @endforeach
            </div>
            @if ($banners->count() > 1)
                <a class="carousel-control-prev" href="#storefront-banners" role="button" data-slide="prev"><span class="carousel-control-prev-icon" aria-hidden="true"></span><span class="sr-only">Trước</span></a>
                <a class="carousel-control-next" href="#storefront-banners" role="button" data-slide="next"><span class="carousel-control-next-icon" aria-hidden="true"></span><span class="sr-only">Tiếp</span></a>
            @endif
        </div>
    @endif
    <div class="mb-4">
        <h1 class="h3 font-weight-bold">Sản phẩm</h1>
        <p class="text-muted mb-0">Khám phá bộ sưu tập mới nhất từ AOVU.</p>
    </div>
    <div class="row">
        @forelse ($products as $product)
            <div class="col-6 col-md-4 col-lg-3 mb-4">
                <div class="card storefront-card h-100">
                    <a href="{{ route('shop.products.show', $product) }}" class="position-relative">
                        @if ($product->hinh_anh)
                            <img class="card-img-top storefront-image" src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($product->hinh_anh) }}" alt="{{ $product->ten_san_pham }}">
                        @else
                            <div class="storefront-image bg-light d-flex align-items-center justify-content-center text-muted"><i class="far fa-image fa-3x"></i></div>
                        @endif
                    </a>
                    <div class="card-body d-flex flex-column">
                        <a href="{{ route('shop.products.show', $product) }}" class="font-weight-bold text-dark">{{ $product->ten_san_pham }}</a>
                        <div class="mt-2">
                            <span class="h5 font-weight-bold">{{ number_format((float) $product->gia_san_pham, 0, ',', '.') }} ₫</span>
                        </div>
                        <a href="{{ route('shop.products.show', $product) }}" class="btn btn-outline-primary btn-sm mt-auto">Xem sản phẩm</a>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12"><div class="alert alert-light">Hiện chưa có sản phẩm đang kinh doanh.</div></div>
        @endforelse
    </div>
@endsection
