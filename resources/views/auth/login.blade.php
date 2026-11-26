@extends('layouts.auth')
@section('title','Đăng nhập')
@section('content')
<h3 class="text-center mb-1">Đăng nhập</h3><p class="text-center text-muted mb-4">Đăng nhập để quản lý tài khoản và đơn hàng của bạn.</p>
@if($errors->any())<div class="alert alert-danger"><ul class="mb-0 pl-3">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<form method="POST" action="{{ route('login.store') }}">@csrf
    <div class="form-group"><label for="email">Email</label><input id="email" name="email" type="email" value="{{ old('email') }}" class="form-control" autocomplete="username" required autofocus></div>
    <div class="form-group"><label for="password">Mật khẩu</label><input id="password" name="password" type="password" class="form-control" autocomplete="current-password" required></div>
    <button class="btn btn-primary btn-block" type="submit"><i class="fas fa-sign-in-alt mr-1"></i>Đăng nhập</button>
</form>
<div class="text-center mt-3">Chưa có tài khoản? <a href="{{ route('register') }}">Đăng ký</a></div>
@endsection
