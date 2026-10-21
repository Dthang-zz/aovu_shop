@extends('layouts.admin')

@section('title', 'Quản lý người dùng')
@section('page-heading', 'Quản lý người dùng')
@section('page-subheading', 'Danh sách tài khoản khách hàng và quản trị viên.')
@section('breadcrumb', 'Người dùng')

@section('content')
<div class="row">
    <div class="col-lg-4 col-sm-6">
        <div class="small-box bg-white">
            <div class="inner">
                <p class="text-muted mb-1">Tổng tài khoản</p>
                <h3>{{ number_format($totalCount) }}</h3>
            </div>
            <div class="icon text-primary"><i class="fas fa-users"></i></div>
        </div>
    </div>
    <div class="col-lg-4 col-sm-6">
        <div class="small-box bg-white">
            <div class="inner">
                <p class="text-muted mb-1">Đang hoạt động</p>
                <h3>{{ number_format($activeCount) }}</h3>
            </div>
            <div class="icon text-success"><i class="fas fa-user-check"></i></div>
        </div>
    </div>
    <div class="col-lg-4 col-sm-6">
        <div class="small-box bg-white">
            <div class="inner">
                <p class="text-muted mb-1">Đã khóa</p>
                <h3>{{ number_format($lockedCount) }}</h3>
            </div>
            <div class="icon text-danger"><i class="fas fa-user-lock"></i></div>
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
                <h3 class="card-title"><i class="fas fa-users text-primary mr-2"></i>Danh sách người dùng</h3>
            </div>
            <div class="col-md-8 mt-3 mt-md-0">
                <form method="GET" action="{{ route('admin.users.index') }}" class="d-flex flex-wrap justify-content-md-end" style="gap: 6px">
                    <div class="input-group input-group-sm" style="max-width: 210px">
                        <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Tìm tên, email, SĐT..." aria-label="Tìm người dùng">
                        <div class="input-group-append"><button class="btn btn-default" type="submit" aria-label="Tìm"><i class="fas fa-search"></i></button></div>
                    </div>
                    <select name="chuc_vu_id" class="form-control form-control-sm" style="max-width: 145px" aria-label="Lọc chức vụ" onchange="this.form.requestSubmit()">
                        <option value="">Tất cả chức vụ</option>
                        @foreach ($chucVus as $cv)
                            <option value="{{ $cv->id }}" @selected(request('chuc_vu_id') == $cv->id)>{{ $cv->ten_chuc_vu }}</option>
                        @endforeach
                    </select>
                    <select name="status" class="form-control form-control-sm" style="max-width: 145px" aria-label="Lọc trạng thái" onchange="this.form.requestSubmit()">
                        <option value="">Tất cả trạng thái</option>
                        <option value="active" @selected(request('status')==='active')>Hoạt động</option>
                        <option value="locked" @selected(request('status')==='locked')>Đã khóa</option>
                    </select>
                    <a href="{{ route('admin.users.create') }}" class="btn btn-primary btn-sm text-nowrap">
                        <i class="fas fa-plus mr-1"></i>Thêm người dùng
                    </a>
                </form>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th class="pl-3">Người dùng</th>
                        <th>Email</th>
                        <th>Số điện thoại</th>
                        <th>Chức vụ</th>
                        <th>Ngày tạo</th>
                        <th>Trạng thái</th>
                        <th class="text-right pr-3">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                    <tr>
                        <td class="pl-3">
                            <div class="d-flex align-items-center">
                                <div class="user-avatar mr-2">
                                    @if ($user->anh_dai_dien)
                                        <img src="{{ $user->anh_dai_dien }}" alt="{{ $user->ho_ten }}" class="img-circle" style="width:36px;height:36px;object-fit:cover;">
                                    @else
                                        <div class="img-circle bg-gradient-primary d-flex align-items-center justify-content-center text-white font-weight-bold" style="width:36px;height:36px;font-size:.9rem;">
                                            {{ mb_strtoupper(mb_substr($user->ho_ten ?? '?', 0, 1)) }}
                                        </div>
                                    @endif
                                </div>
                                <div>
                                    <div class="font-weight-bold text-dark">{{ $user->ho_ten }}</div>
                                    <small class="text-muted">
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
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->so_dien_thoai ?? '—' }}</td>
                        <td>
                            @if ($user->chucVu)
                                <span class="badge badge-light border">{{ $user->chucVu->ten_chuc_vu }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            @if ($user->ngay_tao)
                                {{ $user->ngay_tao->format('d/m/Y') }}
                            @else
                                —
                            @endif
                        </td>
                        <td>
                            @if ($user->trang_thai)
                                <span class="badge badge-success px-2 py-1">Hoạt động</span>
                            @else
                                <span class="badge badge-danger px-2 py-1">Đã khóa</span>
                            @endif
                        </td>
                        <td class="text-right pr-3 text-nowrap">
                            <a href="{{ route('admin.users.show', $user) }}" class="btn btn-default btn-sm text-info" title="Xem chi tiết" aria-label="Xem chi tiết {{ $user->ho_ten }}"><i class="far fa-eye"></i></a>
                            <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-default btn-sm text-primary" aria-label="Chỉnh sửa {{ $user->ho_ten }}"><i class="far fa-edit"></i></a>
                            <form method="POST" action="{{ route('admin.users.toggle-status', $user) }}" class="d-inline">
                                @csrf
                                @method('PATCH')
                                @if ($user->trang_thai)
                                    <button type="submit" class="btn btn-default btn-sm text-warning" title="Khóa tài khoản" aria-label="Khóa {{ $user->ho_ten }}" onclick="return confirm('Bạn có chắc muốn khóa tài khoản này?')"><i class="fas fa-lock"></i></button>
                                @else
                                    <button type="submit" class="btn btn-default btn-sm text-success" title="Mở khóa tài khoản" aria-label="Mở khóa {{ $user->ho_ten }}"><i class="fas fa-lock-open"></i></button>
                                @endif
                            </form>
                            <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="d-inline" onsubmit="return confirm('Bạn có chắc muốn xóa người dùng này? Thao tác không thể hoàn tác.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-default btn-sm text-danger" aria-label="Xóa {{ $user->ho_ten }}"><i class="far fa-trash-alt"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5">
                            <i class="fas fa-users text-muted mb-2" style="font-size: 2rem"></i>
                            <div class="font-weight-bold">Chưa có người dùng phù hợp</div>
                            <div class="text-muted small">Thêm người dùng mới hoặc điều chỉnh bộ lọc tìm kiếm.</div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if ($users->hasPages())
    <div class="card-footer bg-white d-flex justify-content-between align-items-center">
        <span class="text-muted small">Hiển thị {{ $users->firstItem() }}–{{ $users->lastItem() }} trên {{ $users->total() }} người dùng</span>
        {{ $users->links() }}
    </div>
    @endif
</div>
@endsection
