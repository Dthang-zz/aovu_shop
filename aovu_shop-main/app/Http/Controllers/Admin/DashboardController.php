<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReturnRequest;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    private const PENDING_ORDER_STATUS = 0;

    private const CANCELLED_ORDER_STATUS = 4;

    private const PAID_STATUS = 1;

    private const LOW_STOCK_THRESHOLD = 10;

    public function __invoke(Request $request): View
    {
        $period = $request->query('period', '7d');
        if (! in_array($period, ['7d', '30d', '12m'], true)) {
            $period = '7d';
        }

        $today = now()->startOfDay();
        $start = match ($period) {
            '30d' => $today->copy()->subDays(29),
            '12m' => $today->copy()->startOfMonth()->subMonths(11),
            default => $today->copy()->subDays(6),
        };

        $ordersAvailable = Schema::hasTable('don_hangs');
        $paidOrdersToday = $ordersAvailable
            ? DB::table('don_hangs')->where('trang_thai_thanh_toan', self::PAID_STATUS)
                ->whereDate('ngay_dat', $today)->sum('tong_tien')
            : 0;
        $ordersToday = $ordersAvailable
            ? DB::table('don_hangs')->whereDate('ngay_dat', $today)->count()
            : 0;
        $pendingOrders = $ordersAvailable
            ? DB::table('don_hangs')->where('trang_thai', self::PENDING_ORDER_STATUS)->count()
            : 0;

        $periodEnd = $today->copy()->endOfDay();
        $revenueRows = $ordersAvailable
            ? DB::table('don_hangs')
                ->selectRaw('DATE(ngay_dat) as revenue_date, SUM(COALESCE(tong_tien, 0)) as revenue')
                ->where('trang_thai_thanh_toan', self::PAID_STATUS)
                ->whereBetween('ngay_dat', [$start, $periodEnd])
                ->groupBy('revenue_date')
                ->get()
            : collect();

        $chartLabels = [];
        $chartValues = [];
        $revenueTotal = 0;
        $revenueByBucket = [];
        foreach ($revenueRows as $row) {
            $key = $period === '12m'
                ? Carbon::parse($row->revenue_date)->format('Y-m')
                : $row->revenue_date;
            $revenueByBucket[$key] = ($revenueByBucket[$key] ?? 0) + (float) $row->revenue;
        }
        $cursor = $start->copy();
        $bucketCount = $period === '12m' ? 12 : (int) $start->diffInDays($today) + 1;

        for ($index = 0; $index < $bucketCount; $index++) {
            $bucket = $period === '12m' ? $cursor->copy()->startOfMonth() : $cursor->copy();
            $key = $period === '12m' ? $bucket->format('Y-m') : $bucket->toDateString();
            $revenue = $revenueByBucket[$key] ?? 0;

            if ($period === '12m') {
                $chartLabels[] = $bucket->format('m/Y');
                $cursor->addMonth();
            } else {
                $chartLabels[] = $bucket->format('d/m');
                $cursor->addDay();
            }

            $chartValues[] = $revenue;
            $revenueTotal += $revenue;
        }

        $latestOrders = $ordersAvailable
            ? DB::table('don_hangs')
                ->select(['ma_don_hang', 'ten_nguoi_nhan', 'ngay_dat', 'tong_tien', 'trang_thai'])
                ->orderByDesc('ngay_dat')
                ->orderByDesc('id')
                ->limit(5)
                ->get()
            : collect();

        $topSellers = collect();
        if (Schema::hasTable('chi_tiet_don_hangs') && Schema::hasTable('bien_thes') && Schema::hasTable('san_phams')) {
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
                ->where(function ($query) {
                    $query->whereNull('orders.trang_thai')
                        ->orWhere('orders.trang_thai', '!=', self::CANCELLED_ORDER_STATUS);
                })
                ->groupBy('products.id', 'products.ten_san_pham', 'products.hinh_anh', 'products.gia_san_pham', 'products.gia_khuyen_mai')
                ->orderByDesc('quantity_sold')
                ->limit(5)
                ->get();
        }

        $lowStockVariants = collect();
        if (Schema::hasTable('bien_thes') && Schema::hasTable('san_phams')) {
            $lowStockVariants = DB::table('bien_thes as variants')
                ->join('san_phams as products', 'products.id', '=', 'variants.san_pham_id')
                ->select([
                    'products.ten_san_pham',
                    'variants.sku',
                    'variants.so_luong_ton',
                ])
                ->where('variants.so_luong_ton', '<=', self::LOW_STOCK_THRESHOLD)
                ->orderBy('variants.so_luong_ton')
                ->orderBy('products.ten_san_pham')
                ->limit(5)
                ->get();
        }

        $newCustomers = Schema::hasTable('tai_khoans')
            ? DB::table('tai_khoans')->whereBetween('ngay_tao', [$today->copy()->startOfMonth(), $periodEnd])->count()
            : 0;
        $pendingReturns = Schema::hasTable('yeu_cau_doi_tras')
            ? ReturnRequest::where('trang_thai', ReturnRequest::STATUS_PENDING)->count()
            : 0;

        return view('admin.dashboard', [
            'period' => $period,
            'todayRevenue' => (float) $paidOrdersToday,
            'ordersToday' => $ordersToday,
            'pendingOrders' => $pendingOrders,
            'newCustomers' => $newCustomers,
            'revenueTotal' => $revenueTotal,
            'chartLabels' => $chartLabels,
            'chartValues' => $chartValues,
            'latestOrders' => $latestOrders,
            'topSellers' => $topSellers,
            'lowStockVariants' => $lowStockVariants,
            'pendingReturns' => $pendingReturns,
        ]);
    }
}
