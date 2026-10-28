@extends('layouts.admin')

@section('title', 'Đổi / trả hàng')
@section('page-heading', 'Đổi / trả hàng')
@section('page-subheading', 'Tiếp nhận yêu cầu, theo dõi xử lý và ghi nhận hoàn tiền.')
@section('breadcrumb', 'Đổi / trả hàng')

@section('content')
<div class="row">
    <div class="col-lg-3 col-sm-6">
        <div class="small-box bg-white">
            <div class="inner">
                <p class="text-muted mb-1">Tổng yêu cầu</p>
                <h3>{{ number_format($counts->total ?? 0) }}</h3>
            </div>
            <div class="icon text-primary"><i class="fas fa-exchange-alt"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-sm-6">
        <div class="small-box bg-white">
            <div class="inner">
                <p class="text-muted mb-1">Chờ tiếp nhận</p>
                <h3>{{ number_format($counts->pending ?? 0) }}</h3>
            </div>
            <div class="icon text-warning"><i class="fas fa-clock"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-sm-6">
        <div class="small-box bg-white">
            <div class="inner">
                <p class="text-muted mb-1">Đang xử lý</p>
                <h3>{{ number_format($counts->processing ?? 0) }}</h3>
            </div>
            <div class="icon text-info"><i class="fas fa-spinner"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-sm-6">
        <div class="small-box bg-white">
            <div class="inner">
                <p class="text-muted mb-1">Đã ghi nhận hoàn tiền</p>
                <h3>{{ number_format($counts->refunds ?? 0) }}</h3>
            </div>
            <div class="icon text-success"><i class="fas fa-wallet"></i></div>
        </div>
    </div>
</div>

