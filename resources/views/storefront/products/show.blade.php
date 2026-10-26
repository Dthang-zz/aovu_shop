@extends('layouts.storefront')

@section('title', $product->ten_san_pham)

@section('content')
    <a href="{{ route('shop.products.index') }}" class="btn btn-link px-0 mb-3"><i class="fas fa-arrow-left mr-1"></i>Tiếp tục mua sắm</a>
    <div class="card storefront-card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    @if ($product->hinh_anh)
                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($product->hinh_anh) }}" class="img-fluid rounded w-100" alt="{{ $product->ten_san_pham }}">
                    @else
                        <div class="bg-light d-flex justify-content-center align-items-center rounded" style="height:360px"><i class="far fa-image fa-4x text-muted"></i></div>
                    @endif
                </div>
                <div class="col-md-6">
                    <h1 class="h3 font-weight-bold">{{ $product->ten_san_pham }}</h1>
                    <p class="text-muted">Mã sản phẩm: {{ $product->ma_san_pham }}</p>
                    @if ($quote['promotion'])
                        <span class="badge badge-danger mb-2">Giảm {{ rtrim(rtrim(number_format($quote['discount_percent'], 2, ',', '.'), '0'), ',') }}%</span>
                    @endif
                    <div class="mb-3">
                        <span id="sale-price" class="h3 {{ $quote['promotion'] ? 'text-danger' : '' }} font-weight-bold">{{ number_format($quote['sale_price'], 0, ',', '.') }} ₫</span>
                        <span id="original-price" class="price-old ml-2 {{ $quote['promotion'] ? '' : 'd-none' }}">{{ number_format($quote['original_price'], 0, ',', '.') }} ₫</span>
                    </div>
                    @if ($product->mo_ta)
                        <p class="text-muted">{{ $product->mo_ta }}</p>
                    @endif
                    @if ($errors->any())
                        <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                    @endif
                    <form method="POST" action="{{ route('shop.cart.items.store') }}">
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $product->id }}">
                        @if ($variants->isNotEmpty())
                            <div class="form-group">
                                <label for="variant_id">Phân loại</label>
                                <select id="variant_id" name="variant_id" class="form-control" required>
                                    <option value="">Chọn màu / kích thước</option>
                                    @foreach ($variants as $variant)
                                        @php($variantQuote = $variantQuotes[$variant->id])
                                        <option value="{{ $variant->id }}" data-sale-price="{{ $variantQuote['sale_price'] }}" data-original-price="{{ $variantQuote['original_price'] }}" @selected(old('variant_id') == $variant->id)>
                                            {{ trim(implode(' / ', array_filter([$variant->color, $variant->size]))) }}
                                            — {{ number_format($variantQuote['sale_price'], 0, ',', '.') }} ₫
                                            (còn {{ $variant->stock }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div class="form-group">
                            <label for="quantity">Số lượng</label>
                            <input id="quantity" type="number" name="quantity" value="{{ old('quantity', 1) }}" class="form-control" min="1" max="100" required>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-cart-plus mr-1"></i>Thêm vào giỏ</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        document.getElementById('variant_id')?.addEventListener('change', function () {
            const option = this.selectedOptions[0];
            if (!option || !option.dataset.salePrice) {
                return;
            }

            const salePrice = document.getElementById('sale-price');
            const originalPrice = document.getElementById('original-price');
            salePrice.textContent = Number(option.dataset.salePrice).toLocaleString('vi-VN') + ' ₫';
            originalPrice.textContent = Number(option.dataset.originalPrice).toLocaleString('vi-VN') + ' ₫';
            originalPrice.classList.toggle('d-none', option.dataset.salePrice === option.dataset.originalPrice);
            salePrice.classList.toggle('text-danger', option.dataset.salePrice !== option.dataset.originalPrice);
        });
    </script>
@endsection
