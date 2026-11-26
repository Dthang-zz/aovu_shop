<?php

use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\NewsArticleController;
use App\Http\Controllers\Admin\NewsCategoryController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\PromotionController;
use App\Http\Controllers\Admin\RefundWalletController;
use App\Http\Controllers\Admin\ReturnRequestController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\SupportChatController as AdminSupportChatController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\VoucherController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\StorefrontController;
use App\Http\Controllers\SupportChatController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'loginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1')->name('login.store');
    Route::get('/register', [AuthController::class, 'registerForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1')->name('register.store');
});
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');
Route::middleware('auth')->group(function () {
    Route::get('/account', [AuthController::class, 'profile'])->name('account.profile');
    Route::put('/account', [AuthController::class, 'updateProfile'])->name('account.profile.update');
    Route::put('/account/password', [AuthController::class, 'updatePassword'])->name('account.password.update');
});

Route::redirect('/', '/shop');


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
Route::post('/checkout/voucher', [StorefrontController::class, 'applyVoucher'])
    ->middleware('throttle:20,1')
    ->name('shop.checkout.voucher.apply');
Route::post('/checkout/voucher/remove', [StorefrontController::class, 'removeVoucher'])
    ->middleware('throttle:20,1')
    ->name('shop.checkout.voucher.remove');
Route::post('/checkout', [StorefrontController::class, 'placeOrder'])->name('shop.checkout.store');
Route::get('/checkout/success', [StorefrontController::class, 'orderConfirmation'])->name('shop.checkout.success');

Route::get('/support-chat', [SupportChatController::class, 'index'])
    ->middleware('throttle:60,1')
    ->name('support-chat.index');
Route::post('/support-chat/messages', [SupportChatController::class, 'store'])
    ->middleware('throttle:30,1')
    ->name('support-chat.store');

Route::middleware(['auth', 'role:admin'])->group(function () {
Route::get('/admin', DashboardController::class)->name('admin.dashboard');
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

// Quản lý đơn hàng
Route::get('/admin/orders', [OrderController::class, 'index'])->name('admin.orders.index');
Route::get('/admin/orders/{order}', [OrderController::class, 'show'])
    ->whereNumber('order')
    ->name('admin.orders.show');
Route::get('/admin/orders/{order}/edit', [OrderController::class, 'edit'])
    ->whereNumber('order')
    ->name('admin.orders.edit');
Route::put('/admin/orders/{order}', [OrderController::class, 'update'])
    ->whereNumber('order')
    ->name('admin.orders.update');

// Quản lý người dùng
Route::resource('/admin/users', UserController::class)->names('admin.users');
Route::patch('/admin/users/{user}/toggle-status', [UserController::class, 'toggleStatus'])
    ->whereNumber('user')
    ->name('admin.users.toggle-status');

// Khuyến mãi tự động
Route::resource('/admin/promotions', PromotionController::class)->names('admin.promotions');

// Mã giảm giá (Voucher): khai báo route tĩnh trước resource để tránh xung đột {voucher}.
Route::get('/admin/vouchers/export', [VoucherController::class, 'export'])
    ->name('admin.vouchers.export');
Route::delete('/admin/vouchers/bulk-destroy', [VoucherController::class, 'bulkDestroy'])
    ->name('admin.vouchers.bulk-destroy');
Route::patch('/admin/vouchers/{voucher}/toggle', [VoucherController::class, 'toggleStatus'])
    ->whereNumber('voucher')
    ->name('admin.vouchers.toggle');
Route::resource('/admin/vouchers', VoucherController::class)
    ->except(['show'])
    ->names('admin.vouchers');

Route::resource('/admin/banners', BannerController::class)->names('admin.banners');

// Quản lý đổi trả
Route::get('/admin/returns', [ReturnRequestController::class, 'index'])->name('admin.returns.index');
Route::get('/admin/returns/create', [ReturnRequestController::class, 'create'])->name('admin.returns.create');
Route::post('/admin/returns', [ReturnRequestController::class, 'store'])
    ->middleware('throttle:20,1')
    ->name('admin.returns.store');
Route::get('/admin/returns/{returnRequest}', [ReturnRequestController::class, 'show'])
    ->whereNumber('returnRequest')
    ->name('admin.returns.show');
Route::get('/admin/returns/{returnRequest}/edit', [ReturnRequestController::class, 'edit'])
    ->whereNumber('returnRequest')
    ->name('admin.returns.edit');
Route::put('/admin/returns/{returnRequest}', [ReturnRequestController::class, 'update'])
    ->whereNumber('returnRequest')
    ->name('admin.returns.update');

Route::get('/admin/finance/refunds', [RefundWalletController::class, 'index'])
    ->name('admin.finance.refunds.index');
Route::post('/admin/finance/refunds/{returnRequest}/confirm-manual-payment', [RefundWalletController::class, 'confirmManualPayment'])
    ->whereNumber('returnRequest')
    ->name('admin.finance.refunds.confirm-manual-payment');

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

}); // Admin routes protected by authentication and Admin role.
