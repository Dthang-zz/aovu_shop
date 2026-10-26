@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
@php
$orderStatuses = [
0 => ['Chờ xác nhận', 'warning'],
1 => ['Đang xử lý', 'primary'],
2 => ['Đang giao', 'info'],
3 => ['Hoàn thành', 'success'],
4 => ['Đã hủy', 'secondary'],
];
$periodLabel = $period === 'custom'
    ? $periodStart->format('d/m/Y').' – '.$periodEnd->format('d/m/Y')
    : ($period === '12m' ? '12 tháng gần nhất' : ($period === '30d' ? '30 ngày gần nhất' : '7 ngày gần nhất'));
@endphp
<div class="card">
    <div class="card-body">
        <div>
            <h3 class="mb-1">Tổng quan kinh doanh</h3>
            <span class="text-muted small">Dữ liệu từ {{ $periodStart->format('d/m/Y') }} đến {{ $periodEnd->format('d/m/Y') }}</span>
        </div>
        <div class="btn-group btn-group-sm mt-3" role="group" aria-label="Khoảng thời gian nhanh">
            <a href="{{ route('admin.dashboard', ['period' => '7d']) }}" class="btn {{ $period === '7d' ? 'btn-primary' : 'btn-default' }}">7 ngày</a>
            <a href="{{ route('admin.dashboard', ['period' => '30d']) }}" class="btn {{ $period === '30d' ? 'btn-primary' : 'btn-default' }}">30 ngày</a>
            <a href="{{ route('admin.dashboard', ['period' => '12m']) }}" class="btn {{ $period === '12m' ? 'btn-primary' : 'btn-default' }}">12 tháng</a>
        </div>
        <form method="GET" action="{{ route('admin.dashboard') }}" class="form-row align-items-end mt-3" aria-label="Chọn khoảng ngày tùy chỉnh">
            <input type="hidden" name="period" value="custom">
            <div class="form-group col-md-4">
                <label for="start_date">Từ ngày</label>
                <input type="date" id="start_date" name="start_date" value="{{ old('start_date', $period === 'custom' ? $startDate : '') }}" class="form-control @error('start_date') is-invalid @enderror" required>
                @error('start_date')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-group col-md-4">
                <label for="end_date">Đến ngày</label>
                <input type="date" id="end_date" name="end_date" value="{{ old('end_date', $period === 'custom' ? $endDate : '') }}" class="form-control @error('end_date') is-invalid @enderror" required>
                @error('end_date')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-group col-md-4">
                <button type="submit" class="btn btn-primary"><i class="fas fa-filter mr-1"></i>Áp dụng khoảng ngày</button>
            </div>
        </form>
    </div>
</div>
<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-white">
            <div class="inner">
                <p class="text-muted mb-1">Doanh thu</p>
                <h3>₫ {{ number_format($revenueTotal, 0, ',', '.') }}</h3>
                <span class="text-muted small"><i class="fas fa-check-circle text-success"></i> {{ $periodLabel }} · Hôm nay ₫ {{ number_format($todayRevenue, 0, ',', '.') }}</span>
            </div>
            <div class="icon text-primary"><i class="fas fa-wallet"></i></div>
            <a href="#revenueChart" class="small-box-footer text-primary">Xem biểu đồ <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-white">
            <div class="inner">
                <p class="text-muted mb-1">Tổng đơn hàng</p>
                <h3>{{ number_format($ordersInPeriod) }}</h3>
                <span class="text-muted small">{{ $periodLabel }}</span>
            </div>
            <div class="icon text-info"><i class="fas fa-shopping-bag"></i></div>
            <a href="{{ route('admin.orders.index') }}" class="small-box-footer text-primary">Xem đơn hàng <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-white">
            <div class="inner">
                <p class="text-muted mb-1">Khách hàng</p>
                <h3>{{ number_format($customersInPeriod) }}</h3>
                <span class="text-muted small">Đã phát sinh đơn trong kỳ</span>
            </div>
            <div class="icon text-success"><i class="fas fa-users"></i></div>
            <a href="{{ route('admin.users.index') }}" class="small-box-footer text-primary">Xem khách hàng <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-white">
            <div class="inner">
                <p class="text-muted mb-1">Sản phẩm đã bán</p>
                <h3>{{ number_format($productsSold) }}</h3>
                <span class="text-muted small">{{ number_format($productCount) }} sản phẩm đang kinh doanh</span>
            </div>
            <div class="icon text-warning"><i class="fas fa-box-open"></i></div>
            <a href="{{ route('admin.products.index') }}" class="small-box-footer text-primary">Xem sản phẩm <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
</div>

<div class="row">
    @foreach ($statusCounts as $statusCount)
    <div class="col-md col-6">
        <div class="info-box bg-white">
            <span class="info-box-icon"><i class="fas fa-circle text-{{ $statusCount['badge'] }}"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">{{ $statusCount['label'] }}</span>
                <span class="info-box-number">{{ number_format($statusCount['count']) }}</span>
            </div>
        </div>
    </div>
    @endforeach
</div>

