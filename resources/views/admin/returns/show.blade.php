@extends('layouts.admin')

@section('title', 'Chi tiết yêu cầu đổi / trả')
@section('page-heading', 'Chi tiết yêu cầu')
@section('page-subheading', 'Xem thông tin yêu cầu và tiến độ xử lý.')
@section('breadcrumb')
    <a href="{{ route('admin.returns.index') }}">Đổi / trả hàng</a>
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
                <h3 class="card-title"><i class="fas fa-file-alt text-primary mr-2"></i>#DT-{{ str_pad((string) $returnRequest->id, 6, '0', STR_PAD_LEFT) }}</h3>
                <span class="badge badge-{{ $returnRequest->status_badge }} px-2 py-1">{{ $returnRequest->status_label }}</span>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-sm-6 mb-3">
                        <div class="text-muted small">Khách hàng</div>
                        <div class="font-weight-bold">{{ $returnRequest->recipient_name ?: ($returnRequest->account_name ?: 'Khách hàng') }}</div>
                        @if ($returnRequest->account_email)<div class="text-muted small">{{ $returnRequest->account_email }}</div>@endif
                    </div>
                    <div class="col-sm-6 mb-3">
                        <div class="text-muted small">Đơn hàng</div>
                        <div class="font-weight-bold">{{ $returnRequest->order_number ?: ('#'.$returnRequest->don_hang_id) }}</div>
                        @if ($returnRequest->order_total !== null)<div class="text-muted small">Tổng đơn: {{ number_format($returnRequest->order_total, 0, ',', '.') }} ₫</div>@endif
                    </div>
                    <div class="col-sm-6 mb-3">
                        <div class="text-muted small">Loại yêu cầu</div>
                        <div class="font-weight-bold">{{ $returnRequest->type_label }}</div>
                    </div>
                    <div class="col-sm-6 mb-3">
                        <div class="text-muted small">Ngày gửi</div>
                        <div class="font-weight-bold">{{ $returnRequest->ngay_tao ? \Illuminate\Support\Carbon::parse($returnRequest->ngay_tao)->format('d/m/Y H:i') : '—' }}</div>
                    </div>
                </div>

                @if ($returnRequest->conversation_id)
                    <div class="alert alert-info d-flex flex-wrap justify-content-between align-items-center">
                        <span><i class="fas fa-comments mr-1"></i>Phiếu được tạo từ cuộc trò chuyện CSKH.</span>
                        <a class="btn btn-sm btn-outline-primary"
                            href="{{ route('admin.support-chat.index', ['conversation_id' => $returnRequest->conversation_id]) }}">
                            Mở hội thoại
                        </a>
                    </div>
                @endif

                <h6 class="font-weight-bold border-top pt-3">Sản phẩm yêu cầu</h6>
                <div class="d-flex justify-content-between align-items-center bg-light rounded p-3 mb-3">
                    <div>
                        <div class="font-weight-bold">{{ $returnRequest->product_name ?: 'Sản phẩm trong đơn hàng' }}</div>
                        <small class="text-muted">Mã chi tiết đơn: {{ $returnRequest->chi_tiet_don_hang_id ?? '—' }}</small>
                    </div>
                    <div class="text-right">
                        <div>SL yêu cầu: <strong>{{ $returnRequest->so_luong ?? '—' }}</strong></div>
                        @if ($returnRequest->item_price !== null)<small class="text-muted">{{ number_format($returnRequest->item_price, 0, ',', '.') }} ₫ / sản phẩm</small>@endif
                    </div>
                </div>

                <h6 class="font-weight-bold">Lý do khách hàng</h6>
                <p class="text-muted">{{ $returnRequest->ly_do ?: 'Khách hàng chưa cung cấp lý do.' }}</p>

                @if ($returnRequest->hinh_anh)
                    @php
                        $evidenceUrl = \Illuminate\Support\Str::startsWith($returnRequest->hinh_anh, ['http://', 'https://'])
                            ? $returnRequest->hinh_anh
                            : \Illuminate\Support\Facades\Storage::disk('public')->url($returnRequest->hinh_anh);
                    @endphp
                    <h6 class="font-weight-bold">Ảnh minh chứng</h6>
                    <a href="{{ $evidenceUrl }}" target="_blank" rel="noopener noreferrer">
                        <img src="{{ $evidenceUrl }}" alt="Ảnh minh chứng yêu cầu" class="img-fluid rounded mb-3" style="max-height: 280px">
                    </a>
                @endif

                @if ($returnRequest->phan_hoi)
                    <h6 class="font-weight-bold border-top pt-3">Phản hồi gần nhất</h6>
                    <p class="text-muted mb-0">{{ $returnRequest->phan_hoi }}</p>
                @endif

                @if ($returnRequest->workflow)
                    <h6 class="font-weight-bold border-top pt-3">Thông tin xử lý</h6>
                    <dl class="row mb-0">
                        <dt class="col-sm-5">Khách đã đồng ý</dt>
                        <dd class="col-sm-7">{{ $returnRequest->workflow->customer_agreed ? 'Đã xác nhận' : 'Chưa xác nhận' }}</dd>
                        <dt class="col-sm-5">Đã kiểm tra chính sách</dt>
                        <dd class="col-sm-7">{{ $returnRequest->workflow->policy_reviewed ? 'Đã kiểm tra' : 'Chưa kiểm tra' }}</dd>
                        @if ($returnRequest->workflow->policy_notes)
                            <dt class="col-sm-5">Ghi chú chính sách</dt>
                            <dd class="col-sm-7">{{ $returnRequest->workflow->policy_notes }}</dd>
                        @endif
                        <dt class="col-sm-5">Thu hồi sản phẩm</dt>
                        <dd class="col-sm-7">{{ $returnRequest->workflow->collection_waived ? 'Được miễn' : 'Cần thu hồi' }}</dd>
                        @if ($returnRequest->workflow->received_at)
                            <dt class="col-sm-5">Đã nhận hàng</dt>
                            <dd class="col-sm-7">{{ $returnRequest->workflow->received_at->format('d/m/Y H:i') }}</dd>
                        @endif
                        @if ($returnRequest->workflow->inspection_notes)
                            <dt class="col-sm-5">Kết quả kiểm tra</dt>
                            <dd class="col-sm-7">{{ $returnRequest->workflow->inspection_notes }}</dd>
                        @endif
                        @if ($returnRequest->workflow->replacement_name)
                            <dt class="col-sm-5">Sản phẩm thay thế</dt>
                            <dd class="col-sm-7">
                                {{ $returnRequest->workflow->replacement_name }}
                                ({{ number_format($returnRequest->workflow->replacement_price, 0, ',', '.') }} ₫)
                            </dd>
                            <dt class="col-sm-5">Chênh lệch giá</dt>
                            <dd class="col-sm-7">
                                {{ number_format($returnRequest->workflow->price_difference, 0, ',', '.') }} ₫
                                · {{ $returnRequest->workflow->price_difference_settled ? 'Đã xử lý' : 'Chưa xử lý' }}
                            </dd>
                        @endif
                        @if ($returnRequest->workflow->tracking_code)
                            <dt class="col-sm-5">Mã vận đơn đổi</dt>
                            <dd class="col-sm-7">{{ $returnRequest->workflow->tracking_code }}</dd>
                        @endif
                        @if ($returnRequest->workflow->delivered_at)
                            <dt class="col-sm-5">Khách nhận hàng đổi</dt>
                            <dd class="col-sm-7">{{ $returnRequest->workflow->delivered_at->format('d/m/Y H:i') }}</dd>
                        @endif
                        @if ($returnRequest->workflow->refund_amount !== null)
                            <dt class="col-sm-5">Số tiền đã hoàn</dt>
                            <dd class="col-sm-7">{{ number_format($returnRequest->workflow->refund_amount, 0, ',', '.') }} ₫</dd>
                            <dt class="col-sm-5">Phương thức / mã giao dịch</dt>
                            <dd class="col-sm-7">
                                {{ [
                                    'bank_transfer' => 'Chuyển khoản',
                                    'cash' => 'Tiền mặt',
                                    'original_payment' => 'Phương thức thanh toán ban đầu',
                                    'other' => 'Khác',
                                ][$returnRequest->workflow->refund_method] ?? '—' }}
                                · {{ $returnRequest->workflow->refund_reference ?: 'Không có mã tham chiếu' }}
                            </dd>
                            @if ($returnRequest->workflow->refunded_at)
                                <dt class="col-sm-5">Thời điểm xác nhận hoàn</dt>
                                <dd class="col-sm-7">{{ $returnRequest->workflow->refunded_at->format('d/m/Y H:i') }}</dd>
                            @endif
                        @endif
                    </dl>
                @endif
            </div>
            <div class="card-footer bg-white">
                <a href="{{ route('admin.returns.index') }}" class="btn btn-default"><i class="fas fa-arrow-left mr-1"></i>Quay lại danh sách</a>
            </div>
        </div>

        @include('admin.returns.partials.history')
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-tasks text-primary mr-2"></i>Trạng thái xử lý</h3></div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted">Trạng thái</span>
                    <span class="badge badge-{{ $returnRequest->status_badge }} px-2 py-1">{{ $returnRequest->status_label }}</span>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-muted">Loại yêu cầu</span>
                    <span class="font-weight-bold">{{ $returnRequest->type_label }}</span>
                </div>
            </div>
            <div class="card-footer bg-white">
                @if ($returnRequest->allowed_statuses)
                    <a href="{{ route('admin.returns.edit', $returnRequest->id) }}" class="btn btn-primary btn-block"><i class="fas fa-edit mr-1"></i>Chỉnh sửa / xử lý phiếu</a>
                @else
                    <div class="small">@include('admin.returns.partials.closed-notice')</div>
                @endif
            </div>
        </div>
        <div class="callout callout-info">
            <h5><i class="fas fa-info-circle mr-1"></i>Hoàn tiền</h5>
            <p class="mb-0 small">
                Phiếu trả hàng xuất hiện trong mục chờ chuyển khoản của
                <a href="{{ route('admin.finance.refunds.index') }}">Ví hoàn tiền</a>
                sau khi đạt trạng thái <strong>Đã kiểm tra hàng</strong>.
                Thứ tự xử lý: Đang xử lý → Đã chấp nhận → Chờ nhận hàng hoàn → Đã nhận hàng hoàn → Đã kiểm tra hàng
                (nếu miễn thu hồi hàng thì có thể chuyển thẳng từ Đã chấp nhận sang Đã kiểm tra hàng).
            </p>
        </div>
    </div>
</div>
@endsection
