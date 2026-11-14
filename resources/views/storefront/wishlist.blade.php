@extends('layouts.storefront')

@section('title', 'Sản phẩm yêu thích')

@section('content')
    <div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
        <div>
            <h1 class="h3 font-weight-bold mb-1">
                <i class="fas fa-heart text-danger mr-2"></i>Sản phẩm yêu thích
            </h1>
            <p class="text-muted mb-0">Các mẫu thời trang bạn đã lưu để xem lại hoặc đặt mua sau.</p>
        </div>
        <div class="mt-2 mt-sm-0">
            <span class="badge badge-light border px-3 py-2 font-weight-normal text-muted">
                Tổng cộng: <strong class="text-dark">{{ $products->count() }}</strong> sản phẩm
            </span>
        </div>
    </div>

    @if ($products->isEmpty())
        <div class="card storefront-card text-center py-5">
            <div class="card-body">
                <div class="mb-3 text-danger" style="opacity: 0.35;">
                    <i class="fas fa-heart-broken fa-5x"></i>
                </div>
                <h4 class="font-weight-bold mb-2">Danh sách yêu thích đang trống</h4>
                <p class="text-muted mb-4 max-w-md mx-auto" style="max-width: 480px;">
                    Bạn chưa lưu sản phẩm nào. Hãy bấm vào biểu tượng trái tim <i class="fas fa-heart text-danger"></i> trên sản phẩm để lưu lại và theo dõi những mẫu quần áo ưng ý nhất!
                </p>
                <a href="{{ route('shop.products.index') }}" class="btn btn-primary px-4 py-2">
                    <i class="fas fa-shopping-bag mr-2"></i>Khám phá sản phẩm ngay
                </a>
            </div>
        </div>
    @else
        <div class="row">
            @foreach ($products as $product)
                @php($quote = $quotes[$product->id])
                <div class="col-6 col-md-4 col-lg-3 mb-4">
                    <div class="card storefront-card h-100 position-relative">
                        <!-- Nút xóa nhanh khỏi wishlist -->
                        <form action="{{ route('shop.wishlist.destroy', $product) }}" method="POST" class="position-absolute" style="top: 10px; right: 10px; z-index: 10;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-light rounded-circle shadow-sm text-danger" title="Xóa khỏi yêu thích" style="width: 32px; height: 32px; padding: 0;">
                                <i class="fas fa-times"></i>
                            </button>
                        </form>

                        <a href="{{ route('shop.products.show', $product) }}" class="position-relative d-block">
                            @if ($product->hinh_anh)
                                <img class="card-img-top storefront-image" src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($product->hinh_anh) }}" alt="{{ $product->ten_san_pham }}">
                            @else
                                <div class="storefront-image bg-light d-flex align-items-center justify-content-center text-muted">
                                    <i class="far fa-image fa-3x"></i>
                                </div>
                            @endif
                            @if ($quote['promotion'])
                                <span class="badge badge-danger position-absolute" style="top:10px;left:10px">-{{ rtrim(rtrim(number_format($quote['discount_percent'], 2, ',', '.'), '0'), ',') }}%</span>
                            @endif
                        </a>

                        <div class="card-body d-flex flex-column">
                            <a href="{{ route('shop.products.show', $product) }}" class="font-weight-bold text-dark text-truncate mb-1" title="{{ $product->ten_san_pham }}">
                                {{ $product->ten_san_pham }}
                            </a>
                            <div class="mb-3">
                                @if ($quote['promotion'])
                                    <span class="h5 text-danger font-weight-bold">{{ number_format($quote['sale_price'], 0, ',', '.') }} ₫</span>
                                    <small class="price-old ml-1">{{ number_format($quote['original_price'], 0, ',', '.') }} ₫</small>
                                @else
                                    <span class="h5 font-weight-bold">{{ number_format($quote['original_price'], 0, ',', '.') }} ₫</span>
                                @endif
                            </div>

                            <div class="mt-auto">
                                <a href="{{ route('shop.products.show', $product) }}" class="btn btn-outline-primary btn-sm btn-block">
                                    <i class="fas fa-eye mr-1"></i>Xem chi tiết
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endsection
