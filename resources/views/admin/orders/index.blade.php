@extends('layouts.admin')

@section('title', 'Đơn hàng')
@section('page-heading', 'Quản lý đơn hàng')
@section('page-subheading', 'Theo dõi thông tin khách hàng, thanh toán và tiến độ xử lý đơn.')
@section('breadcrumb', 'Đơn hàng')

@section('content')
<div class="row">
    <div class="col-lg-3 col-sm-6">
        <div class="small-box bg-white"><div class="inner"><p class="text-muted mb-1">Tổng đơn hàng</p><h3>{{ number_format($counts->total ?? 0) }}</h3></div><div class="icon text-primary"><i class="fas fa-shopping-bag"></i></div></div>
    </div>
    <div class="col-lg-3 col-sm-6">
        <div class="small-box bg-white"><div class="inner"><p class="text-muted mb-1">Chờ xác nhận</p><h3>{{ number_format($counts->pending ?? 0) }}</h3></div><div class="icon text-warning"><i class="fas fa-clock"></i></div></div>
    </div>
    <div class="col-lg-3 col-sm-6">
        <div class="small-box bg-white"><div class="inner"><p class="text-muted mb-1">Đang xử lý</p><h3>{{ number_format($counts->processing ?? 0) }}</h3></div><div class="icon text-primary"><i class="fas fa-cog"></i></div></div>
    </div>
    <div class="col-lg-3 col-sm-6">
        <div class="small-box bg-white"><div class="inner"><p class="text-muted mb-1">Đang giao</p><h3>{{ number_format($counts->shipping ?? 0) }}</h3></div><div class="icon text-info"><i class="fas fa-shipping-fast"></i></div></div>
    </div>
</div>

@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle mr-1"></i>{{ session('success') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Đóng"><span aria-hidden="true">&times;</span></button>
    </div>
@endif

<div class="card">
    <div class="card-header py-3">
        <div class="row align-items-center">
            <div class="col-md-4"><h3 class="card-title"><i class="fas fa-shopping-bag text-primary mr-2"></i>Danh sách đơn hàng</h3></div>
            <div class="col-md-8 mt-3 mt-md-0">
                <form method="GET" action="{{ route('admin.orders.index') }}" class="d-flex flex-wrap justify-content-md-end">
                    <div class="input-group input-group-sm mr-2 mb-1" style="max-width: 230px">
                        <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Mã đơn, khách hàng..." aria-label="Tìm đơn hàng">
                        <div class="input-group-append"><button class="btn btn-default" type="submit" aria-label="Tìm"><i class="fas fa-search"></i></button></div>
                    </div>
                    <select name="status" class="form-control form-control-sm mr-2 mb-1" style="max-width: 160px" aria-label="Lọc trạng thái đơn" onchange="this.form.requestSubmit()">
                        <option value="">Tất cả trạng thái</option>
                        @foreach ($statuses as $status => $label)
                            <option value="{{ $status }}" @selected((string) request('status') === (string) $status)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <select name="payment_status" class="form-control form-control-sm mr-2 mb-1" style="max-width: 170px" aria-label="Lọc thanh toán" onchange="this.form.requestSubmit()">
                        <option value="">Mọi thanh toán</option>
                        <option value="0" @selected(request('payment_status') === '0')>Chưa thanh toán</option>
                        <option value="1" @selected(request('payment_status') === '1')>Đã thanh toán</option>
                    </select>
                    <a href="{{ route('admin.orders.index') }}" class="btn btn-default btn-sm mb-1" title="Xóa bộ lọc"><i class="fas fa-undo"></i></a>
                </form>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th class="pl-3">Mã đơn</th><th>Khách hàng</th><th>Ngày đặt</th><th>Tổng tiền</th><th>Thanh toán</th><th>Trạng thái</th><th class="text-right pr-3">Chi tiết</th></tr></thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr>
                            <td class="pl-3"><a href="{{ route('admin.orders.show', $order->id) }}" class="font-weight-bold">{{ $order->ma_don_hang }}</a><div class="small text-muted">#{{ $order->id }}</div></td>
                            <td><div class="font-weight-bold text-dark">{{ $order->ten_nguoi_nhan ?: ($order->account_name ?: 'Khách hàng') }}</div><small class="text-muted">{{ $order->sdt_nguoi_nhan ?: $order->email_nguoi_nhan ?: '' }}</small></td>
                            <td>{{ $order->ngay_dat ? \Illuminate\Support\Carbon::parse($order->ngay_dat)->format('d/m/Y H:i') : '—' }}</td>
                            <td class="font-weight-bold">{{ number_format((float) $order->tong_tien, 0, ',', '.') }} ₫</td>
                            <td>{{ \App\Models\Order::paymentStatusLabel($order->trang_thai_thanh_toan === null ? null : (int) $order->trang_thai_thanh_toan) }}</td>
                            <td><span class="badge badge-{{ $statusBadges[$order->trang_thai] ?? 'secondary' }} px-2 py-1">{{ $statuses[$order->trang_thai] ?? 'Không xác định' }}</span></td>
                            <td class="text-right pr-3"><a href="{{ route('admin.orders.show', $order->id) }}" class="btn btn-default btn-sm text-primary"><i class="far fa-eye"></i> Xem</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center py-5"><i class="fas fa-inbox fa-2x text-muted mb-2"></i><div class="text-muted">Không tìm thấy đơn hàng phù hợp.</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-white d-flex justify-content-between align-items-center">
        <span class="text-muted small">Hiển thị {{ $orders->firstItem() ?? 0 }}–{{ $orders->lastItem() ?? 0 }} trên {{ $orders->total() }} đơn hàng</span>
        {{ $orders->links() }}
    </div>
</div>
@endsection
