@php($workflow = $returnRequest->workflow)
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title"><i class="fas fa-university text-primary mr-2"></i>Tài khoản nhận hoàn tiền</h3>
        @if ($returnRequest->refund_account_editable)
            <a href="{{ route('admin.returns.edit', $returnRequest->id) }}#refund-account" class="btn btn-sm btn-outline-primary ml-auto">
                <i class="fas fa-pen mr-1"></i>{{ $workflow?->hasRefundAccount() ? 'Sửa' : 'Bổ sung' }}
            </a>
        @endif
    </div>
    <div class="card-body">
        @if ($workflow?->hasRefundAccount())
            <div class="return-bank">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <div class="return-bank__label">Ngân hàng</div>
                        <div class="font-weight-bold">{{ $workflow->refund_bank_name }}</div>
                    </div>
                    <i class="fas fa-landmark fa-lg" style="opacity: .7"></i>
                </div>
                <div class="return-bank__label">Số tài khoản</div>
                <div class="d-flex align-items-center flex-wrap mb-3">
                    <span class="return-bank__number mr-2">{{ $workflow->refund_account_number }}</span>
                    <button type="button" class="btn btn-sm btn-light py-0" data-copy="{{ $workflow->refund_account_number }}">
                        <i class="far fa-copy mr-1"></i><span>Sao chép</span>
                    </button>
                </div>
                <div class="return-bank__label">Chủ tài khoản</div>
                <div class="font-weight-bold">{{ $workflow->refund_account_holder }}</div>
            </div>
            @if ($workflow->refunded_at === null)
                <div class="small text-muted mt-2"><i class="fas fa-info-circle mr-1"></i>Đối chiếu tên chủ tài khoản với tên khách trước khi chuyển khoản.</div>
            @endif
        @else
            <div class="d-flex align-items-start text-warning">
                <i class="fas fa-exclamation-triangle fa-lg mr-3 mt-1"></i>
                <div class="text-dark">
                    <div class="font-weight-bold">Chưa có tài khoản nhận hoàn tiền</div>
                    <div class="small text-muted">Tài chính chỉ có thể chuyển khoản sau khi bổ sung ngân hàng, số tài khoản và chủ tài khoản của khách.</div>
                </div>
            </div>
        @endif
    </div>
</div>
<script>
    (function () {
        if (window.returnCopyBound) return;
        window.returnCopyBound = true;

        document.addEventListener('click', function (event) {
            var button = event.target.closest('[data-copy]');
            if (!button || !navigator.clipboard) return;

            navigator.clipboard.writeText(button.getAttribute('data-copy')).then(function () {
                var label = button.querySelector('span');
                if (!label) return;
                label.textContent = 'Đã sao chép';
                window.setTimeout(function () { label.textContent = 'Sao chép'; }, 1500);
            });
        });
    }());
</script>
