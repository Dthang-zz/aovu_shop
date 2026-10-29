<?php

use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ReturnRequestController;
use App\Http\Controllers\OrderTrackingController;
use Illuminate\Support\Facades\Route;

Route::get('/', DashboardController::class);
Route::get('/admin', DashboardController::class)->name('admin.dashboard');

Route::get('/tra-cuu-don-hang', [OrderTrackingController::class, 'show'])
    ->name('orders.track');
Route::post('/tra-cuu-don-hang', [OrderTrackingController::class, 'lookup'])
    ->middleware('throttle:10,1')
    ->name('orders.track.lookup');

Route::get('/admin/orders', [OrderController::class, 'index'])
    ->name('admin.orders.index');
Route::get('/admin/orders/{order}', [OrderController::class, 'show'])
    ->whereNumber('order')
    ->name('admin.orders.show');
Route::put('/admin/orders/{order}', [OrderController::class, 'update'])
    ->whereNumber('order')
    ->name('admin.orders.update');

Route::resource('/admin/categories', CategoryController::class)
    ->except(['show'])
    ->names([
        'index' => 'admin.categories.index',
        'create' => 'admin.categories.create',
        'store' => 'admin.categories.store',
        'edit' => 'admin.categories.edit',
        'update' => 'admin.categories.update',
        'destroy' => 'admin.categories.destroy',
    ]);

Route::resource('/admin/banners', BannerController::class)
    ->except(['show'])
    ->names([
        'index' => 'admin.banners.index',
        'create' => 'admin.banners.create',
        'store' => 'admin.banners.store',
        'edit' => 'admin.banners.edit',
        'update' => 'admin.banners.update',
        'destroy' => 'admin.banners.destroy',
    ]);

Route::resource('/admin/products', ProductController::class)
    ->except(['show'])
    ->names([
        'index' => 'admin.products.index',
        'create' => 'admin.products.create',
        'store' => 'admin.products.store',
        'edit' => 'admin.products.edit',
        'update' => 'admin.products.update',
        'destroy' => 'admin.products.destroy',
    ]);

Route::get('/admin/returns', [ReturnRequestController::class, 'index'])
    ->name('admin.returns.index');
Route::get('/admin/returns/{returnRequest}', [ReturnRequestController::class, 'show'])
    ->whereNumber('returnRequest')
    ->name('admin.returns.show');
Route::put('/admin/returns/{returnRequest}', [ReturnRequestController::class, 'update'])
    ->whereNumber('returnRequest')
    ->name('admin.returns.update');

