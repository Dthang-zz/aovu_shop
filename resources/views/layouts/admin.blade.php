<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f4f6f9">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Tổng quan') | AOVU Shop</title>
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/fontawesome-free/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/overlayScrollbars/css/OverlayScrollbars.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/dist/css/adminlte.min.css') }}">
    <style>
        body {
            font-family: "Source Sans Pro", "Segoe UI", Arial, sans-serif;
        }

        .main-sidebar {
            background: #18212f;
        }

        .brand-link {
            border-bottom: 1px solid rgba(255, 255, 255, .08) !important;
        }

        .brand-text {
            color: #fff;
            color: #fff;
            font-size: 1.15rem;
            font-weight: 600 !important;
            letter-spacing: .02em;
        }

        .sidebar-dark-primary .nav-sidebar>.nav-item>.nav-link.active {
            background: #5b6cff;
            box-shadow: 0 5px 14px rgba(91, 108, 255, .23);
        }

        .nav-sidebar .nav-header {
            color: #718096;
            font-size: .68rem;
            letter-spacing: .1em;
            padding-top: 1.15rem;
        }

        .user-panel {
            border-bottom: 1px solid rgba(255, 255, 255, .08) !important;
        }

        .user-panel .info a {
            color: #eef2f7;
        }

        .main-header {
            border-bottom: 1px solid #edf0f4;
        }

        .content-wrapper {
            background: #f5f7fb;
        }

        .content-header h1 {
            font-size: 1.45rem;
            font-weight: 600;
            color: #202939;
        }

        .content-header .breadcrumb {
            font-size: .82rem;
        }

        .card {
            border: 0;
            border-radius: 10px;
            box-shadow: 0 2px 12px rgba(31, 45, 61, .045);
        }

        .card-header {
            background: #fff;
            border-bottom: 1px solid #f0f2f5;
            border-radius: 10px 10px 0 0 !important;
        }

        .card-title {
            color: #263244;
            font-weight: 600;
        }

        .small-box {
            border-radius: 10px;
            box-shadow: 0 2px 12px rgba(31, 45, 61, .06);
        }

        .small-box .icon {
            top: 12px;
            right: 14px;
        }

        .small-box .icon>i {
            font-size: 52px;
            opacity: .18;
        }

        .table thead th {
            border-top: 0;
            border-bottom: 1px solid #edf0f4;
            color: #7b8797;
            font-size: .72rem;
            font-weight: 600;
            letter-spacing: .045em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .table td {
            border-top: 1px solid #f0f2f5;
            vertical-align: middle;
        }

        .text-muted {
            color: #8994a4 !important;
        }

        .btn-primary {
            background-color: #5b6cff;
            border-color: #5b6cff;
        }

        .btn-primary:hover {
            background-color: #4b5be8;
            border-color: #4b5be8;
        }

        .badge {
            font-weight: 500;
        }

        .badge-success {
            background: #e7f7ef;
            color: #23945d;
        }

        .badge-warning {
            background: #fff4dc;
            color: #b7791f;
        }

        .badge-info {
            background: #e6f4fb;
            color: #287da2;
        }

        .badge-danger {
            background: #fdebed;
            color: #c44450;
        }

        .badge-secondary {
            background: #eef0f3;
            color: #687386;
        }

        .badge-primary {
            background: #eceeff;
            color: #4b5be8;
        }

        .product-thumb {
            width: 38px;
            height: 38px;
            border-radius: 8px;
            object-fit: cover;
        }

        .table-actions .btn {
            padding: .2rem .4rem;
        }

        .content-footer {
            color: #98a2b3;
            font-size: .78rem;
        }

        #spa-progress {
            position: fixed;
            z-index: 2000;
            top: 0;
            left: 0;
            width: 100%;
            height: 3px;
            pointer-events: none;
            opacity: 0;
            background: #5b6cff;
            transform: scaleX(0);
            transform-origin: left;
            transition: transform .25s ease, opacity .2s ease;
        }

        #spa-progress.is-loading {
            opacity: 1;
            transform: scaleX(.82);
        }

        #spa-progress.is-complete {
            opacity: 1;
            transform: scaleX(1);
        }

        .spa-content-enter {
            animation: spa-content-enter .18s ease-out;
        }

        @keyframes spa-content-enter {
            from {
                opacity: .65;
            }

            to {
                opacity: 1;
            }
        }

        @media (max-width: 767.98px) {
            .content-header {
                padding-left: .75rem;
                padding-right: .75rem;
            }

            .content {
                padding-left: .75rem;
                padding-right: .75rem;
            }

            .card-body {
                padding: 1rem;
            }
        }
    </style>
    @yield('styles')
