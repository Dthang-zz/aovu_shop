<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ReturnRequestController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\VariantController;
use Illuminate\Support\Facades\Route;

Route::get('/', DashboardController::class);
Route::get('/admin', DashboardController::class)->name('admin.dashboard');

Route::resource('/admin/categories', CategoryController::class)
    ->except(['show'])
    ->names([
        'index'   => 'admin.categories.index',
        'create'  => 'admin.categories.create',
        'store'   => 'admin.categories.store',
        'edit'    => 'admin.categories.edit',
        'update'  => 'admin.categories.update',
        'destroy' => 'admin.categories.destroy',
    ]);

Route::resource('/admin/products', ProductController::class)
    ->except(['show'])
    ->names([
        'index'   => 'admin.products.index',
        'create'  => 'admin.products.create',
        'store'   => 'admin.products.store',
        'edit'    => 'admin.products.edit',
        'update'  => 'admin.products.update',
        'destroy' => 'admin.products.destroy',
    ]);

// Quáº£n lÃ½ thuá»™c tÃ­nh & biáº¿n thá»ƒ
Route::get('/admin/variants', [VariantController::class, 'index'])->name('admin.variants.index');
Route::get('/admin/variants/create', [VariantController::class, 'create'])->name('admin.variants.create');
Route::post('/admin/variants', [VariantController::class, 'store'])->name('admin.variants.store');
Route::get('/admin/variants/{variant}/edit', [VariantController::class, 'edit'])->name('admin.variants.edit');
Route::put('/admin/variants/{variant}', [VariantController::class, 'update'])->name('admin.variants.update');
Route::delete('/admin/variants/{variant}', [VariantController::class, 'destroy'])->name('admin.variants.destroy');

// Quáº£n lÃ½ ngÆ°á»i dÃ¹ng
Route::resource('/admin/users', UserController::class)
    ->except(['show'])
    ->names([
        'index'   => 'admin.users.index',
        'create'  => 'admin.users.create',
        'store'   => 'admin.users.store',
        'edit'    => 'admin.users.edit',
        'update'  => 'admin.users.update',
        'destroy' => 'admin.users.destroy',
    ]);
