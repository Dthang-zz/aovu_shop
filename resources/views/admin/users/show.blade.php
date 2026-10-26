@extends('layouts.admin')

@section('title', 'Hồ sơ người dùng — ' . $user->ho_ten)
@section('page-heading', $user->ho_ten)
@section('page-subheading', 'Thông tin chi tiết tài khoản, hồ sơ số đo thời trang, sổ địa chỉ và lịch sử giao dịch.')
@section('breadcrumb')
    <a href="{{ route('admin.users.index') }}">Người dùng</a>
@endsection

@section('content')
{{-- Flash Alerts --}}
@if (session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle mr-1"></i>{{ session('success') }}
    <button type="button" class="close" data-dismiss="alert" aria-label="Đóng"><span aria-hidden="true">&times;</span></button>
</div>
@endif
@if (session('error'))
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="fas fa-exclamation-circle mr-1"></i>{{ session('error') }}
    <button type="button" class="close" data-dismiss="alert" aria-label="Đóng"><span aria-hidden="true">&times;</span></button>
</div>
@endif

{{-- Hiển thị mật khẩu vừa được tạo ngẫu nhiên (nếu có) --}}
@if (session('new_password_generated'))
<div class="alert alert-warning border-warning alert-dismissible fade show shadow-sm" role="alert">
    <div class="d-flex align-items-center justify-content-between flex-wrap" style="gap: 10px;">
        <div>
            <i class="fas fa-key fa-lg mr-2 text-dark"></i>
            <strong>Mật khẩu mới đã được cập nhật:</strong>
            <code class="px-3 py-1 bg-white rounded font-weight-bold ml-2 text-primary" style="font-size: 1.15rem;" id="copyPasswordText">{{ session('new_password_generated') }}</code>
        </div>
        <div>
            <button class="btn btn-sm btn-dark" type="button" onclick="copyPassword()">
                <i class="far fa-copy mr-1"></i>Sao chép mật khẩu
            </button>
            <button type="button" class="close position-static ml-2" data-dismiss="alert" aria-label="Đóng"><span aria-hidden="true">&times;</span></button>
        </div>
    </div>
</div>
@endif

<div class="row">
    {{-- CỘT TRÁI (COL-LG-4): AVATAR, THAO TÁC, LOYALTY TIER, CRM GHI CHÚ --}}
    <div class="col-lg-4 col-md-5">
        {{-- Card Hồ sơ chính --}}
        <div class="card mb-3 shadow-sm border-0">
            <div class="card-body text-center py-4">
                <div class="position-relative d-inline-block mb-3">
                    @if ($user->avatar_url)
                        <img src="{{ $user->avatar_url }}" alt="{{ $user->ho_ten }}" class="img-circle border elevation-1" style="width:105px;height:105px;object-fit:cover;">
                    @else
                        <div class="img-circle bg-gradient-primary d-inline-flex align-items-center justify-content-center text-white font-weight-bold elevation-1" style="width:100px;height:100px;font-size:2.4rem;">
                            {{ mb_strtoupper(mb_substr($user->ho_ten ?? '?', 0, 1)) }}
                        </div>
                    @endif
                    @if ($user->trang_thai)
                        <span class="position-absolute bg-success border border-white rounded-circle" style="width: 18px; height: 18px; bottom: 4px; right: 8px;" title="Đang hoạt động"></span>
                    @else
                        <span class="position-absolute bg-danger border border-white rounded-circle" style="width: 18px; height: 18px; bottom: 4px; right: 8px;" title="Đã khóa"></span>
                    @endif
                </div>

                <h4 class="font-weight-bold mb-1 text-dark">{{ $user->ho_ten }}</h4>
                <p class="text-muted small mb-2"><i class="far fa-envelope mr-1"></i>{{ $user->email }}</p>

                <div class="d-flex justify-content-center flex-wrap" style="gap: 6px;">
                    <span class="badge badge-light border px-2 py-1"><i class="fas fa-user-tag mr-1 text-secondary"></i>{{ $user->chucVu?->ten_chuc_vu ?? 'Khách hàng' }}</span>
                    @if ($user->nhan_khach_hang)
                        @php
                            $tagClass = match(true) {
                                str_contains($user->nhan_khach_hang, 'VIP') => 'badge-primary',
                                str_contains($user->nhan_khach_hang, 'sỉ') => 'badge-info',
                                str_contains($user->nhan_khach_hang, 'boom') || str_contains($user->nhan_khach_hang, 'Cảnh báo') => 'badge-danger',
                                default => 'badge-warning',
                            };
                        @endphp
                        <span class="badge {{ $tagClass }} px-2 py-1">{{ $user->nhan_khach_hang }}</span>
                    @endif
                    @if ($user->trang_thai)
                        <span class="badge badge-success px-2 py-1">Hoạt động</span>
                    @else
                        <span class="badge badge-danger px-2 py-1">Đã khóa</span>
                    @endif
                </div>
            </div>

            <div class="card-footer bg-white text-center py-3 border-top">
                <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-outline-primary btn-sm mr-1">
                    <i class="far fa-edit mr-1"></i>Chỉnh sửa
                </a>
                <button type="button" class="btn btn-outline-info btn-sm mr-1" data-toggle="modal" data-target="#resetPasswordModal">
                    <i class="fas fa-key mr-1"></i>Đổi pass
                </button>
                <form method="POST" action="{{ route('admin.users.toggle-status', $user) }}" class="d-inline">
                    @csrf
                    @method('PATCH')
                    @if ($user->trang_thai)
                        <button type="submit" class="btn btn-outline-warning btn-sm mr-1" onclick="return confirm('Bạn có chắc muốn khóa tài khoản này?')">
                            <i class="fas fa-lock mr-1"></i>Khóa
                        </button>
                    @else
                        <button type="submit" class="btn btn-outline-success btn-sm mr-1">
                            <i class="fas fa-lock-open mr-1"></i>Mở khóa
                        </button>
                    @endif
                </form>
                @if ($user->id !== 1)
                <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="d-inline" onsubmit="return confirm('Bạn có chắc muốn xóa vĩnh viễn người dùng này? Thao tác không thể hoàn tác.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger btn-sm">
                        <i class="far fa-trash-alt"></i>
                    </button>
                </form>
                @endif
            </div>
        </div>

        {{-- Card Hạng thành viên Loyalty Tier --}}
        @php $tier = $user->loyalty_tier; @endphp
        <div class="card text-white mb-3 shadow-sm border-0" style="background: {{ $tier['bg_gradient'] }}; border-radius: 12px;">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-uppercase small font-weight-bold" style="letter-spacing: 1px; opacity: .9;">Hạng khách hàng</span>
                    <i class="{{ $tier['icon'] }} fa-2x" style="opacity: .85;"></i>
                </div>
                <h3 class="font-weight-bold mb-1">{{ $tier['name'] }}</h3>
                <p class="small mb-3" style="opacity: .95;">{{ $tier['discount'] }}</p>

                <div class="d-flex justify-content-between small mb-1">
                    <span>Đã chi tiêu: <strong>₫ {{ number_format($tier['total_spent']) }}</strong></span>
                    @if ($tier['next_tier'])
                        <span>Lên {{ $tier['next_tier'] }}</span>
                    @else
                        <span>Hạng cao nhất</span>
                    @endif
                </div>
                <div class="progress" style="height: 6px; background-color: rgba(255,255,255,.3); border-radius: 3px;">
                    <div class="progress-bar bg-white" style="width: {{ $tier['progress'] }}%"></div>
                </div>
                @if ($tier['next_tier'])
                    <small class="d-block mt-2 text-right" style="opacity: .9;">Mua thêm ₫ {{ number_format($tier['needed']) }} để thăng hạng</small>
                @endif
            </div>
        </div>

        {{-- Widget Thời trang & Thể hình (BMI) --}}
        @if ($user->bmi || $user->size_thuong_mac)
        <div class="card mb-3 shadow-sm">
            <div class="card-header bg-white py-3">
                <h3 class="card-title font-weight-bold text-dark"><i class="fas fa-tshirt text-info mr-2"></i>Số đo & Thể hình</h3>
            </div>
            <div class="card-body p-3">
                <div class="row text-center mb-2">
                    <div class="col-4 border-right">
                        <small class="text-muted d-block">Size mặc</small>
                        <span class="h5 font-weight-bold text-primary">{{ $user->size_thuong_mac ?? '—' }}</span>
                    </div>
                    <div class="col-4 border-right">
                        <small class="text-muted d-block">Chiều cao</small>
                        <span class="h5 font-weight-bold text-dark">{{ $user->chieu_cao ? $user->chieu_cao . ' cm' : '—' }}</span>
                    </div>
                    <div class="col-4">
                        <small class="text-muted d-block">Cân nặng</small>
                        <span class="h5 font-weight-bold text-dark">{{ $user->can_nang ? $user->can_nang . ' kg' : '—' }}</span>
                    </div>
                </div>
                @if ($user->bmi)
                    <div class="alert alert-light border mb-0 text-center py-2">
                        <small class="text-muted">Chỉ số BMI: <strong>{{ $user->bmi['score'] }}</strong> &middot; <span class="badge badge-{{ $user->bmi['badge'] }}">{{ $user->bmi['label'] }}</span></small>
                    </div>
                @endif
            </div>
        </div>
        @endif

        {{-- Ghi chú nội bộ CRM & Nhãn khách hàng --}}
        <div class="card mb-3 shadow-sm">
            <div class="card-header bg-white py-3">
                <h3 class="card-title font-weight-bold text-dark"><i class="fas fa-sticky-note text-warning mr-2"></i>Ghi chú CRM nội bộ</h3>
            </div>
            <div class="card-body p-3">
                <form method="POST" action="{{ route('admin.users.admin-note', $user) }}">
                    @csrf
                    <div class="form-group mb-2">
                        <label class="small font-weight-bold text-muted mb-1">Nhãn khách hàng</label>
                        <input type="text" name="nhan_khach_hang" list="crm_tag_list" value="{{ $user->nhan_khach_hang }}" class="form-control form-control-sm" placeholder="Khách VIP, Khách sỉ...">
                        <datalist id="crm_tag_list">
                            <option value="Khách VIP">
                            <option value="Khách quen">
                            <option value="Khách sỉ">
                            <option value="Khách mới">
                            <option value="Cảnh báo boom hàng">
                        </datalist>
                    </div>
                    <div class="form-group mb-2">
                        <label class="small font-weight-bold text-muted mb-1">Ghi chú lưu ý</label>
                        <textarea name="ghi_chu_admin" rows="3" class="form-control form-control-sm" placeholder="Lưu ý về size, form đồ, thái độ khách...">{{ $user->ghi_chu_admin }}</textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm btn-block">
                        <i class="fas fa-save mr-1"></i>Lưu ghi chú
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- CỘT PHẢI (COL-LG-8): TABS THÔNG TIN, ĐỊA CHỈ, ĐƠN HÀNG, ĐỔI TRẢ, VOUCHER --}}
    <div class="col-lg-8 col-md-7">
        <div class="card card-primary card-outline card-outline-tabs shadow-sm border-0">
            <div class="card-header p-0 border-bottom-0">
                <ul class="nav nav-tabs" id="userTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active font-weight-bold" id="tab-profile-link" data-toggle="pill" href="#tab-profile" role="tab">
                            <i class="fas fa-user mr-1 text-primary"></i>Hồ sơ
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link font-weight-bold" id="tab-addresses-link" data-toggle="pill" href="#tab-addresses" role="tab">
                            <i class="fas fa-map-marker-alt mr-1 text-danger"></i>Sổ địa chỉ <span class="badge badge-light border ml-1">{{ $user->diaChis->count() }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link font-weight-bold" id="tab-orders-link" data-toggle="pill" href="#tab-orders" role="tab">
                            <i class="fas fa-shopping-bag mr-1 text-success"></i>Đơn hàng <span class="badge badge-light border ml-1">{{ $user->donHangs->count() }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link font-weight-bold" id="tab-returns-link" data-toggle="pill" href="#tab-returns" role="tab">
                            <i class="fas fa-exchange-alt mr-1 text-warning"></i>Đổi/Trả <span class="badge badge-light border ml-1">{{ $user->returnRequests->count() }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link font-weight-bold" id="tab-vouchers-link" data-toggle="pill" href="#tab-vouchers" role="tab">
                            <i class="fas fa-tags mr-1 text-info"></i>Voucher
                        </a>
                    </li>
                </ul>
            </div>

            <div class="card-body">
                <div class="tab-content" id="userTabsContent">
                    {{-- TAB 1: THÔNG TIN HỒ SƠ CHI TIẾT --}}
                    <div class="tab-pane fade show active" id="tab-profile" role="tabpanel">
                        <h5 class="font-weight-bold mb-3 text-dark"><i class="fas fa-id-card-alt mr-2 text-primary"></i>Thông tin tài khoản & Thời trang</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered mb-0">
                                <tbody>
                                    <tr>
                                        <th class="bg-light text-muted" style="width:30%">Họ và tên</th>
                                        <td class="font-weight-bold">{{ $user->ho_ten }}</td>
                                    </tr>
                                    <tr>
                                        <th class="bg-light text-muted">Địa chỉ Email</th>
                                        <td>{{ $user->email }}</td>
                                    </tr>
                                    <tr>
                                        <th class="bg-light text-muted">Số điện thoại</th>
                                        <td>{{ $user->so_dien_thoai ?? '—' }}</td>
                                    </tr>
                                    <tr>
                                        <th class="bg-light text-muted">Giới tính</th>
                                        <td>
                                            @if ($user->gioi_tinh === 1) Nam
                                            @elseif ($user->gioi_tinh === 0) Nữ
                                            @else Khác @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <th class="bg-light text-muted">Ngày sinh</th>
                                        <td>{{ $user->ngay_sinh?->format('d/m/Y') ?? '—' }}</td>
                                    </tr>
                                    <tr>
                                        <th class="bg-light text-muted">Size trang phục</th>
                                        <td>
                                            @if ($user->size_thuong_mac)
                                                <span class="badge badge-primary px-3 py-1 font-weight-bold">{{ $user->size_thuong_mac }}</span>
                                            @else
                                                <span class="text-muted">Chưa cập nhật</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <th class="bg-light text-muted">Chiều cao / Cân nặng</th>
                                        <td>
                                            @if ($user->chieu_cao || $user->can_nang)
                                                {{ $user->chieu_cao ? $user->chieu_cao . ' cm' : '—' }} &middot; {{ $user->can_nang ? $user->can_nang . ' kg' : '—' }}
                                            @else
                                                <span class="text-muted">Chưa cập nhật</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <th class="bg-light text-muted">Phong cách thời trang</th>
                                        <td>{{ $user->phong_cach ?? 'Chưa xác định' }}</td>
                                    </tr>
                                    <tr>
                                        <th class="bg-light text-muted">Đăng nhập gần nhất</th>
                                        <td>{{ $user->dang_nhap_cuoi?->format('d/m/Y H:i') ?? 'Chưa từng đăng nhập' }}</td>
                                    </tr>
                                    <tr>
                                        <th class="bg-light text-muted">Ngày đăng ký</th>
                                        <td>{{ $user->ngay_tao?->format('d/m/Y H:i') ?? '—' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- TAB 2: SỔ ĐỊA CHỈ NHẬN HÀNG (dia_chis) --}}
                    <div class="tab-pane fade" id="tab-addresses" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="font-weight-bold mb-0 text-dark"><i class="fas fa-map-marked-alt text-danger mr-2"></i>Sổ địa chỉ nhận hàng</h5>
                            <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addAddressModal">
                                <i class="fas fa-plus mr-1"></i>Thêm địa chỉ mới
                            </button>
                        </div>

                        @forelse ($user->diaChis as $addr)
                            <div class="card border mb-3 {{ $addr->mac_dinh ? 'border-primary' : '' }}">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <div class="font-weight-bold text-dark">
                                                {{ $addr->ten_nguoi_nhan }}
                                                <span class="text-muted font-weight-normal ml-2"><i class="fas fa-phone-alt fa-xs mr-1"></i>{{ $addr->sdt_nguoi_nhan }}</span>
                                                @if ($addr->mac_dinh)
                                                    <span class="badge badge-success ml-2"><i class="fas fa-check mr-1"></i>Mặc định</span>
                                                @endif
                                            </div>
                                            <p class="text-muted small mb-0 mt-1">
                                                <i class="fas fa-map-marker-alt text-danger mr-1"></i>{{ $addr->dia_chi_day_du }}
                                            </p>
                                        </div>
                                        <div class="text-nowrap ml-2">
                                            @if (!$addr->mac_dinh)
                                                <form method="POST" action="{{ route('admin.users.addresses.default', [$user, $addr]) }}" class="d-inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="btn btn-outline-secondary btn-sm" title="Đặt làm địa chỉ mặc định">
                                                        Đặt mặc định
                                                    </button>
                                                </form>
                                            @endif
                                            <form method="POST" action="{{ route('admin.users.addresses.destroy', [$user, $addr]) }}" class="d-inline" onsubmit="return confirm('Bạn có chắc muốn xóa địa chỉ này?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger btn-sm ml-1" title="Xóa địa chỉ">
                                                    <i class="far fa-trash-alt"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-5 text-muted bg-light rounded">
                                <i class="fas fa-map-marker-alt fa-2x mb-2 text-secondary"></i>
                                <div>Khách hàng chưa có địa chỉ nhận hàng nào.</div>
                                <button type="button" class="btn btn-primary btn-sm mt-3" data-toggle="modal" data-target="#addAddressModal">
                                    <i class="fas fa-plus mr-1"></i>Thêm địa chỉ đầu tiên
                                </button>
                            </div>
                        @endforelse
                    </div>

                    {{-- TAB 3: LỊCH SỬ ĐƠN HÀNG (don_hangs) --}}
                    <div class="tab-pane fade" id="tab-orders" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="font-weight-bold mb-0 text-dark"><i class="fas fa-shopping-bag text-success mr-2"></i>Lịch sử mua hàng</h5>
                            <span class="text-muted small">Tổng chi tiêu: <strong class="text-primary">₫ {{ number_format($orderStats->tong_chi_tieu) }}</strong></span>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover table-bordered mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Mã đơn</th>
                                        <th>Ngày đặt</th>
                                        <th>Địa chỉ giao</th>
                                        <th>Tổng tiền</th>
                                        <th>Thanh toán</th>
                                        <th>Trạng thái đơn</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($user->donHangs as $order)
                                    <tr>
                                        <td class="font-weight-bold text-primary">{{ $order->ma_don_hang }}</td>
                                        <td class="small">{{ $order->ngay_dat?->format('d/m/Y H:i') ?? '—' }}</td>
                                        <td class="small text-truncate" style="max-width: 180px;" title="{{ $order->dia_chi_nguoi_nhan }}">{{ $order->dia_chi_nguoi_nhan }}</td>
                                        <td class="font-weight-bold text-nowrap">₫ {{ number_format($order->tong_tien) }}</td>
                                        <td>
                                            <span class="badge badge-{{ $order->paymentStatusBadge() }}">{{ $order->paymentStatusLabel() }}</span>
                                        </td>
                                        <td>
                                            <span class="badge badge-{{ $order->statusBadge() }} px-2 py-1">{{ $order->statusLabel() }}</span>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">
                                            <i class="fas fa-shopping-cart fa-2x mb-2 text-secondary"></i>
                                            <div>Khách hàng chưa phát sinh đơn hàng nào.</div>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- TAB 4: LỊCH SỬ ĐỔI / TRẢ HÀNG (yeu_cau_doi_tras) --}}
                    <div class="tab-pane fade" id="tab-returns" role="tabpanel">
                        <h5 class="font-weight-bold mb-3 text-dark"><i class="fas fa-exchange-alt text-warning mr-2"></i>Lịch sử yêu cầu Đổi / Trả hàng</h5>
                        @forelse ($user->returnRequests as $ret)
                            <div class="card border mb-3">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="font-weight-bold text-dark">
                                            <span class="badge badge-{{ $ret->loai_yeu_cau == 1 ? 'info' : 'secondary' }} mr-1">{{ $ret->typeLabel() }}</span>
                                            Đơn hàng: #{{ $ret->don_hang_id }}
                                        </span>
                                        <span class="badge badge-{{ $ret->statusBadge() }} px-2 py-1">
                                            {{ $ret->statusLabel() }}
                                        </span>
                                    </div>
                                    <p class="text-muted small mb-1"><strong>Lý do:</strong> {{ $ret->ly_do }}</p>
                                    @if ($ret->phan_hoi)
                                        <div class="alert alert-light border small mb-0 py-1 px-2">
                                            <strong class="text-primary">Phản hồi của Shop:</strong> {{ $ret->phan_hoi }}
                                        </div>
                                    @endif
                                    <small class="text-muted d-block mt-2"><i class="far fa-clock mr-1"></i>Gửi lúc: {{ $ret->ngay_tao?->format('d/m/Y H:i') ?? '—' }}</small>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-4 text-muted bg-light rounded">
                                <i class="fas fa-shield-check fa-2x mb-2 text-success"></i>
                                <div>Khách hàng chưa có yêu cầu đổi trả nào (Lịch sử mua sắm uy tín).</div>
                            </div>
                        @endforelse
                    </div>

                    {{-- TAB 5: VOUCHER & QUÀ TẶNG --}}
                    <div class="tab-pane fade" id="tab-vouchers" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="font-weight-bold mb-0 text-dark"><i class="fas fa-ticket-alt text-info mr-2"></i>Mã giảm giá khả dụng trong hệ thống</h5>
                            <span class="badge badge-info">{{ $availableVouchers->count() }} mã đang hoạt động</span>
                        </div>
                        <div class="row">
                            @forelse ($availableVouchers as $voc)
                            <div class="col-md-6 mb-3">
                                <div class="card border border-info h-100 p-3 bg-light">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <code class="h6 font-weight-bold text-primary mb-0">{{ $voc->ma_voucher }}</code>
                                        <span class="badge badge-success">{{ $voc->gia_tri_format }}</span>
                                    </div>
                                    <small class="text-muted mb-2 d-block">{{ $voc->mo_ta }}</small>
                                    <div class="d-flex justify-content-between small text-muted border-top pt-2 mt-auto">
                                        <span>Đơn từ: ₫ {{ number_format($voc->don_toi_thieu) }}</span>
                                        <span>Hạn: {{ $voc->ngay_ket_thuc ? $voc->ngay_ket_thuc->format('d/m/Y') : 'Vô hạn' }}</span>
                                    </div>
                                </div>
                            </div>
                            @empty
                            <div class="col-12 text-center py-4 text-muted">
                                <div>Chưa có mã giảm giá nào được tạo trong hệ thống.</div>
                            </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- MODAL 1: CẤP LẠI MẬT KHẨU --}}