<div class="row">
    <section class="col-lg-8">
        <div class="card">
            <div class="card-header border-0">
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <div>
                        <h3 class="card-title"><i class="fas fa-chart-line text-primary mr-2"></i>Doanh thu</h3>
                        <div class="text-muted small mt-1">Doanh thu đã thanh toán theo ngày/tháng</div>
                    </div>
                    <span class="badge badge-light">{{ $periodLabel }}</span>
                </div>
            </div>
            <div class="card-body pt-0">
                <div class="d-flex align-items-baseline mb-2">
                    <h2 class="mb-0 mr-2">₫ {{ number_format($revenueTotal, 0, ',', '.') }}</h2>
                    <span class="text-muted small">{{ $periodLabel }}</span>
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
                        @php($status = $orderStatuses[$order->trang_thai] ?? ['Không xác định', 'secondary'])
                        <tr>
                            <td class="font-weight-bold">{{ $order->ma_don_hang }}</td>
                            <td>{{ $order->ten_nguoi_nhan ?: 'Khách hàng' }}</td>
                            <td>{{ $order->ngay_dat ? \Illuminate\Support\Carbon::parse($order->ngay_dat)->format('H:i, d/m/Y') : '—' }}</td>
                            <td>₫ {{ number_format((float) $order->tong_tien, 0, ',', '.') }}</td>
                            <td><span class="badge badge-{{ $status[1] }} px-2 py-1">{{ $status[0] }}</span></td>
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

<div class="row">
    <section class="col-lg-6">
        <div class="card">
            <div class="card-header border-0 d-flex justify-content-between align-items-center">
                <h3 class="card-title"><i class="fas fa-user-plus text-success mr-2"></i>Khách hàng mới</h3>
            </div>
            <div class="card-body p-0">
                <ul class="products-list product-list-in-card pl-2 pr-2">
                    @forelse ($newCustomerList as $customer)
                    <li class="item">
                        <div class="product-img"><span class="img-size-50 d-flex align-items-center justify-content-center bg-light text-primary"><i class="fas fa-user"></i></span></div>
                        <div class="product-info">
                            <span class="product-title">{{ $customer->ho_ten ?: 'Khách hàng' }}</span>
                            <span class="product-description">{{ $customer->email ?: 'Chưa có email' }} · {{ $customer->ngay_tao ? \Illuminate\Support\Carbon::parse($customer->ngay_tao)->format('d/m/Y') : '—' }}</span>
                        </div>
                    </li>
                    @empty
                    <li class="item text-muted py-3">Chưa có khách hàng mới trong kỳ này.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </section>
    <section class="col-lg-6">
        <div class="card">
            <div class="card-header border-0 d-flex justify-content-between align-items-center">
                <h3 class="card-title"><i class="fas fa-clock text-warning mr-2"></i>Đơn chờ xác nhận</h3>
                <span class="badge badge-warning">{{ number_format($pendingOrders) }} đơn</span>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover text-nowrap mb-0">
                    <thead><tr><th>Mã đơn</th><th>Khách hàng</th><th>Ngày đặt</th><th>Tổng tiền</th></tr></thead>
                    <tbody>
                        @forelse ($pendingOrderList as $order)
                        <tr>
                            <td class="font-weight-bold">{{ $order->ma_don_hang }}</td>
                            <td>{{ $order->ten_nguoi_nhan ?: 'Khách hàng' }}</td>
                            <td>{{ $order->ngay_dat ? \Illuminate\Support\Carbon::parse($order->ngay_dat)->format('d/m/Y') : '—' }}</td>
                            <td>₫ {{ number_format((float) $order->tong_tien, 0, ',', '.') }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center text-muted py-3">Không có đơn chờ xác nhận.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>

<div class="row">
    <section class="col-lg-12">
        <div class="card">
            <div class="card-header border-0"><h3 class="card-title"><i class="fas fa-exchange-alt text-warning mr-2"></i>Thống kê đổi/trả</h3></div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-3"><strong class="d-block">{{ number_format($returnStats['total']) }}</strong><small class="text-muted">Tổng</small></div>
                    <div class="col-3"><strong class="d-block text-warning">{{ number_format($returnStats['pending']) }}</strong><small class="text-muted">Chờ xử lý</small></div>
                    <div class="col-3"><strong class="d-block">{{ number_format($returnStats['exchanges']) }}</strong><small class="text-muted">Đổi hàng</small></div>
                    <div class="col-3"><strong class="d-block">{{ number_format($returnStats['returns']) }}</strong><small class="text-muted">Trả hàng</small></div>
                </div>
                <a href="{{ route('admin.returns.index') }}" class="small d-inline-block mt-3">Quản lý yêu cầu <i class="fas fa-arrow-right ml-1"></i></a>
            </div>
        </div>
    </section>
</div>

<div class="row">
    <section class="col-lg-6">
        <div class="card">
            <div class="card-header border-0"><h3 class="card-title"><i class="fas fa-tags text-primary mr-2"></i>Doanh thu theo danh mục</h3></div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Danh mục</th><th class="text-right">Doanh thu</th></tr></thead>
                    <tbody>
                        @forelse ($categoryRevenue as $category)
                        <tr><td>{{ $category->category_name }}</td><td class="text-right">₫ {{ number_format((float) $category->revenue, 0, ',', '.') }}</td></tr>
                        @empty
                        <tr><td colspan="2" class="text-center text-muted py-3">Chưa có dữ liệu doanh thu theo danh mục.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
    <section class="col-lg-6">
        <div class="card">
            <div class="card-header border-0"><h3 class="card-title"><i class="fas fa-chart-line text-secondary mr-2"></i>Sản phẩm bán chậm</h3></div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Sản phẩm</th><th class="text-right">Đã bán trong kỳ</th></tr></thead>
                    <tbody>
                        @forelse ($slowProducts as $product)
                        <tr><td>{{ $product->ten_san_pham }}</td><td class="text-right">{{ number_format((int) $product->quantity_sold) }}</td></tr>
                        @empty
                        <tr><td colspan="2" class="text-center text-muted py-3">Chưa có dữ liệu sản phẩm.</td></tr>
                        @endforelse
                    </tbody>
                </table>
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