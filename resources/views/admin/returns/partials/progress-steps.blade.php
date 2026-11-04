@php
    $statusLabels = \App\Models\ReturnRequest::statuses();
    $isReturn = $returnRequest->loai_yeu_cau === \App\Models\ReturnRequest::TYPE_RETURN;
    $flow = [
        \App\Models\ReturnRequest::STATUS_PENDING,
        \App\Models\ReturnRequest::STATUS_PROCESSING,
        \App\Models\ReturnRequest::STATUS_APPROVED,
        \App\Models\ReturnRequest::STATUS_AWAITING_RETURN,
        \App\Models\ReturnRequest::STATUS_RETURN_RECEIVED,
        \App\Models\ReturnRequest::STATUS_INSPECTED,
        ...($isReturn
            ? [\App\Models\ReturnRequest::STATUS_REFUNDED, \App\Models\ReturnRequest::STATUS_COMPLETED]
            : [\App\Models\ReturnRequest::STATUS_EXCHANGE_SHIPPED, \App\Models\ReturnRequest::STATUS_COMPLETED]),
    ];
    $reachedAt = [];
    foreach ($returnRequest->history as $entry) {
        $reachedAt[$entry->to_status] ??= $entry->created_at;
    }
    $currentStatus = (int) $returnRequest->trang_thai;
    $isRejected = $currentStatus === \App\Models\ReturnRequest::STATUS_REJECTED;
    $rejection = $isRejected ? $returnRequest->history->last(fn ($entry) => $entry->to_status === \App\Models\ReturnRequest::STATUS_REJECTED) : null;
    $lastReachedStatus = $isRejected ? ($rejection?->from_status ?? \App\Models\ReturnRequest::STATUS_PENDING) : $currentStatus;
    $currentIndex = array_search($lastReachedStatus, $flow, true);
    $currentIndex = $currentIndex === false ? 0 : $currentIndex;
    $collectionWaived = (bool) $returnRequest->workflow?->collection_waived;
@endphp
<ul class="return-steps">
    @foreach ($flow as $index => $status)
        @php
            $skipped = $collectionWaived
                && in_array($status, [\App\Models\ReturnRequest::STATUS_AWAITING_RETURN, \App\Models\ReturnRequest::STATUS_RETURN_RECEIVED], true)
                && ! isset($reachedAt[$status]);
            $state = match (true) {
                $skipped => 'is-skipped is-done',
                $index < $currentIndex, $index === $currentIndex && $isRejected => 'is-done',
                $index === $currentIndex => $status === \App\Models\ReturnRequest::STATUS_COMPLETED ? 'is-done' : 'is-current',
                default => 'is-pending',
            };
        @endphp
        <li class="{{ $state }}">
            <span class="return-steps__dot">@if (str_contains($state, 'is-done'))<i class="fas fa-check"></i>@endif</span>
            <div class="return-steps__label">{{ $statusLabels[$status] }}</div>
            @if ($skipped)
                <div class="return-steps__time">Bỏ qua — miễn thu hồi hàng</div>
            @elseif (isset($reachedAt[$status]))
                <div class="return-steps__time">{{ $reachedAt[$status]->format('d/m/Y H:i') }}</div>
            @endif
        </li>
        @if ($isRejected && $index === $currentIndex)
            <li class="is-rejected">
                <span class="return-steps__dot"><i class="fas fa-times"></i></span>
                <div class="return-steps__label text-danger">{{ $statusLabels[\App\Models\ReturnRequest::STATUS_REJECTED] }}</div>
                @if ($rejection)
                    <div class="return-steps__time">{{ $rejection->created_at->format('d/m/Y H:i') }}</div>
                @endif
            </li>
            @break
        @endif
    @endforeach
</ul>
