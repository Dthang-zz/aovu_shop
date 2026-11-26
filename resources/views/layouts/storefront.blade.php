<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Cửa hàng') | AOVU Shop</title>
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/fontawesome-free/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/dist/css/adminlte.min.css') }}">
    <style>
        :root { --aovu-ink:#17212b; --aovu-muted:#6c7885; --aovu-blue:#263e58; --aovu-accent:#d9a66b; --aovu-bg:#f7f7f5; }
        html { scroll-behavior:smooth; }
        body { background:var(--aovu-bg); color:#202a33; font-family:Inter,system-ui,-apple-system,"Segoe UI",sans-serif; }
        .storefront-header { background:#fff; border-bottom:1px solid #e9ecef; position:sticky; top:0; z-index:1030; box-shadow:0 3px 18px rgba(20,30,40,.035); }
        .announcement { background:var(--aovu-ink); color:#fff; font-size:11px; letter-spacing:.12em; text-transform:uppercase; text-align:center; padding:9px 12px; }
        .shop-navbar { min-height:78px; display:flex; align-items:center; gap:26px; }
        .brand-mark { font-weight:900; font-size:26px; letter-spacing:.17em; color:var(--aovu-ink)!important; text-decoration:none!important; }
        .brand-mark small { display:block; font-size:9px; letter-spacing:.36em; font-weight:500; margin-top:-2px; text-align:center; }
        .shop-nav-links { display:flex; align-items:center; gap:25px; }
        .shop-nav-links a { color:#303b46; font-size:13px; font-weight:600; text-decoration:none; transition:color .2s; }
        .shop-nav-links a:hover { color:#b77c38; }
        .shop-actions { display:flex; align-items:center; gap:16px; margin-left:auto; }
        .shop-actions a { color:#26333e; text-decoration:none; font-size:13px; }
        .shop-icon { width:38px;height:38px;border:1px solid #e6e8ea;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;transition:.2s; }
        .shop-icon:hover { background:var(--aovu-ink);color:white; }
        .shop-main { max-width:1320px; padding-top:28px; padding-bottom:50px; }
        .storefront-card { border:0;border-radius:0;box-shadow:none;overflow:hidden;background:#fff;transition:transform .25s,box-shadow .25s; }
        .storefront-image { height:320px; width:100%; object-fit:cover; background:#eee; transition:transform .45s; }
        .product-image-wrap { overflow:hidden; position:relative; background:#eee; }
        .product-card:hover { transform:translateY(-4px); box-shadow:0 14px 32px rgba(24,36,48,.10); }
        .product-card:hover .storefront-image { transform:scale(1.035); }
        .product-card .card-body { padding:16px 16px 18px; }
        .product-name { font-size:14px; line-height:1.5; min-height:42px; color:#27323c; font-weight:600; text-decoration:none!important; }
        .product-name:hover { color:#a87534; }
        .product-price { font-size:17px; font-weight:800; color:#18232c; }
        .price-old { color:#929aa2;text-decoration:line-through;font-size:12px; }
        .product-quick-link { font-size:11px; letter-spacing:.12em; text-transform:uppercase; border:1px solid #27343e; color:#27343e; padding:11px; display:block; text-align:center; text-decoration:none!important; margin-top:15px; transition:.2s; }
        .product-quick-link:hover { background:#27343e;color:#fff; }
        .storefront-banner { height:min(42vw,520px);min-height:230px;object-fit:cover; }
        .carousel-indicators li { background:#fff; opacity:.65; } .carousel-indicators .active { opacity:1; }
        .section-eyebrow { font-size:11px;letter-spacing:.24em;text-transform:uppercase;color:#9b7346;font-weight:700; }
        .section-heading { font-weight:800;letter-spacing:-.025em;color:#1b2731; }
        .section-subtitle { color:var(--aovu-muted);font-size:14px; }
        .shop-benefits { display:grid;grid-template-columns:repeat(3,1fr);background:white;border:1px solid #e9ecef;margin:30px 0 42px; }
        .benefit-item { padding:20px 14px;text-align:center;border-right:1px solid #e9ecef; }
        .benefit-item:last-child { border-right:0; }
        .benefit-item i { color:#b27a3d;font-size:18px;margin-bottom:8px; }
        .benefit-item strong { display:block;font-size:12px;letter-spacing:.06em;text-transform:uppercase; }
        .benefit-item span { display:block;color:#89939c;font-size:11px;margin-top:4px; }
        .shop-footer { background:#17212b;color:#dce2e7;padding:46px 0 22px;margin-top:20px; }
        .shop-footer a { color:#dce2e7; }
        .footer-brand { font-size:25px;font-weight:900;letter-spacing:.18em;color:white; }
        .footer-note { color:#9da9b4;font-size:12px;line-height:1.8; }
        .footer-title { font-size:11px;letter-spacing:.15em;text-transform:uppercase;color:white;font-weight:700;margin-bottom:14px; }
        .footer-links a { display:block;font-size:13px;color:#b9c3cb;margin:8px 0;text-decoration:none; }
        .footer-links a:hover { color:white; }
        .flash-wrap { max-width:1320px;margin:14px auto 0;padding:0 15px; }
        @media(max-width:767.98px){ .shop-navbar{min-height:64px;gap:10px}.brand-mark{font-size:21px}.shop-nav-links{gap:12px}.shop-nav-links a{font-size:12px}.shop-actions{gap:9px}.shop-actions .auth-link{display:none}.storefront-image{height:220px}.shop-benefits{grid-template-columns:1fr}.benefit-item{border-right:0;border-bottom:1px solid #e9ecef}.benefit-item:last-child{border-bottom:0}.shop-main{padding-top:18px}.storefront-banner{min-height:180px}.footer-columns>div{margin-bottom:24px}}
    </style>
    @yield('styles')
</head>
<body>
    <div class="announcement">Miễn phí vận chuyển cho đơn hàng từ 499.000đ · Bộ sưu tập mới đã lên kệ</div>
    <header class="storefront-header">
        <div class="container shop-navbar">
            <a href="{{ route('shop.products.index') }}" class="brand-mark">AOVU<small>FASHION STORE</small></a>
            <nav class="shop-nav-links ml-3">
                <a href="{{ route('shop.products.index') }}">Cửa hàng</a>
                <a href="{{ route('news.index') }}">Tin tức</a>
            </nav>
            <div class="shop-actions">
                <a class="shop-icon" href="{{ route('shop.cart') }}" title="Giỏ hàng"><i class="fas fa-shopping-bag"></i></a>
                @auth
                    <a class="auth-link" href="{{ route('account.profile') }}">{{ auth()->user()->ho_ten }}</a>
                    <form method="POST" action="{{ route('logout') }}" class="d-inline">@csrf<button type="submit" class="btn btn-sm btn-outline-dark">Đăng xuất</button></form>
                @else
                    <a class="auth-link" href="{{ route('login') }}">Đăng nhập</a>
                    <a class="btn btn-sm btn-dark px-3" href="{{ route('register') }}">Đăng ký</a>
                @endauth
            </div>
        </div>
    </header>
    @if (session('success') || session('info') || session('error'))
        <div class="flash-wrap">
            @if (session('success')) <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="close" data-dismiss="alert">&times;</button></div> @endif
            @if (session('info')) <div class="alert alert-info alert-dismissible fade show">{{ session('info') }}<button type="button" class="close" data-dismiss="alert">&times;</button></div> @endif
            @if (session('error')) <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="close" data-dismiss="alert">&times;</button></div> @endif
        </div>
    @endif
    <main class="container shop-main">
        @yield('content')
    </main>
    <footer class="shop-footer">
        <div class="container">
            <div class="row footer-columns">
                <div class="col-md-4 mb-3"><div class="footer-brand">AOVU</div><p class="footer-note mt-3">Phong cách của bạn. Lựa chọn của bạn. AOVU mang đến những thiết kế dễ mặc, hiện đại và dành cho mỗi ngày.</p><div class="mt-3"><a class="mr-3" href="#"><i class="fab fa-facebook-f"></i></a><a class="mr-3" href="#"><i class="fab fa-instagram"></i></a><a href="#"><i class="fab fa-tiktok"></i></a></div></div>
                <div class="col-md-3 footer-links"><div class="footer-title">Khám phá</div><a href="{{ route('shop.products.index') }}">Tất cả sản phẩm</a><a href="{{ route('news.index') }}">Tin tức & bài viết</a><a href="{{ route('shop.cart') }}">Giỏ hàng</a></div>
                <div class="col-md-2 footer-links"><div class="footer-title">Tài khoản</div><a href="{{ route('login') }}">Đăng nhập</a><a href="{{ route('register') }}">Tạo tài khoản</a></div>
                <div class="col-md-3"><div class="footer-title">Hỗ trợ khách hàng</div><p class="footer-note">Cần trợ giúp về đơn hàng hoặc sản phẩm? Hãy liên hệ với đội ngũ AOVU để được hỗ trợ.</p><p class="footer-note"><i class="fas fa-envelope mr-2"></i> support@aovu.vn</p></div>
            </div>
            <hr style="border-color:#33414d"><div class="d-flex justify-content-between flex-wrap footer-note"><span>© {{ date('Y') }} AOVU Shop. All rights reserved.</span><span>Thanh toán an toàn · Hỗ trợ tận tâm</span></div>
        </div>
    </footer>
    @include('storefront.partials.support-chat-widget')
    <script src="{{ asset('adminlte/plugins/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    @yield('scripts')
</body>
</html>
