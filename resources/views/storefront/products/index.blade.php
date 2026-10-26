@extends('layouts.storefront')

@section('title', 'Sản phẩm')

@section('content')
    <div class="mb-4">
        <h1 class="h3 font-weight-bold">Sản phẩm</h1>
        <p class="text-muted mb-0">Khám phá bộ sưu tập mới nhất từ AOVU.</p>
    </div>
    <div class="row">
        @forelse ($products as $product)
            @php($quote = $quotes[$product->id])
            <div class="col-6 col-md-4 col-lg-3 mb-4">
                <div class="card storefront-card h-100">
                    <a href="{{ route('shop.products.show', $product) }}" class="position-relative">
                        @if ($product->hinh_anh)
                            <img class="card-img-top storefront-image" src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($product->hinh_anh) }}" alt="{{ $product->ten_san_pham }}">
                        @else
                            <div class="storefront-image bg-light d-flex align-items-center justify-content-center text-muted"><i class="far fa-image fa-3x"></i></div>
                        @endif
                        @if ($quote['promotion'])
                            <span class="badge badge-danger position-absolute" style="top:10px;left:10px">-{{ rtrim(rtrim(number_format($quote['discount_percent'], 2, ',', '.'), '0'), ',') }}%</span>
                        @endif
                    </a>
                    <div class="card-body d-flex flex-column">
                        <a href="{{ route('shop.products.show', $product) }}" class="font-weight-bold text-dark">{{ $product->ten_san_pham }}</a>
                        <div class="mt-2">
                            @if ($quote['promotion'])
                                <span class="h5 text-danger font-weight-bold">{{ number_format($quote['sale_price'], 0, ',', '.') }} ₫</span>
                                <small class="price-old ml-1">{{ number_format($quote['original_price'], 0, ',', '.') }} ₫</small>
                            @else
                                <span class="h5 font-weight-bold">{{ number_format($quote['original_price'], 0, ',', '.') }} ₫</span>
                            @endif
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
