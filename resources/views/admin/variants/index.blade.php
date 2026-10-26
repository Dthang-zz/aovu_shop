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
        <div class="card-body border-bottom">
            <form method="GET" action="{{ route('admin.variants.index') }}" class="row align-items-end">
                <div class="form-group col-lg-3 col-md-6 mb-2">
                    <label for="variant-search" class="small">Tìm kiếm</label>
                    <input id="variant-search" type="search" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Tên SP, mã SP, SKU...">
                </div>
                <div class="form-group col-lg-2 col-md-6 mb-2">
                    <label for="variant-product" class="small">Sản phẩm</label>
                    <select id="variant-product" name="product_id" class="form-control form-control-sm">
                        <option value="">Tất cả sản phẩm</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}" @selected((string) request('product_id') === (string) $product->id)>{{ $product->ten_san_pham }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-lg-2 col-md-4 mb-2">
                    <label for="variant-color" class="small">Màu sắc</label>
                    <select id="variant-color" name="color" class="form-control form-control-sm">
                        <option value="">Tất cả màu sắc</option>
                        @foreach ($colors as $color)
                            <option value="{{ $color }}" @selected(request('color') === $color)>{{ $color }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-lg-2 col-md-4 mb-2">
                    <label for="variant-size" class="small">Kích thước</label>
                    <select id="variant-size" name="size" class="form-control form-control-sm">
                        <option value="">Tất cả kích thước</option>
                        @foreach ($sizes as $size)
                            <option value="{{ $size }}" @selected(request('size') === $size)>{{ $size }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-lg-2 col-md-4 mb-2">
                    <label for="variant-status" class="small">Trạng thái</label>
                    <select id="variant-status" name="status" class="form-control form-control-sm">
                        <option value="">Tất cả trạng thái</option>
                        @foreach ($statuses as $status => $label)
                            <option value="{{ $status }}" @selected(request('status') === $status)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-lg-1 col-md-12 mb-2 d-flex">
                    <button type="submit" class="btn btn-primary btn-sm mr-1" aria-label="Lọc biến thể"><i class="fas fa-search"></i></button>
                    <a href="{{ route('admin.variants.index') }}" class="btn btn-default btn-sm" aria-label="Xóa bộ lọc"><i class="fas fa-times"></i></a>
                </div>
            </form>
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
                            <td>{{ $variant->price !== null ? number_format((float) $variant->price, 0, ',', '.') . ' ₫' : 'Theo SP chính' }}</td>
                            <td>
                                @if ($variant->stock <= 5)
                                    <span class="badge badge-danger">{{ $variant->stock }}</span>
                                @else
                                    <span class="badge badge-info">{{ $variant->stock }}</span>
                                @endif
                            </td>
                            <td><span class="badge badge-{{ $statusBadges[$variant->status] ?? 'secondary' }}">{{ $statuses[$variant->status] ?? 'Không xác định' }}</span></td>
                            <td class="text-center">
                                <a href="{{ route('admin.variants.show', $variant) }}" class="btn btn-sm btn-default text-primary"
                                    title="Xem chi tiết" aria-label="Xem chi tiết biến thể {{ $variant->sku }}">
                                    <i class="far fa-eye"></i>
                                </a>
                                <a href="{{ route('admin.variants.edit', $variant->id) }}" class="btn btn-sm btn-info"
                                    title="Sửa" aria-label="Sửa biến thể {{ $variant->sku }}">
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
