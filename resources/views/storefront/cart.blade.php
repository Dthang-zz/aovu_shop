@extends('layouts.storefront')

@section('title', 'Giỏ hàng')

@section('content')
    <h1 class="h3 font-weight-bold mb-4">Giỏ hàng</h1>
    <div class="card storefront-card">
        <div class="card-body p-0 table-responsive">
            <table class="table mb-0">
                <thead><tr><th class="pl-3">Sản phẩm</th><th>Đơn giá</th><th>Số lượng</th><th>Thành tiền</th><th></th></tr></thead>
                <tbody>
                    @foreach ($quote['lines'] as $index => $line)
                        <tr>
                            <td class="pl-3">
                                <strong>{{ $line['product']->ten_san_pham }}</strong>
                                @if ($line['variant_label'])<div class="small text-muted">{{ $line['variant_label'] }}</div>@endif
                            </td>
                            <td>{{ number_format($line['unit_price'], 0, ',', '.') }} ₫</td>
                            <td>
                                <form method="POST" action="{{ route('shop.cart.items.update', array_keys(session('storefront_cart', []))[$index]) }}" class="d-flex">
                                    @csrf
                                    @method('PATCH')
                                    <input type="number" name="quantity" value="{{ $line['quantity'] }}" min="1" max="100" class="form-control form-control-sm" style="width:80px">
                                    <button class="btn btn-default btn-sm ml-1" aria-label="Cập nhật số lượng"><i class="fas fa-sync"></i></button>
                                </form>
                            </td>
                            <td>{{ number_format($line['line_total'], 0, ',', '.') }} ₫</td>
                            <td>
                                <form method="POST" action="{{ route('shop.cart.items.destroy', array_keys(session('storefront_cart', []))[$index]) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-link text-danger" aria-label="Xóa sản phẩm"><i class="far fa-trash-alt"></i></button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white d-flex justify-content-between align-items-center">
            <a href="{{ route('shop.products.index') }}" class="btn btn-default">Tiếp tục mua sắm</a>
            <div class="text-right">
                <div class="h5 mb-1">Tạm tính: <strong>{{ number_format($quote['subtotal'], 0, ',', '.') }} ₫</strong></div>
                <div class="small text-muted mb-2">Có mã giảm giá? Nhập ở bước đặt hàng.</div>
                <a href="{{ route('shop.checkout') }}" class="btn btn-primary">Tiến hành đặt hàng</a>
            </div>
        </div>
    </div>
@endsection
