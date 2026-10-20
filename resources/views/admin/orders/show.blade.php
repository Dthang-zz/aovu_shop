@extends('layouts.admin')

@section('title', 'Chi tiết đơn hàng')
@section('page-heading', 'Chi tiết đơn hàng')
@section('page-subheading', 'Xem thông tin giao nhận, sản phẩm và lịch sử trạng thái.')
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
                <h3 class="card-title"><i class="fas fa-receipt text-primary mr-2"></i>{{ $order->ma_don_hang }}</h3>
                <span class="badge badge-{{ $order->status_badge }} px-2 py-1">{{ $order->status_label }}</span>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-sm-6 mb-3"><div class="text-muted small">Người nhận</div><div class="font-weight-bold">{{ $order->ten_nguoi_nhan ?: '—' }}</div></div>
                    <div class="col-sm-6 mb-3"><div class="text-muted small">Ngày đặt</div><div class="font-weight-bold">{{ $order->ngay_dat ? \Illuminate\Support\Carbon::parse($order->ngay_dat)->format('H:i, d/m/Y') : '—' }}</div></div>
                    <div class="col-sm-6 mb-3"><div class="text-muted small">Email</div><div>{{ $order->email_nguoi_nhan ?: '—' }}</div></div>
                    <div class="col-sm-6 mb-3"><div class="text-muted small">Điện thoại</div><div>{{ $order->sdt_nguoi_nhan ?: '—' }}</div></div>
                    <div class="col-sm-6 mb-3">
                        <div class="text-muted small">Thanh toán</div>
                        <div>
                            @if ((int) $order->trang_thai_thanh_toan === 1)
                                Đã thanh toán
                            @elseif ((int) $order->trang_thai_thanh_toan === 0)
                                Chưa thanh toán
                            @else
                                Chưa cập nhật
                            @endif
                        </div>
                    </div>
                    <div class="col-12 mb-3"><div class="text-muted small">Địa chỉ giao hàng</div><div>{{ $order->dia_chi_nguoi_nhan ?: '—' }}</div></div>
                    @if ($order->ghi_chu)
                        <div class="col-12"><div class="text-muted small">Ghi chú đặt hàng</div><div>{{ $order->ghi_chu }}</div></div>
                    @endif
                </div>

                <h6 class="font-weight-bold border-top pt-3">Sản phẩm trong đơn</h6>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead><tr><th>Sản phẩm</th><th class="text-right">Đơn giá</th><th class="text-right">Số lượng</th><th class="text-right">Thành tiền</th></tr></thead>
                        <tbody>
                            @forelse ($order->items as $item)
                                <tr>
                                    <td>{{ $item->ten_san_pham ?: 'Sản phẩm' }}</td>
                                    <td class="text-right">{{ number_format((float) $item->don_gia, 0, ',', '.') }} ₫</td>
                                    <td class="text-right">{{ $item->so_luong ?? 0 }}</td>
                                    <td class="text-right">{{ number_format((float) ($item->thanh_tien ?? (($item->don_gia ?? 0) * ($item->so_luong ?? 0))), 0, ',', '.') }} ₫</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted">Chưa có thông tin sản phẩm.</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr><th colspan="3" class="text-right">Tiền hàng</th><td class="text-right">{{ number_format((float) $order->tien_hang, 0, ',', '.') }} ₫</td></tr>
                            <tr><th colspan="3" class="text-right">Phí vận chuyển</th><td class="text-right">{{ number_format((float) $order->phi_van_chuyen, 0, ',', '.') }} ₫</td></tr>
                            <tr><th colspan="3" class="text-right">Giảm giá</th><td class="text-right">−{{ number_format((float) $order->tien_giam, 0, ',', '.') }} ₫</td></tr>
                            <tr><th colspan="3" class="text-right">Tổng thanh toán</th><th class="text-right text-primary">{{ number_format((float) $order->tong_tien, 0, ',', '.') }} ₫</th></tr>
                        </tfoot>
                    </table>
                </div>

                <h6 class="font-weight-bold border-top pt-3">Lịch sử trạng thái</h6>
                @if ($order->status_history->isNotEmpty())
                    <div class="list-group list-group-flush">
                        @foreach ($order->status_history as $entry)
                            <div class="list-group-item px-0">
                                <div class="d-flex justify-content-between">
                                    <span class="badge badge-{{ $entry->status_badge }} px-2 py-1">{{ $entry->status_label }}</span>
                                    <small class="text-muted">{{ $entry->ngay_cap_nhat ? \Illuminate\Support\Carbon::parse($entry->ngay_cap_nhat)->format('H:i, d/m/Y') : '—' }}</small>
                                </div>
                                @if ($entry->ghi_chu)<div class="small mt-2">{{ $entry->ghi_chu }}</div>@endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-muted mb-0">Chưa có lịch sử cập nhật trạng thái.</p>
                @endif
            </div>
            <div class="card-footer bg-white"><a href="{{ route('admin.orders.index') }}" class="btn btn-default"><i class="fas fa-arrow-left mr-1"></i>Quay lại danh sách</a></div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-tasks text-primary mr-2"></i>Cập nhật trạng thái</h3></div>
            @if ($errors->any())
                <div class="card-body pb-0"><div class="alert alert-danger" role="alert"><ul class="mb-0 pl-3">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div></div>
            @endif
            @if ($order->allowed_statuses)
                <form method="POST" action="{{ route('admin.orders.update', $order->id) }}">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <div class="form-group">
                            <label for="trang_thai">Trạng thái tiếp theo</label>
                            <select id="trang_thai" name="trang_thai" class="form-control @error('trang_thai') is-invalid @enderror" required>
                                @foreach ($order->allowed_statuses as $status)
                                    <option value="{{ $status->value }}" @selected((string) old('trang_thai') === (string) $status->value)>{{ $statuses[$status->value] }}</option>
                                @endforeach
                            </select>
                            @error('trang_thai')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group mb-0">
                            <label for="ghi_chu">Ghi chú / lý do hủy</label>
                            <textarea id="ghi_chu" name="ghi_chu" rows="4" maxlength="255" class="form-control @error('ghi_chu') is-invalid @enderror" placeholder="Bắt buộc nhập lý do khi hủy đơn">{{ old('ghi_chu') }}</textarea>
                            @error('ghi_chu')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <small class="form-text text-muted">Lý do hủy sẽ được lưu trong lịch sử để khách hàng tra cứu.</small>
                        </div>
                    </div>
                    <div class="card-footer bg-white"><button type="submit" class="btn btn-primary btn-block"><i class="fas fa-save mr-1"></i>Lưu trạng thái</button></div>
                </form>
            @else
                <div class="card-body"><div class="alert alert-light border mb-0">Đơn hàng đã kết thúc, không thể đổi trạng thái thêm.</div></div>
            @endif
        </div>
        @if ((int) $order->trang_thai === \App\OrderStatus::Cancelled->value)
            <div class="callout callout-warning">
                <h5><i class="fas fa-exclamation-triangle mr-1"></i>Xử lý đơn đã hủy</h5>
                <p class="mb-0 small">Trạng thái hủy và lý do đã được lưu. Nếu khách đã thanh toán, hãy thực hiện hoàn tiền qua cổng thanh toán; hệ thống chưa tự chuyển tiền hoặc điều chỉnh tồn kho.</p>
            </div>
        @endif
    </div>
</div>
@endsection
