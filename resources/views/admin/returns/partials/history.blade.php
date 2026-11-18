<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-history text-primary mr-2"></i>Lịch sử xử lý</h3>
    </div>
    <div class="card-body">
        @forelse ($returnRequest->history as $historyItem)
            <div class="d-flex border-bottom pb-3 mb-3">
                <div class="mr-3 text-primary"><i class="fas fa-circle"></i></div>
                <div>
                    <strong>
                        {{ $historyItem->from_status === null ? 'Tạo phiếu' : (\App\Models\ReturnRequest::statuses()[$historyItem->from_status] ?? 'Trạng thái cũ') }}
                        @if ($historyItem->from_status !== null) → @endif
                        {{ \App\Models\ReturnRequest::statuses()[$historyItem->to_status] ?? 'Không xác định' }}
                    </strong>
                    <div class="small text-muted">
                        {{ $historyItem->actor_name }} · {{ $historyItem->created_at->format('d/m/Y H:i') }}
                    </div>
                    @if ($historyItem->note)
                        <div class="mt-1">{{ $historyItem->note }}</div>
                    @endif
                </div>
            </div>
        @empty
            <p class="text-muted mb-0">Chưa có lịch sử xử lý.</p>
        @endforelse
    </div>
</div>
