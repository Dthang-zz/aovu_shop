@extends('layouts.admin')

@section('title', 'Chi tiết khuyến mãi')
@section('page-heading', $promotion->ten_chuong_trinh)
@section('page-subheading', 'Thông số chương trình và danh sách sản phẩm đang áp dụng.')
@section('breadcrumb')
    <a href="{{ route('admin.promotions.index') }}">Khuyến mãi</a> / Chi tiết
@endsection

@section('content')
    @php
        $isRunning = $promotion->trang_thai
            && $promotion->ngay_bat_dau->lte(now())
            && $promotion->ngay_ket_thuc->gte(now());
        $isUpcoming = $promotion->ngay_bat_dau->gt(now());
    @endphp

    <div class="d-flex justify-content-between align-items-center mb-3">
        <a href="{{ route('admin.promotions.index') }}" class="btn btn-default">
            <i class="fas fa-arrow-left mr-1"></i>Quay lại danh sách
        </a>
        <a href="{{ route('admin.promotions.edit', $promotion) }}" class="btn btn-primary">
            <i class="fas fa-edit mr-1"></i>Chỉnh sửa chương trình
        </a>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-tags text-primary mr-2"></i>Thông số chương trình</h3>
        </div>
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">Tên chương trình</dt>
                <dd class="col-sm-9">{{ $promotion->ten_chuong_trinh }}</dd>

                <dt class="col-sm-3">Mức giảm</dt>
                <dd class="col-sm-9">{{ rtrim(rtrim(number_format((float) $promotion->phan_tram_giam, 2, ',', '.'), '0'), ',') }}%</dd>

                <dt class="col-sm-3">Bắt đầu</dt>
                <dd class="col-sm-9">{{ $promotion->ngay_bat_dau->format('d/m/Y H:i') }}</dd>

                <dt class="col-sm-3">Kết thúc</dt>
                <dd class="col-sm-9">{{ $promotion->ngay_ket_thuc->format('d/m/Y H:i') }}</dd>

                <dt class="col-sm-3">Trạng thái</dt>
                <dd class="col-sm-9">
                    @if (! $promotion->trang_thai)
                        <span class="badge badge-secondary">Đã tắt</span>
                    @elseif ($isRunning)
                        <span class="badge badge-success">Đang áp dụng</span>
                    @elseif ($isUpcoming)
                        <span class="badge badge-info">Sắp diễn ra</span>
                    @else
                        <span class="badge badge-light">Đã kết thúc</span>
                    @endif
                    <span class="text-muted ml-1">{{ $promotion->trang_thai ? 'Đang bật' : 'Đang tắt' }}</span>
                </dd>
            </dl>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-box-open text-primary mr-2"></i>Sản phẩm áp dụng ({{ $products->total() }})</h3>
        </div>
        <div class="card-body p-0 table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th class="pl-3">Sản phẩm</th>
                        <th>Mã sản phẩm</th>
                        <th>Giá bán</th>
                        <th>Trạng thái sản phẩm</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($products as $product)
                        <tr>
                            <td class="pl-3">
                                <a href="{{ route('admin.products.show', $product) }}">{{ $product->ten_san_pham }}</a>
                            </td>
                            <td>{{ $product->ma_san_pham }}</td>
                            <td>{{ number_format($product->gia_san_pham, 0, ',', '.') }} ₫</td>
                            <td>
                                @if ($product->trang_thai)
                                    <span class="badge badge-success">Đang kinh doanh</span>
                                @else
                                    <span class="badge badge-secondary">Đang ẩn</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-5">Chương trình chưa có sản phẩm áp dụng.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($products->hasPages())
            <div class="card-footer">{{ $products->links() }}</div>
        @endif
    </div>
@endsection
