@extends('layouts.storefront')

@section('title', 'Hồ sơ cá nhân')

@section('content')
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 font-weight-bold mb-1">Quản lý tài khoản</h1>
            <p class="text-muted">Theo dõi và cập nhật thông tin cá nhân của bạn tại AOVU Shop.</p>
        </div>
    </div>

    <div class="row">
        <!-- Sidebar tài khoản -->
        <div class="col-lg-4 mb-4">
            <div class="card storefront-card mb-3 text-center p-4">
                <div class="position-relative d-inline-block mx-auto mb-3">
                    @if ($customer->anh_dai_dien)
                        <img src="{{ $customer->avatarUrl() }}" alt="{{ $customer->ho_ten }}" class="rounded-circle shadow-sm" style="width: 100px; height: 100px; object-fit: cover;">
                    @else
                        <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center shadow-sm" style="width: 100px; height: 100px; font-size: 38px;">
                            <i class="fas fa-user"></i>
                        </div>
                    @endif
                </div>
                <h5 class="font-weight-bold mb-1">{{ $customer->ho_ten }}</h5>
                <p class="text-muted small mb-2">{{ $customer->email }}</p>
                <span class="badge badge-success px-3 py-1 font-weight-normal align-self-center">Thành viên thân thiết</span>
                <small class="text-muted mt-2">Tham gia: {{ $customer->ngay_tao ? \Carbon\Carbon::parse($customer->ngay_tao)->format('d/m/Y') : 'Khách hàng mới' }}</small>
            </div>

            <!-- Menu điều hướng tài khoản -->
            <div class="list-group storefront-card overflow-hidden">
                <a href="{{ route('customer.profile') }}" class="list-group-item list-group-item-action active font-weight-bold">
                    <i class="fas fa-id-card mr-2"></i>Hồ sơ cá nhân
                </a>
                <a href="{{ route('customer.addresses.index') }}" class="list-group-item list-group-item-action">
                    <i class="fas fa-map-marked-alt text-info mr-2"></i>Sổ địa chỉ nhận hàng
                    <span class="badge badge-light border float-right">{{ $addressCount }}</span>
                </a>
                <a href="{{ route('customer.orders') }}" class="list-group-item list-group-item-action">
                    <i class="fas fa-box-open text-warning mr-2"></i>Đơn hàng của tôi
                    <span class="badge badge-light border float-right">{{ $orderCount }}</span>
                </a>
                <a href="{{ route('shop.wishlist.index') }}" class="list-group-item list-group-item-action">
                    <i class="fas fa-heart text-danger mr-2"></i>Sản phẩm yêu thích
                    <span class="badge badge-light border float-right">{{ $wishlistCount }}</span>
                </a>
            </div>
        </div>

        <!-- Nội dung chính: Cập nhật hồ sơ & Đổi mật khẩu -->
        <div class="col-lg-8">
            <!-- Thống kê nhanh -->
            <div class="row mb-4">
                <div class="col-sm-6 mb-2">
                    <div class="card storefront-card p-3 border-left border-primary" style="border-left-width: 4px !important;">
                        <small class="text-muted d-block font-weight-bold">TỔNG ĐƠN HÀNG</small>
                        <span class="h4 font-weight-bold text-primary mb-0">{{ $orderCount }}</span> <small class="text-muted">đơn hàng</small>
                    </div>
                </div>
                <div class="col-sm-6 mb-2">
                    <div class="card storefront-card p-3 border-left border-success" style="border-left-width: 4px !important;">
                        <small class="text-muted d-block font-weight-bold">TỔNG CHI TIÊU</small>
                        <span class="h4 font-weight-bold text-success mb-0">{{ number_format($totalSpent, 0, ',', '.') }} ₫</span>
                    </div>
                </div>
            </div>

            <!-- Form Cập nhật thông tin -->
            <div class="card storefront-card mb-4">
                <div class="card-header bg-white py-3">
                    <h2 class="h5 font-weight-bold mb-0"><i class="fas fa-user-edit text-primary mr-2"></i>Thông tin cá nhân</h2>
                </div>
                <div class="card-body">
                    @if ($errors->any() && ! $errors->has('mat_khau_cu') && ! $errors->has('mat_khau'))
                        <div class="alert alert-danger py-2 small">
                            <ul class="mb-0 pl-3">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('customer.profile.update') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="ho_ten" class="small font-weight-bold">Họ và tên <span class="text-danger">*</span></label>
                                <input type="text" id="ho_ten" name="ho_ten" class="form-control" value="{{ old('ho_ten', $customer->ho_ten) }}" required maxlength="50">
                            </div>
                            <div class="form-group col-md-6">
                                <label for="email" class="small font-weight-bold">Địa chỉ Email</label>
                                <input type="email" id="email" class="form-control bg-light" value="{{ $customer->email }}" disabled readonly>
                                <small class="text-muted">Email dùng để đăng nhập và không thể thay đổi.</small>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label for="so_dien_thoai" class="small font-weight-bold">Số điện thoại</label>
                                <input type="text" id="so_dien_thoai" name="so_dien_thoai" class="form-control" value="{{ old('so_dien_thoai', $customer->so_dien_thoai) }}" maxlength="15" placeholder="0901234567">
                            </div>
                            <div class="form-group col-md-4">
                                <label for="ngay_sinh" class="small font-weight-bold">Ngày sinh</label>
                                <input type="date" id="ngay_sinh" name="ngay_sinh" class="form-control" value="{{ old('ngay_sinh', $customer->ngay_sinh ? $customer->ngay_sinh->format('Y-m-d') : '') }}">
                            </div>
                            <div class="form-group col-md-4">
                                <label for="gioi_tinh" class="small font-weight-bold">Giới tính</label>
                                <select id="gioi_tinh" name="gioi_tinh" class="form-control">
                                    <option value="">-- Chọn --</option>
                                    <option value="1" @selected(old('gioi_tinh', $customer->gioi_tinh) === 1)>Nam</option>
                                    <option value="0" @selected(old('gioi_tinh', $customer->gioi_tinh) === 0)>Nữ</option>
                                    <option value="2" @selected(old('gioi_tinh', $customer->gioi_tinh) === 2)>Khác</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="anh_dai_dien" class="small font-weight-bold">Cập nhật ảnh đại diện</label>
                            <input type="file" id="anh_dai_dien" name="anh_dai_dien" class="form-control-file" accept="image/*">
                            <small class="text-muted">Định dạng hỗ trợ: JPG, PNG, WebP. Tối đa 2MB.</small>
                        </div>

                        <button type="submit" class="btn btn-primary px-4">
                            <i class="fas fa-save mr-1"></i>Lưu thay đổi hồ sơ
                        </button>
                    </form>
                </div>
            </div>

            <!-- Form Đổi mật khẩu -->
            <div class="card storefront-card">
                <div class="card-header bg-white py-3">
                    <h2 class="h5 font-weight-bold mb-0"><i class="fas fa-key text-warning mr-2"></i>Đổi mật khẩu</h2>
                </div>
                <div class="card-body">
                    @if ($errors->has('mat_khau_cu') || $errors->has('mat_khau'))
                        <div class="alert alert-danger py-2 small">
                            <ul class="mb-0 pl-3">
                                @foreach ($errors->get('mat_khau_cu') as $error)<li>{{ $error }}</li>@endforeach
                                @foreach ($errors->get('mat_khau') as $error)<li>{{ $error }}</li>@endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('customer.password.update') }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="form-group">
                            <label for="mat_khau_cu" class="small font-weight-bold">Mật khẩu hiện tại <span class="text-danger">*</span></label>
                            <input type="password" id="mat_khau_cu" name="mat_khau_cu" class="form-control" required placeholder="Nhập mật khẩu đang dùng">
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="mat_khau_moi" class="small font-weight-bold">Mật khẩu mới <span class="text-danger">*</span></label>
                                <input type="password" id="mat_khau_moi" name="mat_khau" class="form-control" required minlength="6" placeholder="Tối thiểu 6 ký tự">
                            </div>
                            <div class="form-group col-md-6">
                                <label for="mat_khau_xac_nhan" class="small font-weight-bold">Xác nhận mật khẩu mới <span class="text-danger">*</span></label>
                                <input type="password" id="mat_khau_xac_nhan" name="mat_khau_confirmation" class="form-control" required minlength="6" placeholder="Nhập lại mật khẩu mới">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-warning px-4 text-dark font-weight-bold">
                            <i class="fas fa-lock mr-1"></i>Cập nhật mật khẩu mới
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
