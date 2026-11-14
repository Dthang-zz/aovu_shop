@extends('layouts.storefront')

@section('title', 'Đăng ký tài khoản')

@section('content')
    <div class="row justify-content-center py-3">
        <div class="col-md-7 col-lg-5">
            <div class="card storefront-card">
                <div class="card-header bg-white text-center py-4 border-0">
                    <div class="bg-success text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-2" style="width: 52px; height: 52px;">
                        <i class="fas fa-user-plus fa-lg"></i>
                    </div>
                    <h1 class="h4 font-weight-bold mb-1">Đăng ký tài khoản mới</h1>
                    <p class="text-muted small mb-0">Trở thành thành viên AOVU Shop để nhận ưu đãi và mua sắm tiện lợi.</p>
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

                    <form action="{{ route('customer.register.store') }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label for="ho_ten" class="small font-weight-bold">Họ và tên <span class="text-danger">*</span></label>
                            <input type="text" id="ho_ten" name="ho_ten" class="form-control" value="{{ old('ho_ten') }}" required maxlength="50" placeholder="Ví dụ: Nguyễn Văn A">
                        </div>

                        <div class="form-group">
                            <label for="email" class="small font-weight-bold">Địa chỉ Email <span class="text-danger">*</span></label>
                            <input type="email" id="email" name="email" class="form-control" value="{{ old('email') }}" required maxlength="255" placeholder="email@example.com">
                        </div>

                        <div class="form-group">
                            <label for="so_dien_thoai" class="small font-weight-bold">Số điện thoại <span class="text-danger">*</span></label>
                            <input type="text" id="so_dien_thoai" name="so_dien_thoai" class="form-control" value="{{ old('so_dien_thoai') }}" required maxlength="15" placeholder="0901234567">
                        </div>

                        <div class="form-group">
                            <label for="mat_khau" class="small font-weight-bold">Mật khẩu <span class="text-danger">*</span></label>
                            <input type="password" id="mat_khau" name="mat_khau" class="form-control" required minlength="6" placeholder="Tối thiểu 6 ký tự">
                        </div>

                        <div class="form-group">
                            <label for="mat_khau_confirmation" class="small font-weight-bold">Xác nhận mật khẩu <span class="text-danger">*</span></label>
                            <input type="password" id="mat_khau_confirmation" name="mat_khau_confirmation" class="form-control" required minlength="6" placeholder="Nhập lại mật khẩu">
                        </div>

                        <button type="submit" class="btn btn-success btn-block py-2 font-weight-bold mt-4">
                            <i class="fas fa-check-circle mr-2"></i>Đăng ký ngay
                        </button>
                    </form>

                    <div class="text-center mt-3 pt-3 border-top small">
                        <span>Đã có tài khoản? </span>
                        <a href="{{ route('customer.login') }}" class="font-weight-bold text-primary">Đăng nhập tại đây</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
