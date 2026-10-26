<?php

namespace App\Providers;

use App\Models\Product;
use App\Models\ReturnRequest;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFour();

        View::composer('layouts.admin', function ($view): void {
            $pendingOrderCount = Schema::hasTable('don_hangs') && Schema::hasColumn('don_hangs', 'trang_thai')
                ? DB::table('don_hangs')->where('trang_thai', 0)->count()
                : 0;
            $lowStockVariantCount = Schema::hasTable('bien_thes') && Schema::hasColumn('bien_thes', 'so_luong_ton')
                ? DB::table('bien_thes')->where('so_luong_ton', '<=', 10)->count()
                : 0;
            $pendingReturnCount = Schema::hasTable('yeu_cau_doi_tras')
                ? ReturnRequest::where('trang_thai', ReturnRequest::STATUS_PENDING)->count()
                : 0;

            $view->with([
                'productCount' => Product::count(),
                'pendingOrderCount' => $pendingOrderCount,
                'lowStockVariantCount' => $lowStockVariantCount,
                'pendingReturnCount' => $pendingReturnCount,
                'notificationCount' => $pendingOrderCount + $lowStockVariantCount + $pendingReturnCount,
            ]);
        });
    }
}
