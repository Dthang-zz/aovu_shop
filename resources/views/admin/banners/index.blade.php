@extends('layouts.admin')

@section('title', 'Quản lý banner')
@section('page-heading', 'Quản lý banner')
@section('page-subheading', 'Quản lý hình ảnh, liên kết và thứ tự hiển thị banner.')
@section('breadcrumb', 'Banner')

@section('content')
<div class="row">
    <div class="col-lg-4 col-sm-6">
        <div class="small-box bg-white">
            <div class="inner">
                <p class="text-muted mb-1">Tổng banner</p>
                <h3>{{ number_format($totalCount) }}</h3>
            </div>
            <div class="icon text-primary"><i class="far fa-images"></i></div>
        </div>
    </div>
    <div class="col-lg-4 col-sm-6">
        <div class="small-box bg-white">
            <div class="inner">
                <p class="text-muted mb-1">Đang hiển thị</p>
                <h3>{{ number_format($activeCount) }}</h3>
            </div>
            <div class="icon text-success"><i class="fas fa-eye"></i></div>
        </div>
    </div>
    <div class="col-lg-4 col-sm-6">
        <div class="small-box bg-white">
            <div class="inner">
                <p class="text-muted mb-1">Đang ẩn</p>
                <h3>{{ number_format($hiddenCount) }}</h3>
            </div>
            <div class="icon text-secondary"><i class="fas fa-eye-slash"></i></div>
        </div>
    </div>
</div>

@if (session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle mr-1"></i>{{ session('success') }}
    <button type="button" class="close" data-dismiss="alert" aria-label="Đóng"><span aria-hidden="true">&times;</span></button>
</div>
@endif

<div class="card">
    <div class="card-header py-3">
        <div class="row align-items-center">
            <div class="col-md-5">
                <h3 class="card-title"><i class="far fa-images text-primary mr-2"></i>Danh sách banner</h3>
            </div>
            <div class="col-md-7 mt-3 mt-md-0">
                <form method="GET" action="{{ route('admin.banners.index') }}" class="d-flex flex-wrap justify-content-md-end">
                    <div class="input-group input-group-sm mr-2 mb-1" style="max-width: 220px">
                        <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Tìm tên banner..." aria-label="Tìm banner">
                        <div class="input-group-append"><button class="btn btn-default" type="submit" aria-label="Tìm"><i class="fas fa-search"></i></button></div>
                    </div>
                    <select name="status" class="form-control form-control-sm mr-2 mb-1" style="max-width: 145px" aria-label="Lọc trạng thái" onchange="this.form.requestSubmit()">
                        <option value="">Tất cả trạng thái</option>
                        <option value="active" @selected(request('status') === 'active')>Đang hiển thị</option>
                        <option value="hidden" @selected(request('status') === 'hidden')>Đang ẩn</option>
                    </select>
                    <a href="{{ route('admin.banners.create') }}" class="btn btn-primary btn-sm text-nowrap mb-1">
                        <i class="fas fa-plus mr-1"></i>Thêm banner
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
                        <th class="pl-3">Banner</th>
                        <th>Liên kết</th>
                        <th>Thứ tự</th>
                        <th>Trạng thái</th>
                        <th class="text-right pr-3">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($banners as $banner)
                    <tr>
                        <td class="pl-3">
                            <div class="d-flex align-items-center">
                                @if ($banner->hinh_anh)
                                    <img src="{{ Storage::disk('public')->url($banner->hinh_anh) }}" alt="{{ $banner->ten_banner }}" class="mr-2 rounded" style="width: 120px; height: 64px; object-fit: cover">
                                @else
                                    <span class="mr-2 d-inline-flex align-items-center justify-content-center bg-light text-muted rounded" style="width: 120px; height: 64px"><i class="far fa-image"></i></span>
                                @endif
                                <div class="font-weight-bold text-dark">{{ $banner->ten_banner }}</div>
                            </div>
                        </td>
                        <td class="text-truncate" style="max-width: 220px">{{ $banner->lien_ket ?: '—' }}</td>
                        <td>{{ $banner->thu_tu }}</td>
                        <td>
                            @if ($banner->trang_thai)
                                <span class="badge badge-success px-2 py-1">Đang hiển thị</span>
                            @else
                                <span class="badge badge-secondary px-2 py-1">Đang ẩn</span>
                            @endif
                        </td>
                        <td class="text-right pr-3 text-nowrap">
                            <a href="{{ route('admin.banners.edit', $banner) }}" class="btn btn-default btn-sm text-primary" aria-label="Chỉnh sửa {{ $banner->ten_banner }}"><i class="far fa-edit"></i></a>
                            <form method="POST" action="{{ route('admin.banners.destroy', $banner) }}" class="d-inline" onsubmit="return confirm('Bạn có chắc muốn xóa banner này?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-default btn-sm text-danger" aria-label="Xóa {{ $banner->ten_banner }}"><i class="far fa-trash-alt"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-5">
                            <i class="far fa-images text-muted mb-2" style="font-size: 2rem"></i>
                            <div class="font-weight-bold">Chưa có banner phù hợp</div>
                            <div class="text-muted small">Thêm banner mới hoặc điều chỉnh bộ lọc tìm kiếm.</div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if ($banners->hasPages())
    <div class="card-footer bg-white d-flex justify-content-between align-items-center">
        <span class="text-muted small">Hiển thị {{ $banners->firstItem() }}–{{ $banners->lastItem() }} trên {{ $banners->total() }} banner</span>
        {{ $banners->links() }}
    </div>
    @endif
</div>
@endsection