<div class="modal fade" id="resetPasswordModal" tabindex="-1" role="dialog" aria-labelledby="resetPasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form method="POST" action="{{ route('admin.users.reset-password', $user) }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title font-weight-bold text-dark" id="resetPasswordModalLabel"><i class="fas fa-key mr-2"></i>Cấp lại mật khẩu tài khoản</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Đóng"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted">Cấp mật khẩu mới cho tài khoản: <strong>{{ $user->email }}</strong>.</p>
                    <div class="form-group">
                        <label for="custom_password">Nhập mật khẩu mới (hoặc để trống để hệ thống tự sinh ngẫu nhiên)</label>
                        <input type="text" id="custom_password" name="custom_password" class="form-control" placeholder="Để trống nếu muốn tạo tự động...">
                        <small class="form-text text-muted">Mật khẩu tự sinh sẽ có độ an toàn cao và được hiển thị ngay sau khi tạo.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Hủy bỏ</button>
                    <button type="submit" class="btn btn-warning font-weight-bold"><i class="fas fa-check mr-1"></i>Xác nhận đổi mật khẩu</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- MODAL 2: THÊM ĐỊA CHỈ GIAO HÀNG --}}
<div class="modal fade" id="addAddressModal" tabindex="-1" role="dialog" aria-labelledby="addAddressModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <form method="POST" action="{{ route('admin.users.addresses.store', $user) }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title font-weight-bold" id="addAddressModalLabel"><i class="fas fa-map-marker-alt mr-2"></i>Thêm địa chỉ giao hàng cho khách</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Đóng"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="ten_nguoi_nhan">Tên người nhận <span class="text-danger">*</span></label>
                                <input type="text" id="ten_nguoi_nhan" name="ten_nguoi_nhan" class="form-control" value="{{ $user->ho_ten }}" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="sdt_nguoi_nhan">Số điện thoại nhận hàng <span class="text-danger">*</span></label>
                                <input type="text" id="sdt_nguoi_nhan" name="sdt_nguoi_nhan" class="form-control" value="{{ $user->so_dien_thoai }}" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="tinh_thanh">Tỉnh / Thành phố <span class="text-danger">*</span></label>
                                <input type="text" id="tinh_thanh" name="tinh_thanh" class="form-control" placeholder="Hà Nội, TP.HCM..." required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="quan_huyen">Quận / Huyện <span class="text-danger">*</span></label>
                                <input type="text" id="quan_huyen" name="quan_huyen" class="form-control" placeholder="Quận 1, Cầu Giấy..." required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="phuong_xa">Phường / Xã</label>
                                <input type="text" id="phuong_xa" name="phuong_xa" class="form-control" placeholder="Dịch Vọng, Bến Nghé...">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="dia_chi_chi_tiet">Địa chỉ chi tiết (Số nhà, tên đường, tòa nhà) <span class="text-danger">*</span></label>
                        <input type="text" id="dia_chi_chi_tiet" name="dia_chi_chi_tiet" class="form-control" placeholder="Số 12 Ngõ 34 Đường ABC..." required>
                    </div>
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="mac_dinh" name="mac_dinh" value="1" checked>
                        <label class="custom-control-label" for="mac_dinh">Đặt làm địa chỉ nhận hàng mặc định</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>Lưu địa chỉ</button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function copyPassword() {
    const text = document.getElementById('copyPasswordText')?.innerText;
    if (text) {
        navigator.clipboard.writeText(text).then(function() {
            alert('Đã sao chép mật khẩu: ' + text);
        });
    }
}
</script>
@endpush
@endsection
