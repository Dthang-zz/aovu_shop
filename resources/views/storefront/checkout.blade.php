@extends('layouts.storefront')

@section('title', 'Thanh toán')

@section('content')
    <h1 class="h3 font-weight-bold mb-4">Thông tin đặt hàng</h1>
    @if ($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif
    <form method="GET" action="{{ route('shop.checkout') }}" class="card storefront-card mb-3">
        <div class="card-body">
            <label for="voucher_code">Mã voucher</label>
            <div class="input-group">
                <input id="voucher_code" name="voucher_code" class="form-control" maxlength="50" value="{{ old('voucher_code', $quote['voucher_code']) }}" placeholder="Nhập mã giảm giá">
                <div class="input-group-append"><button type="submit" class="btn btn-outline-secondary">Áp dụng</button></div>
            </div>
        </div>
    </form>
    <form method="POST" action="{{ route('shop.checkout.store') }}">
        @csrf
        @if ($quote['voucher_code'])
            <input type="hidden" name="voucher_code" value="{{ $quote['voucher_code'] }}">
        @endif
        <div class="row">
            <div class="col-lg-7">
                <div class="card storefront-card">
                    <div class="card-header bg-white"><strong>Thông tin nhận hàng</strong></div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="ten_nguoi_nhan">Họ tên</label>
                            <input id="ten_nguoi_nhan" name="ten_nguoi_nhan" class="form-control" required maxlength="100" value="{{ old('ten_nguoi_nhan') }}">
                        </div>
                        <div class="form-group">
                            <label for="email_nguoi_nhan">Email</label>
                            <input id="email_nguoi_nhan" type="email" name="email_nguoi_nhan" class="form-control" required maxlength="255" value="{{ old('email_nguoi_nhan') }}">
                        </div>
                        <div class="form-group">
                            <label for="sdt_nguoi_nhan">Số điện thoại</label>
                            <input id="sdt_nguoi_nhan" name="sdt_nguoi_nhan" class="form-control" required maxlength="15" value="{{ old('sdt_nguoi_nhan') }}">
                        </div>
                        <div class="form-group">
                            <label for="dia_chi_nguoi_nhan">Địa chỉ nhận hàng</label>
                            <input id="dia_chi_nguoi_nhan" name="dia_chi_nguoi_nhan" class="form-control" required maxlength="255" value="{{ old('dia_chi_nguoi_nhan') }}">
                        </div>
                        <div class="form-group mb-0">
                            <label for="ghi_chu">Ghi chú</label>
                            <textarea id="ghi_chu" name="ghi_chu" class="form-control" rows="2" maxlength="2000">{{ old('ghi_chu') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="card storefront-card">
                    <div class="card-header bg-white"><strong>Đơn hàng</strong></div>
                    <div class="card-body">
                        @foreach ($quote['lines'] as $line)
                            <div class="d-flex justify-content-between mb-2">
                                <span>{{ $line['product']->ten_san_pham }}{{ $line['variant_label'] ? ' ('.$line['variant_label'].')' : '' }} × {{ $line['quantity'] }}</span>
                                <span>{{ number_format($line['line_total'], 0, ',', '.') }} ₫</span>
                            </div>
                        @endforeach
                        <hr>
                        <div class="d-flex justify-content-between"><span>Tổng giá gốc</span><span>{{ number_format($quote['gross_total'], 0, ',', '.') }} ₫</span></div>
                        <div class="d-flex justify-content-between text-danger"><span>Khuyến mãi</span><span>-{{ number_format($quote['promotion_discount'], 0, ',', '.') }} ₫</span></div>
                        @if ($quote['voucher'])
                            <div class="d-flex justify-content-between text-success"><span>Voucher {{ $quote['voucher_code'] }}</span><span>-{{ number_format($quote['voucher_discount'], 0, ',', '.') }} ₫</span></div>
                        @endif
                        <div class="d-flex justify-content-between"><span>Phí vận chuyển</span><span>Miễn phí</span></div>
                        <hr>
                        <div class="d-flex justify-content-between h5"><strong>Thanh toán</strong><strong class="text-primary">{{ number_format($quote['total'], 0, ',', '.') }} ₫</strong></div>
                        <button type="submit" class="btn btn-primary btn-block mt-3">Đặt hàng</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection
