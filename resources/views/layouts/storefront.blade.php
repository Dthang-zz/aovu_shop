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
        body { background: #f5f7fb; min-height: 100vh; display: flex; flex-direction: column; }
        .storefront-header { background: #18212f; }
        .storefront-header a { color: #f1f5f9; text-decoration: none; }
        .storefront-header a:hover { color: #38bdf8; }
        .storefront-card { border: 0; border-radius: 12px; box-shadow: 0 4px 16px rgba(31,45,61,.06); transition: transform .15s ease, box-shadow .15s ease; }
        .storefront-image { height: 230px; object-fit: cover; }
        .storefront-banner { height: min(36vw, 430px); min-height: 160px; object-fit: cover; }
        .price-old { color: #8993a2; text-decoration: line-through; }
        .wishlist-heart-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: rgba(255,255,255,0.9);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 0;
            color: #94a3b8;
            box-shadow: 0 2px 6px rgba(0,0,0,0.15);
            transition: all .2s;
            cursor: pointer;
            z-index: 5;
        }
        .wishlist-heart-btn:hover {
            transform: scale(1.12);
            color: #ef4444;
            background: #fff;
        }
        .wishlist-heart-btn.active {
            color: #ef4444;
        }
        .nav-avatar {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            object-fit: cover;
            border: 1px solid rgba(255,255,255,0.4);
        }
        .footer-storefront {
            background: #0f172a;
            color: #94a3b8;
            margin-top: auto;
        }
        .footer-storefront a { color: #cbd5e1; text-decoration: none; }
        .footer-storefront a:hover { color: #38bdf8; }
    </style>
    @yield('styles')
</head>
<body>
    <header class="storefront-header py-3 shadow-sm sticky-top">
        <div class="container d-flex justify-content-between align-items-center flex-wrap">
            <div class="d-flex align-items-center">
                <a href="{{ route('shop.products.index') }}" class="h4 mb-0 font-weight-bold mr-4 text-white">
                    <i class="fas fa-tshirt text-primary mr-2"></i>AOVU Shop
                </a>
                <nav class="d-none d-md-flex align-items-center">
                    <a class="mr-3 font-weight-500" href="{{ route('shop.products.index') }}">
                        <i class="fas fa-store mr-1 text-muted"></i>Sản phẩm
                    </a>
                    <a class="mr-3 font-weight-500" href="{{ route('news.index') }}">
                        <i class="far fa-newspaper mr-1 text-muted"></i>Tin tức
                    </a>
                    <a class="mr-3 font-weight-500" href="{{ route('cskh.index') }}">
                        <i class="fas fa-headset mr-1 text-muted"></i>CSKH & Hỗ trợ
                    </a>
                </nav>
            </div>

            <div class="d-flex align-items-center mt-2 mt-md-0">
                <!-- Yêu thích -->
                <a href="{{ route('shop.wishlist.index') }}" class="mr-3 position-relative text-white" title="Sản phẩm yêu thích">
                    <i class="fas fa-heart text-danger mr-1"></i>
                    <span class="d-none d-sm-inline">Yêu thích</span>
                    <span id="wishlist-badge-count" class="badge badge-danger badge-pill ml-1 {{ ($wishlistCount ?? 0) > 0 ? '' : 'd-none' }}">
                        {{ $wishlistCount ?? 0 }}
                    </span>
                </a>

                <!-- Giỏ hàng -->
                <a href="{{ route('shop.cart') }}" class="mr-3 position-relative text-white" title="Giỏ hàng">
                    <i class="fas fa-shopping-cart text-warning mr-1"></i>
                    <span class="d-none d-sm-inline">Giỏ hàng</span>
                    <span class="badge badge-warning badge-pill ml-1 {{ ($cartCount ?? 0) > 0 ? '' : 'd-none' }}">
                        {{ $cartCount ?? 0 }}
                    </span>
                </a>

                <!-- Tài khoản -->
                @if (! empty($currentCustomer))
                    <div class="dropdown">
                        <a href="#" class="dropdown-toggle text-white d-flex align-items-center" id="customerDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            @if ($currentCustomer->anh_dai_dien)
                                <img src="{{ $currentCustomer->avatarUrl() }}" alt="{{ $currentCustomer->ho_ten }}" class="nav-avatar mr-2">
                            @else
                                <div class="nav-avatar bg-primary text-white d-inline-flex align-items-center justify-content-center mr-2">
                                    <i class="fas fa-user small"></i>
                                </div>
                            @endif
                            <span class="d-none d-md-inline font-weight-bold">{{ Str::limit($currentCustomer->ho_ten, 18) }}</span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right shadow border-0" aria-labelledby="customerDropdown">
                            <div class="dropdown-header text-truncate">
                                <strong>{{ $currentCustomer->ho_ten }}</strong><br>
                                <small class="text-muted">{{ $currentCustomer->email }}</small>
                            </div>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="{{ route('customer.profile') }}">
                                <i class="fas fa-id-card text-primary mr-2"></i>Hồ sơ cá nhân
                            </a>
                            <a class="dropdown-item" href="{{ route('customer.addresses.index') }}">
                                <i class="fas fa-map-marked-alt text-info mr-2"></i>Sổ địa chỉ nhận hàng
                            </a>
                            <a class="dropdown-item" href="{{ route('customer.orders') }}">
                                <i class="fas fa-box-open text-warning mr-2"></i>Đơn hàng của tôi
                            </a>
                            <a class="dropdown-item" href="{{ route('shop.wishlist.index') }}">
                                <i class="fas fa-heart text-danger mr-2"></i>Sản phẩm yêu thích
                            </a>
                            <div class="dropdown-divider"></div>
                            <form action="{{ route('customer.logout') }}" method="POST" class="m-0">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger">
                                    <i class="fas fa-sign-out-alt mr-2"></i>Đăng xuất
                                </button>
                            </form>
                        </div>
                    </div>
                @else
                    <div class="d-flex align-items-center">
                        <a href="{{ route('customer.login') }}" class="btn btn-outline-light btn-sm mr-2">
                            <i class="fas fa-sign-in-alt mr-1"></i>Đăng nhập
                        </a>
                        <a href="{{ route('customer.register') }}" class="btn btn-primary btn-sm d-none d-sm-inline-block">
                            Đăng ký
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </header>

    <main class="container py-4 flex-grow-1">
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif
        @if (session('info'))
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <i class="fas fa-info-circle mr-2"></i>{{ session('info') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle mr-2"></i>{{ session('error') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

        @yield('content')
    </main>

    <footer class="footer-storefront py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <h5 class="text-white font-weight-bold"><i class="fas fa-tshirt text-primary mr-2"></i>AOVU Shop</h5>
                    <p class="small">Thương hiệu thời trang nam nữ hiện đại, thanh lịch và dẫn đầu xu hướng. Tự hào mang tới trải nghiệm mua sắm tiện lợi và sản phẩm chất lượng tốt nhất.</p>
                    <p class="small mb-1"><i class="fas fa-map-marker-alt text-danger mr-2"></i>123 Cầu Giấy, Hà Nội</p>
                    <p class="small mb-1"><i class="fas fa-phone-alt text-success mr-2"></i>Hotline: 1900 6868 (8:00 - 22:00)</p>
                    <p class="small mb-0"><i class="fas fa-envelope text-info mr-2"></i>Email: cskh@aovushop.vn</p>
                </div>
                <div class="col-md-3 col-6 mb-3">
                    <h6 class="text-white font-weight-bold">Chăm sóc khách hàng</h6>
                    <ul class="list-unstyled small">
                        <li class="mb-2"><a href="{{ route('cskh.index') }}"><i class="fas fa-chevron-right fa-xs mr-1 text-muted"></i>Trung tâm hỗ trợ CSKH</a></li>
                        <li class="mb-2"><a href="{{ route('cskh.index') }}#tra-cuu"><i class="fas fa-chevron-right fa-xs mr-1 text-muted"></i>Tra cứu đơn hàng</a></li>
                        <li class="mb-2"><a href="{{ route('cskh.index') }}#faq"><i class="fas fa-chevron-right fa-xs mr-1 text-muted"></i>Chính sách đổi trả 7 ngày</a></li>
                        <li class="mb-2"><a href="{{ route('cskh.index') }}#size-guide"><i class="fas fa-chevron-right fa-xs mr-1 text-muted"></i>Bảng hướng dẫn chọn size</a></li>
                        <li class="mb-2"><a href="{{ route('cskh.index') }}#lien-he"><i class="fas fa-chevron-right fa-xs mr-1 text-muted"></i>Gửi phản ánh & khiếu nại</a></li>
                    </ul>
                </div>
                <div class="col-md-3 col-6 mb-3">
                    <h6 class="text-white font-weight-bold">Tài khoản & Dịch vụ</h6>
                    <ul class="list-unstyled small">
                        <li class="mb-2"><a href="{{ route('customer.profile') }}"><i class="fas fa-chevron-right fa-xs mr-1 text-muted"></i>Thông tin cá nhân</a></li>
                        <li class="mb-2"><a href="{{ route('customer.addresses.index') }}"><i class="fas fa-chevron-right fa-xs mr-1 text-muted"></i>Sổ địa chỉ nhận hàng</a></li>
                        <li class="mb-2"><a href="{{ route('customer.orders') }}"><i class="fas fa-chevron-right fa-xs mr-1 text-muted"></i>Theo dõi đơn hàng đã mua</a></li>
                        <li class="mb-2"><a href="{{ route('shop.wishlist.index') }}"><i class="fas fa-chevron-right fa-xs mr-1 text-muted"></i>Danh sách yêu thích</a></li>
                    </ul>
                </div>
                <div class="col-md-2 mb-3">
                    <h6 class="text-white font-weight-bold">Cam kết cửa hàng</h6>
                    <ul class="list-unstyled small text-muted">
                        <li class="mb-2"><i class="fas fa-shield-alt text-primary mr-1"></i>Hàng chính hãng 100%</li>
                        <li class="mb-2"><i class="fas fa-sync-alt text-success mr-1"></i>Đổi hàng trong 7 ngày</li>
                        <li class="mb-2"><i class="fas fa-shipping-fast text-warning mr-1"></i>Giao hàng toàn quốc</li>
                        <li class="mb-2"><i class="fas fa-headset text-info mr-1"></i>Hỗ trợ 24/7</li>
                    </ul>
                </div>
            </div>
            <hr class="border-secondary my-3">
            <div class="text-center small text-muted">
                &copy; {{ date('Y') }} AOVU Shop — Đồ án tốt nghiệp hệ thống quản lý & bán hàng thời trang. All rights reserved.
            </div>
        </div>
    </footer>

    @include('storefront.partials.support-chat-widget')

    <script src="{{ asset('adminlte/plugins/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script>
        // Xử lý AJAX toggle sản phẩm yêu thích toàn trang
        $(document).on('click', '.btn-wishlist-toggle', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var url = $btn.data('url');

            $.ajax({
                url: url,
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'Accept': 'application/json'
                },
                success: function(res) {
                    if (res.success) {
                        if (res.is_favorited) {
                            $btn.addClass('active');
                            $btn.find('i').removeClass('far text-muted').addClass('fas text-danger');
                            if ($btn.find('.wishlist-text').length) {
                                $btn.find('.wishlist-text').text('Đã yêu thích');
                            }
                        } else {
                            $btn.removeClass('active');
                            $btn.find('i').removeClass('fas text-danger').addClass('far text-muted');
                            if ($btn.find('.wishlist-text').length) {
                                $btn.find('.wishlist-text').text('Thêm vào yêu thích');
                            }
                        }

                        // Cập nhật số lượng trên badge header
                        var $badge = $('#wishlist-badge-count');
                        if ($badge.length) {
                            $badge.text(res.count);
                            if (res.count > 0) {
                                $badge.removeClass('d-none');
                            } else {
                                $badge.addClass('d-none');
                            }
                        }
                    }
                },
                error: function(xhr) {
                    if (xhr.status === 419) {
                        location.reload();
                    } else {
                        alert('Không thể cập nhật danh sách yêu thích. Vui lòng thử lại.');
                    }
                }
            });
        });

        @if (session('open_support_chat'))
            $(function() {
                var chatBtn = document.getElementById('support-chat-toggle') || document.querySelector('.support-chat-btn');
                if (chatBtn) {
                    chatBtn.click();
                }
            });
        @endif
    </script>
    @yield('scripts')
</body>
</html>