Route::get('/admin/{section}', function (string $section) {
    $pages = [
        'variants' => [
            'title' => 'Thuộc tính & biến thể',
            'subtitle' => 'Theo dõi màu sắc, kích thước và mã SKU theo từng phiên bản.',
            'icon' => 'fas fa-layer-group',
            'action' => 'Thêm biến thể',
            'recordLabel' => 'Biến thể',
            'stats' => [
                ['Tổng biến thể', '326', 'fas fa-layer-group', 'primary'],
                ['Màu sắc', '18', 'fas fa-palette', 'info'],
                ['Kích thước', '06', 'fas fa-ruler', 'warning'],
            ],
            'columns' => ['Sản phẩm', 'Màu sắc / Kích thước', 'SKU'],
            'rows' => [
                ['title' => 'Áo sơ mi Linen', 'meta' => 'Phiên bản sản phẩm', 'cells' => ['Áo sơ mi Linen', 'Trắng / M', 'AO-LINEN-WH-M'], 'status' => ['Đang bán', 'success']],
                ['title' => 'Áo sơ mi Linen', 'meta' => 'Phiên bản sản phẩm', 'cells' => ['Áo sơ mi Linen', 'Be / L', 'AO-LINEN-BE-L'], 'status' => ['Đang bán', 'success']],
                ['title' => 'Đầm Midi hoa nhí', 'meta' => 'Phiên bản sản phẩm', 'cells' => ['Đầm Midi hoa nhí', 'Xanh / S', 'DAM-MIDI-BL-S'], 'status' => ['Đang bán', 'success']],
                ['title' => 'Áo thun Basic', 'meta' => 'Phiên bản sản phẩm', 'cells' => ['Áo thun Basic', 'Đen / XL', 'AT-BASIC-BK-XL'], 'status' => ['Tạm ngưng', 'secondary']],
            ],
        ],
        'inventory' => [
            'title' => 'Kho hàng',
            'subtitle' => 'Theo dõi tồn kho và các cảnh báo cần được lưu ý.',
            'icon' => 'fas fa-warehouse',
            'action' => 'Phiếu nhập / xuất',
            'recordLabel' => 'Sản phẩm / SKU',
            'stats' => [
                ['Tổng tồn kho', '1.284', 'fas fa-boxes', 'primary'],
                ['Sắp hết hàng', '08', 'fas fa-exclamation-triangle', 'warning'],
                ['Hết hàng', '03', 'fas fa-times-circle', 'danger'],
            ],
            'columns' => ['Phân loại', 'Tồn kho', 'Ngưỡng cảnh báo'],
            'rows' => [
                ['title' => 'Áo sơ mi Linen', 'meta' => 'AO-LINEN-WH-M', 'cells' => ['Trắng / M', '42', '10'], 'status' => ['Ổn định', 'success']],
                ['title' => 'Đầm Midi hoa nhí', 'meta' => 'DAM-MIDI-BL-S', 'cells' => ['Xanh / S', '05', '10'], 'status' => ['Sắp hết', 'warning']],
                ['title' => 'Quần ống rộng', 'meta' => 'Q-ONGRONG-BK-M', 'cells' => ['Đen / M', '00', '08'], 'status' => ['Hết hàng', 'danger']],
                ['title' => 'Áo thun Basic', 'meta' => 'AT-BASIC-WH-L', 'cells' => ['Trắng / L', '28', '10'], 'status' => ['Ổn định', 'success']],
            ],
        ],
        'users' => [
            'title' => 'Người dùng',
            'subtitle' => 'Danh sách tài khoản quản trị và khách hàng.',
            'icon' => 'fas fa-users',
            'action' => 'Thêm tài khoản',
            'recordLabel' => 'Tài khoản',
            'stats' => [
                ['Tổng tài khoản', '1.248', 'fas fa-users', 'primary'],
                ['Khách hàng', '1.240', 'fas fa-user', 'info'],
                ['Quản trị viên', '08', 'fas fa-user-shield', 'warning'],
            ],
            'columns' => ['Email', 'Vai trò', 'Ngày tham gia'],
            'rows' => [
                ['title' => 'Nguyễn Minh Anh', 'meta' => '0903 123 456', 'cells' => ['minhanh@email.vn', 'Khách hàng', '12/08/2026'], 'status' => ['Hoạt động', 'success']],
                ['title' => 'Trần Hoàng Nam', 'meta' => '0912 456 789', 'cells' => ['hoangnam@email.vn', 'Khách hàng', '03/07/2026'], 'status' => ['Hoạt động', 'success']],
                ['title' => 'Lê Thu Hà', 'meta' => '0988 222 010', 'cells' => ['thuha@email.vn', 'Quản trị viên', '15/02/2026'], 'status' => ['Hoạt động', 'success']],
                ['title' => 'Phạm Quốc Bảo', 'meta' => '0934 555 210', 'cells' => ['quocbao@email.vn', 'Khách hàng', '22/01/2026'], 'status' => ['Đã khóa', 'secondary']],
            ],
        ],
        'promotions' => [
            'title' => 'Khuyến mãi',
            'subtitle' => 'Quản lý mã giảm giá và chương trình ưu đãi.',
            'icon' => 'fas fa-tags',
            'action' => 'Tạo khuyến mãi',
            'recordLabel' => 'Chương trình',
            'stats' => [
                ['Chương trình', '12', 'fas fa-tags', 'primary'],
                ['Đang diễn ra', '04', 'fas fa-bolt', 'success'],
                ['Lượt sử dụng', '826', 'fas fa-ticket-alt', 'info'],
            ],
            'columns' => ['Mã ưu đãi', 'Giảm giá', 'Thời hạn'],
            'rows' => [
                ['title' => 'Ưu đãi tháng 10', 'meta' => 'Áp dụng toàn cửa hàng', 'cells' => ['THANG10', 'Giảm 15%', '01/10 – 31/10'], 'status' => ['Đang diễn ra', 'success']],
                ['title' => 'Khách hàng mới', 'meta' => 'Đơn hàng đầu tiên', 'cells' => ['WELCOME50', 'Giảm ₫ 50.000', 'Đến 30/11'], 'status' => ['Đang diễn ra', 'success']],
                ['title' => 'Ưu đãi sinh nhật', 'meta' => 'Khách hàng thành viên', 'cells' => ['HAPPYBDAY', 'Giảm 20%', '01/01 – 31/12'], 'status' => ['Đang diễn ra', 'success']],
                ['title' => 'Sale cuối hè', 'meta' => 'Sản phẩm chọn lọc', 'cells' => ['SUMMER25', 'Giảm 25%', 'Đã kết thúc'], 'status' => ['Đã kết thúc', 'secondary']],
            ],
        ],
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
    'variants',
    'inventory',
    'users',
    'promotions',
    'reviews',
])->name('admin.resource');
