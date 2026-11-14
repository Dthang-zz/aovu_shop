@if ($history->isNotEmpty())
    <div class="card">
        <div class="card-header"><h3 class="card-title"><i class="fas fa-history text-primary mr-2"></i>Lịch sử trạng thái</h3></div>
        <div class="card-body">
            @foreach ($history as $entry)
                <div class="border-bottom pb-3 mb-3">
                    <span class="badge badge-{{ $statusBadges[$entry->trang_thai] ?? 'secondary' }}">{{ $statuses[$entry->trang_thai] ?? 'Không xác định' }}</span>
                    <span class="text-muted small ml-2">{{ $entry->ngay_cap_nhat ? \Illuminate\Support\Carbon::parse($entry->ngay_cap_nhat)->format('d/m/Y H:i') : '—' }}</span>
                    @if ($entry->ghi_chu)<div class="mt-1">{{ $entry->ghi_chu }}</div>@endif
                </div>
            @endforeach
        </div>
    </div>
@endif
