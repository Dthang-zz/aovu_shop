@extends('layouts.admin')

@section('title', 'Chi tiết danh mục')
@section('page-heading', $category->ten_danh_muc)
@section('page-subheading', 'Thông tin và sản phẩm thuộc danh mục.')
@section('breadcrumb')
    <a href="{{ route('admin.categories.index') }}">Danh mục</a>
    <span class="mx-1">/</span>
    Chi tiết
@endsection

@section('content')
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-info-circle text-primary mr-2"></i>Thông tin danh mục</h3>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Tên danh mục</dt>
                    <dd class="col-sm-8">{{ $category->ten_danh_muc }}</dd>

                    <dt class="col-sm-4">Danh mục cha</dt>
                    <dd class="col-sm-8">{{ $category->parent?->ten_danh_muc ?? 'Không có (danh mục gốc)' }}</dd>

                    <dt class="col-sm-4">Trạng thái</dt>
                    <dd class="col-sm-8">
                        @if ($category->trang_thai)
                        <span class="badge badge-success px-2 py-1">Đang hiển thị</span>
                        @else
                        <span class="badge badge-secondary px-2 py-1">Đang ẩn</span>
                        @endif
                    </dd>

                    <dt class="col-sm-4">Mô tả</dt>
                    <dd class="col-sm-8">{{ $category->mo_ta ?: 'Chưa có mô tả.' }}</dd>
                </dl>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="small-box bg-white">
            <div class="inner">
                <p class="text-muted mb-1">Sản phẩm trong danh mục</p>
                <h3>{{ number_format($category->products_count) }}</h3>
            </div>
            <div class="icon text-primary"><i class="fas fa-box-open"></i></div>
        </div>
        <div class="small-box bg-white">
            <div class="inner">
                <p class="text-muted mb-1">Danh mục con</p>
                <h3>{{ number_format($category->children->count()) }}</h3>
            </div>
            <div class="icon text-info"><i class="fas fa-sitemap"></i></div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-sitemap text-primary mr-2"></i>Danh mục con</h3>
    </div>
    <div class="card-body p-0">
        @if ($category->children->isNotEmpty())
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th class="pl-3">Tên danh mục</th>
                        <th>Số sản phẩm</th>
                        <th class="text-right pr-3">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($category->children as $child)
                    <tr>
                        <td class="pl-3">{{ $child->ten_danh_muc }}</td>
                        <td>{{ number_format($child->products_count) }}</td>
                        <td class="text-right pr-3">
                            <a href="{{ route('admin.categories.show', $child) }}" class="btn btn-default btn-sm text-info" aria-label="Xem chi tiết {{ $child->ten_danh_muc }}"><i class="far fa-eye"></i></a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="text-center text-muted py-4">Danh mục này chưa có danh mục con.</div>
        @endif
    </div>
    <div class="card-footer bg-white d-flex justify-content-between">
        <a href="{{ route('admin.categories.index') }}" class="btn btn-default"><i class="fas fa-arrow-left mr-1"></i>Quay lại</a>
        <a href="{{ route('admin.categories.edit', $category) }}" class="btn btn-primary"><i class="far fa-edit mr-1"></i>Chỉnh sửa</a>
    </div>
</div>
@endsection
