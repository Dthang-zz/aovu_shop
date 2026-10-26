@extends('layouts.admin')

@section('title', 'Khuyến mãi')
@section('page-heading', 'Chương trình khuyến mãi')
@section('page-subheading', 'Tạo chương trình giảm giá theo phần trăm cho các sản phẩm được chọn.')
@section('breadcrumb', 'Khuyến mãi')

@section('content')
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title"><i class="fas fa-tags text-primary mr-2"></i>Danh sách chương trình</h3>
            <a href="{{ route('admin.promotions.create') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus mr-1"></i>Tạo khuyến mãi
            </a>
        </div>
        <div class="card-body p-0 table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th class="pl-3">Tên chương trình</th>
                        <th>Mức giảm</th>
                        <th>Sản phẩm áp dụng</th>
                        <th>Thời gian</th>
                        <th>Trạng thái</th>
                        <th class="text-right pr-3">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($promotions as $promotion)
                        @php
                            $isRunning = $promotion->trang_thai
                                && $promotion->ngay_bat_dau->lte(now())
                                && $promotion->ngay_ket_thuc->gte(now());
                            $isUpcoming = $promotion->ngay_bat_dau->gt(now());
                        @endphp
                        <tr>
                            <td class="pl-3 font-weight-bold">{{ $promotion->ten_chuong_trinh }}</td>
                            <td>{{ rtrim(rtrim(number_format((float) $promotion->phan_tram_giam, 2, ',', '.'), '0'), ',') }}%</td>
                            <td>
                                @foreach ($promotion->products as $product)
                                    <span class="badge badge-light border">{{ $product->ten_san_pham }}</span>
                                @endforeach
                            </td>
                            <td>{{ $promotion->ngay_bat_dau->format('d/m/Y H:i') }} – {{ $promotion->ngay_ket_thuc->format('d/m/Y H:i') }}</td>
                            <td>
                                @if (! $promotion->trang_thai)
                                    <span class="badge badge-secondary">Đã tắt</span>
                                @elseif ($isRunning)
                                    <span class="badge badge-success">Đang áp dụng</span>
                                @elseif ($isUpcoming)
                                    <span class="badge badge-info">Sắp diễn ra</span>
                                @else
                                    <span class="badge badge-light">Đã kết thúc</span>
                                @endif
                            </td>
                            <td class="text-right pr-3 text-nowrap">
                                <a href="{{ route('admin.promotions.show', $promotion) }}" class="btn btn-default btn-sm text-info" aria-label="Xem chi tiết {{ $promotion->ten_chuong_trinh }}">
                                    <i class="far fa-eye"></i>
                                </a>
                                <a href="{{ route('admin.promotions.edit', $promotion) }}" class="btn btn-default btn-sm text-primary" aria-label="Chỉnh sửa {{ $promotion->ten_chuong_trinh }}">
                                    <i class="far fa-edit"></i>
                                </a>
                                <form method="POST" action="{{ route('admin.promotions.destroy', $promotion) }}" class="d-inline" onsubmit="return confirm('Bạn có chắc muốn xóa chương trình này?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-default btn-sm text-danger" aria-label="Xóa {{ $promotion->ten_chuong_trinh }}">
                                        <i class="far fa-trash-alt"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-5">Chưa có chương trình khuyến mãi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($promotions->hasPages())
            <div class="card-footer">{{ $promotions->links() }}</div>
        @endif
    </div>
@endsection
