<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\PromotionController;
use App\Http\Controllers\Admin\ReturnRequestController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\VariantController;
use App\Http\Controllers\StorefrontController;
use Illuminate\Support\Facades\Route;

Route::get('/', DashboardController::class);
Route::get('/admin', DashboardController::class)->name('admin.dashboard');

Route::get('/shop', [StorefrontController::class, 'index'])->name('shop.products.index');
Route::get('/shop/{product}', [StorefrontController::class, 'show'])
    ->whereNumber('product')
    ->name('shop.products.show');
Route::post('/cart/items', [StorefrontController::class, 'addToCart'])->name('shop.cart.items.store');
Route::get('/cart', [StorefrontController::class, 'cart'])->name('shop.cart');
Route::patch('/cart/items/{item}', [StorefrontController::class, 'updateCart'])->name('shop.cart.items.update');
Route::delete('/cart/items/{item}', [StorefrontController::class, 'removeFromCart'])->name('shop.cart.items.destroy');
Route::get('/checkout', [StorefrontController::class, 'checkout'])->name('shop.checkout');
Route::post('/checkout', [StorefrontController::class, 'placeOrder'])->name('shop.checkout.store');
Route::get('/checkout/success', [StorefrontController::class, 'orderConfirmation'])->name('shop.checkout.success');

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
Route::get('/admin/returns/{returnRequest}', [ReturnRequestController::class, 'show'])
    ->whereNumber('returnRequest')
    ->name('admin.returns.show');
Route::put('/admin/returns/{returnRequest}', [ReturnRequestController::class, 'update'])
    ->whereNumber('returnRequest')
    ->name('admin.returns.update');

// Các trang quản trị dạng tĩnh khác
Route::get('/admin/{section}', function (string $section) {
    $pages = [
        'reviews' => [
            'title' => 'Đánh giá sản phẩm',
            'subtitle' => 'Theo dõi phản hồi và nội dung cần được kiểm duyệt.',
            'icon' => 'fas fa-star',
            'action' => 'Xem đánh giá chờ duyệt',
            'recordLabel' => 'Đánh giá',
            'stats' => [
                ['Tổng đánh giá', '386', 'fas fa-star', 'primary'],
                ['Chờ duyệt', '12', 'fas fa-hourglass-half', 'warning'],
                ['Điểm trung bình', '4.8 / 5', 'fas fa-star-half-alt', 'success'],
            ],
            'columns' => ['Sản phẩm', 'Điểm', 'Ngày gửi'],
            'rows' => [
                ['title' => 'Nguyễn Minh Anh', 'meta' => '“Chất vải đẹp, mặc rất thoải mái.”', 'cells' => ['Áo sơ mi Linen', '★★★★★', '16/10/2026'], 'status' => ['Chờ duyệt', 'warning']],
                ['title' => 'Trần Hoàng Nam', 'meta' => '“Đúng form, giao hàng nhanh.”', 'cells' => ['Áo thun Basic', '★★★★★', '15/10/2026'], 'status' => ['Đã hiển thị', 'success']],
                ['title' => 'Phạm Quốc Bảo', 'meta' => '“Sản phẩm không giống mô tả.”', 'cells' => ['Quần ống rộng', '★★☆☆☆', '14/10/2026'], 'status' => ['Cần xem xét', 'danger']],
                ['title' => 'Lê Thu Hà', 'meta' => '“Màu sắc xinh, đóng gói cẩn thận.”', 'cells' => ['Đầm Midi hoa nhí', '★★★★☆', '13/10/2026'], 'status' => ['Đã hiển thị', 'success']],
            ],
        ],
    ];

    abort_unless(array_key_exists($section, $pages), 404);

    return view('admin.resource', ['page' => $pages[$section], 'section' => $section]);
})->whereIn('section', [
    'reviews',
])->name('admin.resource');
