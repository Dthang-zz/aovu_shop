<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\NewsArticleController;
use App\Http\Controllers\Admin\NewsCategoryController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\PromotionController;
use App\Http\Controllers\Admin\ReturnRequestController;
use App\Http\Controllers\Admin\RefundWalletController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\SupportChatController as AdminSupportChatController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\VariantController;
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
    Route::get('/customers', [AdminSupportChatController::class, 'customers'])->name('customers');
    Route::get('/customers/{customer}/orders', [AdminSupportChatController::class, 'orders'])
        ->whereNumber('customer')
        ->name('customers.orders');
    Route::get('/orders/{order}/items', [AdminSupportChatController::class, 'orderItems'])
        ->whereNumber('order')
        ->name('orders.items');
    Route::post('/conversations/{conversation}/return-requests', [ReturnRequestController::class, 'storeFromConversation'])
        ->whereNumber('conversation')
        ->middleware('throttle:20,1')
        ->name('return-requests.store');
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

// Quản lý thuộc tính & biến thể (Dùng Controller và Database thật)
// Quản lý Thuộc tính & Biến thể (Đầy đủ CRUD)
Route::get('/admin/variants', [VariantController::class, 'index'])->name('admin.variants.index');
Route::get('/admin/variants/create', [VariantController::class, 'create'])->name('admin.variants.create');
Route::post('/admin/variants', [VariantController::class, 'store'])->name('admin.variants.store');
Route::get('/admin/variants/{variant}', [VariantController::class, 'show'])->name('admin.variants.show');
Route::get('/admin/variants/{variant}/edit', [VariantController::class, 'edit'])->name('admin.variants.edit');
Route::put('/admin/variants/{variant}', [VariantController::class, 'update'])->name('admin.variants.update');
Route::delete('/admin/variants/{variant}', [VariantController::class, 'destroy'])->name('admin.variants.destroy');

// Quản lý đơn hàng
Route::get('/admin/orders', [OrderController::class, 'index'])->name('admin.orders.index');
Route::get('/admin/orders/{order}', [OrderController::class, 'show'])
    ->whereNumber('order')
    ->name('admin.orders.show');
Route::put('/admin/orders/{order}', [OrderController::class, 'update'])
    ->whereNumber('order')
    ->name('admin.orders.update');

// Quản lý người dùng
Route::resource('/admin/users', UserController::class)
    ->names('admin.users');
Route::patch('/admin/users/{user}/toggle-status', [UserController::class, 'toggleStatus'])
    ->name('admin.users.toggle-status');

Route::resource('/admin/promotions', PromotionController::class)
    ->names('admin.promotions');

// Quản lý đổi trả
Route::get('/admin/returns', [ReturnRequestController::class, 'index'])
    ->name('admin.returns.index');
Route::get('/admin/returns/create', [ReturnRequestController::class, 'create'])
    ->name('admin.returns.create');
Route::post('/admin/returns', [ReturnRequestController::class, 'store'])
    ->middleware('throttle:20,1')
    ->name('admin.returns.store');
Route::get('/admin/returns/{returnRequest}', [ReturnRequestController::class, 'show'])
    ->whereNumber('returnRequest')
    ->name('admin.returns.show');
Route::put('/admin/returns/{returnRequest}', [ReturnRequestController::class, 'update'])
    ->whereNumber('returnRequest')
    ->name('admin.returns.update');

Route::get('/admin/finance/refunds', [RefundWalletController::class, 'index'])
    ->name('admin.finance.refunds.index');

Route::get('/admin/reviews', [ReviewController::class, 'index'])->name('admin.reviews.index');
Route::get('/admin/reviews/{review}', [ReviewController::class, 'show'])
    ->whereNumber('review')
    ->name('admin.reviews.show');
Route::put('/admin/reviews/{review}', [ReviewController::class, 'update'])
    ->whereNumber('review')
    ->name('admin.reviews.update');

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
