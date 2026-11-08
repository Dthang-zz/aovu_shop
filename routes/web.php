<?php

use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\NewsArticleController;
use App\Http\Controllers\Admin\NewsCategoryController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\OrderReturnController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\PromotionController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\SupportChatController as AdminSupportChatController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\StorefrontController;
use App\Http\Controllers\SupportChatController;
use Illuminate\Support\Facades\Route;

Route::get('/', DashboardController::class);
Route::get('/admin', DashboardController::class)->name('admin.dashboard');

Route::get('/shop', [StorefrontController::class, 'index'])->name('shop.products.index');
Route::get('/shop/{product}', [StorefrontController::class, 'show'])
    ->whereNumber('product')
    ->name('shop.products.show');
Route::get('/tin-tuc', [NewsController::class, 'index'])->name('news.index');
Route::get('/tin-tuc/{slug}', [NewsController::class, 'show'])->name('news.show');
Route::post('/cart/items', [StorefrontController::class, 'addToCart'])->name('shop.cart.items.store');
Route::get('/cart', [StorefrontController::class, 'cart'])->name('shop.cart');
Route::patch('/cart/items/{item}', [StorefrontController::class, 'updateCart'])->name('shop.cart.items.update');
Route::delete('/cart/items/{item}', [StorefrontController::class, 'removeFromCart'])->name('shop.cart.items.destroy');
Route::get('/checkout', [StorefrontController::class, 'checkout'])->name('shop.checkout');
Route::post('/checkout', [StorefrontController::class, 'placeOrder'])->name('shop.checkout.store');
Route::get('/checkout/success', [StorefrontController::class, 'orderConfirmation'])->name('shop.checkout.success');

Route::get('/support-chat', [SupportChatController::class, 'index'])
    ->middleware('throttle:60,1')
    ->name('support-chat.index');
Route::post('/support-chat/messages', [SupportChatController::class, 'store'])
    ->middleware('throttle:30,1')
    ->name('support-chat.store');

Route::prefix('/admin/support-chat')->name('admin.support-chat.')->group(function () {
    Route::get('/', [AdminSupportChatController::class, 'index'])->name('index');
    Route::get('/conversations/{conversation}/messages', [AdminSupportChatController::class, 'messages'])
        ->whereNumber('conversation')
        ->middleware('throttle:60,1')
        ->name('messages');
    Route::post('/conversations/{conversation}/messages', [AdminSupportChatController::class, 'storeMessage'])
        ->whereNumber('conversation')
        ->middleware('throttle:30,1')
        ->name('messages.store');
    Route::patch('/conversations/{conversation}/status', [AdminSupportChatController::class, 'updateStatus'])
        ->whereNumber('conversation')
        ->middleware('throttle:30,1')
        ->name('status.update');
});

// Quản lý danh mục
Route::resource('/admin/categories', CategoryController::class)
    ->names([
        'index' => 'admin.categories.index',
        'create' => 'admin.categories.create',
        'show' => 'admin.categories.show',
        'store' => 'admin.categories.store',
        'edit' => 'admin.categories.edit',
        'update' => 'admin.categories.update',
        'destroy' => 'admin.categories.destroy',
    ]);

// Quản lý sản phẩm
Route::resource('/admin/products', ProductController::class)
    ->names([
        'index' => 'admin.products.index',
        'create' => 'admin.products.create',
        'show' => 'admin.products.show',
        'store' => 'admin.products.store',
        'edit' => 'admin.products.edit',
        'update' => 'admin.products.update',
        'destroy' => 'admin.products.destroy',
    ]);

// Quản lý đơn hàng
Route::get('/admin/orders', [OrderController::class, 'index'])->name('admin.orders.index');
Route::get('/admin/orders/{order}', [OrderController::class, 'show'])
    ->whereNumber('order')
    ->name('admin.orders.show');
Route::put('/admin/orders/{order}', [OrderController::class, 'update'])
    ->whereNumber('order')
    ->name('admin.orders.update');

// Đổi / trả hàng xử lý ngay trong đơn hàng
Route::post('/admin/orders/{order}/returns', [OrderReturnController::class, 'store'])
    ->whereNumber('order')
    ->middleware('throttle:20,1')
    ->name('admin.orders.returns.store');
Route::patch('/admin/orders/{order}/returns/{returnRequest}', [OrderReturnController::class, 'update'])
    ->whereNumber(['order', 'returnRequest'])
    ->middleware('throttle:30,1')
    ->name('admin.orders.returns.update');

// URL cũ của module Đổi/trả và Ví hoàn tiền (đã gộp vào đơn hàng) chuyển hướng sang đơn hàng
Route::get('/admin/returns', [OrderReturnController::class, 'legacyIndex']);
Route::get('/admin/finance/refunds', [OrderReturnController::class, 'legacyIndex']);
Route::get('/admin/returns/{returnRequest}/{edit?}', [OrderReturnController::class, 'legacyShow'])
    ->whereNumber('returnRequest')
    ->whereIn('edit', ['edit']);

// Quản lý người dùng
Route::resource('/admin/users', UserController::class)
    ->names('admin.users');
Route::patch('/admin/users/{user}/toggle-status', [UserController::class, 'toggleStatus'])
    ->name('admin.users.toggle-status');

Route::resource('/admin/promotions', PromotionController::class)
    ->names('admin.promotions');

Route::resource('/admin/banners', BannerController::class)
    ->names('admin.banners');

Route::get('/admin/reviews', [ReviewController::class, 'index'])->name('admin.reviews.index');
Route::get('/admin/reviews/{review}', [ReviewController::class, 'show'])
    ->whereNumber('review')
    ->name('admin.reviews.show');
Route::put('/admin/reviews/{review}', [ReviewController::class, 'update'])
    ->whereNumber('review')
    ->name('admin.reviews.update');

Route::post('/admin/news/images', [NewsArticleController::class, 'uploadContentImage'])
    ->middleware('throttle:30,1')
    ->name('admin.news.images.store');
Route::get('/admin/news/{article}/preview', [NewsArticleController::class, 'preview'])
    ->whereNumber('article')
    ->name('admin.news.preview');
Route::resource('/admin/news', NewsArticleController::class)
    ->parameters(['news' => 'article'])
    ->only(['index', 'create', 'store', 'edit', 'update', 'destroy'])
    ->names('admin.news');
Route::resource('/admin/news-categories', NewsCategoryController::class)
    ->parameters(['news-categories' => 'newsCategory'])
    ->only(['index', 'create', 'store', 'edit', 'update', 'destroy'])
    ->names('admin.news-categories');
