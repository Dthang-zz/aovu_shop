@extends('layouts.admin')

@section('title', 'Khuyến mãi')
@section('page-heading', 'Mã giảm giá')
@section('page-subheading', 'Tạo mã để khách nhập ở giỏ hàng / thanh toán và được giảm trên tổng đơn.')
@section('breadcrumb', 'Khuyến mãi')

@section('content')
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card">
        <div class="card-header">
            <div class="d-flex flex-wrap justify-content-between align-items-center">
                <h3 class="card-title mb-2 mb-md-0"><i class="fas fa-ticket-alt text-primary mr-2"></i>Danh sách mã giảm giá</h3>
                <div class="d-flex flex-wrap">
                    <form method="GET" action="{{ route('admin.promotions.index') }}" class="input-group input-group-sm mr-2" style="max-width: 240px">
                        <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Tìm mã, mô tả..." aria-label="Tìm mã giảm giá">
                        <div class="input-group-append"><button class="btn btn-default" type="submit" aria-label="Tìm"><i class="fas fa-search"></i></button></div>
                    </form>
                    <a href="{{ route('admin.promotions.create') }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus mr-1"></i>Tạo mã giảm giá
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body p-0 table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th class="pl-3">Mã</th>
                        <th>Mức giảm</th>
                        <th>Lượt dùng</th>
                        <th>Thời gian</th>
                        <th>Trạng thái</th>
                        <th class="text-right pr-3">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($vouchers as $voucher)
                        @php([$stateLabel, $stateBadge] = $voucher->stateBadge())
                        <tr>
                            <td class="pl-3">
                                <code class="font-weight-bold">{{ $voucher->ma_voucher }}</code>
                                @if ($voucher->mo_ta)
                                    <small class="d-block text-muted">{{ $voucher->mo_ta }}</small>
                                @endif
                            </td>
                            <td>
                                {{ $voucher->discountLabel() }}
                                @if ($voucher->conditionsLabel())
                                    <small class="d-block text-muted">{{ $voucher->conditionsLabel() }}</small>
                                @endif
                            </td>
                            <td>{{ number_format($voucher->da_su_dung ?? 0) }} / {{ $voucher->so_luong !== null ? number_format($voucher->so_luong) : '∞' }}</td>
                            <td class="text-nowrap">
                                {{ $voucher->ngay_bat_dau?->format('d/m/Y H:i') ?? '—' }}
                                <small class="d-block text-muted">đến {{ $voucher->ngay_ket_thuc?->format('d/m/Y H:i') ?? '—' }}</small>
                            </td>
                            <td><span class="badge badge-{{ $stateBadge }}">{{ $stateLabel }}</span></td>
                            <td class="text-right pr-3 text-nowrap">
                                <a href="{{ route('admin.promotions.show', $voucher) }}" class="btn btn-default btn-sm text-info" aria-label="Xem chi tiết {{ $voucher->ma_voucher }}">
                                    <i class="far fa-eye"></i>
                                </a>
                                <a href="{{ route('admin.promotions.edit', $voucher) }}" class="btn btn-default btn-sm text-primary" aria-label="Chỉnh sửa {{ $voucher->ma_voucher }}">
                                    <i class="far fa-edit"></i>
                                </a>
                                <form method="POST" action="{{ route('admin.promotions.destroy', $voucher) }}" class="d-inline" onsubmit="return confirm('Bạn có chắc muốn xóa mã {{ $voucher->ma_voucher }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-default btn-sm text-danger" aria-label="Xóa {{ $voucher->ma_voucher }}">
                                        <i class="far fa-trash-alt"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-5">Chưa có mã giảm giá nào.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($vouchers->hasPages())
            <div class="card-footer">{{ $vouchers->links() }}</div>
        @endif
    </div>
@endsection