Route::get('/admin/users/{user}', [UserController::class, 'show'])->name('admin.users.show');
Route::patch('/admin/users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('admin.users.toggle-status');

// Quáº£n lÃ½ Ä‘á»•i tráº£
Route::get('/admin/returns', [ReturnRequestController::class, 'index'])
    ->name('admin.returns.index');
Route::get('/admin/returns/{returnRequest}', [ReturnRequestController::class, 'show'])
    ->whereNumber('returnRequest')
    ->name('admin.returns.show');
Route::put('/admin/returns/{returnRequest}', [ReturnRequestController::class, 'update'])
    ->whereNumber('returnRequest')
    ->name('admin.returns.update');

// CÃ¡c trang quáº£n trá»‹ dáº¡ng tÄ©nh khÃ¡c
Route::get('/admin/{section}', function (string $section) {
    $pages = [
        'variants' => [
            'title'       => 'Thuá»™c tÃ­nh & biáº¿n thá»ƒ',
            'subtitle'    => 'Theo dÃµi mÃ u sáº¯c, kÃ­ch thÆ°á»›c vÃ  mÃ£ SKU theo tá»«ng phiÃªn báº£n.',
            'icon'        => 'fas fa-layer-group',
            'action'      => 'ThÃªm biáº¿n thá»ƒ',
            'recordLabel' => 'Biáº¿n thá»ƒ',
            'stats' => [
                ['Tá»•ng biáº¿n thá»ƒ', '326', 'fas fa-layer-group', 'primary'],
                ['MÃ u sáº¯c', '18', 'fas fa-palette', 'info'],
                ['KÃ­ch thÆ°á»›c', '06', 'fas fa-ruler', 'warning'],
            ],
            'columns' => ['Sáº£n pháº©m', 'MÃ u sáº¯c / KÃ­ch thÆ°á»›c', 'SKU'],
            'rows' => [
                ['title' => 'Ão sÆ¡ mi Linen',   'meta' => 'PhiÃªn báº£n sáº£n pháº©m', 'cells' => ['Ão sÆ¡ mi Linen',   'Tráº¯ng / M', 'AO-LINEN-WH-M'],  'status' => ['Äang bÃ¡n',  'success']],
                ['title' => 'Ão sÆ¡ mi Linen',   'meta' => 'PhiÃªn báº£n sáº£n pháº©m', 'cells' => ['Ão sÆ¡ mi Linen',   'Be / L',    'AO-LINEN-BE-L'],   'status' => ['Äang bÃ¡n',  'success']],
                ['title' => 'Äáº§m Midi hoa nhÃ­', 'meta' => 'PhiÃªn báº£n sáº£n pháº©m', 'cells' => ['Äáº§m Midi hoa nhÃ­', 'Xanh / S',  'DAM-MIDI-BL-S'],   'status' => ['Äang bÃ¡n',  'success']],
                ['title' => 'Ão thun Basic',    'meta' => 'PhiÃªn báº£n sáº£n pháº©m', 'cells' => ['Ão thun Basic',    'Äen / XL',  'AT-BASIC-BK-XL'],  'status' => ['Táº¡m ngÆ°ng', 'secondary']],
            ],
        ],
        'inventory' => [
            'title'       => 'Kho hÃ ng',
            'subtitle'    => 'Theo dÃµi tá»“n kho vÃ  cÃ¡c cáº£nh bÃ¡o cáº§n Ä‘Æ°á»£c lÆ°u Ã½.',
            'icon'        => 'fas fa-warehouse',
            'action'      => 'Phiáº¿u nháº­p / xuáº¥t',
            'recordLabel' => 'Sáº£n pháº©m / SKU',
            'stats' => [
                ['Tá»•ng tá»“n kho', '1.284', 'fas fa-boxes',                'primary'],
                ['Sáº¯p háº¿t hÃ ng', '08',    'fas fa-exclamation-triangle', 'warning'],
                ['Háº¿t hÃ ng',     '03',    'fas fa-times-circle',         'danger'],
            ],
            'columns' => ['PhÃ¢n loáº¡i', 'Tá»“n kho', 'NgÆ°á»¡ng cáº£nh bÃ¡o'],
            'rows' => [
                ['title' => 'Ão sÆ¡ mi Linen',   'meta' => 'AO-LINEN-WH-M',   'cells' => ['Tráº¯ng / M', '42', '10'], 'status' => ['á»”n Ä‘á»‹nh', 'success']],
                ['title' => 'Äáº§m Midi hoa nhÃ­', 'meta' => 'DAM-MIDI-BL-S',   'cells' => ['Xanh / S',  '05', '10'], 'status' => ['Sáº¯p háº¿t', 'warning']],
                ['title' => 'Quáº§n á»‘ng rá»™ng',    'meta' => 'Q-ONGRONG-BK-M',  'cells' => ['Äen / M',   '00', '08'], 'status' => ['Háº¿t hÃ ng', 'danger']],
                ['title' => 'Ão thun Basic',    'meta' => 'AT-BASIC-WH-L',   'cells' => ['Tráº¯ng / L', '28', '10'], 'status' => ['á»”n Ä‘á»‹nh', 'success']],
            ],
        ],
        'orders' => [
            'title'       => 'ÄÆ¡n hÃ ng',
            'subtitle'    => 'Theo dÃµi Ä‘Æ¡n hÃ ng vÃ  tráº¡ng thÃ¡i xá»­ lÃ½ gáº§n nháº¥t.',
            'icon'        => 'fas fa-shopping-bag',
            'action'      => 'Xuáº¥t danh sÃ¡ch',
            'recordLabel' => 'MÃ£ Ä‘Æ¡n',
            'stats' => [
                ['Táº¥t cáº£ Ä‘Æ¡n hÃ ng', '284', 'fas fa-shopping-bag',  'primary'],
                ['Chá» xá»­ lÃ½',       '18',  'fas fa-clock',         'warning'],
                ['Äang giao',        '32',  'fas fa-shipping-fast', 'info'],
            ],
            'columns' => ['KhÃ¡ch hÃ ng', 'NgÃ y Ä‘áº·t', 'Tá»•ng tiá»n'],
            'rows' => [
                ['title' => '#DH-20261016-0284', 'meta' => '3 sáº£n pháº©m', 'cells' => ['Nguyá»…n Minh Anh', '16/10/2026', 'â‚« 1.248.000'], 'status' => ['Chá» xÃ¡c nháº­n',  'warning']],
                ['title' => '#DH-20261016-0283', 'meta' => '1 sáº£n pháº©m', 'cells' => ['Tráº§n HoÃ ng Nam',  '16/10/2026', 'â‚« 459.000'],   'status' => ['Äang giao',     'info']],
                ['title' => '#DH-20261015-0282', 'meta' => '2 sáº£n pháº©m', 'cells' => ['LÃª Thu HÃ ',       '15/10/2026', 'â‚« 928.000'],   'status' => ['ÄÃ£ hoÃ n thÃ nh', 'success']],
                ['title' => '#DH-20261015-0281', 'meta' => '4 sáº£n pháº©m', 'cells' => ['Pháº¡m Quá»‘c Báº£o',  '15/10/2026', 'â‚« 1.890.000'], 'status' => ['ÄÃ£ há»§y',        'danger']],
            ],
        ],
        'users' => [
            'title'       => 'NgÆ°á»i dÃ¹ng',
            'subtitle'    => 'Danh sÃ¡ch tÃ i khoáº£n quáº£n trá»‹ vÃ  khÃ¡ch hÃ ng.',
            'icon'        => 'fas fa-users',
            'action'      => 'ThÃªm tÃ i khoáº£n',
            'recordLabel' => 'TÃ i khoáº£n',
            'stats' => [
                ['Tá»•ng tÃ i khoáº£n', '1.248', 'fas fa-users',       'primary'],
                ['KhÃ¡ch hÃ ng',     '1.240', 'fas fa-user',        'info'],
                ['Quáº£n trá»‹ viÃªn',  '08',    'fas fa-user-shield', 'warning'],
            ],
            'columns' => ['Email', 'Vai trÃ²', 'NgÃ y tham gia'],
            'rows' => [
                ['title' => 'Nguyá»…n Minh Anh', 'meta' => '0903 123 456', 'cells' => ['minhanh@email.vn',  'KhÃ¡ch hÃ ng',     '12/08/2026'], 'status' => ['Hoáº¡t Ä‘á»™ng', 'success']],
                ['title' => 'Tráº§n HoÃ ng Nam',  'meta' => '0912 456 789', 'cells' => ['hoangnam@email.vn', 'KhÃ¡ch hÃ ng',     '03/07/2026'], 'status' => ['Hoáº¡t Ä‘á»™ng', 'success']],
                ['title' => 'LÃª Thu HÃ ',       'meta' => '0988 222 010', 'cells' => ['thuha@email.vn',    'Quáº£n trá»‹ viÃªn',  '15/02/2026'], 'status' => ['Hoáº¡t Ä‘á»™ng', 'success']],
                ['title' => 'Pháº¡m Quá»‘c Báº£o',  'meta' => '0934 555 210', 'cells' => ['quocbao@email.vn',  'KhÃ¡ch hÃ ng',     '22/01/2026'], 'status' => ['ÄÃ£ khÃ³a',   'secondary']],
            ],
        ],
        'promotions' => [
            'title'       => 'Khuyáº¿n mÃ£i',
            'subtitle'    => 'Quáº£n lÃ½ mÃ£ giáº£m giÃ¡ vÃ  chÆ°Æ¡ng trÃ¬nh Æ°u Ä‘Ã£i.',
            'icon'        => 'fas fa-tags',
            'action'      => 'Táº¡o khuyáº¿n mÃ£i',
            'recordLabel' => 'ChÆ°Æ¡ng trÃ¬nh',
            'stats' => [
                ['ChÆ°Æ¡ng trÃ¬nh', '12',  'fas fa-tags',       'primary'],
                ['Äang diá»…n ra', '04',  'fas fa-bolt',       'success'],
                ['LÆ°á»£t sá»­ dá»¥ng', '826', 'fas fa-ticket-alt', 'info'],
            ],
            'columns' => ['MÃ£ Æ°u Ä‘Ã£i', 'Giáº£m giÃ¡', 'Thá»i háº¡n'],
            'rows' => [
                ['title' => 'Æ¯u Ä‘Ã£i thÃ¡ng 10',  'meta' => 'Ãp dá»¥ng toÃ n cá»­a hÃ ng', 'cells' => ['THANG10',   'Giáº£m 15%',       '01/10 â€“ 31/10'], 'status' => ['Äang diá»…n ra', 'success']],
                ['title' => 'KhÃ¡ch hÃ ng má»›i',   'meta' => 'ÄÆ¡n hÃ ng Ä‘áº§u tiÃªn',     'cells' => ['WELCOME50', 'Giáº£m â‚« 50.000',  'Äáº¿n 30/11'],     'status' => ['Äang diá»…n ra', 'success']],
                ['title' => 'Æ¯u Ä‘Ã£i sinh nháº­t', 'meta' => 'KhÃ¡ch hÃ ng thÃ nh viÃªn', 'cells' => ['HAPPYBDAY', 'Giáº£m 20%',       '01/01 â€“ 31/12'], 'status' => ['Äang diá»…n ra', 'success']],
                ['title' => 'Sale cuá»‘i hÃ¨',      'meta' => 'Sáº£n pháº©m chá»n lá»c',     'cells' => ['SUMMER25',  'Giáº£m 25%',       'ÄÃ£ káº¿t thÃºc'],   'status' => ['ÄÃ£ káº¿t thÃºc',  'secondary']],
            ],
        ],
        'reviews' => [
            'title'       => 'ÄÃ¡nh giÃ¡ sáº£n pháº©m',
            'subtitle'    => 'Theo dÃµi pháº£n há»“i vÃ  ná»™i dung cáº§n Ä‘Æ°á»£c kiá»ƒm duyá»‡t.',
            'icon'        => 'fas fa-star',
            'action'      => 'Xem Ä‘Ã¡nh giÃ¡ chá» duyá»‡t',
            'recordLabel' => 'ÄÃ¡nh giÃ¡',
            'stats' => [
                ['Tá»•ng Ä‘Ã¡nh giÃ¡',   '386',     'fas fa-star',          'primary'],
                ['Chá» duyá»‡t',        '12',      'fas fa-hourglass-half', 'warning'],
                ['Äiá»ƒm trung bÃ¬nh', '4.8 / 5', 'fas fa-star-half-alt', 'success'],
            ],
            'columns' => ['Sáº£n pháº©m', 'Äiá»ƒm', 'NgÃ y gá»­i'],
            'rows' => [
                ['title' => 'Nguyá»…n Minh Anh', 'meta' => '"Cháº¥t váº£i Ä‘áº¹p, máº·c ráº¥t thoáº£i mÃ¡i."', 'cells' => ['Ão sÆ¡ mi Linen',   'â˜…â˜…â˜…â˜…â˜…', '16/10/2026'], 'status' => ['Chá» duyá»‡t',   'warning']],
                ['title' => 'Tráº§n HoÃ ng Nam',  'meta' => '"ÄÃºng form, giao hÃ ng nhanh."',      'cells' => ['Ão thun Basic',    'â˜…â˜…â˜…â˜…â˜…', '15/10/2026'], 'status' => ['ÄÃ£ hiá»ƒn thá»‹', 'success']],
                ['title' => 'Pháº¡m Quá»‘c Báº£o',  'meta' => '"Sáº£n pháº©m khÃ´ng giá»‘ng mÃ´ táº£."',      'cells' => ['Quáº§n á»‘ng rá»™ng',    'â˜…â˜…â˜†â˜†â˜†', '14/10/2026'], 'status' => ['Cáº§n xem xÃ©t', 'danger']],
                ['title' => 'LÃª Thu HÃ ',       'meta' => '"MÃ u sáº¯c xinh, Ä‘Ã³ng gÃ³i cáº©n tháº­n."', 'cells' => ['Äáº§m Midi hoa nhÃ­', 'â˜…â˜…â˜…â˜…â˜†', '13/10/2026'], 'status' => ['ÄÃ£ hiá»ƒn thá»‹', 'success']],
            ],
        ],
    ];

    abort_unless(array_key_exists($section, $pages), 404);

    return view('admin.resource', ['page' => $pages[$section], 'section' => $section]);
})->whereIn('section', [
    'variants',
    'inventory',
    'orders',
    'users',
    'promotions',
    'reviews',
])->name('admin.resource');
