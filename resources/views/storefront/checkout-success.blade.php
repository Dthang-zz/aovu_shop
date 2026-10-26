@extends('layouts.storefront')

@section('title', 'Đặt hàng thành công')

@section('content')
    <div class="card storefront-card mx-auto text-center" style="max-width:640px">
        <div class="card-body py-5">
            <i class="fas fa-check-circle text-success mb-3" style="font-size:3rem"></i>
            <h1 class="h3 font-weight-bold">Đặt hàng thành công</h1>
            <p class="text-muted">Mã đơn hàng của bạn: <strong>{{ $confirmation['order_code'] }}</strong></p>
            <p class="h5">Tổng thanh toán: <strong class="text-primary">{{ number_format($confirmation['total'], 0, ',', '.') }} ₫</strong></p>
            <a href="{{ route('shop.products.index') }}" class="btn btn-primary mt-3">Tiếp tục mua sắm</a>
        </div>
    </div>
@endsection
