@extends('layouts.admin')

@section('title', 'Quản lý người dùng')
@section('page-heading', 'Quản lý người dùng')
@section('page-subheading', 'Danh sách tài khoản khách hàng, nhân viên và quản trị viên cửa hàng thời trang.')
@section('breadcrumb', 'Người dùng')

@section('content')
{{-- Thống kê tổng quan 4 thẻ --}}
<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-white shadow-sm border">
            <div class="inner">
                <p class="text-muted mb-1 font-weight-bold">Tổng tài khoản</p>
                <h3 class="font-weight-bold text-dark">{{ number_format($totalCount) }}</h3>
            </div>
            <div class="icon text-primary"><i class="fas fa-users"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-white shadow-sm border">
            <div class="inner">
                <p class="text-muted mb-1 font-weight-bold">Đang hoạt động</p>
                <h3 class="font-weight-bold text-success">{{ number_format($activeCount) }}</h3>
            </div>
            <div class="icon text-success"><i class="fas fa-user-check"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-white shadow-sm border">
            <div class="inner">
                <p class="text-muted mb-1 font-weight-bold">Khách hàng VIP</p>
                <h3 class="font-weight-bold text-warning">{{ number_format($vipCount) }}</h3>
            </div>
            <div class="icon text-warning"><i class="fas fa-crown"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-white shadow-sm border">
            <div class="inner">
                <p class="text-muted mb-1 font-weight-bold">Tài khoản khóa</p>
                <h3 class="font-weight-bold text-danger">{{ number_format($lockedCount) }}</h3>
            </div>
            <div class="icon text-danger"><i class="fas fa-user-lock"></i></div>
        </div>
    </div>
</div>

{{-- Flash messages --}}
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

@if (session('new_password_generated'))
<div class="alert alert-warning border-warning alert-dismissible fade show shadow-sm" role="alert">
    <div class="d-flex align-items-center justify-content-between flex-wrap" style="gap: 10px;">
        <div>
            <i class="fas fa-key fa-lg mr-2 text-dark"></i>
            <strong>Mật khẩu mới đã được cập nhật:</strong>
            <code class="px-3 py-1 bg-white rounded font-weight-bold ml-2 text-primary" style="font-size: 1.15rem;" id="copyPasswordText">{{ session('new_password_generated') }}</code>
        </div>
        <div>
            <button class="btn btn-sm btn-dark" type="button" onclick="copyPassword()">
                <i class="far fa-copy mr-1"></i>Sao chép mật khẩu
            </button>
            <button type="button" class="close position-static ml-2" data-dismiss="alert" aria-label="Đóng"><span aria-hidden="true">&times;</span></button>
        </div>
    </div>
</div>
@endif

