@php
    $workflow = $returnRequest->workflow;
    $isExchange = $returnRequest->loai_yeu_cau === \App\Models\ReturnRequest::TYPE_EXCHANGE;
    $formatTime = fn ($value): ?string => $value ? \Illuminate\Support\Carbon::parse($value)->format('d/m/Y H:i') : null;
    $checks = [
        [
            'done' => (bool) $workflow?->customer_agreed,
            'title' => 'Khách hàng đồng ý phương án',
            'note' => $workflow?->customer_agreed
                ? 'Xác nhận lúc '.($formatTime($workflow->customer_agreed_at) ?? '—')
                : 'Chưa ghi nhận sự đồng ý của khách.',
        ],
        [
            'done' => (bool) $workflow?->policy_reviewed,
            'title' => 'Kiểm tra chính sách đổi/trả',
            'note' => $workflow?->policy_notes ?: ($workflow?->policy_reviewed ? 'Đã kiểm tra, không có ghi chú.' : 'Chưa kiểm tra chính sách.'),
        ],
        [
            'done' => (bool) ($workflow?->collection_waived || $workflow?->received_at),
            'title' => $workflow?->collection_waived ? 'Miễn thu hồi sản phẩm' : 'Nhận lại sản phẩm từ khách',
            'note' => $workflow?->collection_waived
                ? 'Không cần khách gửi lại hàng theo chính sách.'
                : ($workflow?->received_at ? 'Đã nhận hàng lúc '.$formatTime($workflow->received_at) : 'Đang chờ khách gửi hàng về.'),
        ],
        [
            'done' => filled($workflow?->inspection_notes),
            'title' => 'Kiểm tra tình trạng sản phẩm',
            'note' => $workflow?->inspection_notes ?: 'Chưa có kết quả kiểm tra.',
        ],
    ];
    if (! $workflow?->collection_waived) {
        $checks[] = [
            'done' => $workflow?->restocked_at !== null,
            'title' => 'Nhập lại kho',
            'note' => $workflow?->restocked_at
                ? 'Đã cộng lại tồn kho lúc '.$formatTime($workflow->restocked_at)
                : 'Tự động cộng tồn kho khi kiểm tra hàng đạt.',
        ];
    }
@endphp
<div class="card">
    <div class="card-header"><h3 class="card-title"><i class="fas fa-clipboard-check text-primary mr-2"></i>Chi tiết xử lý</h3></div>
    <div class="card-body">
        <div class="return-section-title">Các bước kiểm soát</div>
        @foreach ($checks as $check)
            <div class="return-check">
                <span class="return-check__icon">
                    <i class="{{ $check['done'] ? 'fas fa-check-circle text-success' : 'far fa-circle text-muted' }}"></i>
                </span>
                <div>
                    <div class="return-check__title">{{ $check['title'] }}</div>
                    <div class="return-check__note">{{ $check['note'] }}</div>
                </div>
            </div>
        @endforeach

        @if ($isExchange)
            <div class="return-section-title mt-4">Hàng đổi cho khách</div>
            @if ($workflow?->replacement_name)
                <div class="row">
                    <div class="col-sm-6 mb-3">
                        <div class="return-meta__label">Sản phẩm thay thế</div>
                        <div class="return-meta__value">{{ $workflow->replacement_name }}</div>
                        <div class="small text-muted">{{ number_format((float) $workflow->replacement_price, 0, ',', '.') }} ₫ / sản phẩm</div>
                    </div>
                    <div class="col-sm-6 mb-3">
                        <div class="return-meta__label">Chênh lệch giá</div>
                        <div class="return-meta__value {{ $workflow->price_difference > 0 ? 'text-danger' : ($workflow->price_difference < 0 ? 'text-success' : '') }}">
                            {{ $workflow->price_difference > 0 ? '+' : '' }}{{ number_format((float) $workflow->price_difference, 0, ',', '.') }} ₫
                        </div>
                        <span class="badge badge-{{ $workflow->price_difference_settled || (int) $workflow->price_difference === 0 ? 'success' : 'warning' }}">
                            {{ (int) $workflow->price_difference === 0 ? 'Không chênh lệch' : ($workflow->price_difference_settled ? 'Đã xử lý chênh lệch' : 'Chưa xử lý chênh lệch') }}
                        </span>
                        @if ($workflow->price_difference_note)<div class="small text-muted mt-1">{{ $workflow->price_difference_note }}</div>@endif
                    </div>
                    <div class="col-sm-6 mb-3">
                        <div class="return-meta__label">Mã vận đơn</div>
                        <div class="return-meta__value">{{ $workflow->tracking_code ?: 'Chưa gửi hàng' }}</div>
                    </div>
                    <div class="col-sm-6 mb-3">
                        <div class="return-meta__label">Khách nhận hàng đổi</div>
                        <div class="return-meta__value">{{ $formatTime($workflow->delivered_at) ?? 'Chưa xác nhận' }}</div>
                    </div>
                </div>
            @else
                <div class="text-muted small">Chưa chọn sản phẩm thay thế.</div>
            @endif
        @endif

        @if (! $isExchange && $workflow?->refund_amount !== null)
            <div class="return-section-title mt-4">Hoàn tiền</div>
            <div class="row align-items-center">
                <div class="col-sm-7 mb-3">
                    <div class="return-meta__label">Số tiền đã hoàn</div>
                    <div class="h4 font-weight-bold text-success mb-1">{{ number_format((float) $workflow->refund_amount, 0, ',', '.') }} ₫</div>
                    <div class="small text-muted">
                        {{ [
                            'manual_bank_transfer' => 'Chuyển khoản thủ công',
                            'bank_transfer' => 'Chuyển khoản',
                            'cash' => 'Tiền mặt',
                            'original_payment' => 'Phương thức thanh toán ban đầu',
                            'other' => 'Khác',
                        ][$workflow->refund_method] ?? 'Không xác định' }}
                        · {{ $formatTime($workflow->refunded_at) ?? '—' }}
                    </div>
                    <div class="small mt-1">Mã giao dịch: <strong>{{ $workflow->refund_reference ?: 'Không có' }}</strong></div>
                </div>
                @if ($workflow->refund_proof_path)
                    <div class="col-sm-5 mb-3 text-sm-right">
                        <div class="return-meta__label mb-1">Minh chứng chuyển khoản</div>
                        <a href="{{ Storage::disk('public')->url($workflow->refund_proof_path) }}" target="_blank" rel="noopener noreferrer" data-no-spa>
                            <img src="{{ Storage::disk('public')->url($workflow->refund_proof_path) }}" alt="Minh chứng chuyển khoản"
                                class="img-fluid rounded border" style="max-height: 160px">
                        </a>
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>
