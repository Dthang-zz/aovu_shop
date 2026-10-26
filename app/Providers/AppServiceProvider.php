<?php

namespace App\Providers;

use App\Models\Product;
use App\Models\ReturnRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

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
        View::composer('layouts.admin', function ($view): void {
            $pendingOrderCount = Schema::hasTable('don_hangs') && Schema::hasColumn('don_hangs', 'trang_thai')
                ? DB::table('don_hangs')->where('trang_thai', 0)->count()
                : 0;
            $pendingReturnCount = Schema::hasTable('yeu_cau_doi_tras')
                ? ReturnRequest::where('trang_thai', ReturnRequest::STATUS_PENDING)->count()
                : 0;

            $view->with([
                'productCount' => Product::count(),
                'pendingOrderCount' => $pendingOrderCount,
                'pendingReturnCount' => $pendingReturnCount,
                'notificationCount' => $pendingOrderCount + $pendingReturnCount,
            ]);
        });
    }
}