</head>

<body class="hold-transition sidebar-mini layout-fixed">
    <div id="spa-progress" role="progressbar" aria-label="Đang cập nhật nội dung"></div>
    <div class="wrapper">
        <nav class="main-header navbar navbar-expand navbar-white navbar-light">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-widget="pushmenu" href="#" role="button"
                        aria-label="Mở hoặc thu gọn menu"><i class="fas fa-bars"></i></a>
                </li>
                <li class="nav-item d-none d-sm-inline-block">
                    <a href="{{ route('admin.dashboard') }}" class="nav-link text-muted">Cửa hàng</a>
                </li>
            </ul>
            <ul class="navbar-nav ml-auto align-items-center">
                <li class="nav-item mr-2 d-none d-md-block">
                    <span class="nav-link text-muted small"><i class="far fa-calendar-alt mr-1"></i> Hôm nay,
                        {{ now()->format('d/m/Y') }}</span>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link" data-toggle="dropdown" href="#" aria-label="Thông báo">
                        <i class="far fa-bell"></i><span
                            class="badge badge-warning navbar-badge">{{ number_format($notificationCount) }}</span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                        <span class="dropdown-header">{{ number_format($notificationCount) }} thông báo cần lưu ý</span>
                        <div class="dropdown-divider"></div>
                        <a href="{{ route('admin.orders.index') }}" class="dropdown-item"><i
                                class="fas fa-shopping-bag mr-2 text-primary"></i>
                            {{ number_format($pendingOrderCount) }} đơn hàng chờ xử lý</a>
                        <div class="dropdown-divider"></div>
                        <a href="{{ route('admin.resource', ['section' => 'inventory']) }}" class="dropdown-item"><i
                                class="fas fa-exclamation-triangle mr-2 text-warning"></i>
                            {{ number_format($lowStockVariantCount) }} biến thể sắp hết hàng</a>
                        <div class="dropdown-divider"></div>
                        <a href="{{ route('admin.returns.index') }}" class="dropdown-item"><i
                                class="fas fa-exchange-alt mr-2 text-warning"></i>
                            {{ number_format($pendingReturnCount) }} yêu cầu đổi/trả chờ xử lý</a>
                    </div>
                </li>
                <li class="nav-item d-none d-sm-inline-block ml-2">
                    <span class="nav-link py-1"><img src="{{ asset('adminlte/img/avatar4.png') }}"
                            alt="Ảnh quản trị viên" class="img-circle elevation-1" width="30" height="30"></span>
                </li>
            </ul>
        </nav>

        <aside class="main-sidebar sidebar-dark-primary elevation-4">
            <a href="{{ route('admin.dashboard') }}"
                class="brand-link d-flex align-items-center justify-content-center">
                <span class="brand-text">AOVU</span>
            </a>
            <div class="sidebar">
                <div class="user-panel mt-3 pb-3 mb-2 d-flex align-items-center">
                    <div class="image"><img src="{{ asset('adminlte/img/avatar4.png') }}"
                            class="img-circle elevation-1" alt="Quản trị viên"></div>
                    <div class="info">
                        <a href="#" class="d-block">Quản trị viên</a>
                        <span class="text-success small"><i class="fas fa-circle" style="font-size:7px"></i> Đang hoạt
                            động</span>
                    </div>
                </div>
                <nav class="mt-2">
                    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu"
                        data-accordion="false">
                        <li class="nav-header">TỔNG QUAN</li>
                        <li class="nav-item">
                            <a href="{{ route('admin.dashboard') }}"
                                class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-th-large"></i>
                                <p>Dashboard</p>
                            </a>
                        </li>
                        <li class="nav-header">SẢN PHẨM</li>
                        <li class="nav-item">
                            <a href="{{ route('admin.categories.index') }}"
                                class="nav-link {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-sitemap"></i>
                                <p>Danh mục</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('admin.products.index') }}"
                                class="nav-link {{ request()->routeIs('admin.products.*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-tshirt"></i>
                                <p>Sản phẩm</p><span
                                    class="right badge badge-primary">{{ number_format($productCount) }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('admin.variants.index') }}"
                                class="nav-link {{ request()->routeIs('admin.variants.*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-layer-group"></i>
                                <p>Thuộc tính & biến thể</p>
                            </a>
                        </li>
                        <li class="nav-header">VẬN HÀNH</li>
                        <li class="nav-item">
                            <a href="{{ route('admin.resource', ['section' => 'inventory']) }}"
                                class="nav-link {{ ($section ?? '') === 'inventory' ? 'active' : '' }}">
                                <i class="nav-icon fas fa-warehouse"></i>
                                <p>Kho hàng</p><span class="right badge badge-warning">8</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('admin.orders.index') }}"
                                class="nav-link {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-shopping-bag"></i>
                                <p>Đơn hàng</p><span class="right badge badge-danger">{{ number_format($pendingOrderCount) }}</span>
                            </a>
                        </li>
                        <li class="nav-header">KHÁCH HÀNG & TIỆN ÍCH</li>
                        <li class="nav-item">
                            <a href="{{ route('admin.users.index') }}"
                                class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-users"></i>
                                <p>Người dùng</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('admin.resource', ['section' => 'promotions']) }}"
                                class="nav-link {{ ($section ?? '') === 'promotions' ? 'active' : '' }}">
                                <i class="nav-icon fas fa-tags"></i>
                                <p>Khuyến mãi</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('admin.resource', ['section' => 'reviews']) }}"
                                class="nav-link {{ ($section ?? '') === 'reviews' ? 'active' : '' }}">
                                <i class="nav-icon fas fa-star"></i>
                                <p>Đánh giá</p><span class="right badge badge-warning">12</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('admin.returns.index') }}"
                                class="nav-link {{ request()->routeIs('admin.returns.*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-exchange-alt"></i>
                                <p>Đổi / trả hàng</p><span
                                    class="right badge badge-info">{{ number_format($pendingReturnCount) }}</span>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        </aside>

        <div class="content-wrapper">
            <section class="content-header">
                <div class="container-fluid">
                    <div class="row align-items-center">
                        <div class="col-sm-7">
                            <h1>@yield('page-heading', 'Dashboard')</h1>
                            <p class="text-muted mb-0 mt-1">@yield('page-subheading', 'Chào mừng bạn quay trở lại với AOVU Shop.')</p>
                        </div>
                        <div class="col-sm-5">
                            <ol class="breadcrumb float-sm-right mb-0">
                                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Trang chủ</a>
                                </li>
                                @hasSection('breadcrumb')
                                    <li class="breadcrumb-item active">@yield('breadcrumb')</li>
                                @else
                                    <li class="breadcrumb-item active">Dashboard</li>
                                @endif
                            </ol>
                        </div>
                    </div>
                </div>
            </section>
            <section class="content">
                <div class="container-fluid">
                    @yield('content')
                </div>
            </section>
        </div>

        <footer class="main-footer content-footer">
            <strong>&copy; 2026 <span class="text-primary">AOVU Shop</span>.</strong> Giao diện quản trị cửa hàng.
            <div class="float-right d-none d-sm-inline-block">Admin</div>
        </footer>
    </div>
    <script src="{{ asset('adminlte/plugins/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js') }}"></script>
    <script src="{{ asset('adminlte/dist/js/adminlte.min.js') }}"></script>
    @yield('scripts')
    <script src="{{ asset('adminlte/js/admin-navigation.js') }}"></script>
</body>

</html>
