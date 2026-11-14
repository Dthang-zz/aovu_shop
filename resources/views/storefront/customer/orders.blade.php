@extends('layouts.storefront')

@section('title', 'Đơn hàng của tôi')

@section('content')
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 font-weight-bold mb-1">Lịch sử đơn hàng</h1>
            <p class="text-muted">Theo dõi và kiểm tra tình trạng các đơn hàng bạn đã mua tại AOVU Shop.</p>
        </div>
    </div>

    <div class="row">
        <!-- Sidebar tài khoản -->
        <div class="col-lg-4 mb-4">
            <div class="list-group storefront-card overflow-hidden">
                <a href="{{ route('customer.profile') }}" class="list-group-item list-group-item-action">
                    <i class="fas fa-id-card text-primary mr-2"></i>Hồ sơ cá nhân
                </a>
                <a href="{{ route('customer.addresses.index') }}" class="list-group-item list-group-item-action">
                    <i class="fas fa-map-marked-alt text-info mr-2"></i>Sổ địa chỉ nhận hàng
                </a>
                <a href="{{ route('customer.orders') }}" class="list-group-item list-group-item-action active font-weight-bold">
                    <i class="fas fa-box-open mr-2"></i>Đơn hàng của tôi
                    <span class="badge badge-light border float-right">{{ $orders->total() ?? $orders->count() }}</span>
                </a>
                <a href="{{ route('shop.wishlist.index') }}" class="list-group-item list-group-item-action">
                    <i class="fas fa-heart text-danger mr-2"></i>Sản phẩm yêu thích
                </a>
            </div>
        </div>

        <!-- Danh sách đơn hàng -->
        <div class="col-lg-8">
            @if ($orders->isEmpty())
                <div class="card storefront-card text-center py-5">
                    <div class="card-body">
                        <i class="fas fa-shopping-bag fa-4x text-muted mb-3"></i>
                        <h5 class="font-weight-bold mb-2">Bạn chưa có đơn hàng nào</h5>
                        <p class="text-muted small mb-4">Hãy chọn những món đồ thời trang yêu thích và trải nghiệm mua sắm tại cửa hàng ngay hôm nay!</p>
                        <a href="{{ route('shop.products.index') }}" class="btn btn-primary px-4">
                            Khám phá sản phẩm ngay
                        </a>
                    </div>
                </div>
            @else
                <div class="card storefront-card">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Mã đơn</th>
                                        <th>Ngày đặt</th>
                                        <th>Tổng tiền</th>
                                        <th>Trạng thái đơn</th>
                                        <th>Thanh toán</th>
                                        <th class="text-right">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $statusMap = [
                                            0 => ['badge' => 'warning', 'text' => 'Chờ xác nhận'],
                                            1 => ['badge' => 'info', 'text' => 'Đang xử lý'],
                                            2 => ['badge' => 'primary', 'text' => 'Đang giao'],
                                            3 => ['badge' => 'success', 'text' => 'Đã giao'],
                                            4 => ['badge' => 'danger', 'text' => 'Đã hủy'],
                                        ];
                                    @endphp
                                    @foreach ($orders as $order)
                                        @php($st = $statusMap[(int) $order->trang_thai] ?? ['badge' => 'secondary', 'text' => 'Chờ xử lý'])
                                        <tr>
                                            <td class="font-weight-bold text-primary">#{{ $order->ma_don_hang }}</td>
                                            <td class="small">{{ $order->ngay_dat ? \Carbon\Carbon::parse($order->ngay_dat)->format('d/m/Y H:i') : '' }}</td>
                                            <td class="font-weight-bold text-danger">{{ number_format($order->tong_tien, 0, ',', '.') }} ₫</td>
                                            <td><span class="badge badge-{{ $st['badge'] }} px-2 py-1">{{ $st['text'] }}</span></td>
                                            <td>
                                                <span class="badge badge-{{ $order->trang_thai_thanh_toan ? 'success' : 'secondary' }} px-2 py-1">
                                                    {{ $order->trang_thai_thanh_toan ? 'Đã thanh toán' : 'Chưa thanh toán' }}
                                                </span>
                                            </td>
                                            <td class="text-right">
                                                <a href="{{ route('customer.orders.show', $order->id) }}" class="btn btn-outline-primary btn-sm">
                                                    Chi tiết
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                @if (method_exists($orders, 'links') && $orders->hasPages())
                    <div class="mt-3 d-flex justify-content-center">
                        {{ $orders->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
@endsection
