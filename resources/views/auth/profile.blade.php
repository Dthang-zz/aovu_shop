@extends('layouts.auth')
@section('title','Hồ sơ tài khoản')
@section('content')
<h3 class="mb-1">Hồ sơ tài khoản</h3><p class="text-muted mb-4">Cập nhật thông tin liên hệ và mật khẩu.</p>
@if($errors->any())<div class="alert alert-danger"><ul class="mb-0 pl-3">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<form method="POST" action="{{ route('account.profile.update') }}">@csrf @method('PUT')
<div class="form-group"><label for="ho_ten">Họ và tên</label><input id="ho_ten" name="ho_ten" class="form-control" value="{{ old('ho_ten',$account->ho_ten) }}" required maxlength="50"></div>
<div class="form-group"><label for="email">Email</label><input id="email" name="email" type="email" class="form-control" value="{{ old('email',$account->email) }}" required maxlength="255"></div>
<div class="form-group"><label for="so_dien_thoai">Số điện thoại</label><input id="so_dien_thoai" name="so_dien_thoai" type="tel" class="form-control" value="{{ old('so_dien_thoai',$account->so_dien_thoai) }}" maxlength="15"></div>
<div class="form-group"><label for="ngay_sinh">Ngày sinh</label><input id="ngay_sinh" name="ngay_sinh" type="date" class="form-control" value="{{ old('ngay_sinh',$account->ngay_sinh?->format('Y-m-d')) }}" max="{{ now()->subDay()->format('Y-m-d') }}"></div>
<div class="form-group"><label for="gioi_tinh">Giới tính</label><select id="gioi_tinh" name="gioi_tinh" class="form-control"><option value="">-- Chọn --</option><option value="1" @selected((string)old('gioi_tinh',$account->gioi_tinh)==='1')>Nam</option><option value="0" @selected((string)old('gioi_tinh',$account->gioi_tinh)==='0')>Nữ</option><option value="2" @selected((string)old('gioi_tinh',$account->gioi_tinh)==='2')>Khác</option></select></div>
<button class="btn btn-primary btn-block">Lưu hồ sơ</button></form>
<hr class="my-4"><h5>Đổi mật khẩu</h5>
<form method="POST" action="{{ route('account.password.update') }}">@csrf @method('PUT')
<div class="form-group"><label for="password_current">Mật khẩu hiện tại</label><input id="password_current" name="password_current" type="password" class="form-control" required autocomplete="current-password"></div>
<div class="form-group"><label for="password">Mật khẩu mới</label><input id="password" name="password" type="password" class="form-control" required minlength="8" autocomplete="new-password"></div>
<div class="form-group"><label for="password_confirmation">Xác nhận mật khẩu mới</label><input id="password_confirmation" name="password_confirmation" type="password" class="form-control" required minlength="8" autocomplete="new-password"></div>
<button class="btn btn-outline-primary btn-block">Đổi mật khẩu</button></form>
<div class="text-center mt-3"><a href="{{ route('shop.products.index') }}">Quay lại cửa hàng</a></div>
@endsection
