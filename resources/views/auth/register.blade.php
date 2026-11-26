@extends('layouts.auth')
@section('title','Đăng ký tài khoản')
@section('content')
<div class="text-center mb-4">
    <div class="rounded-circle bg-primary d-inline-flex align-items-center justify-content-center mb-3" style="width:64px;height:64px"><i class="fas fa-user-plus text-white fa-lg"></i></div>
    <h3 class="font-weight-bold mb-1">Tạo tài khoản AOVU</h3>
    <p class="text-muted mb-0">Đăng ký để mua sắm và theo dõi đơn hàng dễ dàng hơn.</p>
</div>
@if($errors->any())<div class="alert alert-danger"><ul class="mb-0 pl-3">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<form method="POST" action="{{ route('register.store') }}">@csrf
    <div class="form-group"><label for="ho_ten">Họ và tên <span class="text-danger">*</span></label><input id="ho_ten" name="ho_ten" type="text" value="{{ old('ho_ten') }}" class="form-control" maxlength="50" autocomplete="name" placeholder="Nguyễn Văn A" required autofocus></div>
    <div class="form-group"><label for="email">Email <span class="text-danger">*</span></label><input id="email" name="email" type="email" value="{{ old('email') }}" class="form-control" autocomplete="email" placeholder="email@example.com" required></div>
    <div class="form-group"><label for="so_dien_thoai">Số điện thoại</label><input id="so_dien_thoai" name="so_dien_thoai" type="tel" value="{{ old('so_dien_thoai') }}" class="form-control" maxlength="15" autocomplete="tel" placeholder="09xxxxxxxx"></div>
    <div class="form-row"><div class="form-group col-md-6"><label for="ngay_sinh">Ngày sinh</label><input id="ngay_sinh" name="ngay_sinh" type="date" value="{{ old('ngay_sinh') }}" max="{{ now()->toDateString() }}" class="form-control"></div>
    <div class="form-group col-md-6"><label for="gioi_tinh">Giới tính</label><select id="gioi_tinh" name="gioi_tinh" class="form-control"><option value="">-- Chọn --</option><option value="0" @selected(old('gioi_tinh')==='0')>Nam</option><option value="1" @selected(old('gioi_tinh')==='1')>Nữ</option><option value="2" @selected(old('gioi_tinh')==='2')>Khác / Không muốn nêu</option></select></div></div>
<div class="form-group"><label for="password">Mật khẩu <span class="text-danger">*</span></label><input id="password" name="password" type="password" class="form-control" minlength="8" autocomplete="new-password" placeholder="Tối thiểu 8 ký tự" required></div>
<div class="form-group"><label for="password_confirmation">Xác nhận mật khẩu <span class="text-danger">*</span></label><input id="password_confirmation" name="password_confirmation" type="password" class="form-control" minlength="8" autocomplete="new-password" placeholder="Nhập lại mật khẩu" required></div>
<div class="form-check mb-3"><input id="terms" name="terms" type="checkbox" value="1" class="form-check-input" required><label class="form-check-label" for="terms">Tôi đồng ý với điều khoản sử dụng.</label></div>
<button class="btn btn-primary btn-block py-2" type="submit"><i class="fas fa-user-plus mr-1"></i> Tạo tài khoản</button>
</form>
<div class="text-center mt-3">Đã có tài khoản? <a href="{{ route('login') }}">Đăng nhập</a></div>
@endsection
