@extends('layouts.admin')

@section('title', 'Khuyến mãi')
@section('page-heading', 'Khuyến mãi')
@section('page-subheading', 'Quản lý mã giảm giá và chương trình ưu đãi.')
@section('breadcrumb', 'Khuyến mãi')

@section('content')
<div class="row">
    <div class="col-lg-4 col-sm-6">
        <div class="small-box bg-white">
            <div class="inner">
                <p class="text-muted mb-1">Tổng voucher</p>
                <h3>{{ number_format($totalCount) }}</h3>
            </div>
            <div class="icon text-primary"><i class="fas fa-tags"></i></div>
        </div>
    </div>
    <div class="col-lg-4 col-sm-6">
        <div class="small-box bg-white">
            <div class="inner">
                <p class="text-muted mb-1">Đang hoạt động</p>
                <h3>{{ number_format($activeCount) }}</h3>
            </div>
            <div class="icon text-success"><i class="fas fa-check-circle"></i></div>
        </div>
    </div>
    <div class="col-lg-4 col-sm-6">
        <div class="small-box bg-white">
            <div class="inner">
                <p class="text-muted mb-1">Tổng lượt sử dụng</p>
                <h3>{{ number_format($totalUsed) }}</h3>
            </div>
            <div class="icon text-info"><i class="fas fa-ticket-alt"></i></div>
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
            <div class="col-md-5">
                <h3 class="card-title"><i class="fas fa-tags text-primary mr-2"></i>Danh sách khuyến mãi</h3>
                <div class="small text-muted mt-1"></div>
            </div>
            <div class="col-md-7 mt-3 mt-md-0">
                <form method="GET" action="{{ route('admin.vouchers.index') }}" class="d-flex justify-content-md-end flex-wrap" style="gap: 0.4rem">
                    <div class="input-group input-group-sm" style="max-width: 220px">
                        <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Tìm mã voucher..." aria-label="Tìm voucher">
                        <div class="input-group-append"><button class="btn btn-default" type="submit" aria-label="Tìm"><i class="fas fa-search"></i></button></div>
                    </div>
                    <select name="status" class="form-control form-control-sm" style="max-width: 145px" aria-label="Lọc trạng thái" onchange="this.form.requestSubmit()">
                        <option value="">Tất cả trạng thái</option>
                        <option value="active" @selected(request('status')==='active')>Đang hoạt động</option>
                        <option value="inactive" @selected(request('status')==='inactive')>Tạm ngưng</option>
                    </select>
                    <a href="{{ route('admin.vouchers.export', request()->only(['q', 'status', 'type'])) }}" class="btn btn-outline-secondary btn-sm text-nowrap" title="Xuất CSV">
                        <i class="fas fa-file-csv mr-1"></i>Xuất CSV
                    </a>
                    <a href="{{ route('admin.vouchers.create') }}" class="btn btn-primary btn-sm text-nowrap">
                        <i class="fas fa-plus mr-1"></i>Thêm voucher
                    </a>
                </form>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        {{-- Bulk action bar --}}
        <div id="bulkBar" class="bg-light border-bottom px-3 py-2 d-none align-items-center" style="gap: 0.5rem">
            <span class="small text-muted" id="bulkCount">0 mục đã chọn</span>
            <form method="POST" action="{{ route('admin.vouchers.bulk-destroy') }}" id="bulkForm" class="ml-2" onsubmit="return confirm('Bạn có chắc muốn xóa các voucher đã chọn?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger btn-sm"><i class="far fa-trash-alt mr-1"></i>Xóa đã chọn</button>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th class="pl-3" style="width: 40px">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="checkAll">
                                <label class="custom-control-label" for="checkAll"></label>
                            </div>
                        </th>
                        <th>Mã voucher</th>
                        <th>Loại giảm</th>
                        <th>Giá trị</th>
                        <th>Đơn tối thiểu</th>
                        <th>Đã dùng / Tổng</th>
                        <th>Thời hạn</th>
                        <th>Trạng thái</th>
                        <th class="text-right pr-3">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($vouchers as $voucher)
                    <tr>
                        <td class="pl-3">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input row-check" id="check{{ $voucher->id }}" value="{{ $voucher->id }}">
                                <label class="custom-control-label" for="check{{ $voucher->id }}"></label>
                            </div>
                        </td>
                        <td>
                            <div class="font-weight-bold text-dark">{{ $voucher->ma_voucher }}</div>
                            @if ($voucher->mo_ta)
                            <small class="text-muted">{{ \Illuminate\Support\Str::limit($voucher->mo_ta, 60) }}</small>
                            @endif
                        </td>
                        <td>{{ $voucher->discountTypeLabel() }}</td>
                        <td class="font-weight-bold">{{ $voucher->formattedDiscount() }}</td>
                        <td>
                            @if ($voucher->don_toi_thieu)
                                ₫ {{ number_format($voucher->don_toi_thieu) }}
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            <div>
                                {{ number_format($voucher->da_su_dung ?? 0) }}
                                /
                                {{ $voucher->so_luong !== null ? number_format($voucher->so_luong) : '∞' }}
                            </div>
                            @if ($voucher->usagePercent() !== null)
                            <div class="progress mt-1" style="height: 4px; max-width: 80px">
                                @php
                                    $pct = $voucher->usagePercent();
                                    $barColor = $pct >= 90 ? 'bg-danger' : ($pct >= 70 ? 'bg-warning' : 'bg-success');
                                @endphp
                                <div class="progress-bar {{ $barColor }}" role="progressbar" style="width: {{ $pct }}%" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            @endif
                        </td>
                        <td>
                            @if ($voucher->ngay_bat_dau || $voucher->ngay_ket_thuc)
                                <small>
                                    {{ $voucher->ngay_bat_dau?->format('d/m/Y') ?? '—' }}
                                    –
                                    {{ $voucher->ngay_ket_thuc?->format('d/m/Y') ?? '—' }}
                                </small>
                            @else
                                <span class="text-muted">Không giới hạn</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge badge-{{ $voucher->statusBadge() }} px-2 py-1 voucher-badge" id="badge-{{ $voucher->id }}">{{ $voucher->statusLabel() }}</span>
                        </td>
                        <td class="text-right pr-3 text-nowrap">
                            {{-- Toggle nhanh trạng thái --}}
                            <button type="button"
                                class="btn btn-default btn-sm toggle-btn"
                                data-id="{{ $voucher->id }}"
                                data-url="{{ route('admin.vouchers.toggle', $voucher) }}"
                                title="{{ $voucher->trang_thai ? 'Tạm ngưng' : 'Kích hoạt' }}"
                                aria-label="Toggle {{ $voucher->ma_voucher }}">
                                <i class="fas {{ $voucher->trang_thai ? 'fa-toggle-on text-success' : 'fa-toggle-off text-secondary' }}"></i>
                            </button>
                            <a href="{{ route('admin.vouchers.edit', $voucher) }}" class="btn btn-default btn-sm text-primary" aria-label="Chỉnh sửa {{ $voucher->ma_voucher }}"><i class="far fa-edit"></i></a>
                            <form method="POST" action="{{ route('admin.vouchers.destroy', $voucher) }}" class="d-inline" onsubmit="return confirm('Bạn có chắc muốn xóa voucher này?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-default btn-sm text-danger" aria-label="Xóa {{ $voucher->ma_voucher }}"><i class="far fa-trash-alt"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-5">
                            <i class="fas fa-ticket-alt text-muted mb-2" style="font-size: 2rem"></i>
                            <div class="font-weight-bold">Chưa có voucher nào</div>
                            <div class="text-muted small">Thêm voucher mới hoặc điều chỉnh bộ lọc tìm kiếm.</div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if ($vouchers->hasPages())
    <div class="card-footer bg-white d-flex justify-content-between align-items-center">
        <span class="text-muted small">Hiển thị {{ $vouchers->firstItem() }}–{{ $vouchers->lastItem() }} trên {{ $vouchers->total() }} voucher</span>
        {{ $vouchers->links() }}
    </div>
    @endif
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // ── Bulk select ──────────────────────────────
    const checkAll  = document.getElementById('checkAll');
    const bulkBar   = document.getElementById('bulkBar');
    const bulkCount = document.getElementById('bulkCount');
    const bulkForm  = document.getElementById('bulkForm');

    function getChecked() {
        return document.querySelectorAll('.row-check:checked');
    }

    function syncBulkBar() {
        const checked = getChecked();
        if (checked.length > 0) {
            bulkBar.classList.remove('d-none');
            bulkBar.classList.add('d-flex');
            bulkCount.textContent = checked.length + ' mục đã chọn';
        } else {
            bulkBar.classList.add('d-none');
            bulkBar.classList.remove('d-flex');
        }

        // Sync hidden inputs
        bulkForm.querySelectorAll('input[name="ids[]"]').forEach(el => el.remove());
        checked.forEach(cb => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'ids[]';
            input.value = cb.value;
            bulkForm.appendChild(input);
        });
    }

    if (checkAll) {
        checkAll.addEventListener('change', function () {
            document.querySelectorAll('.row-check').forEach(cb => {
                cb.checked = checkAll.checked;
            });
            syncBulkBar();
        });
    }

    document.querySelectorAll('.row-check').forEach(cb => {
        cb.addEventListener('change', function () {
            const all = document.querySelectorAll('.row-check');
            checkAll.checked = all.length > 0 && getChecked().length === all.length;
            syncBulkBar();
        });
    });

    // ── Toggle trạng thái (AJAX) ──────────────────
    document.querySelectorAll('.toggle-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const url = this.dataset.url;
            const id  = this.dataset.id;
            const icon = this.querySelector('i');
            const badge = document.getElementById('badge-' + id);

            btn.disabled = true;

            fetch(url, {
                method: 'PATCH',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    // Update icon
                    if (data.trang_thai) {
                        icon.className = 'fas fa-toggle-on text-success';
                        btn.title = 'Tạm ngưng';
                    } else {
                        icon.className = 'fas fa-toggle-off text-secondary';
                        btn.title = 'Kích hoạt';
                    }
                    // Update badge
                    if (badge) {
                        badge.textContent = data.label;
                        badge.className = 'badge badge-' + data.badge + ' px-2 py-1 voucher-badge';
                    }
                }
            })
            .catch(() => {
                // Fallback: reload
                window.location.reload();
            })
            .finally(() => {
                btn.disabled = false;
            });
        });
    });
});
</script>
@endsection
