<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') | AOVU Shop</title>
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/fontawesome-free/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/dist/css/adminlte.min.css') }}">
    <style>body{background:#f5f7fb}.auth-card{max-width:480px;margin:5vh auto;border:0;border-radius:14px;box-shadow:0 10px 35px rgba(20,30,50,.10)}.auth-brand{font-weight:800;letter-spacing:.04em;color:#5b6cff}.auth-card .card-body{padding:2rem}</style>
</head>
<body>
<div class="container px-3">
    <div class="text-center mt-4"><a class="auth-brand h3" href="{{ route('shop.products.index') }}">AOVU Shop</a></div>
    <div class="card auth-card"><div class="card-body">
        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
        @yield('content')
    </div></div>
    <p class="text-center text-muted small"><a href="{{ route('shop.products.index') }}"><i class="fas fa-arrow-left mr-1"></i>Quay về cửa hàng</a></p>
</div>
<script src="{{ asset('adminlte/plugins/jquery/jquery.min.js') }}"></script><script src="{{ asset('adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
</body></html>
