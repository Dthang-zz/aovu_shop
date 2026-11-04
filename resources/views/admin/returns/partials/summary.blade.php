@php
    $requestCode = '#DT-'.str_pad((string) $returnRequest->id, 6, '0', STR_PAD_LEFT);
    $isReturn = $returnRequest->loai_yeu_cau === \App\Models\ReturnRequest::TYPE_RETURN;
    $evidenceUrl = $returnRequest->hinh_anh
        ? (\Illuminate\Support\Str::startsWith($returnRequest->hinh_anh, ['http://', 'https://'])
            ? $returnRequest->hinh_anh
            : \Illuminate\Support\Facades\Storage::disk('public')->url($returnRequest->hinh_anh))
        : null;
@endphp
<div class="card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
        <div>
            <h3 class="card-title mb-0 font-weight-bold">{{ $requestCode }}</h3>
            <div class="small text-muted" style="clear: both">
                <span class="badge badge-{{ $isReturn ? 'primary' : 'info' }} mr-1">{{ $returnRequest->type_label }}</span>
                Gửi lúc {{ $returnRequest->ngay_tao ? \Illuminate\Support\Carbon::parse($returnRequest->ngay_tao)->format('d/m/Y H:i') : '—' }}
            </div>
        </div>
        <span class="badge badge-{{ $returnRequest->status_badge }} px-3 py-2">{{ $returnRequest->status_label }}</span>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6 mb-3">
                <div class="return-meta">
                    <span class="return-meta__icon"><i class="fas fa-user"></i></span>
                    <div>
                        <div class="return-meta__label">Khách hàng</div>
                        <div class="return-meta__value">{{ $returnRequest->recipient_name ?: ($returnRequest->account_name ?: 'Khách hàng') }}</div>
                        @if ($returnRequest->account_email)<div class="small text-muted">{{ $returnRequest->account_email }}</div>@endif
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="return-meta">
                    <span class="return-meta__icon"><i class="fas fa-receipt"></i></span>
                    <div>
                        <div class="return-meta__label">Đơn hàng</div>
                        <div class="return-meta__value">{{ $returnRequest->order_number ?: ('#'.$returnRequest->don_hang_id) }}</div>
                        @if ($returnRequest->order_total !== null)<div class="small text-muted">Tổng đơn {{ number_format($returnRequest->order_total, 0, ',', '.') }} ₫</div>@endif
                    </div>
                </div>
            </div>
        </div>

        @if ($returnRequest->conversation_id)
            <div class="alert alert-light border d-flex flex-wrap justify-content-between align-items-center py-2">
                <span class="small"><i class="fas fa-comments text-info mr-1"></i>Phiếu được tạo từ cuộc trò chuyện CSKH.</span>
                <a class="btn btn-sm btn-outline-info"
                    href="{{ route('admin.support-chat.index', ['conversation_id' => $returnRequest->conversation_id]) }}">Mở hội thoại</a>
            </div>
        @endif

        <div class="return-section-title mt-2">Sản phẩm yêu cầu</div>
        <div class="return-product mb-4">
            <div class="mr-3">
                <div class="font-weight-bold">{{ $returnRequest->product_name ?: 'Sản phẩm trong đơn hàng' }}</div>
                <div class="small text-muted">Mã chi tiết đơn #{{ $returnRequest->chi_tiet_don_hang_id ?? '—' }}</div>
            </div>
            <div class="text-md-right">
                <div><span class="text-muted small">Số lượng</span> <strong>{{ $returnRequest->so_luong ?? '—' }}</strong>@if ($returnRequest->order_item_quantity)<span class="text-muted small"> / {{ $returnRequest->order_item_quantity }} đã mua</span>@endif</div>
                @if ($returnRequest->item_price !== null)
                    <div class="small text-muted">{{ number_format($returnRequest->item_price, 0, ',', '.') }} ₫ / sản phẩm</div>
                @endif
            </div>
        </div>

        <div class="return-section-title">Lý do của khách hàng</div>
        <div class="return-quote mb-4">{{ $returnRequest->ly_do ?: 'Khách hàng chưa cung cấp lý do.' }}</div>

        @if ($evidenceUrl)
            <div class="return-section-title">Ảnh khách gửi</div>
            <a href="{{ $evidenceUrl }}" target="_blank" rel="noopener noreferrer" class="d-inline-block mb-4">
                <img src="{{ $evidenceUrl }}" alt="Ảnh minh chứng yêu cầu {{ $requestCode }}" class="img-fluid rounded border" style="max-height: 240px">
            </a>
        @endif

        <div class="return-section-title">Phản hồi gần nhất của CSKH</div>
        @if ($returnRequest->phan_hoi)
            <div class="return-reply">{{ $returnRequest->phan_hoi }}</div>
        @else
            <div class="text-muted small">Chưa có phản hồi gửi khách hàng.</div>
        @endif
    </div>
</div>