<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3">
        <div class="row align-items-center">
            <div class="col-lg-3 col-md-4 mb-2 mb-md-0">
                <h3 class="card-title font-weight-bold text-dark"><i class="fas fa-users text-primary mr-2"></i>Danh sách người dùng</h3>
            </div>
            <div class="col-lg-9 col-md-8">
                <form method="GET" action="{{ route('admin.users.index') }}" class="d-flex flex-wrap justify-content-md-end align-items-center" style="gap: 8px">
                    {{-- Tìm kiếm --}}
                    <div class="input-group input-group-sm" style="max-width: 210px">
                        <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Tên, email, SĐT, nhãn..." aria-label="Tìm người dùng">
                        <div class="input-group-append"><button class="btn btn-default" type="submit" aria-label="Tìm"><i class="fas fa-search"></i></button></div>
                    </div>

                    {{-- Lọc chức vụ --}}
                    <select name="chuc_vu_id" class="form-control form-control-sm" style="max-width: 145px" aria-label="Lọc chức vụ" onchange="this.form.requestSubmit()">
                        <option value="">Tất cả vai trò</option>
                        @foreach ($chucVus as $cv)
                            <option value="{{ $cv->id }}" @selected(request('chuc_vu_id') == $cv->id)>{{ $cv->ten_chuc_vu }}</option>
                        @endforeach
                    </select>

                    {{-- Lọc trạng thái --}}
                    <select name="status" class="form-control form-control-sm" style="max-width: 135px" aria-label="Lọc trạng thái" onchange="this.form.requestSubmit()">
                        <option value="">Tất cả trạng thái</option>
                        <option value="active" @selected(request('status')==='active')>Hoạt động</option>
                        <option value="locked" @selected(request('status')==='locked')>Đã khóa</option>
                    </select>

                    {{-- Lọc nhãn CRM --}}
                    @if ($availableTags->isNotEmpty())
                    <select name="tag" class="form-control form-control-sm" style="max-width: 135px" aria-label="Lọc nhãn" onchange="this.form.requestSubmit()">
                        <option value="">Tất cả nhãn</option>
                        @foreach ($availableTags as $tag)
                            <option value="{{ $tag }}" @selected(request('tag') === $tag)>{{ $tag }}</option>
                        @endforeach
                    </select>
                    @endif

                    {{-- Xuất CSV / Excel --}}
                    <a href="{{ route('admin.users.export', request()->query()) }}" class="btn btn-outline-success btn-sm text-nowrap" title="Xuất dữ liệu ra file Excel / CSV có dấu">
                        <i class="fas fa-file-excel mr-1"></i>Xuất Excel
                    </a>

                    {{-- Thêm mới --}}
                    <a href="{{ route('admin.users.create') }}" class="btn btn-primary btn-sm text-nowrap">
                        <i class="fas fa-plus mr-1"></i>Thêm người dùng
                    </a>
                </form>
            </div>
        </div>
    </div>

    {{-- Thanh thao tác hàng loạt (Bulk Actions Toolbar) --}}
    <form id="bulkForm" method="POST" action="{{ route('admin.users.bulk-action') }}">
        @csrf
        <div id="bulkActionBar" class="bg-light px-3 py-2 border-bottom d-none align-items-center justify-content-between">
            <div>
                <span class="font-weight-bold text-dark"><i class="fas fa-check-square text-primary mr-1"></i>Đã chọn: <span id="selectedCount">0</span> tài khoản</span>
            </div>
            <div class="d-flex" style="gap: 6px;">
                <button type="submit" name="action" value="lock" class="btn btn-warning btn-sm" onclick="return confirm('Bạn có chắc muốn khóa tất cả tài khoản đã chọn?')">
                    <i class="fas fa-lock mr-1"></i>Khóa tài khoản đã chọn
                </button>
                <button type="submit" name="action" value="unlock" class="btn btn-success btn-sm">
                    <i class="fas fa-lock-open mr-1"></i>Mở khóa tài khoản đã chọn
                </button>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="bg-light">
                        <tr>
                            <th class="pl-3" style="width: 40px;">
                                <input type="checkbox" id="selectAllCheckbox" title="Chọn tất cả">
                            </th>
                            <th>Họ và tên</th>
                            <th>Liên hệ</th>
                            <th>Hạng & Nhãn</th>
                            <th>Size đồ</th>
                            <th>Vai trò</th>
                            <th>Trạng thái</th>
                            <th class="text-right pr-3">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $user)
                        @php $tier = $user->loyalty_tier; @endphp
                        <tr>
                            <td class="pl-3">
                                @if ($user->id !== 1)
                                    <input type="checkbox" name="user_ids[]" value="{{ $user->id }}" class="user-checkbox">
                                @endif
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="mr-2 position-relative">
                                        @if ($user->avatar_url)
                                            <img src="{{ $user->avatar_url }}" alt="{{ $user->ho_ten }}" class="img-circle border" style="width:38px;height:38px;object-fit:cover;">
                                        @else
                                            <div class="img-circle bg-gradient-primary d-flex align-items-center justify-content-center text-white font-weight-bold" style="width:38px;height:38px;font-size:.9rem;">
                                                {{ mb_strtoupper(mb_substr($user->ho_ten ?? '?', 0, 1)) }}
                                            </div>
                                        @endif
                                    </div>
                                    <div>
                                        <a href="{{ route('admin.users.show', $user) }}" class="font-weight-bold text-dark text-decoration-none">
                                            {{ $user->ho_ten }}
                                        </a>
                                        <small class="text-muted d-block">
                                            @if ($user->gioi_tinh === 1) Nam
                                            @elseif ($user->gioi_tinh === 0) Nữ
                                            @else Khác @endif
                                            @if ($user->ngay_sinh)
                                                &middot; {{ $user->ngay_sinh->format('d/m/Y') }}
                                            @endif
                                        </small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="small font-weight-bold text-dark">{{ $user->email }}</div>
                                <small class="text-muted">{{ $user->so_dien_thoai ?? '—' }}</small>
                            </td>
                            <td>
                                <div>
                                    <span class="badge {{ $tier['badge_class'] }} px-2 py-1" title="Hạng thành viên: {{ $tier['name'] }}">
                                        <i class="{{ $tier['icon'] }} mr-1"></i>{{ $tier['name'] }}
                                    </span>
                                </div>
                                @if ($user->nhan_khach_hang)
                                    <div class="mt-1">
                                        <span class="badge badge-light border text-secondary font-weight-normal small">{{ $user->nhan_khach_hang }}</span>
                                    </div>
                                @endif
                            </td>
                            <td>
                                @if ($user->size_thuong_mac)
                                    <span class="badge badge-primary px-2 py-1 font-weight-bold">{{ $user->size_thuong_mac }}</span>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                                @if ($user->chieu_cao || $user->can_nang)
                                    <small class="text-muted d-block font-italic">{{ $user->chieu_cao ? $user->chieu_cao . 'cm' : '' }}{{ $user->can_nang ? ' - ' . $user->can_nang . 'kg' : '' }}</small>
                                @endif
                            </td>
                            <td>
                                <span class="badge badge-light border font-weight-normal">{{ $user->chucVu?->ten_chuc_vu ?? 'Khách hàng' }}</span>
                            </td>
                            <td>
                                @if ($user->trang_thai)
                                    <span class="badge badge-success px-2 py-1">Hoạt động</span>
                                @else
                                    <span class="badge badge-danger px-2 py-1">Đã khóa</span>
                                @endif
                            </td>
                            <td class="text-right pr-3 text-nowrap">
                                <a href="{{ route('admin.users.show', $user) }}" class="btn btn-default btn-sm text-info" title="Xem chi tiết hồ sơ"><i class="far fa-eye"></i></a>
                                <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-default btn-sm text-primary" title="Chỉnh sửa"><i class="far fa-edit"></i></a>
                                <form method="POST" action="{{ route('admin.users.toggle-status', $user) }}" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    @if ($user->trang_thai)
                                        <button type="submit" class="btn btn-default btn-sm text-warning" title="Khóa tài khoản" onclick="return confirm('Bạn có chắc muốn khóa tài khoản này?')"><i class="fas fa-lock"></i></button>
                                    @else
                                        <button type="submit" class="btn btn-default btn-sm text-success" title="Mở khóa tài khoản"><i class="fas fa-lock-open"></i></button>
                                    @endif
                                </form>
                                @if ($user->id !== 1)
                                <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="d-inline" onsubmit="return confirm('Bạn có chắc muốn xóa vĩnh viễn người dùng này? Thao tác không thể hoàn tác.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-default btn-sm text-danger" title="Xóa người dùng"><i class="far fa-trash-alt"></i></button>
                                </form>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <i class="fas fa-users text-muted mb-2" style="font-size: 2.2rem"></i>
                                <div class="font-weight-bold text-dark">Chưa có người dùng phù hợp</div>
                                <div class="text-muted small">Hãy thử tìm kiếm với từ khóa khác hoặc điều chỉnh các bộ lọc phía trên.</div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </form>

    {{-- Phân trang chuẩn 6 users / trang --}}
    @if ($users->hasPages())
    <div class="card-footer bg-white d-flex flex-wrap justify-content-between align-items-center py-3 border-top" style="gap: 12px;">
        <span class="text-muted small">Hiển thị <strong>{{ $users->firstItem() }}–{{ $users->lastItem() }}</strong> trên <strong>{{ $users->total() }}</strong> người dùng</span>
        <div>
            {{ $users->links() }}
        </div>
    </div>
    @endif
