@extends('layouts.storefront')

@section('title', 'Chi tiết đơn hàng #' . $order->ma_don_hang)

@section('content')
    <div class="mb-3">
        <a href="{{ route('customer.orders') }}" class="btn btn-link px-0 text-muted">
            <i class="fas fa-arrow-left mr-1"></i>Quay lại danh sách đơn hàng
        </a>
    </div>

    <div class="card storefront-card mb-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h1 class="h4 font-weight-bold mb-0 text-primary">Đơn hàng #{{ $order->ma_don_hang }}</h1>
                <small class="text-muted">Ngày đặt hàng: {{ $order->ngay_dat ? \Carbon\Carbon::parse($order->ngay_dat)->format('d/m/Y H:i') : '' }}</small>
            </div>
            <div class="mt-2 mt-sm-0">
                @php
                    $statusMap = [
                        0 => ['badge' => 'warning', 'text' => 'Chờ xác nhận'],
                        1 => ['badge' => 'info', 'text' => 'Đang chuẩn bị hàng'],
                        2 => ['badge' => 'primary', 'text' => 'Đang giao hàng'],
                        3 => ['badge' => 'success', 'text' => 'Giao thành công'],
                        4 => ['badge' => 'danger', 'text' => 'Đã hủy'],
                    ];
                    $st = $statusMap[(int) $order->trang_thai] ?? ['badge' => 'secondary', 'text' => 'Đang xử lý'];
                @endphp
                <span class="badge badge-{{ $st['badge'] }} px-3 py-2 font-weight-normal mr-2">
                    {{ $st['text'] }}
                </span>
                <span class="badge badge-{{ $order->trang_thai_thanh_toan ? 'success' : 'secondary' }} px-3 py-2 font-weight-normal">
                    {{ $order->trang_thai_thanh_toan ? 'Đã thanh toán' : 'Chưa thanh toán (COD)' }}
                </span>
            </div>
        </div>
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-6 mb-3 mb-md-0">
                    <div class="card bg-light border-0 p-3 h-100">
                        <h6 class="font-weight-bold text-dark mb-2"><i class="fas fa-map-marker-alt text-danger mr-1"></i>Địa chỉ nhận hàng</h6>
                        <p class="mb-1"><strong>{{ $order->ten_nguoi_nhan }}</strong></p>
                        <p class="mb-1 text-muted small"><i class="fas fa-phone-alt mr-1"></i>{{ $order->sdt_nguoi_nhan }}</p>
                        <p class="mb-0 text-muted small"><i class="fas fa-home mr-1"></i>{{ $order->dia_chi_nguoi_nhan }}</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card bg-light border-0 p-3 h-100">
                        <h6 class="font-weight-bold text-dark mb-2"><i class="fas fa-info-circle text-info mr-1"></i>Thông tin bổ sung</h6>
                        <p class="mb-1 text-muted small">Email: <strong>{{ $order->email_nguoi_nhan ?: $customer->email }}</strong></p>
                        <p class="mb-1 text-muted small">Ghi chú: {{ $order->ghi_chu ?: 'Không có ghi chú' }}</p>
                        @if ($order->voucher_id)
                            <p class="mb-0 text-success small"><i class="fas fa-tag mr-1"></i>Có áp dụng mã voucher giảm giá</p>
                        @endif
                    </div>
                </div>
            </div>

            <h5 class="font-weight-bold mb-3">Danh sách sản phẩm trong đơn</h5>
            <div class="table-responsive border rounded mb-3">
                <table class="table mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Sản phẩm</th>
                            <th class="text-center">Số lượng</th>
                            <th class="text-right">Đơn giá</th>
                            <th class="text-right">Thành tiền</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $item)
                            <tr>
                                <td>
                                    <strong>{{ $item->ten_san_pham }}</strong>
                                    @if (! empty($item->ten_khuyen_mai))
                                        <br><small class="text-danger"><i class="fas fa-tag mr-1"></i>Khuyến mãi: {{ $item->ten_khuyen_mai }} (-{{ $item->phan_tram_giam }}%)</small>
                                    @endif
                                </td>
                                <td class="text-center">{{ $item->so_luong }}</td>
                                <td class="text-right">{{ number_format($item->don_gia, 0, ',', '.') }} ₫</td>
                                <td class="text-right font-weight-bold">{{ number_format($item->thanh_tien, 0, ',', '.') }} ₫</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-3">Không có thông tin chi tiết món hàng.</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        @if (! empty($order->tien_giam) && $order->tien_giam > 0)
                            <tr>
                                <td colspan="3" class="text-right text-muted">Giảm giá:</td>
                                <td class="text-right text-success font-weight-bold">-{{ number_format($order->tien_giam, 0, ',', '.') }} ₫</td>
                            </tr>
                        @endif
                        <tr>
                            <td colspan="3" class="text-right text-muted">Phí vận chuyển:</td>
                            <td class="text-right text-muted">Miễn phí</td>
                        </tr>
                        <tr class="h6">
                            <th colspan="3" class="text-right">Tổng thanh toán:</th>
                            <th class="text-right text-danger font-weight-bold h5 mb-0">{{ number_format($order->tong_tien, 0, ',', '.') }} ₫</th>
                        </tr>
                    </tfoot>
                </table>
            </div>

            @if ($returnRequests->isNotEmpty())
                <div class="alert alert-info mt-4 mb-0">
                    <h6 class="font-weight-bold mb-2"><i class="fas fa-undo-alt mr-1"></i>Yêu cầu đổi / trả của đơn hàng này:</h6>
                    @foreach ($returnRequests as $rr)
                        <div class="mb-1">
                            - Yêu cầu #{{ $rr->id }} | Loại: <strong>{{ method_exists($rr, 'typeLabel') ? $rr->typeLabel() : 'Đổi trả' }}</strong> | Trạng thái: <strong>{{ method_exists($rr, 'statusLabel') ? $rr->statusLabel() : 'Đang xử lý' }}</strong>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="d-flex justify-content-end mt-3">
                    <a href="{{ route('cskh.index') }}#lien-he" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-question-circle mr-1"></i>Cần trợ giúp về đơn hàng này? Liên hệ CSKH
                    </a>
                </div>
            @endif
        </div>
    </div>
@endsection
