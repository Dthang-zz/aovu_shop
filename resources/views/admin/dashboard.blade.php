@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
@php
$orderStatuses = \App\OrderStatus::labels();
$orderStatusBadges = \App\OrderStatus::badges();
@endphp
<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-white">
            <div class="inner">
                <p class="text-muted mb-1">Doanh thu hôm nay</p>
                <h3>₫ {{ number_format($todayRevenue, 0, ',', '.') }}</h3>
                <span class="text-muted small"><i class="fas fa-check-circle text-success"></i> Đơn đã thanh toán</span>
            </div>
            <div class="icon text-primary"><i class="fas fa-wallet"></i></div>
            <a href="{{ route('admin.dashboard', ['period' => '7d']) }}" class="small-box-footer text-primary">Chi tiết <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-white">
            <div class="inner">
                <p class="text-muted mb-1">Đơn hàng hôm nay</p>
                <h3>{{ number_format($ordersToday) }}</h3>
                <span class="text-muted small">Đơn được tạo trong ngày</span>
            </div>
            <div class="icon text-info"><i class="fas fa-shopping-bag"></i></div>
            <a href="{{ route('admin.orders.index') }}" class="small-box-footer text-primary">Xem đơn hàng <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-white">
            <div class="inner">
                <p class="text-muted mb-1">Chờ xử lý</p>
                <h3>{{ number_format($pendingOrders) }}</h3>
                <span class="text-warning small"><i class="fas fa-clock"></i> Đơn chờ xác nhận</span>
            </div>
            <div class="icon text-warning"><i class="fas fa-clipboard-list"></i></div>
            <a href="{{ route('admin.orders.index', ['status' => 0]) }}" class="small-box-footer text-primary">Xử lý đơn hàng <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-white">
            <div class="inner">
                <p class="text-muted mb-1">Khách hàng mới</p>
                <h3>{{ number_format($newCustomers) }}</h3>
                <span class="text-muted small">Từ đầu tháng đến nay</span>
            </div>
            <div class="icon text-success"><i class="fas fa-user-plus"></i></div>
            <a href="{{ route('admin.resource', ['section' => 'users']) }}" class="small-box-footer text-primary">Xem khách hàng <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
</div>

