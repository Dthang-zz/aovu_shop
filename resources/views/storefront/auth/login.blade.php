@extends('layouts.storefront')

@section('title', 'Đăng nhập tài khoản')

@section('content')
    <div class="row justify-content-center py-3">
        <div class="col-md-7 col-lg-5">
            <div class="card storefront-card mb-4">
                <div class="card-header bg-white text-center py-4 border-0">
                    <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-2" style="width: 52px; height: 52px;">
                        <i class="fas fa-user-lock fa-lg"></i>
                    </div>
                    <h1 class="h4 font-weight-bold mb-1">Đăng nhập tài khoản</h1>
                    <p class="text-muted small mb-0">Quản lý hồ sơ, đơn hàng và sổ địa chỉ giao nhận của bạn.</p>
                </div>
                <div class="card-body px-4 pt-0 pb-4">
                    @if ($errors->any())
                        <div class="alert alert-danger py-2 small">
                            <ul class="mb-0 pl-3">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('customer.login.store') }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label for="email" class="small font-weight-bold">Địa chỉ Email</label>
                            <div class="input-group">
                                <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-envelope"></i></span></div>
                                <input type="email" id="email" name="email" class="form-control" value="{{ old('email') }}" required autofocus placeholder="nhapemail@example.com">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="mat_khau" class="small font-weight-bold">Mật khẩu</label>
                            <div class="input-group">
                                <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-lock"></i></span></div>
                                <input type="password" id="mat_khau" name="mat_khau" class="form-control" required placeholder="Nhập mật khẩu">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block py-2 font-weight-bold mt-4">
                            <i class="fas fa-sign-in-alt mr-2"></i>Đăng nhập
                        </button>
                    </form>

                    <div class="text-center mt-3 pt-3 border-top small">
                        <span>Chưa có tài khoản? </span>
                        <a href="{{ route('customer.register') }}" class="font-weight-bold text-primary">Đăng ký thành viên mới</a>
                    </div>
                </div>
            </div>

            @if ($demoCustomers->isNotEmpty())
                <div class="card storefront-card border-info">
                    <div class="card-header bg-light py-2 px-3 small font-weight-bold text-info">
                        <i class="fas fa-bolt mr-1"></i>Tài khoản khách hàng mẫu (Demo / Báo cáo đồ án)
                    </div>
                    <div class="card-body p-3">
                        <p class="text-muted small mb-2">Bấm chọn một tài khoản bên dưới để đăng nhập tức thì mà không cần nhập mật khẩu:</p>
                        <div class="list-group list-group-flush">
                            @foreach ($demoCustomers as $demo)
                                <form action="{{ route('customer.demo-login', $demo->id) }}" method="POST" class="mb-1">
                                    @csrf
                                    <button type="submit" class="list-group-item list-group-item-action py-2 px-2 d-flex justify-content-between align-items-center rounded border">
                                        <div class="text-left">
                                            <strong class="d-block small text-dark">{{ $demo->ho_ten }}</strong>
                                            <span class="text-muted" style="font-size: 11px;">{{ $demo->email }}</span>
                                        </div>
                                        <span class="badge badge-primary badge-pill">Đăng nhập</span>
                                    </button>
                                </form>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
