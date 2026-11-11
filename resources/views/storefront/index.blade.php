<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Aovu Shop') }} — Thời trang dành cho bạn</title>
    <style>
        :root {
            color-scheme: light;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: #24211f;
            background: #fbfaf8;
            font-synthesis: none;
        }
        * { box-sizing: border-box; }
        body { margin: 0; }
        a { color: inherit; text-decoration: none; }
        button, input, select { font: inherit; }
        .topbar { background: #282421; color: #fff; padding: 12px 24px; text-align: center; font-size: 12px; letter-spacing: .12em; }
        .site-header { background: #fff; border-bottom: 1px solid #eeeae5; }
        .header-inner, .main, .footer-inner { width: min(1180px, calc(100% - 40px)); margin: 0 auto; }
        .header-inner { min-height: 76px; display: flex; align-items: center; justify-content: space-between; gap: 24px; }
        .brand { font-family: Georgia, serif; font-size: 29px; font-weight: 700; letter-spacing: .06em; }
        .header-note { color: #77716c; font-size: 13px; }
        .hero { background: #f0e9e1; padding: 48px 24px; text-align: center; }
        .eyebrow { color: #8c6b55; font-size: 11px; font-weight: 700; letter-spacing: .2em; text-transform: uppercase; }
        .hero h1 { margin: 10px 0 8px; font-family: Georgia, serif; font-size: clamp(32px, 5vw, 48px); font-weight: 500; }
        .hero p { margin: 0; color: #6d6660; font-size: 15px; }
        .main { padding: 34px 0 60px; }
        .toolbar { display: flex; justify-content: space-between; align-items: end; gap: 18px; margin-bottom: 20px; }
        .toolbar h2 { margin: 0; font-family: Georgia, serif; font-size: 27px; font-weight: 500; }
        .result-count { margin-top: 6px; color: #77716c; font-size: 13px; }
        .sort-control { display: flex; align-items: center; gap: 10px; white-space: nowrap; font-size: 13px; }
        .sort-control select { min-width: 190px; }
        .catalog { display: grid; grid-template-columns: 230px minmax(0, 1fr); align-items: start; gap: 26px; }
        .filters { padding: 20px; background: #fff; border: 1px solid #eeeae5; }
        .filters h3 { margin: 0 0 18px; font-size: 15px; }
        .filter-field { display: grid; gap: 7px; margin-bottom: 17px; }
        .filter-field label, .filter-label { color: #57514b; font-size: 12px; font-weight: 650; }
        .filter-field input, .filter-field select, .sort-control select {
            width: 100%; min-height: 42px; padding: 9px 10px; color: #282421; background: #fff;
            border: 1px solid #ded9d3; border-radius: 2px;
        }
        .price-inputs { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
        .filter-actions { display: grid; gap: 9px; margin-top: 20px; }
        .button { min-height: 43px; display: inline-flex; align-items: center; justify-content: center; padding: 10px 14px; border: 1px solid #282421; border-radius: 2px; cursor: pointer; font-size: 13px; font-weight: 700; }
        .button-primary { background: #282421; color: #fff; }
        .button-secondary { background: #fff; color: #282421; }
        .products { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 22px 16px; }
        .product-card { min-width: 0; background: #fff; border: 1px solid #eeeae5; }
        .product-image { display: block; width: 100%; aspect-ratio: 4 / 5; object-fit: cover; background: #f3f0ed; }
        .image-placeholder { aspect-ratio: 4 / 5; display: grid; place-items: center; background: #f3f0ed; color: #a29a92; font-size: 12px; }
        .product-info { padding: 14px; }
        .product-category { color: #8c6b55; font-size: 11px; letter-spacing: .08em; text-transform: uppercase; }
        .product-name { display: block; overflow: hidden; margin: 6px 0 8px; font-family: Georgia, serif; font-size: 16px; text-overflow: ellipsis; white-space: nowrap; }
        .product-price { display: flex; flex-wrap: wrap; align-items: baseline; gap: 8px; font-size: 14px; font-weight: 700; }
        .product-price del { color: #99918a; font-size: 12px; font-weight: 400; }
        .empty-state { grid-column: 1 / -1; padding: 54px 20px; background: #fff; border: 1px solid #eeeae5; text-align: center; }
        .empty-state h3 { margin: 0 0 8px; font-family: Georgia, serif; font-size: 22px; font-weight: 500; }
        .empty-state p { margin: 0; color: #77716c; font-size: 14px; }
        .pagination { grid-column: 1 / -1; display: flex; justify-content: center; margin-top: 12px; }
        .pagination nav > div:first-child { display: none; }
        .pagination nav > div:last-child { display: flex; flex-wrap: wrap; justify-content: center; gap: 5px; }
        .pagination a, .pagination span { min-width: 36px; padding: 8px 11px; border: 1px solid #ded9d3; background: #fff; text-align: center; font-size: 13px; }
        .pagination span[aria-current="page"] span { background: #282421; color: #fff; border-color: #282421; }
        .errors { margin-bottom: 16px; padding: 12px; background: #fff3f1; color: #8d3026; font-size: 13px; }
        footer { padding: 25px 0; border-top: 1px solid #eeeae5; background: #fff; color: #77716c; font-size: 12px; text-align: center; }
        @media (max-width: 900px) { .catalog { grid-template-columns: 205px minmax(0, 1fr); gap: 16px; } .products { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (max-width: 640px) {
            .header-inner, .main, .footer-inner { width: min(100% - 28px, 1180px); }
            .header-inner { min-height: 64px; }
            .header-note { display: none; }
            .hero { padding: 36px 16px; }
            .toolbar { align-items: start; flex-direction: column; }
            .sort-control { width: 100%; justify-content: space-between; }
            .sort-control select { width: auto; flex: 1; }
            .catalog { grid-template-columns: 1fr; }
            .filters { padding: 16px; }
            .products { gap: 12px; }
            .product-info { padding: 11px; }
            .product-name { font-size: 14px; }
        }
    </style>
</head>
<body>
    <div class="topbar">MIỄN PHÍ VẬN CHUYỂN CHO ĐƠN HÀNG TỪ 500.000₫</div>
    <header class="site-header">
        <div class="header-inner">
            <a class="brand" href="{{ route('storefront.index') }}">{{ config('app.name', 'AOVU') }}</a>
            <span class="header-note">Thời trang tinh giản, dành riêng cho bạn</span>
        </div>
    </header>
    <section class="hero">
        <div class="eyebrow">Bộ sưu tập mới</div>
        <h1>Phong cách của riêng bạn</h1>
        <p>Khám phá những thiết kế được chọn lọc cho mọi ngày.</p>
    </section>
    <main class="main">
        <div class="toolbar">
            <div>
                <h2>Sản phẩm</h2>
                <div class="result-count">{{ $products->total() }} sản phẩm</div>
            </div>
            <label class="sort-control" for="sort">
                <span>Sắp xếp:</span>
                <select id="sort" name="sort" form="product-filters" onchange="this.form.submit()">
                    <option value="popular" @selected($filters['sort'] === 'popular')>Phổ biến nhất</option>
                    <option value="newest" @selected($filters['sort'] === 'newest')>Mới nhất</option>
                    <option value="price_asc" @selected($filters['sort'] === 'price_asc')>Giá: thấp đến cao</option>
                    <option value="price_desc" @selected($filters['sort'] === 'price_desc')>Giá: cao đến thấp</option>
                </select>
            </label>
        </div>
        <div class="catalog">
            <form class="filters" id="product-filters" method="GET" action="{{ route('storefront.index') }}">
                <h3>Lọc sản phẩm</h3>
                @if ($errors->any())
                    <div class="errors" role="alert">
                        @foreach ($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif
                <div class="filter-field">
                    <label for="q">Từ khóa</label>
                    <input id="q" name="q" type="search" value="{{ $filters['q'] }}" placeholder="Tên hoặc mã sản phẩm">
                </div>
                <div class="filter-field">
                    <label for="category_id">Danh mục</label>
                    <select id="category_id" name="category_id">
                        <option value="">Tất cả danh mục</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected(($filters['category_id'] ?? '') == $category->id)>
                                {{ $category->ten_danh_muc }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="filter-field">
                    <span class="filter-label">Khoảng giá (₫)</span>
                    <div class="price-inputs">
                        <input aria-label="Giá tối thiểu" name="min_price" type="number" min="0" step="1000" value="{{ $filters['min_price'] ?? '' }}" placeholder="Từ">
                        <input aria-label="Giá tối đa" name="max_price" type="number" min="0" step="1000" value="{{ $filters['max_price'] ?? '' }}" placeholder="Đến">
                    </div>
                </div>
                <div class="filter-field">
                    <label for="color_id">Màu sắc</label>
                    <select id="color_id" name="color_id">
                        <option value="">Tất cả màu sắc</option>
                        @foreach ($colors as $color)
                            <option value="{{ $color->id }}" @selected(($filters['color_id'] ?? '') == $color->id)>
                                {{ $color->ten_mau }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="filter-field">
                    <label for="size_id">Kích thước</label>
                    <select id="size_id" name="size_id">
                        <option value="">Tất cả kích thước</option>
                        @foreach ($sizes as $size)
                            <option value="{{ $size->id }}" @selected(($filters['size_id'] ?? '') == $size->id)>
                                {{ $size->ten_kich_thuoc }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="filter-actions">
                    <button class="button button-primary" type="submit">Áp dụng bộ lọc</button>
                    <a class="button button-secondary" href="{{ route('storefront.index') }}">Xóa bộ lọc</a>
                </div>
            </form>
            <section class="products" aria-label="Danh sách sản phẩm">
                @forelse ($products as $product)
                    <article class="product-card">
                        @if ($product->hinh_anh)
                            <img class="product-image" src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($product->hinh_anh) }}" alt="{{ $product->ten_san_pham }}" loading="lazy">
                        @else
                            <div class="image-placeholder">Chưa có hình ảnh</div>
                        @endif
                        <div class="product-info">
                            <div class="product-category">{{ $product->category?->ten_danh_muc ?? 'Thời trang' }}</div>
                            <span class="product-name">{{ $product->ten_san_pham }}</span>
                            <div class="product-price">
                                @if ($product->gia_khuyen_mai)
                                    <span>{{ number_format($product->gia_khuyen_mai, 0, ',', '.') }}₫</span>
                                    <del>{{ number_format($product->gia_san_pham, 0, ',', '.') }}₫</del>
                                @else
                                    <span>{{ number_format($product->gia_san_pham ?? 0, 0, ',', '.') }}₫</span>
                                @endif
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="empty-state">
                        <h3>Chưa tìm thấy sản phẩm phù hợp</h3>
                        <p>Hãy thử thay đổi từ khóa hoặc điều chỉnh các bộ lọc.</p>
                    </div>
                @endforelse
                @if ($products->hasPages())
                    <div class="pagination">{{ $products->links() }}</div>
                @endif
            </section>
        </div>
    </main>
    <footer><div class="footer-inner">© {{ date('Y') }} {{ config('app.name', 'AOVU') }}. Tất cả quyền được bảo lưu.</div></footer>
</body>
</html>
