@extends('layouts.admin')

@section('title', 'Sản phẩm')
@section('page-heading', 'Sản phẩm')
@section('page-subheading', 'Quản lý thông tin, danh mục, giá bán và hình ảnh sản phẩm.')
@section('breadcrumb', 'Sản phẩm')

@section('content')
<div class="row">
    <div class="col-lg-3 col-sm-6">
        <div class="small-box bg-white">
            <div class="inner">
                <p class="text-muted mb-1">Tổng sản phẩm</p>
                <h3>{{ number_format($totalCount) }}</h3>
            </div>
            <div class="icon text-primary"><i class="fas fa-box-open"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-sm-6">
        <div class="small-box bg-white">
            <div class="inner">
                <p class="text-muted mb-1">Đang kinh doanh</p>
                <h3>{{ number_format($activeCount) }}</h3>
            </div>
            <div class="icon text-success"><i class="fas fa-check-circle"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-sm-6">
        <div class="small-box bg-white">
            <div class="inner">
                <p class="text-muted mb-1">Đang ẩn</p>
                <h3>{{ number_format($hiddenCount) }}</h3>
            </div>
            <div class="icon text-secondary"><i class="fas fa-eye-slash"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-sm-6">
        <div class="small-box bg-white">
            <div class="inner">
                <p class="text-muted mb-1">Sản phẩm nổi bật</p>
                <h3>{{ number_format($featuredCount) }}</h3>
            </div>
            <div class="icon text-warning"><i class="fas fa-star"></i></div>
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
                <h3 class="card-title"><i class="fas fa-tshirt text-primary mr-2"></i>Danh sách sản phẩm</h3>
                <div class="small text-muted mt-1"></div>
            </div>
            <div class="col-md-8 mt-3 mt-md-0">
                <form method="GET" action="{{ route('admin.products.index') }}" class="d-flex flex-wrap justify-content-md-end">
                    <div class="input-group input-group-sm mr-2 mb-1" style="max-width: 200px">
                        <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Tên hoặc mã sản phẩm..." aria-label="Tìm sản phẩm">
                        <div class="input-group-append"><button class="btn btn-default" type="submit" aria-label="Tìm"><i class="fas fa-search"></i></button></div>
                    </div>
                    <select name="category_id" class="form-control form-control-sm mr-2 mb-1" style="max-width: 165px" aria-label="Lọc danh mục" onchange="this.form.requestSubmit()">
                        <option value="">Tất cả danh mục</option>
                        @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) request('category_id')===(string) $category->id)>{{ $category->ten_danh_muc }}</option>
                        @endforeach
                    </select>
                    <select name="status" class="form-control form-control-sm mr-2 mb-1" style="max-width: 145px" aria-label="Lọc trạng thái" onchange="this.form.requestSubmit()">
                        <option value="">Tất cả trạng thái</option>
                        <option value="active" @selected(request('status')==='active' )>Đang kinh doanh</option>
                        <option value="hidden" @selected(request('status')==='hidden' )>Đang ẩn</option>
                    </select>
                    <select name="featured" class="form-control form-control-sm mr-2 mb-1" style="max-width: 145px" aria-label="Lọc sản phẩm nổi bật" onchange="this.form.requestSubmit()">
                        <option value="">Tất cả sản phẩm</option>
                        <option value="1" @selected(request('featured')==='1' )>Nổi bật</option>
                    </select>
                    <a href="{{ route('admin.products.create') }}" class="btn btn-primary btn-sm text-nowrap mb-1">
                        <i class="fas fa-plus mr-1"></i>Thêm sản phẩm
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
                        <th class="pl-3">Sản phẩm</th>
                        <th>Danh mục</th>
                        <th>Giá bán</th>
                        <th>Giá khuyến mãi</th>
                        <th>Trạng thái</th>
                        <th class="text-right pr-3">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($products as $product)
                    <tr>
                        <td class="pl-3">
                            <div class="d-flex align-items-center">
                                @if ($product->hinh_anh)
                                <img src="{{ Storage::disk('public')->url($product->hinh_anh) }}" alt="{{ $product->ten_san_pham }}" class="product-thumb mr-2">
                                @else
                                <span class="product-thumb mr-2 d-inline-flex align-items-center justify-content-center bg-light text-muted"><i class="fas fa-image"></i></span>
                                @endif
                                <div>
                                    <div class="font-weight-bold text-dark">{{ $product->ten_san_pham }}</div>
                                    <small class="text-muted">Mã: {{ $product->ma_san_pham }} · {{ $product->images_count }} ảnh</small>
                                    @if ($product->noi_bat)
                                    <span class="badge badge-warning ml-1"><i class="fas fa-star mr-1"></i>Nổi bật</span>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>{{ $product->category?->ten_danh_muc ?? 'Chưa phân loại' }}</td>
                        <td>{{ number_format($product->gia_san_pham, 0, ',', '.') }} ₫</td>
                        <td>{{ $product->gia_khuyen_mai !== null ? number_format($product->gia_khuyen_mai, 0, ',', '.') . ' ₫' : '—' }}</td>
                        <td>
                            @if ($product->trang_thai)
                            <span class="badge badge-success px-2 py-1">Đang kinh doanh</span>
                            @else
                            <span class="badge badge-secondary px-2 py-1">Đang ẩn</span>
                            @endif
                        </td>
                        <td class="text-right pr-3 text-nowrap">
                            <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-default btn-sm text-primary" aria-label="Chỉnh sửa {{ $product->ten_san_pham }}"><i class="far fa-edit"></i></a>
                            <form method="POST" action="{{ route('admin.products.destroy', $product) }}" class="d-inline" onsubmit="return confirm('Bạn có chắc muốn xóa sản phẩm này?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-default btn-sm text-danger" aria-label="Xóa {{ $product->ten_san_pham }}"><i class="far fa-trash-alt"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-5">
                            <i class="fas fa-box-open text-muted mb-2" style="font-size: 2rem"></i>
                            <div class="font-weight-bold">Chưa có sản phẩm phù hợp</div>
                            <div class="text-muted small">Thêm sản phẩm mới hoặc điều chỉnh bộ lọc tìm kiếm.</div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if ($products->hasPages())
    <div class="card-footer bg-white d-flex justify-content-between align-items-center">
        <span class="text-muted small">Hiển thị {{ $products->firstItem() }}–{{ $products->lastItem() }} trên {{ $products->total() }} sản phẩm</span>
        {{ $products->links() }}
    </div>
    @endif
</div>
@endsection