@extends('layouts.admin')

@section('title', 'Chi tiết đơn hàng')
@section('page-heading', 'Chi tiết đơn hàng')
@section('page-subheading', 'Xem thông tin giao nhận, sản phẩm và tiến độ xử lý.')
@section('breadcrumb')
    <a href="{{ route('admin.orders.index') }}">Đơn hàng</a>
@endsection

@section('content')
@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle mr-1"></i>{{ session('success') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Đóng"><span aria-hidden="true">&times;</span></button>
    </div>
@endif

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title"><i class="fas fa-file-invoice text-primary mr-2"></i>{{ $order->ma_don_hang }}</h3>
                <span class="badge badge-{{ $order->status_badge }} px-2 py-1">{{ $order->status_label }}</span>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-sm-6 mb-3">
                        <div class="border rounded p-3 h-100">
                            <div class="text-muted small text-uppercase mb-1"><i class="fas fa-user mr-1"></i>Người đặt</div>
                            @if ($order->tai_khoan_id && $order->account_name !== null)
                                <div class="font-weight-bold">{{ $order->account_name ?: 'Khách hàng' }}</div>
                                <div class="text-muted small">{{ $order->account_phone ?: '—' }} · {{ $order->account_email ?: '—' }}</div>
                                <div class="small mt-1"><a href="{{ route('admin.users.show', $order->tai_khoan_id) }}">Xem tài khoản #{{ $order->tai_khoan_id }}</a></div>
                            @else
                                <div class="font-weight-bold">Khách vãng lai</div>
                                <div class="text-muted small">Đơn không gắn với tài khoản nào.</div>
                            @endif
                        </div>
                    </div>
                    <div class="col-sm-6 mb-3">
                        <div class="border rounded p-3 h-100">
                            <div class="text-muted small text-uppercase mb-1"><i class="fas fa-shipping-fast mr-1"></i>Người nhận</div>
                            <div class="font-weight-bold">{{ $order->ten_nguoi_nhan ?: '—' }}</div>
                            <div class="text-muted small">{{ $order->sdt_nguoi_nhan ?: '—' }} · {{ $order->email_nguoi_nhan ?: '—' }}</div>
                            <div class="small mt-1">{{ $order->dia_chi_nguoi_nhan ?: 'Chưa có địa chỉ' }}</div>
                        </div>
                    </div>
                    <div class="col-sm-6 mb-3"><div class="text-muted small">Ngày đặt hàng</div><div class="font-weight-bold">{{ $order->ngay_dat ? \Illuminate\Support\Carbon::parse($order->ngay_dat)->format('d/m/Y H:i') : '—' }}</div></div>
                    <div class="col-sm-6 mb-3"><div class="text-muted small">Thanh toán</div><div class="font-weight-bold">{{ $order->payment_status_label }}</div><div class="text-muted small">{{ $order->payment_method_name ?: 'Chưa chọn phương thức' }}</div></div>
                    @if ($order->ghi_chu)
                        <div class="col-12 mb-3"><div class="text-muted small">Ghi chú của khách</div><div>{{ $order->ghi_chu }}</div></div>
                    @endif
                </div>

                <h6 class="font-weight-bold border-top pt-3">Sản phẩm trong đơn</h6>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead><tr><th>Sản phẩm</th><th class="text-right">Đơn giá</th><th class="text-center">Số lượng</th><th class="text-right">Thành tiền</th></tr></thead>
                        <tbody>
                            @forelse ($items as $item)
                                <tr>
                                    <td>
                                        {{ $item->ten_san_pham ?: 'Sản phẩm' }}
                                        @if ($item->ten_khuyen_mai)
                                            <div class="small text-danger">{{ $item->ten_khuyen_mai }} ({{ $item->phan_tram_giam !== null ? rtrim(rtrim(number_format((float) $item->phan_tram_giam, 2, ',', '.'), '0'), ',').'%' : '-'.number_format((float) ($item->tien_giam_khuyen_mai ?? 0) / max(1, (int) $item->so_luong), 0, ',', '.').' ₫/sp' }})</div>
                                        @endif
                                    </td>
                                    <td class="text-right">
                                        @if (isset($item->gia_goc) && $item->gia_goc > $item->don_gia)
                                            <div class="text-muted small"><s>{{ number_format((float) $item->gia_goc, 0, ',', '.') }} ₫</s></div>
                                        @endif
                                        {{ number_format((float) $item->don_gia, 0, ',', '.') }} ₫
                                    </td>
                                    <td class="text-center">{{ $item->so_luong ?? 0 }}</td>
                                    <td class="text-right">{{ number_format((float) ($item->thanh_tien ?? ((float) $item->don_gia * (int) $item->so_luong)), 0, ',', '.') }} ₫</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted">Đơn hàng chưa có thông tin sản phẩm.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="row justify-content-end">
                    <div class="col-md-5">
                        <div class="d-flex justify-content-between py-1"><span class="text-muted">Tiền hàng</span><span>{{ number_format((float) $order->tien_hang, 0, ',', '.') }} ₫</span></div>
                        <div class="d-flex justify-content-between py-1"><span class="text-muted">Phí vận chuyển</span><span>{{ number_format((float) $order->phi_van_chuyen, 0, ',', '.') }} ₫</span></div>
                        @if ($promotionDiscountsAvailable)
                            <div class="d-flex justify-content-between py-1"><span class="text-muted">Giảm từ chương trình</span><span>-{{ number_format((float) $order->tien_giam_khuyen_mai, 0, ',', '.') }} ₫</span></div>
                            <div class="d-flex justify-content-between py-1"><span class="text-muted">Giảm từ voucher</span><span>-{{ number_format((float) $order->tien_giam_voucher, 0, ',', '.') }} ₫</span></div>
                        @else
                            <div class="d-flex justify-content-between py-1"><span class="text-muted">Giảm giá</span><span>-{{ number_format((float) $order->tien_giam, 0, ',', '.') }} ₫</span></div>
                        @endif
                        <div class="d-flex justify-content-between border-top pt-2 mt-1 font-weight-bold"><span>Tổng thanh toán</span><span class="text-primary">{{ number_format((float) $order->tong_tien, 0, ',', '.') }} ₫</span></div>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-white"><a href="{{ route('admin.orders.index') }}" class="btn btn-default"><i class="fas fa-arrow-left mr-1"></i>Quay lại danh sách</a></div>
        </div>

        @include('admin.orders.partials.returns')

        @include('admin.orders.partials.history')
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-tasks text-primary mr-2"></i>Trạng thái đơn hàng</h3></div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted">Trạng thái</span>
                    <span class="badge badge-{{ $order->status_badge }} px-2 py-1">{{ $order->status_label }}</span>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-muted">Thanh toán</span>
                    <span class="font-weight-bold">{{ $order->payment_status_label }}</span>
                </div>
            </div>
            @if ($errors->any())
                <div class="card-body pt-0 pb-0"><div class="alert alert-danger" role="alert"><ul class="mb-0 pl-3">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div></div>
            @endif
            @if ($allowedStatuses)
                <form method="POST" action="{{ route('admin.orders.update', $order->id) }}">
                    @csrf
                    @method('PUT')
                    <div class="card-body border-top">
                        <div class="form-group">
                            <label for="trang_thai">Cập nhật trạng thái</label>
                            <select name="trang_thai" id="trang_thai" class="form-control @error('trang_thai') is-invalid @enderror" required>
                                @foreach ($allowedStatuses as $status)
                                    <option value="{{ $status }}" @selected((string) old('trang_thai') === (string) $status)>{{ $statuses[$status] }}</option>
                                @endforeach
                            </select>
                            @error('trang_thai')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <small class="form-text text-muted">Chuyển sang Hoàn thành sẽ ghi nhận đã thanh toán; Hủy đơn sẽ hoàn lại tồn kho.</small>
                        </div>
                        <div class="form-group mb-0">
                            <label for="ghi_chu">Ghi chú xử lý</label>
                            <textarea name="ghi_chu" id="ghi_chu" rows="3" maxlength="255" class="form-control @error('ghi_chu') is-invalid @enderror" placeholder="Ghi chú tùy chọn...">{{ old('ghi_chu') }}</textarea>
                            @error('ghi_chu')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="card-footer bg-white"><button type="submit" class="btn btn-primary btn-block"><i class="fas fa-save mr-1"></i>Lưu trạng thái</button></div>
                </form>
            @else
                <div class="card-footer bg-white">
                    <div class="text-muted small"><i class="fas fa-check-circle text-success mr-1"></i>Đơn hàng đã kết thúc, không thể cập nhật thêm.</div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
