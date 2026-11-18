@extends('layouts.storefront')

@section('title', $product->ten_san_pham)

@section('content')
    @php
        $hasVariants = $variants->isNotEmpty();
        $promoPercent = (float) ($quote['discount_percent'] ?? 0);
        $variantPayload = $variants->map(fn ($variant) => [
            'id' => $variant->id,
            'sku' => $variant->sku,
            'price' => $variant->price,
            'stock' => (int) $variant->stock,
            'attributes' => $variant->display_attributes ?? $variant->attribute_values ?? [],
        ])->values();
    @endphp
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
                    @if ($product->mo_ta)<p class="text-muted">{{ $product->mo_ta }}</p>@endif
                    @if ($errors->any())
                        <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                    @endif
                    <form method="POST" action="{{ route('shop.cart.items.store') }}" id="product-cart-form">
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $product->id }}">
                        <input type="hidden" name="variant_id" id="selected-variant-id" value="{{ old('variant_id') }}">
                        @if ($hasVariants)
                            @foreach ($variantAttributes as $attributeName => $values)
                                <div class="form-group">
                                    <label for="variant-attribute-{{ $loop->index }}">{{ $attributeName }} <span class="text-danger">*</span></label>
                                    <select class="form-control variant-attribute-select" id="variant-attribute-{{ $loop->index }}" name="attributes[{{ $attributeName }}]" data-attribute="{{ $attributeName }}" required>
                                        <option value="">Chọn {{ mb_strtolower($attributeName) }}</option>
                                        @foreach ($values as $value)
                                            <option value="{{ $value }}" @selected(old('attributes.'.$attributeName) === $value)>{{ $value }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endforeach
                            <div class="small text-muted mb-3" id="variant-stock-text">Vui lòng chọn đầy đủ thuộc tính.</div>
                        @else
                            <p class="small text-muted">Còn {{ number_format($product->so_luong_ton ?? 0) }} sản phẩm</p>
                        @endif
                        <div class="form-group">
                            <label for="quantity">Số lượng</label>
                            <input id="quantity" type="number" name="quantity" value="{{ old('quantity', 1) }}" class="form-control" min="1" max="100" required>
                        </div>
                        <button type="submit" id="add-to-cart-button" class="btn btn-primary" @disabled($hasVariants || (int) ($product->so_luong_ton ?? 0) < 1)><i class="fas fa-cart-plus mr-1"></i>{{ $hasVariants ? 'Chọn phân loại' : ((int) ($product->so_luong_ton ?? 0) < 1 ? 'Hết hàng' : 'Thêm vào giỏ') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @if ($hasVariants)
        <script>
        (() => {
            const variants = @json($variantPayload);
            const selectors = Array.from(document.querySelectorAll('.variant-attribute-select'));
            const idInput = document.getElementById('selected-variant-id');
            const button = document.getElementById('add-to-cart-button');
            const stockText = document.getElementById('variant-stock-text');
            const quantity = document.getElementById('quantity');
            const salePrice = document.getElementById('sale-price');
            const originalPrice = document.getElementById('original-price');
            const percent = {{ $promoPercent }};
            const fallbackPrice = {{ (int) $product->gia_san_pham }};
            const formatMoney = amount => Math.round(amount).toLocaleString('vi-VN') + ' ₫';
            const findVariant = () => {
                const selected = {};
                selectors.forEach(select => selected[select.dataset.attribute] = select.value);
                if (Object.values(selected).some(value => !value)) return null;
                return variants.find(variant => Object.entries(selected).every(([name, value]) => String(variant.attributes?.[name] ?? '') === value)) || null;
            };
            const update = () => {
                const variant = findVariant();
                if (!variant) {
                    idInput.value = ''; button.disabled = true;
                    stockText.textContent = 'Tổ hợp phân loại này không tồn tại. Vui lòng chọn lại.';
                    return;
                }
                idInput.value = variant.id;
                const stock = Number(variant.stock || 0);
                const base = variant.price === null || variant.price === '' ? fallbackPrice : Number(variant.price);
                const discount = Math.round(base * percent / 100);
                salePrice.textContent = formatMoney(base - discount);
                originalPrice.textContent = formatMoney(base);
                originalPrice.classList.toggle('d-none', percent <= 0);
                stockText.textContent = stock > 0 ? `Còn ${stock} sản phẩm trong phân loại này` : 'Phân loại này đã hết hàng.';
                quantity.max = Math.min(100, stock);
                if (Number(quantity.value) > stock) quantity.value = Math.max(1, stock);
                button.disabled = stock < 1;
                button.innerHTML = stock > 0 ? '<i class="fas fa-cart-plus mr-1"></i>Thêm vào giỏ' : 'Hết hàng';
            };
            selectors.forEach(select => select.addEventListener('change', update));
            update();
        })();
        </script>
    @endif

    <section class="card storefront-card mt-4" aria-labelledby="product-reviews-heading">
        <div class="card-header bg-white">
            <h2 id="product-reviews-heading" class="h5 mb-0">Đánh giá sản phẩm</h2>
        </div>
        <div class="card-body">
            @if ((int) $reviewSummary->total > 0)
                <div class="d-flex align-items-center mb-4">
                    <span class="h3 text-warning mb-0 mr-2"><i class="fas fa-star"></i></span>
                    <strong class="h3 mb-0">{{ number_format((float) $reviewSummary->average_rating, 1, ',', '.') }}</strong>
                    <span class="text-muted ml-2">/ 5 · {{ number_format((int) $reviewSummary->total) }} đánh giá</span>
                </div>
                <div class="row">
                    @foreach ($reviews as $review)
                        <div class="col-12 mb-3">
                            <article class="border rounded p-3">
                                <div class="d-flex justify-content-between align-items-start flex-wrap">
                                    <div>
                                        <strong>{{ $review->customer_name ?: 'Khách hàng' }}</strong>
                                        <div class="text-warning" aria-label="{{ $review->so_sao }} sao">
                                            @for ($star = 1; $star <= 5; $star++)
                                                <i class="{{ $star <= (int) $review->so_sao ? 'fas' : 'far' }} fa-star"></i>
                                            @endfor
                                        </div>
                                    </div>
                                    <small class="text-muted">{{ $review->ngay_dang ? \Illuminate\Support\Carbon::parse($review->ngay_dang)->format('d/m/Y') : '' }}</small>
                                </div>
                                @if ($review->noi_dung)
                                    <p class="mb-0 mt-2 text-break">{{ $review->noi_dung }}</p>
                                @endif
                                @if ($review->phan_hoi)
                                    <div class="bg-light rounded p-3 mt-3">
                                        <strong class="small text-primary">Phản hồi từ cửa hàng</strong>
                                        <p class="mb-0 mt-1">{{ $review->phan_hoi }}</p>
                                    </div>
                                @endif
                            </article>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-muted mb-0">Sản phẩm chưa có đánh giá.</p>
            @endif
        </div>
    </section>
@endsection
