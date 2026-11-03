@extends('layouts.admin')

@section('title', 'Chỉnh sửa đơn hàng')
@section('page-heading', 'Chỉnh sửa trạng thái đơn hàng')
@section('page-subheading', 'Cập nhật tiến độ xử lý và ghi chú cho đơn hàng.')
@section('breadcrumb')
    <a href="{{ route('admin.orders.index') }}">Đơn hàng</a>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title"><i class="fas fa-file-invoice text-primary mr-2"></i>{{ $order->ma_don_hang }}</h3>
                <span class="badge badge-{{ $order->status_badge }} px-2 py-1">{{ $order->status_label }}</span>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-sm-6 mb-3"><div class="text-muted small">Người nhận</div><div class="font-weight-bold">{{ $order->ten_nguoi_nhan ?: ($order->account_name ?: 'Khách hàng') }}</div><div class="text-muted small">{{ $order->sdt_nguoi_nhan ?: '—' }} · {{ $order->email_nguoi_nhan ?: '—' }}</div></div>
                    <div class="col-sm-6 mb-3"><div class="text-muted small">Ngày đặt hàng</div><div class="font-weight-bold">{{ $order->ngay_dat ? \Illuminate\Support\Carbon::parse($order->ngay_dat)->format('d/m/Y H:i') : '—' }}</div></div>
                    <div class="col-sm-6 mb-3"><div class="text-muted small">Thanh toán</div><div class="font-weight-bold">{{ $order->payment_status_label }}</div></div>
                    <div class="col-sm-6 mb-3"><div class="text-muted small">Tổng thanh toán</div><div class="font-weight-bold text-primary">{{ number_format((float) $order->tong_tien, 0, ',', '.') }} ₫</div></div>
                </div>
            </div>
            <div class="card-footer bg-white"><a href="{{ route('admin.orders.show', $order->id) }}" class="btn btn-default"><i class="fas fa-arrow-left mr-1"></i>Quay lại chi tiết</a></div>
        </div>

        @include('admin.orders.partials.history')
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-tasks text-primary mr-2"></i>Cập nhật trạng thái</h3></div>
            @if ($errors->any())
                <div class="card-body pb-0"><div class="alert alert-danger" role="alert"><ul class="mb-0 pl-3">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div></div>
            @endif
            @if ($allowedStatuses)
                <form method="POST" action="{{ route('admin.orders.update', $order->id) }}">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <div class="form-group">
                            <label for="trang_thai">Trạng thái tiếp theo</label>
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
                <div class="card-body"><div class="alert alert-light border mb-0"><i class="fas fa-check-circle text-success mr-1"></i>Đơn hàng đã kết thúc, không thể cập nhật thêm.</div></div>
            @endif
        </div>
    </div>
</div>
@endsection
