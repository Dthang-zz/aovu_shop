@extends('layouts.storefront')

@section('title', 'Cửa hàng')

@section('content')
    @if ($banners->isNotEmpty())
        <div id="storefront-banners" class="carousel slide mb-4" data-ride="carousel" data-interval="5500">
            @if ($banners->count() > 1)
                <ol class="carousel-indicators">
                    @foreach ($banners as $banner)
                        <li data-target="#storefront-banners" data-slide-to="{{ $loop->index }}" @class(['active' => $loop->first])></li>
                    @endforeach
                </ol>
            @endif
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
    @else
        <section class="mb-4 p-4 p-md-5" style="background:linear-gradient(120deg,#e7e9e7,#d6d9d7);min-height:300px;display:flex;align-items:center;">
            <div class="row align-items-center w-100">
                <div class="col-md-7 py-3 py-md-4">
                    <div class="section-eyebrow mb-3">AOVU · NEW SEASON</div>
                    <h1 class="display-4 font-weight-bold mb-3" style="font-size:clamp(2rem,5vw,4rem);line-height:1.05;color:#1a2833;">Mặc chất riêng.<br>Sống theo cách bạn.</h1>
                    <p class="section-subtitle mb-4" style="max-width:420px;">Khám phá những thiết kế hiện đại, thoải mái và dễ kết hợp cho phong cách mỗi ngày.</p>
                    <a href="#collection" class="btn btn-dark rounded-0 px-4 py-3">KHÁM PHÁ BỘ SƯU TẬP <i class="fas fa-arrow-right ml-2"></i></a>
                </div>
                <div class="col-md-5 text-center d-none d-md-block"><div style="font-size:clamp(5rem,15vw,12rem);font-weight:900;letter-spacing:.12em;color:rgba(25,39,50,.11);">AOVU</div></div>
            </div>
        </section>
    @endif


    <section class="text-center py-4 py-md-5">
        <div class="section-eyebrow mb-2">Made for your everyday</div>
        <h1 class="section-heading h2 mb-2">Lựa chọn phong cách của bạn</h1>
        <p class="section-subtitle mb-0">Những món đồ được chọn lọc để bạn tự tin trong mọi khoảnh khắc.</p>
    </section>


    <div class="shop-benefits">
        <div class="benefit-item"><i class="fas fa-box-open d-block"></i><strong>Đóng gói chỉn chu</strong><span>Chăm chút từng đơn hàng</span></div>
        <div class="benefit-item"><i class="fas fa-shield-alt d-block"></i><strong>Mua sắm an tâm</strong><span>Thông tin đơn hàng rõ ràng</span></div>
        <div class="benefit-item"><i class="fas fa-headset d-block"></i><strong>Hỗ trợ khách hàng</strong><span>Luôn sẵn sàng giải đáp</span></div>
    </div>

    <section id="collection">
        <div class="d-flex align-items-end justify-content-between flex-wrap mb-4">
            <div><div class="section-eyebrow mb-2">Shop the collection</div><h2 class="section-heading h3 mb-1">Sản phẩm nổi bật</h2><p class="section-subtitle mb-0">Cập nhật những item đang được yêu thích tại AOVU.</p></div>
            <span class="text-muted small mt-3">{{ $products->count() }} sản phẩm</span>
        </div>
        <div class="row">
            @forelse ($products as $product)
                @php($quote = $quotes[$product->id])
                <div class="col-6 col-md-4 col-lg-3 mb-4">
                    <div class="card storefront-card product-card h-100">
                        <a href="{{ route('shop.products.show', $product) }}" class="product-image-wrap">
                            @if ($product->hinh_anh)
                                <img class="card-img-top storefront-image" src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($product->hinh_anh) }}" alt="{{ $product->ten_san_pham }}" loading="lazy">
                            @else
                                <div class="storefront-image d-flex align-items-center justify-content-center text-muted"><i class="far fa-image fa-3x"></i></div>
                            @endif
                            @if ($quote['promotion'])
                                <span class="badge badge-danger position-absolute" style="top:12px;left:12px;padding:8px 10px;">-{{ rtrim(rtrim(number_format($quote['discount_percent'], 2, ',', '.'), '0'), ',') }}%</span>
                            @endif
                        </a>
                        <div class="card-body d-flex flex-column">
                            <a href="{{ route('shop.products.show', $product) }}" class="product-name">{{ $product->ten_san_pham }}</a>
                            <div class="mt-2">
                                @if ($quote['promotion'])
                                    <span class="product-price text-danger">{{ number_format($quote['sale_price'], 0, ',', '.') }} ₫</span>
                                    <span class="price-old ml-1">{{ number_format($quote['original_price'], 0, ',', '.') }} ₫</span>
                                @else
                                    <span class="product-price">{{ number_format($quote['original_price'], 0, ',', '.') }} ₫</span>
                                @endif
                            </div>
                            <a href="{{ route('shop.products.show', $product) }}" class="product-quick-link mt-auto">Xem chi tiết <i class="fas fa-arrow-right ml-1"></i></a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12"><div class="bg-white p-5 text-center"><i class="fas fa-box-open fa-2x text-muted mb-3"></i><h5>Chưa có sản phẩm</h5><p class="text-muted mb-0">Sản phẩm mới sẽ sớm được cập nhật tại cửa hàng.</p></div></div>
            @endforelse
        </div>
    </section>
    <section class="mt-5 p-4 p-md-5 text-center" style="background:#e9eae7;">
        <div class="section-eyebrow mb-2">AOVU community</div><h2 class="section-heading h3">Tìm cảm hứng. Tạo phong cách riêng.</h2><p class="section-subtitle mb-4">Theo dõi AOVU để cập nhật sản phẩm, xu hướng và những câu chuyện mới.</p><a href="{{ route('news.index') }}" class="btn btn-outline-dark rounded-0 px-4 py-2">ĐỌC TIN & BÀI VIẾT</a>
    </section>
@endsection
