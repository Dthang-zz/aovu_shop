<?php

namespace App\Providers;

use App\Models\Product;
use App\Models\ReturnRequest;
use App\Models\TaiKhoan;
use App\Models\YeuThich;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
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

        View::composer(['layouts.storefront', 'storefront.*'], function ($view): void {
            $currentCustomer = null;
            $customerId = Session::get('customer_id');
            if ($customerId && Schema::hasTable('tai_khoans')) {
                $currentCustomer = TaiKhoan::query()->find($customerId);
            }

            $cart = Session::get('storefront_cart', []);
            $cartCount = is_array($cart) ? array_sum(array_column($cart, 'quantity')) : 0;

            $wishlistProductIds = [];
            if ($customerId && Schema::hasTable('yeu_thichs')) {
                $wishlistProductIds = YeuThich::query()
                    ->where('tai_khoan_id', $customerId)
                    ->pluck('san_pham_id')
                    ->map(fn ($id) => (int) $id)
                    ->all();
            } else {
                $guestWishlist = Session::get('storefront_wishlist', []);
                $wishlistProductIds = is_array($guestWishlist) ? array_map('intval', $guestWishlist) : [];
            }

            $view->with([
                'currentCustomer' => $currentCustomer,
                'cartCount' => $cartCount,
                'wishlistCount' => count($wishlistProductIds),
                'favoritedProductIds' => $wishlistProductIds,
            ]);
        });
    }
}
