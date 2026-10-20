@extends('layouts.admin')

@section('title', 'Quản lý đơn hàng')
@section('page-heading', 'Quản lý đơn hàng')
@section('page-subheading', 'Tìm kiếm đơn, theo dõi trạng thái và xử lý các trường hợp hủy.')
@section('breadcrumb', 'Đơn hàng')

@section('content')
<div class="row">
    <div class="col-lg-3 col-sm-6">
        <div class="small-box bg-white">
            <div class="inner"><p class="text-muted mb-1">Tất cả đơn hàng</p><h3>{{ number_format($counts['total']) }}</h3></div>
            <div class="icon text-primary"><i class="fas fa-shopping-bag"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-sm-6">
        <div class="small-box bg-white">
            <div class="inner"><p class="text-muted mb-1">Chờ xác nhận</p><h3>{{ number_format($counts['pending']) }}</h3></div>
            <div class="icon text-warning"><i class="fas fa-clock"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-sm-6">
        <div class="small-box bg-white">
            <div class="inner"><p class="text-muted mb-1">Đang giao</p><h3>{{ number_format($counts['shipping']) }}</h3></div>
            <div class="icon text-info"><i class="fas fa-shipping-fast"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-sm-6">
        <div class="small-box bg-white">
            <div class="inner"><p class="text-muted mb-1">Đã hủy</p><h3>{{ number_format($counts['cancelled']) }}</h3></div>
            <div class="icon text-danger"><i class="fas fa-ban"></i></div>
        </div>
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
        <form method="GET" action="{{ route('admin.orders.index') }}" class="row align-items-end">
            <div class="col-lg-4 col-md-6 form-group mb-2">
                <label for="q" class="small text-muted mb-1">Tìm đơn hàng</label>
                <input id="q" type="search" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Mã đơn, tên, email, số điện thoại">
            </div>
            <div class="col-lg-2 col-md-6 form-group mb-2">
                <label for="status" class="small text-muted mb-1">Trạng thái</label>
                <select id="status" name="status" class="form-control form-control-sm">
                    <option value="">Tất cả trạng thái</option>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected((string) request('status') === (string) $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-2 col-md-6 form-group mb-2">
                <label for="from" class="small text-muted mb-1">Từ ngày</label>
                <input id="from" type="date" name="from" value="{{ request('from') }}" class="form-control form-control-sm">
            </div>
            <div class="col-lg-2 col-md-6 form-group mb-2">
                <label for="to" class="small text-muted mb-1">Đến ngày</label>
                <input id="to" type="date" name="to" value="{{ request('to') }}" class="form-control form-control-sm">
            </div>
            <div class="col-lg-2 col-md-12 form-group mb-2 d-flex">
                <button type="submit" class="btn btn-primary btn-sm mr-2"><i class="fas fa-search mr-1"></i>Lọc đơn</button>
                <a href="{{ route('admin.orders.index') }}" class="btn btn-default btn-sm" title="Xóa bộ lọc"><i class="fas fa-undo"></i></a>
            </div>
        </form>
        @if ($errors->any())
            <div class="alert alert-danger mb-0 mt-2" role="alert">
                @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
            </div>
        @endif
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th class="pl-3">Mã đơn hàng</th>
                        <th>Khách hàng</th>
                        <th>Ngày đặt</th>
                        <th class="text-right">Tổng tiền</th>
                        <th>Trạng thái</th>
                        <th class="text-right pr-3">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr>
                            <td class="pl-3"><a href="{{ route('admin.orders.show', $order->id) }}" class="font-weight-bold">{{ $order->ma_don_hang }}</a></td>
                            <td>
                                <div class="font-weight-bold text-dark">{{ $order->ten_nguoi_nhan ?: 'Khách hàng' }}</div>
                                @if ($order->sdt_nguoi_nhan)<small class="text-muted">{{ $order->sdt_nguoi_nhan }}</small>@endif
                            </td>
                            <td>{{ $order->ngay_dat ? \Illuminate\Support\Carbon::parse($order->ngay_dat)->format('H:i, d/m/Y') : '—' }}</td>
                            <td class="text-right">{{ number_format((float) $order->tong_tien, 0, ',', '.') }} ₫</td>
                            <td><span class="badge badge-{{ $statusBadges[$order->trang_thai] ?? 'secondary' }} px-2 py-1">{{ $statuses[$order->trang_thai] ?? 'Không xác định' }}</span></td>
                            <td class="text-right pr-3"><a href="{{ route('admin.orders.show', $order->id) }}" class="btn btn-default btn-sm text-primary"><i class="far fa-eye mr-1"></i>Chi tiết</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center py-5"><i class="fas fa-inbox text-muted mb-2" style="font-size: 2rem"></i><div class="font-weight-bold">Không tìm thấy đơn hàng</div><div class="text-muted small">Thử thay đổi từ khóa hoặc bộ lọc.</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if ($orders->hasPages())
        <div class="card-footer bg-white d-flex justify-content-between align-items-center">
            <span class="text-muted small">Hiển thị {{ $orders->firstItem() }}–{{ $orders->lastItem() }} trên {{ $orders->total() }} đơn hàng</span>
            {{ $orders->links() }}
        </div>
    @endif
</div>
<div class="text-muted small mt-2"><a href="{{ route('orders.track') }}">Mở trang khách hàng tra cứu đơn hàng</a></div>
@endsection
