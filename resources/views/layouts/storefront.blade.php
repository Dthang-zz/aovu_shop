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
        body { background: #f5f7fb; }
        .storefront-header { background: #18212f; }
        .storefront-header a { color: #fff; }
        .storefront-card { border: 0; border-radius: 10px; box-shadow: 0 2px 12px rgba(31,45,61,.08); }
        .storefront-image { height: 220px; object-fit: cover; }
        .price-old { color: #8993a2; text-decoration: line-through; }
    </style>
    @yield('styles')
</head>
<body>
    <header class="storefront-header py-3">
        <div class="container d-flex justify-content-between align-items-center">
            <a href="{{ route('shop.products.index') }}" class="h4 mb-0 font-weight-bold">AOVU Shop</a>
            <nav class="d-flex align-items-center">
                <a class="mr-3" href="{{ route('shop.products.index') }}">Sản phẩm</a>
                <a class="mr-3" href="{{ route('news.index') }}">Tin tức</a>
                <a href="{{ route('shop.cart') }}"><i class="fas fa-shopping-cart mr-1"></i>Giỏ hàng</a>
            </nav>
        </div>
    </header>
    <main class="container py-4">
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if (session('info'))
            <div class="alert alert-info">{{ session('info') }}</div>
        @endif
        @yield('content')
    </main>
    <script src="{{ asset('adminlte/plugins/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    @yield('scripts')
</body>
</html>
