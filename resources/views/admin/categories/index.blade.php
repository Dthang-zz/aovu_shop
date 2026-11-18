@extends('layouts.admin')

@section('title', 'Danh mục sản phẩm')
@section('page-heading', 'Danh mục sản phẩm')
@section('page-subheading', 'Tổ chức sản phẩm theo nhóm và quản lý trạng thái hiển thị.')
@section('breadcrumb', 'Danh mục')

@section('content')
<div class="row">
    <div class="col-lg-4 col-sm-6">
        <div class="small-box bg-white">
            <div class="inner">
                <p class="text-muted mb-1">Tổng danh mục</p>
                <h3>{{ number_format($totalCount) }}</h3>
            </div>
            <div class="icon text-primary"><i class="fas fa-sitemap"></i></div>
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
                <h3 class="card-title"><i class="fas fa-sitemap text-primary mr-2"></i>Danh sách danh mục</h3>
                <div class="small text-muted mt-1"></div>
            </div>
            <div class="col-md-7 mt-3 mt-md-0">
                <form method="GET" action="{{ route('admin.categories.index') }}" class="d-flex justify-content-md-end">
                    <div class="input-group input-group-sm mr-2" style="max-width: 220px">
                        <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Tìm danh mục..." aria-label="Tìm danh mục">
                        <div class="input-group-append"><button class="btn btn-default" type="submit" aria-label="Tìm"><i class="fas fa-search"></i></button></div>
                    </div>
                    <select name="status" class="form-control form-control-sm mr-2" style="max-width: 145px" aria-label="Lọc trạng thái" onchange="this.form.requestSubmit()">
                        <option value="">Tất cả trạng thái</option>
                        <option value="active" @selected(request('status')==='active' )>Đang hiển thị</option>
                        <option value="hidden" @selected(request('status')==='hidden' )>Đang ẩn</option>
                    </select>
                    <a href="{{ route('admin.categories.create') }}" class="btn btn-primary btn-sm text-nowrap">
                        <i class="fas fa-plus mr-1"></i>Thêm danh mục
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
                        <th class="pl-3">Tên danh mục</th>
                        <th>Danh mục cha</th>
                        <th>Số danh mục con</th>
                        <th>Số sản phẩm</th>
                        <th>Trạng thái</th>
                        <th class="text-right pr-3">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($categories as $category)
                    <tr>
                        <td class="pl-3">
                            <div class="font-weight-bold text-dark" style="padding-left: {{ $category->hierarchy_depth * 1.25 }}rem">
                                @if ($category->hierarchy_depth > 0)
                                <i class="fas fa-level-up-alt fa-rotate-90 text-muted mr-1" aria-hidden="true"></i>
                                @endif
                                {{ $category->ten_danh_muc }}
                            </div>
                            @if ($category->mo_ta)
                            <small class="text-muted">{{ \Illuminate\Support\Str::limit($category->mo_ta, 90) }}</small>
                            @endif
                        </td>
                        <td>{{ $category->parent?->ten_danh_muc ?? '—' }}</td>
                        <td>{{ number_format($category->children_count) }}</td>
                        <td>{{ number_format($category->products_count) }}</td>
                        <td>
                            @if ($category->trang_thai)
                            <span class="badge badge-success px-2 py-1">Đang hiển thị</span>
                            @else
                            <span class="badge badge-secondary px-2 py-1">Đang ẩn</span>
                            @endif
                        </td>
                        <td class="text-right pr-3 text-nowrap">
                            <a href="{{ route('admin.categories.show', $category) }}" class="btn btn-default btn-sm text-info" aria-label="Xem chi tiết {{ $category->ten_danh_muc }}"><i class="far fa-eye"></i></a>
                            <a href="{{ route('admin.categories.edit', $category) }}" class="btn btn-default btn-sm text-primary" aria-label="Chỉnh sửa {{ $category->ten_danh_muc }}"><i class="far fa-edit"></i></a>
                            <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" class="d-inline" onsubmit="return confirm('Bạn có chắc muốn xóa danh mục này? Các danh mục con sẽ được chuyển thành danh mục gốc.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-default btn-sm text-danger" aria-label="Xóa {{ $category->ten_danh_muc }}"><i class="far fa-trash-alt"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-5">
                            <i class="fas fa-folder-open text-muted mb-2" style="font-size: 2rem"></i>
                            <div class="font-weight-bold">Chưa có danh mục phù hợp</div>
                            <div class="text-muted small">Thêm danh mục mới hoặc điều chỉnh bộ lọc tìm kiếm.</div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if ($categories->hasPages())
    <div class="card-footer bg-white d-flex justify-content-between align-items-center">
        <span class="text-muted small">Hiển thị {{ $categories->firstItem() }}–{{ $categories->lastItem() }} trên {{ $categories->total() }} danh mục</span>
        {{ $categories->links() }}
    </div>
    @endif
</div>
@endsection