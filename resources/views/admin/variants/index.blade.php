@extends('layouts.admin')

@section('page-heading', 'Thuộc tính & biến thể')
@section('page-subheading', 'Theo dõi màu sắc, kích thước và mã SKU theo từng phiên bản.')
@section('breadcrumb', 'Biến thể sản phẩm')

@section('content')
    <!-- Thông báo thành công -->
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="icon fas fa-check"></i> {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <!-- Thẻ thống kê -->
    <div class="row">
        <div class="col-lg-4 col-6">
            <div class="small-box bg-primary">
                <div class="inner">
                    <h3>{{ $totalVariants ?? 0 }}</h3>
                    <p>Tổng biến thể</p>
                </div>
                <div class="icon"><i class="fas fa-layer-group"></i></div>
            </div>
        </div>
        <div class="col-lg-4 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $colorsCount ?? 0 }}</h3>
                    <p>Màu sắc</p>
                </div>
                <div class="icon"><i class="fas fa-palette"></i></div>
            </div>
        </div>
        <div class="col-lg-4 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ $sizesCount ?? 0 }}</h3>
                    <p>Kích thước</p>
                </div>
                <div class="icon"><i class="fas fa-ruler"></i></div>
            </div>
        </div>
    </div>

    <!-- Bảng dữ liệu -->
    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title">Danh sách biến thể sản phẩm</h3>
            <div class="card-tools">
                <a href="{{ route('admin.variants.create') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Thêm biến thể
                </a>
            </div>
        </div>
        <div class="card-body p-0 table-responsive">
            <table class="table table-hover table-striped text-nowrap">
                <thead>
                    <tr>
                        <th style="width: 60px">ID</th>
                        <th>Sản phẩm</th>
                        <th>Màu sắc / Kích thước</th>
                        <th>SKU</th>
                        <th>Giá bán</th>
                        <th>Tồn kho</th>
                        <th>Trạng thái</th>
                        <th style="width: 120px" class="text-center">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($variants as $variant)
                        <tr>
                            <td>{{ $variant->id }}</td>
                            <td>
                                <strong>{{ $variant->product->ten_san_pham ?? 'N/A' }}</strong>
                                <br>
                                <small class="text-muted">Mã SP: {{ $variant->product->ma_san_pham ?? 'N/A' }}</small>
                            </td>
                            <td>
                                <span class="badge badge-light border">{{ $variant->color ?? 'Mặc định' }}</span> /
                                <span class="badge badge-light border">{{ $variant->size ?? 'Mặc định' }}</span>
                            </td>
                            <td><code>{{ $variant->sku }}</code></td>
                            <td>{{ $variant->price ? number_format($variant->price) . ' đ' : 'Theo SP chính' }}</td>
                            <td>
                                @if ($variant->stock <= 5)
                                    <span class="badge badge-danger">{{ $variant->stock }}</span>
                                @else
                                    <span class="badge badge-info">{{ $variant->stock }}</span>
                                @endif
                            </td>
                            <td>
                                @if ($variant->status == 'active')
                                    <span class="badge badge-success">Đang bán</span>
                                @else
                                    <span class="badge badge-secondary">Tạm ngưng</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <a href="{{ route('admin.variants.edit', $variant->id) }}" class="btn btn-sm btn-info"
                                    title="Sửa">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('admin.variants.destroy', $variant->id) }}" method="POST"
                                    class="d-inline-block"
                                    onsubmit="return confirm('Bạn có chắc chắn muốn xóa biến thể này?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" title="Xóa">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4">Chưa có dữ liệu biến thể nào.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($variants->hasPages())
            <div class="card-footer clearfix">
                <div class="float-right">
                    {{ $variants->links() }}
                </div>
            </div>
        @endif
    </div>
@endsection
