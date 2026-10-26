@extends('layouts.admin')

@section('title', 'Chi tiết biến thể')
@section('page-heading', 'Chi tiết biến thể')
@section('page-subheading', 'Thông tin SKU, thuộc tính, giá và trạng thái kinh doanh.')
@section('breadcrumb')
    <a href="{{ route('admin.variants.index') }}">Thuộc tính & biến thể</a> / Chi tiết
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <a href="{{ route('admin.variants.index') }}" class="btn btn-default">
            <i class="fas fa-arrow-left mr-1"></i>Quay lại danh sách
        </a>
        <a href="{{ route('admin.variants.edit', $variant) }}" class="btn btn-primary">
            <i class="fas fa-edit mr-1"></i>Chỉnh sửa biến thể
        </a>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-layer-group mr-2"></i>Thông tin biến thể #{{ $variant->id }}</h3>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Sản phẩm</dt>
                        <dd class="col-sm-8">
                            @if ($variant->product)
                                <a href="{{ route('admin.products.show', $variant->product) }}">
                                    {{ $variant->product->ten_san_pham }}
                                </a>
                                <div class="small text-muted">Mã sản phẩm: {{ $variant->product->ma_san_pham }}</div>
                            @else
                                <span class="text-muted">Không tìm thấy sản phẩm</span>
                            @endif
                        </dd>

                        <dt class="col-sm-4">SKU</dt>
                        <dd class="col-sm-8"><code>{{ $variant->sku }}</code></dd>

                        <dt class="col-sm-4">Màu sắc</dt>
                        <dd class="col-sm-8">{{ $variant->color ?: 'Mặc định' }}</dd>

                        <dt class="col-sm-4">Kích thước</dt>
                        <dd class="col-sm-8">{{ $variant->size ?: 'Mặc định' }}</dd>

                        <dt class="col-sm-4">Giá riêng</dt>
                        <dd class="col-sm-8">
                            {{ $variant->price !== null ? number_format((float) $variant->price, 0, ',', '.') . ' ₫' : 'Theo giá sản phẩm' }}
                        </dd>

                        <dt class="col-sm-4">Tồn kho</dt>
                        <dd class="col-sm-8">{{ number_format($variant->stock) }}</dd>

                        <dt class="col-sm-4">Trạng thái kinh doanh</dt>
                        <dd class="col-sm-8"><span class="badge badge-{{ $statusBadge }}">{{ $statusLabel }}</span></dd>

                        <dt class="col-sm-4">Ngày tạo</dt>
                        <dd class="col-sm-8">{{ $variant->created_at?->format('d/m/Y H:i') ?? '—' }}</dd>

                        <dt class="col-sm-4">Cập nhật lần cuối</dt>
                        <dd class="col-sm-8">{{ $variant->updated_at?->format('d/m/Y H:i') ?? '—' }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="small-box bg-primary">
                <div class="inner">
                    <h3>{{ number_format($variant->stock) }}</h3>
                    <p>Số lượng tồn kho</p>
                </div>
                <div class="icon"><i class="fas fa-boxes"></i></div>
            </div>
            <div class="card">
                <div class="card-body">
                    <h5><i class="fas fa-info-circle text-primary mr-1"></i>Giá áp dụng</h5>
                    <p class="mb-0 text-muted">
                        @if ($variant->price !== null)
                            Biến thể sử dụng giá riêng {{ number_format((float) $variant->price, 0, ',', '.') }} ₫.
                        @else
                            Biến thể sử dụng giá bán của sản phẩm.
                        @endif
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection
