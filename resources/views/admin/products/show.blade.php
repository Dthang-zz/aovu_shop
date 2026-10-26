@extends('layouts.admin')

@section('title', 'Chi tiết sản phẩm')
@section('page-heading', $product->ten_san_pham)
@section('page-subheading', 'Thông tin sản phẩm, doanh thu và các đơn hàng đã hoàn thành.')
@section('breadcrumb')
    <a href="{{ route('admin.products.index') }}">Sản phẩm</a> / Chi tiết
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <a href="{{ route('admin.products.index') }}" class="btn btn-default">
            <i class="fas fa-arrow-left mr-1"></i>Quay lại danh sách
        </a>
        <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-primary">
            <i class="fas fa-edit mr-1"></i>Chỉnh sửa sản phẩm
        </a>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-box-open text-primary mr-2"></i>Thông tin sản phẩm</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            @if ($product->hinh_anh)
                                <img src="{{ Storage::disk('public')->url($product->hinh_anh) }}" alt="{{ $product->ten_san_pham }}" class="img-fluid rounded w-100" style="max-height: 260px; object-fit: cover">
                            @else
                                <div class="bg-light rounded d-flex align-items-center justify-content-center text-muted" style="height: 220px">
                                    <i class="far fa-image fa-3x"></i>
                                </div>
                            @endif
                        </div>
                        <dl class="col-md-8 row mb-0">
                            <dt class="col-sm-4">Mã sản phẩm</dt>
                            <dd class="col-sm-8">{{ $product->ma_san_pham }}</dd>
                            <dt class="col-sm-4">Danh mục</dt>
                            <dd class="col-sm-8">{{ $product->category?->ten_danh_muc ?? 'Chưa phân loại' }}</dd>
                            <dt class="col-sm-4">Giá bán</dt>
                            <dd class="col-sm-8">{{ number_format($product->gia_san_pham, 0, ',', '.') }} ₫</dd>
                            <dt class="col-sm-4">Giá khuyến mãi</dt>
                            <dd class="col-sm-8">{{ $product->gia_khuyen_mai !== null ? number_format($product->gia_khuyen_mai, 0, ',', '.') . ' ₫' : 'Không áp dụng' }}</dd>
                            <dt class="col-sm-4">Trạng thái</dt>
                            <dd class="col-sm-8">
                                @if ($product->trang_thai)
                                    <span class="badge badge-success">Đang kinh doanh</span>
                                @else
                                    <span class="badge badge-secondary">Đang ẩn</span>
                                @endif
                            </dd>
                            @if ($product->chat_lieu)
                                <dt class="col-sm-4">Chất liệu</dt>
                                <dd class="col-sm-8">{{ $product->chat_lieu }}</dd>
                            @endif
                            @if ($product->mo_ta)
                                <dt class="col-sm-4">Mô tả</dt>
                                <dd class="col-sm-8 text-break">{{ $product->mo_ta }}</dd>
                            @endif
                            @if ($product->huong_dan_su_dung)
                                <dt class="col-sm-4">Hướng dẫn sử dụng</dt>
                                <dd class="col-sm-8 text-break">{{ $product->huong_dan_su_dung }}</dd>
                            @endif
                        </dl>
                    </div>

                    @if ($product->images->isNotEmpty())
                        <hr>
                        <div class="row">
                            @foreach ($product->images as $image)
                                <div class="col-4 col-md-3 col-lg-2 mb-3">
                                    <img src="{{ Storage::disk('public')->url($image->link_hinh_anh) }}" alt="Ảnh {{ $product->ten_san_pham }}" class="img-fluid rounded w-100" style="height: 100px; object-fit: cover">
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-receipt text-primary mr-2"></i>Đơn hàng có sản phẩm này</h3>
                </div>
                <div class="card-body p-0 table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th class="pl-3">Mã đơn hàng</th>
                                <th>Ngày đặt</th>
                                <th>Số lượng</th>
                                <th class="text-right pr-3">Doanh thu</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($orders as $order)
                                <tr>
                                    <td class="pl-3">
                                        <a href="{{ route('admin.orders.show', $order->id) }}">{{ $order->ma_don_hang }}</a>
                                    </td>
                                    <td>{{ $order->ngay_dat ? \Illuminate\Support\Carbon::parse($order->ngay_dat)->format('d/m/Y H:i') : '—' }}</td>
                                    <td>{{ number_format($order->units_sold) }}</td>
                                    <td class="text-right pr-3">{{ number_format($order->revenue, 0, ',', '.') }} ₫</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">Chưa có đơn hàng hoàn thành chứa sản phẩm này.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($orders->hasPages())
                    <div class="card-footer">{{ $orders->links() }}</div>
                @endif
            </div>
        </div>

        <div class="col-lg-4">
            <div class="row">
                <div class="col-12">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3>{{ number_format($salesSummary->revenue, 0, ',', '.') }} ₫</h3>
                            <p>Doanh thu từ đơn hoàn thành</p>
                        </div>
                        <div class="icon"><i class="fas fa-coins"></i></div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-12">
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3>{{ number_format($salesSummary->units_sold) }}</h3>
                            <p>Sản phẩm đã bán</p>
                        </div>
                        <div class="icon"><i class="fas fa-shopping-bag"></i></div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-12">
                    <div class="small-box bg-primary">
                        <div class="inner">
                            <h3>{{ number_format($salesSummary->orders_count) }}</h3>
                            <p>Đơn hàng hoàn thành</p>
                        </div>
                        <div class="icon"><i class="fas fa-receipt"></i></div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-layer-group text-primary mr-2"></i>Biến thể ({{ $product->variants->count() }})</h3>
                </div>
                <div class="card-body p-0 table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th class="pl-3">SKU / Phân loại</th>
                                <th>Giá riêng</th>
                                <th>Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($product->variants as $variant)
                                <tr>
                                    <td class="pl-3">
                                        <code>{{ $variant->sku }}</code>
                                        <div class="small text-muted">{{ $variant->color ?: 'Mặc định' }} / {{ $variant->size ?: 'Mặc định' }}</div>
                                        <div class="small text-muted">Tồn kho: {{ number_format($variant->stock) }}</div>
                                    </td>
                                    <td>{{ $variant->price !== null ? number_format((float) $variant->price, 0, ',', '.') . ' ₫' : 'Theo giá SP' }}</td>
                                    <td>{{ $variantStatuses[$variant->status] ?? 'Không xác định' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center py-3 text-muted">Sản phẩm chưa có biến thể.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