</div>

@push('scripts')
<script>
// Chọn tất cả checkboxes
const selectAll = document.getElementById('selectAllCheckbox');
const userCheckboxes = document.querySelectorAll('.user-checkbox');
const bulkActionBar = document.getElementById('bulkActionBar');
const selectedCount = document.getElementById('selectedCount');

function updateBulkState() {
    const checked = document.querySelectorAll('.user-checkbox:checked');
    selectedCount.textContent = checked.length;
    if (checked.length > 0) {
        bulkActionBar.classList.remove('d-none');
        bulkActionBar.classList.add('d-flex');
    } else {
        bulkActionBar.classList.add('d-none');
        bulkActionBar.classList.remove('d-flex');
    }
}

selectAll?.addEventListener('change', function () {
    userCheckboxes.forEach(cb => cb.checked = this.checked);
    updateBulkState();
});

userCheckboxes.forEach(cb => {
    cb.addEventListener('change', function () {
        updateBulkState();
        if (!this.checked) selectAll.checked = false;
    });
});

function copyPassword() {
    const text = document.getElementById('copyPasswordText')?.innerText;
    if (text) {
        navigator.clipboard.writeText(text).then(function() {
            alert('Đã sao chép mật khẩu: ' + text);
        });
    }
}
</script>
@endpush
@endsection
