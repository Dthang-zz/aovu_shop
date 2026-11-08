@extends('layouts.admin')

@section('title', 'Chi tiết mã giảm giá')
@section('page-heading', 'Mã '.$voucher->ma_voucher)
@section('page-subheading', $voucher->mo_ta ?: 'Thông số mã giảm giá và các đơn hàng đã sử dụng.')
@section('breadcrumb')
    <a href="{{ route('admin.promotions.index') }}">Khuyến mãi</a> / Chi tiết
@endsection

@section('content')
    @php([$stateLabel, $stateBadge] = $voucher->stateBadge())

    <div class="d-flex justify-content-between align-items-center mb-3">
        <a href="{{ route('admin.promotions.index') }}" class="btn btn-default">
            <i class="fas fa-arrow-left mr-1"></i>Quay lại danh sách
        </a>
        <a href="{{ route('admin.promotions.edit', $voucher) }}" class="btn btn-primary">
            <i class="fas fa-edit mr-1"></i>Chỉnh sửa mã
        </a>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-ticket-alt text-primary mr-2"></i>Thông số mã giảm giá</h3>
        </div>
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">Mã</dt>
                <dd class="col-sm-9"><code class="font-weight-bold">{{ $voucher->ma_voucher }}</code></dd>

                <dt class="col-sm-3">Hình thức</dt>
                <dd class="col-sm-9">{{ \App\Models\Voucher::types()[$voucher->loai_giam] ?? 'Không xác định' }}</dd>

                <dt class="col-sm-3">Mức giảm</dt>
                <dd class="col-sm-9">{{ $voucher->discountLabel() }}</dd>

                @if ($voucher->loai_giam === \App\Models\Voucher::TYPE_PERCENT)
                    <dt class="col-sm-3">Giảm tối đa</dt>
                    <dd class="col-sm-9">{{ $voucher->giam_toi_da ? number_format($voucher->giam_toi_da, 0, ',', '.').' ₫' : 'Không giới hạn' }}</dd>
                @endif

                <dt class="col-sm-3">Đơn tối thiểu</dt>
                <dd class="col-sm-9">{{ $voucher->don_toi_thieu ? number_format($voucher->don_toi_thieu, 0, ',', '.').' ₫' : 'Không yêu cầu' }}</dd>

                <dt class="col-sm-3">Lượt sử dụng</dt>
                <dd class="col-sm-9">{{ number_format($voucher->da_su_dung ?? 0) }} / {{ $voucher->so_luong !== null ? number_format($voucher->so_luong) : 'Không giới hạn' }}</dd>

                <dt class="col-sm-3">Thời gian</dt>
                <dd class="col-sm-9">{{ $voucher->ngay_bat_dau?->format('d/m/Y H:i') ?? '—' }} – {{ $voucher->ngay_ket_thuc?->format('d/m/Y H:i') ?? '—' }}</dd>

                <dt class="col-sm-3">Trạng thái</dt>
                <dd class="col-sm-9"><span class="badge badge-{{ $stateBadge }}">{{ $stateLabel }}</span></dd>
            </dl>
        </div>
    </div>

    @if ($orders)
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-receipt text-primary mr-2"></i>Đơn hàng đã dùng mã ({{ $orders->total() }})</h3>
            </div>
            <div class="card-body p-0 table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th class="pl-3">Mã đơn</th>
                            <th>Người nhận</th>
                            <th>Ngày đặt</th>
                            <th>Tiền giảm</th>
                            <th>Tổng thanh toán</th>
                            <th>Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($orders as $order)
                            <tr>
                                <td class="pl-3"><a href="{{ route('admin.orders.show', $order->id) }}">{{ $order->ma_don_hang }}</a></td>
                                <td>{{ $order->ten_nguoi_nhan ?? '—' }}</td>
                                <td>{{ $order->ngay_dat ? \Illuminate\Support\Carbon::parse($order->ngay_dat)->format('d/m/Y H:i') : '—' }}</td>
                                <td class="text-danger">-{{ number_format((float) ($order->tien_giam_voucher ?? 0), 0, ',', '.') }} ₫</td>
                                <td>{{ number_format((float) ($order->tong_tien ?? 0), 0, ',', '.') }} ₫</td>
                                <td>{{ $orderStatuses[$order->trang_thai ?? null] ?? 'Không xác định' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-5">Chưa có đơn hàng nào dùng mã này.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($orders->hasPages())
                <div class="card-footer">{{ $orders->links() }}</div>
            @endif
        </div>
    @endif
@endsection