@if (session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle mr-1"></i>{{ session('success') }}
    <button type="button" class="close" data-dismiss="alert" aria-label="Đóng"><span aria-hidden="true">&times;</span></button>
</div>
@endif
@if (session('error'))
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="fas fa-exclamation-circle mr-1"></i>{{ session('error') }}
    <button type="button" class="close" data-dismiss="alert" aria-label="Đóng"><span aria-hidden="true">&times;</span></button>
</div>
@endif

<div class="card">
    <div class="card-header py-3">
        <div class="row align-items-center">
            <div class="col-md-4">
                <h3 class="card-title"><i class="fas fa-exchange-alt text-primary mr-2"></i>Danh sách yêu cầu</h3>
                <div class="small text-muted mt-1">Tạo phiếu thủ công hoặc theo dõi yêu cầu từ CSKH.</div>
            </div>
            <div class="col-md-8 mt-3 mt-md-0">
                <div class="d-flex flex-wrap justify-content-md-end">
                    <a href="{{ route('admin.returns.create') }}" class="btn btn-primary btn-sm mb-1 mr-2">
                        <i class="fas fa-plus mr-1"></i>Tạo phiếu đổi / trả
                    </a>
                    <form method="GET" action="{{ route('admin.returns.index') }}" class="d-flex flex-wrap justify-content-md-end">
                    <div class="input-group input-group-sm mr-2 mb-1" style="max-width: 230px">
                        <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Mã đơn, khách hàng..." aria-label="Tìm yêu cầu">
                        <div class="input-group-append"><button class="btn btn-default" type="submit" aria-label="Tìm"><i class="fas fa-search"></i></button></div>
                    </div>
                    <select name="type" class="form-control form-control-sm mr-2 mb-1" style="max-width: 135px" aria-label="Lọc loại yêu cầu" onchange="this.form.requestSubmit()">
                        <option value="">Tất cả loại</option>
                        <option value="1" @selected(request('type')==='1' )>Đổi hàng</option>
                        <option value="2" @selected(request('type')==='2' )>Trả hàng</option>
                    </select>
                    <select name="status" class="form-control form-control-sm mr-2 mb-1" style="max-width: 175px" aria-label="Lọc trạng thái" onchange="this.form.requestSubmit()">
                        <option value="">Tất cả trạng thái</option>
                        @foreach ($statuses as $status => $label)
                        <option value="{{ $status }}" @selected((string) request('status')===(string) $status)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <a href="{{ route('admin.returns.index') }}" class="btn btn-default btn-sm mb-1" title="Xóa bộ lọc"><i class="fas fa-undo"></i></a>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th class="pl-3">Mã yêu cầu</th>
                        <th>Khách hàng</th>
                        <th>Đơn hàng / Sản phẩm</th>
                        <th>Loại yêu cầu</th>
                        <th>Ngày gửi</th>
                        <th>Trạng thái</th>
                        <th class="text-right pr-3">Chi tiết</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($requests as $returnRequest)
                    <tr>
                        <td class="pl-3">
                            <a href="{{ route('admin.returns.show', $returnRequest->id) }}" class="font-weight-bold">#DT-{{ str_pad((string) $returnRequest->id, 6, '0', STR_PAD_LEFT) }}</a>
                            <div class="small text-muted">Đơn #{{ $returnRequest->order_number ?? $returnRequest->don_hang_id ?? '—' }}</div>
                        </td>
                        <td>
                            <div class="font-weight-bold text-dark">{{ $returnRequest->recipient_name ?: ($returnRequest->account_name ?: 'Khách hàng') }}</div>
                            @if ($returnRequest->account_email)<small class="text-muted">{{ $returnRequest->account_email }}</small>@endif
                        </td>
                        <td>
                            <div>{{ $returnRequest->product_name ?: 'Sản phẩm trong đơn hàng' }}</div>
                            <small class="text-muted">SL yêu cầu: {{ $returnRequest->so_luong ?? '—' }}</small>
                            @if ($returnRequest->ly_do)
                            <div class="small text-muted">{{ \Illuminate\Support\Str::limit($returnRequest->ly_do, 70) }}</div>
                            @endif
                        </td>
                        <td>
                            @if ($returnRequest->loai_yeu_cau === 1)
                            <span class="badge badge-info px-2 py-1">Đổi hàng</span>
                            @elseif ($returnRequest->loai_yeu_cau === 2)
                            <span class="badge badge-primary px-2 py-1">Trả hàng</span>
                            @else
                            <span class="badge badge-secondary px-2 py-1">Khác</span>
                            @endif
                        </td>
                        <td>{{ $returnRequest->ngay_tao ? \Illuminate\Support\Carbon::parse($returnRequest->ngay_tao)->format('d/m/Y H:i') : '—' }}</td>
                        <td>
                            <span class="badge badge-{{ $statusBadges[$returnRequest->trang_thai] ?? 'secondary' }} px-2 py-1">
                                {{ $statuses[$returnRequest->trang_thai] ?? 'Không xác định' }}
                            </span>
                        </td>
                        <td class="text-right pr-3">
                            @if ($returnRequest->conversation_id)
                            <a href="{{ route('admin.support-chat.index', ['conversation_id' => $returnRequest->conversation_id]) }}"
                                class="btn btn-default btn-sm text-info" aria-label="Mở hội thoại CSKH"><i class="fas fa-comments"></i></a>
                            @endif
                            <a href="{{ route('admin.returns.show', $returnRequest->id) }}" class="btn btn-default btn-sm text-primary" aria-label="Xem yêu cầu"><i class="far fa-eye"></i> Xem</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5">
                            <i class="fas fa-inbox text-muted mb-2" style="font-size: 2rem"></i>
                            <div class="font-weight-bold">Chưa có yêu cầu đổi / trả hàng</div>
                            <div class="text-muted small">Yêu cầu mới sẽ xuất hiện tại đây.</div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if ($requests->hasPages())
    <div class="card-footer bg-white d-flex justify-content-between align-items-center">
        <span class="text-muted small">Hiển thị {{ $requests->firstItem() }}–{{ $requests->lastItem() }} trên {{ $requests->total() }} yêu cầu</span>
        {{ $requests->links() }}
    </div>
    @endif
</div>
<div class="text-muted small mt-2"><i class="fas fa-info-circle mr-1"></i>Trạng thái “Đã ghi nhận hoàn tiền” chỉ lưu kết quả xử lý; thao tác chuyển tiền cần thực hiện qua đơn vị thanh toán.</div>
@endsection