<div class="row">
    <section class="col-lg-8">
        <div class="card">
            <div class="card-header border-0">
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <div>
                        <h3 class="card-title"><i class="fas fa-chart-line text-primary mr-2"></i>Doanh thu</h3>
                        <div class="text-muted small mt-1"></div>
                    </div>
                    <div class="btn-group btn-group-sm mt-2 mt-md-0" role="group" aria-label="Khoảng thời gian biểu đồ doanh thu">
                        <a href="{{ route('admin.dashboard', ['period' => '7d']) }}" class="btn {{ $period === '7d' ? 'btn-primary' : 'btn-default' }}">7 ngày</a>
                        <a href="{{ route('admin.dashboard', ['period' => '30d']) }}" class="btn {{ $period === '30d' ? 'btn-primary' : 'btn-default' }}">30 ngày</a>
                        <a href="{{ route('admin.dashboard', ['period' => '12m']) }}" class="btn {{ $period === '12m' ? 'btn-primary' : 'btn-default' }}">12 tháng</a>
                    </div>
                </div>
            </div>
            <div class="card-body pt-0">
                <div class="d-flex align-items-baseline mb-2">
                    <h2 class="mb-0 mr-2">₫ {{ number_format($revenueTotal, 0, ',', '.') }}</h2>
                    <span class="text-muted small">{{ $period === '12m' ? '12 tháng gần nhất' : ($period === '30d' ? '30 ngày gần nhất' : '7 ngày gần nhất') }}</span>
                </div>
                <div class="position-relative" style="height: 280px">
                    <canvas id="revenueChart" aria-label="Biểu đồ doanh thu" role="img"></canvas>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header border-0 d-flex justify-content-between align-items-center">
                <h3 class="card-title"><i class="fas fa-shopping-bag text-primary mr-2"></i>Đơn hàng mới</h3>
                <a href="{{ route('admin.orders.index') }}" class="btn btn-sm btn-outline-primary">Tất cả đơn hàng</a>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover text-nowrap mb-0">
                    <thead>
                        <tr>
                            <th>Mã đơn</th>
                            <th>Khách hàng</th>
                            <th>Ngày đặt</th>
                            <th>Tổng tiền</th>
                            <th>Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($latestOrders as $order)
                        @php
                            $statusLabel = $orderStatuses[$order->trang_thai] ?? 'Không xác định';
                            $statusBadge = $orderStatusBadges[$order->trang_thai] ?? 'secondary';
                        @endphp
                        <tr>
                            <td class="font-weight-bold">{{ $order->ma_don_hang }}</td>
                            <td>{{ $order->ten_nguoi_nhan ?: 'Khách hàng' }}</td>
                            <td>{{ $order->ngay_dat ? \Illuminate\Support\Carbon::parse($order->ngay_dat)->format('H:i, d/m/Y') : '—' }}</td>
                            <td>₫ {{ number_format((float) $order->tong_tien, 0, ',', '.') }}</td>
                            <td><span class="badge badge-{{ $statusBadge }} px-2 py-1">{{ $statusLabel }}</span></td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">Chưa có đơn hàng.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <section class="col-lg-4">
        <div class="card">
            <div class="card-header border-0 d-flex justify-content-between align-items-center">
                <h3 class="card-title"><i class="fas fa-fire text-warning mr-2"></i>Sản phẩm bán chạy</h3>
                <a href="{{ route('admin.products.index') }}" class="text-primary small">Xem tất cả</a>
            </div>
            <div class="card-body p-0">
                <ul class="products-list product-list-in-card pl-2 pr-2">
                    @forelse ($topSellers as $product)
                    <li class="item">
                        <div class="product-img">
                            @if ($product->hinh_anh)
                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($product->hinh_anh) }}" alt="{{ $product->ten_san_pham }}" class="img-size-50">
                            @else
                            <span class="img-size-50 d-flex align-items-center justify-content-center bg-light text-muted"><i class="fas fa-tshirt"></i></span>
                            @endif
                        </div>
                        <div class="product-info">
                            <a href="{{ route('admin.products.index') }}" class="product-title">{{ $product->ten_san_pham }} <span class="badge badge-primary float-right">{{ number_format($product->quantity_sold) }} đã bán</span></a>
                            <span class="product-description">₫ {{ number_format((float) ($product->gia_khuyen_mai ?: $product->gia_san_pham), 0, ',', '.') }} · Còn {{ number_format((int) $product->stock) }} sản phẩm</span>
                        </div>
                    </li>
                    @empty
                    <li class="item text-muted py-3">Chưa có dữ liệu bán hàng.</li>
                    @endforelse
                </ul>
            </div>
        </div>

        <div class="card">
            <div class="card-header border-0 d-flex justify-content-between align-items-center">
                <h3 class="card-title"><i class="fas fa-exclamation-triangle text-warning mr-2"></i>Cảnh báo tồn kho</h3>
                <a href="{{ route('admin.resource', ['section' => 'inventory']) }}" class="text-primary small">Chi tiết</a>
            </div>
            <div class="card-body pt-0">
                @forelse ($lowStockVariants as $variant)
                <div class="d-flex justify-content-between align-items-center border-bottom py-3">
                    <div>
                        <div class="font-weight-bold">{{ $variant->ten_san_pham }}</div>
                        <small class="text-muted">SKU {{ $variant->sku }}</small>
                    </div>
                    <span class="badge badge-{{ $variant->so_luong_ton <= 0 ? 'danger' : 'warning' }} px-2 py-1">
                        {{ $variant->so_luong_ton <= 0 ? 'Hết hàng' : 'Còn '.number_format((int) $variant->so_luong_ton) }}
                    </span>
                </div>
                @empty
                <div class="text-muted py-3">Không có biến thể nào tồn kho dưới {{ number_format(10) }} sản phẩm.</div>
                @endforelse
            </div>
        </div>

        <div class="info-box bg-white">
            <span class="info-box-icon bg-light"><i class="fas fa-exchange-alt text-warning"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Đổi/trả chờ xử lý</span>
                <span class="info-box-number">{{ number_format($pendingReturns) }} yêu cầu</span>
                <a href="{{ route('admin.returns.index') }}" class="small">Xem danh sách <i class="fas fa-arrow-right ml-1"></i></a>
            </div>
        </div>
    </section>
</div>
@endsection

@section('scripts')
<script src="{{ asset('adminlte/plugins/chart.js/Chart.min.js') }}"></script>
<script>
    (function() {
        var canvas = document.getElementById('revenueChart');
        if (!canvas || typeof Chart === 'undefined') return;

        var context = canvas.getContext('2d');
        var gradient = context.createLinearGradient(0, 0, 0, 280);
        gradient.addColorStop(0, 'rgba(91, 108, 255, .22)');
        gradient.addColorStop(1, 'rgba(91, 108, 255, 0)');
        var revenueValues = @json(array_map(static fn($value) => round($value / 1000000, 2), $chartValues));

        new Chart(context, {
            type: 'line',
            data: {
                labels: @json($chartLabels),
                datasets: [{
                    label: 'Doanh thu (triệu đồng)',
                    data: revenueValues,
                    borderColor: '#5b6cff',
                    backgroundColor: gradient,
                    borderWidth: 2,
                    pointRadius: 3,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#5b6cff',
                    pointBorderWidth: 2,
                    fill: true,
                    lineTension: .35
                }]
            },
            options: {
                maintainAspectRatio: false,
                legend: {
                    display: false
                },
                tooltips: {
                    backgroundColor: '#202939',
                    callbacks: {
                        label: function(tooltipItem) {
                            return ' ₫ ' + Number(tooltipItem.yLabel).toLocaleString('vi-VN') + ' triệu';
                        }
                    }
                },
                scales: {
                    yAxes: [{
                        ticks: {
                            beginAtZero: true,
                            fontColor: '#98a2b3',
                            callback: function(value) {
                                return value + ' tr';
                            }
                        },
                        gridLines: {
                            color: '#f0f2f5',
                            drawBorder: false
                        }
                    }],
                    xAxes: [{
                        ticks: {
                            fontColor: '#98a2b3',
                            autoSkip: true,
                            maxTicksLimit: 12
                        },
                        gridLines: {
                            display: false
                        }
                    }]
                }
            }
        });
    })();
</script>
@endsection