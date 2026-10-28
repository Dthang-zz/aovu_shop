<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ReturnRequest;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View|RedirectResponse
    {
        $validator = Validator::make($request->query(), [
            'start_date' => ['nullable', 'required_with:end_date', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'required_with:start_date', 'date_format:Y-m-d', 'after_or_equal:start_date'],
        ], [
            'start_date.required_with' => 'Vui lòng chọn ngày bắt đầu.',
            'start_date.date_format' => 'Ngày bắt đầu không đúng định dạng.',
            'end_date.required_with' => 'Vui lòng chọn ngày kết thúc.',
            'end_date.date_format' => 'Ngày kết thúc không đúng định dạng.',
            'end_date.after_or_equal' => 'Ngày kết thúc phải bằng hoặc sau ngày bắt đầu.',
        ]);
        if ($validator->fails()) {
            return redirect()->route('admin.dashboard')
                ->withErrors($validator)
                ->withInput($request->query());
        }
        $validated = $validator->validated();
        $hasCustomRange = isset($validated['start_date'], $validated['end_date']);
        $period = $request->query('period', '7d');
        if ($hasCustomRange) {
            $period = 'custom';
        } elseif (! in_array($period, ['7d', '30d', '12m'], true)) {
            $period = '7d';
        }

        $today = now()->startOfDay();
        $start = $hasCustomRange
            ? Carbon::createFromFormat('!Y-m-d', $validated['start_date'])
            : match ($period) {
                '30d' => $today->copy()->subDays(29),
                '12m' => $today->copy()->startOfMonth()->subMonths(11),
                default => $today->copy()->subDays(6),
            };
        $periodEnd = $hasCustomRange
            ? Carbon::createFromFormat('!Y-m-d', $validated['end_date'])->endOfDay()
            : $today->copy()->endOfDay();
        $ordersAvailable = Schema::hasTable('don_hangs');
        $accountsAvailable = Schema::hasTable('tai_khoans');
        $orderItemsAvailable = Schema::hasTable('chi_tiet_don_hangs');
        $productsAvailable = Schema::hasTable('san_phams');
        $variantsAvailable = Schema::hasTable('bien_thes');
        $periodOrders = fn (): Builder => DB::table('don_hangs')->whereBetween('ngay_dat', [$start, $periodEnd]);
        $todayRevenue = $ordersAvailable
            ? (float) DB::table('don_hangs')
                ->where('trang_thai_thanh_toan', Order::PAYMENT_PAID)
                ->whereDate('ngay_dat', $today)
                ->sum('tong_tien')
            : 0.0;
        $ordersToday = $ordersAvailable
            ? DB::table('don_hangs')->whereDate('ngay_dat', $today)->count()
            : 0;

        $revenueTotal = $ordersAvailable
            ? (float) $periodOrders()
                ->where('trang_thai_thanh_toan', Order::PAYMENT_PAID)
                ->sum('tong_tien')
            : 0.0;
        $ordersInPeriod = $ordersAvailable ? $periodOrders()->count() : 0;
        $pendingOrders = $ordersAvailable
            ? DB::table('don_hangs')->where('trang_thai', Order::STATUS_PENDING)->count()
            : 0;

        $customersInPeriod = $ordersAvailable && Schema::hasColumn('don_hangs', 'tai_khoan_id')
            ? $periodOrders()
                ->whereNotNull('tai_khoan_id')
                ->where(fn (Builder $query) => $query->whereNull('trang_thai')->orWhere('trang_thai', '!=', Order::STATUS_CANCELLED))
                ->distinct()
                ->count('tai_khoan_id')
            : 0;
        $newCustomersQuery = $accountsAvailable
            ? DB::table('tai_khoans')->whereBetween('ngay_tao', [$start, $periodEnd])
            : null;
        $newCustomers = $newCustomersQuery?->count() ?? 0;
        $newCustomerList = $newCustomersQuery
            ? (clone $newCustomersQuery)->select(['id', 'ho_ten', 'email', 'ngay_tao'])->orderByDesc('ngay_tao')->limit(5)->get()
            : collect();

        $chartRows = $ordersAvailable
            ? DB::table('don_hangs')
                ->selectRaw('DATE(ngay_dat) as revenue_date, SUM(COALESCE(tong_tien, 0)) as revenue')
                ->where('trang_thai_thanh_toan', Order::PAYMENT_PAID)
                ->whereBetween('ngay_dat', [$start, $periodEnd])
                ->groupBy('revenue_date')
                ->get()
            : collect();
        $revenueByBucket = [];
        foreach ($chartRows as $row) {
            $bucket = $period === '12m'
                ? Carbon::parse($row->revenue_date)->format('Y-m')
                : $row->revenue_date;
            $revenueByBucket[$bucket] = ($revenueByBucket[$bucket] ?? 0) + (float) $row->revenue;
        }

        $chartLabels = [];
        $chartValues = [];
        $cursor = $start->copy();
        $chartEnd = $periodEnd->copy()->startOfDay();
        $bucketCount = $period === '12m' ? 12 : (int) $start->diffInDays($chartEnd) + 1;
        for ($index = 0; $index < $bucketCount; $index++) {
            $bucket = $period === '12m' ? $cursor->copy()->startOfMonth() : $cursor->copy();
            $key = $period === '12m' ? $bucket->format('Y-m') : $bucket->toDateString();
            $chartLabels[] = $period === '12m' ? $bucket->format('m/Y') : $bucket->format('d/m');
            $chartValues[] = $revenueByBucket[$key] ?? 0;

            if ($period === '12m') {
                $cursor->addMonth();
            } else {
                $cursor->addDay();
            }
        }

        $statusCounts = [];
        if ($ordersAvailable) {
            foreach (Order::statuses() as $status => $label) {
                $statusCounts[] = [
                    'status' => $status,
                    'label' => $label,
                    'badge' => Order::statusBadges()[$status] ?? 'secondary',
                    'count' => $periodOrders()->where('trang_thai', $status)->count(),
                ];
            }
        }

        $latestOrders = $ordersAvailable
            ? (clone $periodOrders())
                ->select(['ma_don_hang', 'ten_nguoi_nhan', 'ngay_dat', 'tong_tien', 'trang_thai'])
                ->orderByDesc('ngay_dat')
                ->orderByDesc('id')
                ->limit(5)
                ->get()
            : collect();
        $pendingOrderList = $ordersAvailable
            ? DB::table('don_hangs')
                ->select(['id', 'ma_don_hang', 'ten_nguoi_nhan', 'ngay_dat', 'tong_tien'])
                ->where('trang_thai', Order::STATUS_PENDING)
                ->orderBy('ngay_dat')
                ->limit(5)
                ->get()
            : collect();

        $productsSold = $orderItemsAvailable && $ordersAvailable
            ? (int) DB::table('chi_tiet_don_hangs as items')
                ->join('don_hangs as orders', 'orders.id', '=', 'items.don_hang_id')
                ->whereBetween('orders.ngay_dat', [$start, $periodEnd])
                ->where(function (Builder $query): void {
                    $query->whereNull('orders.trang_thai')
                        ->orWhere('orders.trang_thai', '!=', Order::STATUS_CANCELLED);
                })
                ->sum('items.so_luong')
            : 0;

        $topSellers = collect();
        $slowProducts = collect();
        if ($orderItemsAvailable && $ordersAvailable && $productsAvailable && $variantsAvailable) {
            $sales = DB::table('chi_tiet_don_hangs as items')
                ->join('don_hangs as orders', 'orders.id', '=', 'items.don_hang_id')
                ->join('bien_thes as variants', 'variants.id', '=', 'items.bien_the_id')
                ->select('variants.san_pham_id')
                ->selectRaw('SUM(COALESCE(items.so_luong, 0)) as quantity_sold')
                ->whereBetween('orders.ngay_dat', [$start, $periodEnd])
                ->where(function (Builder $query): void {
                    $query->whereNull('orders.trang_thai')
                        ->orWhere('orders.trang_thai', '!=', Order::STATUS_CANCELLED);
                })
                ->groupBy('variants.san_pham_id');

            $topSellers = DB::table('chi_tiet_don_hangs as items')
                ->join('don_hangs as orders', 'orders.id', '=', 'items.don_hang_id')
                ->join('bien_thes as variants', 'variants.id', '=', 'items.bien_the_id')
                ->join('san_phams as products', 'products.id', '=', 'variants.san_pham_id')
                ->select([
                    'products.id',
                    'products.ten_san_pham',
                    'products.hinh_anh',
                    'products.gia_san_pham',
                    'products.gia_khuyen_mai',
                ])
                ->selectRaw('SUM(COALESCE(items.so_luong, 0)) as quantity_sold')
                ->selectRaw('(SELECT COALESCE(SUM(stock_variants.so_luong_ton), 0) FROM bien_thes AS stock_variants WHERE stock_variants.san_pham_id = products.id) as stock')
                ->whereBetween('orders.ngay_dat', [$start, $periodEnd])
                ->where(function (Builder $query): void {
                    $query->whereNull('orders.trang_thai')
                        ->orWhere('orders.trang_thai', '!=', Order::STATUS_CANCELLED);
                })
                ->groupBy('products.id', 'products.ten_san_pham', 'products.hinh_anh', 'products.gia_san_pham', 'products.gia_khuyen_mai')
                ->orderByDesc('quantity_sold')
                ->limit(5)
                ->get();

            $slowProducts = DB::table('san_phams as products')
                ->leftJoinSub($sales, 'sales', 'sales.san_pham_id', '=', 'products.id')
                ->select('products.id', 'products.ten_san_pham')
                ->selectRaw('COALESCE(sales.quantity_sold, 0) as quantity_sold')
                ->orderBy('quantity_sold')
                ->orderBy('products.ten_san_pham')
                ->limit(5)
                ->get();
        }

        $productCount = $productsAvailable
            ? DB::table('san_phams')->where('trang_thai', true)->count()
            : 0;

        $categoryRevenue = collect();
        if (
            $orderItemsAvailable
            && $ordersAvailable
            && $productsAvailable
            && $variantsAvailable
            && Schema::hasTable('danh_mucs')
            && Schema::hasColumn('chi_tiet_don_hangs', 'don_gia')
        ) {
            $categoryRevenueExpression = Schema::hasColumn('chi_tiet_don_hangs', 'thanh_tien')
                ? 'COALESCE(items.thanh_tien, COALESCE(items.so_luong, 0) * COALESCE(items.don_gia, 0))'
                : 'COALESCE(items.so_luong, 0) * COALESCE(items.don_gia, 0)';
            $categoryRevenue = DB::table('chi_tiet_don_hangs as items')
                ->join('don_hangs as orders', 'orders.id', '=', 'items.don_hang_id')
                ->join('bien_thes as variants', 'variants.id', '=', 'items.bien_the_id')
                ->join('san_phams as products', 'products.id', '=', 'variants.san_pham_id')
                ->leftJoin('danh_mucs as categories', 'categories.id', '=', 'products.danh_muc_id')
                ->selectRaw("COALESCE(categories.ten_danh_muc, 'Chưa phân loại') as category_name")
                ->selectRaw("SUM({$categoryRevenueExpression}) as revenue")
                ->where('orders.trang_thai_thanh_toan', Order::PAYMENT_PAID)
                ->whereBetween('orders.ngay_dat', [$start, $periodEnd])
                ->where(function (Builder $query): void {
                    $query->whereNull('orders.trang_thai')
                        ->orWhere('orders.trang_thai', '!=', Order::STATUS_CANCELLED);
                })
                ->groupBy('categories.ten_danh_muc')
                ->orderByDesc('revenue')
                ->limit(5)
                ->get();
        }

        $returnStats = [
            'total' => 0,
            'pending' => 0,
            'exchanges' => 0,
            'returns' => 0,
        ];
        if (Schema::hasTable('yeu_cau_doi_tras')) {
            $returnsQuery = DB::table('yeu_cau_doi_tras')->whereBetween('ngay_tao', [$start, $periodEnd]);
            $returnStats = [
                'total' => (clone $returnsQuery)->count(),
                'pending' => (clone $returnsQuery)->where('trang_thai', ReturnRequest::STATUS_PENDING)->count(),
                'exchanges' => (clone $returnsQuery)->where('loai_yeu_cau', ReturnRequest::TYPE_EXCHANGE)->count(),
                'returns' => (clone $returnsQuery)->where('loai_yeu_cau', ReturnRequest::TYPE_RETURN)->count(),
            ];
        }
        $pendingReturns = Schema::hasTable('yeu_cau_doi_tras')
            ? DB::table('yeu_cau_doi_tras')->where('trang_thai', ReturnRequest::STATUS_PENDING)->count()
            : 0;

        return view('admin.dashboard', [
            'period' => $period,
            'startDate' => $start->toDateString(),
            'endDate' => $periodEnd->toDateString(),
            'periodStart' => $start,
            'periodEnd' => $periodEnd,
            'todayRevenue' => $todayRevenue,
            'ordersToday' => $ordersToday,
            'revenueTotal' => $revenueTotal,
            'ordersInPeriod' => $ordersInPeriod,
            'customersInPeriod' => $customersInPeriod,
            'productsSold' => $productsSold,
            'productCount' => $productCount,
            'newCustomers' => $newCustomers,
            'newCustomerList' => $newCustomerList,
            'pendingOrders' => $pendingOrders,
            'pendingOrderList' => $pendingOrderList,
            'statusCounts' => $statusCounts,
            'chartLabels' => $chartLabels,
            'chartValues' => $chartValues,
            'latestOrders' => $latestOrders,
            'topSellers' => $topSellers,
            'slowProducts' => $slowProducts,
            'categoryRevenue' => $categoryRevenue,
            'returnStats' => $returnStats,
            'pendingReturns' => $pendingReturns,
        ]);
    }
}
