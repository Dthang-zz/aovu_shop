@extends('layouts.admin')

@section('title', $formTitle)
@section('page-heading', $formTitle)
@section('page-subheading', 'Cập nhật thông tin cá nhân, hồ sơ số đo thời trang và phân quyền tài khoản.')
@section('breadcrumb')
    <a href="{{ route('admin.users.index') }}">Người dùng</a>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-8">
        <form method="POST" action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}" enctype="multipart/form-data">
            @csrf
            @if ($user->exists)
                @method('PUT')
            @endif

            {{-- 1. THÔNG TIN CƠ BẢN --}}
            <div class="card mb-3">
                <div class="card-header bg-white py-3">
                    <h3 class="card-title font-weight-bold text-dark"><i class="fas fa-id-card text-primary mr-2"></i>1. Thông tin tài khoản</h3>
                </div>
                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger" role="alert">
                            <div class="font-weight-bold mb-1"><i class="fas fa-exclamation-triangle mr-1"></i>Vui lòng kiểm tra lại thông tin:</div>
                            <ul class="mb-0 pl-3">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="ho_ten">Họ và tên <span class="text-danger">*</span></label>
                                <input id="ho_ten" type="text" name="ho_ten" value="{{ old('ho_ten', $user->ho_ten) }}"
                                    class="form-control @error('ho_ten') is-invalid @enderror"
                                    maxlength="50" required autofocus placeholder="Ví dụ: Nguyễn Minh Anh">
                                @error('ho_ten')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="email">Địa chỉ Email <span class="text-danger">*</span></label>
                                <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}"
                                    class="form-control @error('email') is-invalid @enderror"
                                    maxlength="255" required placeholder="customer@email.com">
                                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="so_dien_thoai">Số điện thoại</label>
                                <input id="so_dien_thoai" type="text" name="so_dien_thoai" value="{{ old('so_dien_thoai', $user->so_dien_thoai) }}"
                                    class="form-control @error('so_dien_thoai') is-invalid @enderror"
                                    maxlength="15" placeholder="0901 234 567">
                                @error('so_dien_thoai')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="ngay_sinh">Ngày sinh</label>
                                <input id="ngay_sinh" type="date" name="ngay_sinh" value="{{ old('ngay_sinh', $user->ngay_sinh?->format('Y-m-d')) }}"
                                    class="form-control @error('ngay_sinh') is-invalid @enderror"
                                    max="{{ now()->subDay()->format('Y-m-d') }}">
                                @error('ngay_sinh')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="gioi_tinh">Giới tính</label>
                                <select id="gioi_tinh" name="gioi_tinh" class="form-control @error('gioi_tinh') is-invalid @enderror">
                                    <option value="">-- Chọn --</option>
                                    <option value="1" @selected((string) old('gioi_tinh', $user->gioi_tinh) === '1')>Nam</option>
                                    <option value="0" @selected((string) old('gioi_tinh', $user->gioi_tinh) === '0')>Nữ</option>
                                    <option value="2" @selected((string) old('gioi_tinh', $user->gioi_tinh) === '2')>Khác</option>
                                </select>
                                @error('gioi_tinh')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 2. HỒ SƠ THỜI TRANG & SỐ ĐO (Fashion Profile) --}}
            <div class="card mb-3 border-info">
                <div class="card-header bg-white py-3">
                    <h3 class="card-title font-weight-bold text-info"><i class="fas fa-tshirt mr-2"></i>2. Hồ sơ thời trang & Số đo (Fashion Profile)</h3>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">Dữ liệu số đo giúp nhân viên tư vấn chọn chuẩn size áo, quần và gợi ý phong cách trang phục phù hợp.</p>
                    <div class="row">
                        <div class="col-md-3 col-6">
                            <div class="form-group">
                                <label for="size_thuong_mac">Size thường mặc</label>
                                <select id="size_thuong_mac" name="size_thuong_mac" class="form-control @error('size_thuong_mac') is-invalid @enderror">
                                    <option value="">-- Chọn size --</option>
                                    @foreach(['XS', 'S', 'M', 'L', 'XL', '2XL', '3XL', 'Freesize'] as $size)
                                        <option value="{{ $size }}" @selected(old('size_thuong_mac', $user->size_thuong_mac) === $size)>{{ $size }}</option>
                                    @endforeach
                                </select>
                                @error('size_thuong_mac')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="form-group">
                                <label for="chieu_cao">Chiều cao (cm)</label>
                                <div class="input-group">
                                    <input id="chieu_cao" type="number" name="chieu_cao" value="{{ old('chieu_cao', $user->chieu_cao) }}"
                                        class="form-control @error('chieu_cao') is-invalid @enderror" min="50" max="250" placeholder="170">
                                    <div class="input-group-append"><span class="input-group-text">cm</span></div>
                                </div>
                                @error('chieu_cao')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="form-group">
                                <label for="can_nang">Cân nặng (kg)</label>
                                <div class="input-group">
                                    <input id="can_nang" type="number" name="can_nang" value="{{ old('can_nang', $user->can_nang) }}"
                                        class="form-control @error('can_nang') is-invalid @enderror" min="20" max="250" placeholder="60">
                                    <div class="input-group-append"><span class="input-group-text">kg</span></div>
                                </div>
                                @error('can_nang')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="form-group">
                                <label for="phong_cach">Phong cách yêu thích</label>
                                <input id="phong_cach" type="text" name="phong_cach" list="phong_cach_list"
                                    value="{{ old('phong_cach', $user->phong_cach) }}"
                                    class="form-control @error('phong_cach') is-invalid @enderror"
                                    placeholder="Streetwear, Vintage...">
                                <datalist id="phong_cach_list">
                                    <option value="Công sở, Lịch thiệp">
                                    <option value="Streetwear, Năng động">
                                    <option value="Tối giản (Minimalism)">
                                    <option value="Vintage, Cổ điển">
                                    <option value="Hàn Quốc, Trẻ trung">
                                    <option value="Thể thao, Khỏe khoắn">
                                </datalist>
                                @error('phong_cach')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 3. ẢNH ĐẠI DIỆN --}}
            <div class="card mb-3">
                <div class="card-header bg-white py-3">
                    <h3 class="card-title font-weight-bold text-dark"><i class="fas fa-camera text-primary mr-2"></i>3. Ảnh đại diện</h3>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3 p-3 bg-light rounded border">
                        <div class="mr-3">
                            <div id="avatarPreviewContainer" style="width: 72px; height: 72px;">
                                @if ($user->avatar_url)
                                    <img id="avatarPreviewImg" src="{{ $user->avatar_url }}" alt="Avatar" class="img-circle border" style="width: 72px; height: 72px; object-fit: cover;">
                                    <div id="avatarPlaceholder" class="img-circle bg-white border d-none align-items-center justify-content-center text-muted" style="width: 72px; height: 72px;">
                                        <i class="fas fa-user fa-2x text-secondary"></i>
                                    </div>
                                @else
                                    <div id="avatarPlaceholder" class="img-circle bg-white border d-flex align-items-center justify-content-center text-muted" style="width: 72px; height: 72px;">
                                        <i class="fas fa-user fa-2x text-secondary"></i>
                                    </div>
                                    <img id="avatarPreviewImg" src="" alt="Avatar" class="img-circle border d-none" style="width: 72px; height: 72px; object-fit: cover;">
                                @endif
                            </div>
                        </div>
                        <div>
                            <div class="font-weight-bold text-dark mb-1">Xem trước ảnh đại diện</div>
                            <small class="text-muted d-block">Tự động cập nhật khi bạn chọn tệp ảnh từ máy tính hoặc dán đường dẫn ảnh.</small>
                            @if ($user->avatar_url)
                                <div class="custom-control custom-checkbox mt-2">
                                    <input type="checkbox" class="custom-control-input" id="xoa_anh" name="xoa_anh" value="1">
                                    <label class="custom-control-label text-danger font-weight-normal small" for="xoa_anh">
                                        <i class="far fa-trash-alt mr-1"></i>Xóa ảnh đại diện hiện tại (quay về chữ cái mặc định)
                                    </label>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <label for="avatar_file" class="font-weight-bold small text-dark"><i class="fas fa-upload mr-1 text-primary"></i>Tải ảnh từ máy tính</label>
                            <div class="custom-file">
                                <input type="file" id="avatar_file" name="avatar_file" class="custom-file-input @error('avatar_file') is-invalid @enderror" accept=".jpg,.jpeg,.png,.webp,.gif,image/*">
                                <label class="custom-file-label" for="avatar_file" id="avatarFileLabel">Chọn tệp ảnh...</label>
                            </div>
                            @error('avatar_file')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            <small class="form-text text-muted">Hỗ trợ JPG, PNG, WebP, GIF (tối đa 2MB).</small>
                        </div>
                        <div class="col-md-6">
                            <label for="anh_dai_dien" class="font-weight-bold small text-dark"><i class="fas fa-link mr-1 text-primary"></i>Hoặc nhập link ảnh (URL)</label>
                            <input id="anh_dai_dien" type="url" name="anh_dai_dien" value="{{ old('anh_dai_dien', $user->anh_dai_dien) }}"
                                class="form-control @error('anh_dai_dien') is-invalid @enderror"
                                placeholder="https://example.com/avatar.jpg">
                            @error('anh_dai_dien')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <small class="form-text text-muted">Tệp tải lên sẽ được ưu tiên hơn nếu cung cấp cả hai.</small>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 4. PHÂN QUYỀN & CRM --}}
            <div class="card mb-3">
                <div class="card-header bg-white py-3">
                    <h3 class="card-title font-weight-bold text-dark"><i class="fas fa-shield-alt text-success mr-2"></i>4. Phân quyền & Quản lý quan hệ khách hàng (CRM)</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="chuc_vu_id">Chức vụ / Vai trò</label>
                                <select id="chuc_vu_id" name="chuc_vu_id" class="form-control @error('chuc_vu_id') is-invalid @enderror">
                                    <option value="">-- Không có chức vụ --</option>
                                    @foreach ($chucVus as $cv)
                                        <option value="{{ $cv->id }}" @selected((string) old('chuc_vu_id', $user->chuc_vu_id) === (string) $cv->id)>
                                            {{ $cv->ten_chuc_vu }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('chuc_vu_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="nhan_khach_hang">Nhãn phân loại (CRM Tag)</label>
                                <input id="nhan_khach_hang" type="text" name="nhan_khach_hang" list="nhan_list"
                                    value="{{ old('nhan_khach_hang', $user->nhan_khach_hang) }}"
                                    class="form-control @error('nhan_khach_hang') is-invalid @enderror"
                                    placeholder="VIP, Khách sỉ, Cảnh báo...">
                                <datalist id="nhan_list">
                                    <option value="Khách VIP">
                                    <option value="Khách quen">
                                    <option value="Khách sỉ">
                                    <option value="Khách mới">
                                    <option value="Cảnh báo boom hàng">
                                </datalist>
                                @error('nhan_khach_hang')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="trang_thai">Trạng thái tài khoản <span class="text-danger">*</span></label>
                                <select id="trang_thai" name="trang_thai" class="form-control @error('trang_thai') is-invalid @enderror" required>
                                    <option value="1" @selected((string) old('trang_thai', (int) $user->trang_thai) === '1')>Hoạt động</option>
                                    <option value="0" @selected((string) old('trang_thai', (int) $user->trang_thai) === '0')>Khóa tài khoản</option>
                                </select>
                                @error('trang_thai')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-0">
                        <label for="ghi_chu_admin"><i class="far fa-sticky-note mr-1 text-warning"></i>Ghi chú nội bộ của Quản trị viên (Khách hàng không thấy)</label>
                        <textarea id="ghi_chu_admin" name="ghi_chu_admin" rows="3"
                            class="form-control @error('ghi_chu_admin') is-invalid @enderror"
                            placeholder="Ghi chú thói quen mua sắm, sở thích đóng gói, lưu ý khi giao hàng...">{{ old('ghi_chu_admin', $user->ghi_chu_admin) }}</textarea>
                        @error('ghi_chu_admin')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>

            {{-- 5. BẢO MẬT MẬT KHẨU --}}
            <div class="card mb-4">
                <div class="card-header bg-white py-3">
                    <h3 class="card-title font-weight-bold text-dark"><i class="fas fa-lock text-warning mr-2"></i>5. Bảo mật mật khẩu</h3>
                </div>
                <div class="card-body">
                    <div class="form-group mb-0">
                        <label for="mat_khau">
                            Mật khẩu
                            @if ($user->exists)
                                <small class="text-muted font-weight-normal">(để trống nếu không muốn thay đổi)</small>
                            @else
                                <span class="text-danger">*</span>
                            @endif
                        </label>
                        <div class="input-group">
                            <input id="mat_khau" type="password" name="mat_khau"
                                class="form-control @error('mat_khau') is-invalid @enderror"
                                minlength="6" maxlength="255"
                                placeholder="{{ $user->exists ? 'Nhập mật khẩu mới nếu muốn đổi...' : 'Tối thiểu 6 ký tự' }}"
                                {{ $user->exists ? '' : 'required' }}>
                            <div class="input-group-append">
                                <button class="btn btn-default" type="button" id="togglePassword" aria-label="Hiện / ẩn mật khẩu">
                                    <i class="far fa-eye" id="eyeIcon"></i>
                                </button>
                            </div>
                            @error('mat_khau')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-white d-flex justify-content-between py-3">
                    <a href="{{ route('admin.users.index') }}" class="btn btn-default"><i class="fas fa-arrow-left mr-1"></i>Quay lại danh sách</a>
                    <button type="submit" class="btn btn-primary px-4"><i class="fas fa-save mr-1"></i>{{ $user->exists ? 'Lưu thay đổi' : 'Tạo người dùng' }}</button>
                </div>
            </div>
        </form>
    </div>

    {{-- CỘT PHẢI: WIDGET THỜI TRANG & THÔNG TIN HỆ THỐNG --}}
    <div class="col-lg-4">
        @if ($user->exists)
            {{-- Thẻ Hạng Thành Viên Loyalty --}}
            @php $tier = $user->loyalty_tier; @endphp
            <div class="card text-white mb-3 shadow-sm border-0" style="background: {{ $tier['bg_gradient'] }}; border-radius: 12px;">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-uppercase small font-weight-bold" style="letter-spacing: 1px; opacity: .9;">Hạng thành viên</span>
                        <i class="{{ $tier['icon'] }} fa-2x" style="opacity: .85;"></i>
                    </div>
                    <h3 class="font-weight-bold mb-1">{{ $tier['name'] }}</h3>
                    <p class="small mb-3" style="opacity: .95;">{{ $tier['discount'] }}</p>

                    <div class="d-flex justify-content-between small mb-1">
                        <span>Đã chi tiêu: <strong>₫ {{ number_format($tier['total_spent']) }}</strong></span>
                        @if ($tier['next_tier'])
                            <span>Lên {{ $tier['next_tier'] }}</span>
                        @else
                            <span>Hạng tối đa</span>
                        @endif
                    </div>
                    <div class="progress" style="height: 6px; background-color: rgba(255,255,255,.3); border-radius: 3px;">
                        <div class="progress-bar bg-white" style="width: {{ $tier['progress'] }}%"></div>
                    </div>
                    @if ($tier['next_tier'])
                        <small class="d-block mt-2 text-right" style="opacity: .9;">Cần thêm ₫ {{ number_format($tier['needed']) }} để lên hạng</small>
                    @endif
                </div>
            </div>

            {{-- Thước đo BMI thời trang --}}
            @if ($user->bmi)
            <div class="card mb-3">
                <div class="card-header bg-white"><h3 class="card-title font-weight-bold"><i class="fas fa-ruler-combined text-info mr-2"></i>Chỉ số thể hình (BMI)</h3></div>
                <div class="card-body text-center py-3">
                    <div class="h2 font-weight-bold text-{{ $user->bmi['badge'] }} mb-0">{{ $user->bmi['score'] }}</div>
                    <span class="badge badge-{{ $user->bmi['badge'] }} px-3 py-1 mb-2">{{ $user->bmi['label'] }}</span>
                    <p class="text-muted small mb-0">Chiều cao: <strong>{{ $user->chieu_cao }} cm</strong> &middot; Cân nặng: <strong>{{ $user->can_nang }} kg</strong></p>
                </div>
            </div>
            @endif

            {{-- Thông tin hệ thống --}}
            <div class="card mb-3">
                <div class="card-header bg-white"><h3 class="card-title font-weight-bold"><i class="fas fa-info-circle text-info mr-2"></i>Thông tin hệ thống</h3></div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <tr><td class="text-muted pl-3">Mã tài khoản</td><td class="font-weight-bold">#{{ $user->id }}</td></tr>
                        <tr><td class="text-muted pl-3">Ngày đăng ký</td><td>{{ $user->ngay_tao?->format('d/m/Y H:i') ?? '—' }}</td></tr>
                        <tr><td class="text-muted pl-3">Đăng nhập cuối</td><td>{{ $user->dang_nhap_cuoi?->format('d/m/Y H:i') ?? 'Chưa ghi nhận' }}</td></tr>
                        <tr><td class="text-muted pl-3">Số địa chỉ</td><td>{{ $user->diaChis()->count() }} địa chỉ</td></tr>
                        <tr><td class="text-muted pl-3">Tổng đơn hàng</td><td>{{ $user->donHangs()->count() }} đơn</td></tr>
                    </table>
                </div>
            </div>
        @else
            <div class="card mb-3">
                <div class="card-header bg-white"><h3 class="card-title font-weight-bold"><i class="far fa-lightbulb text-warning mr-2"></i>Lưu ý khi tạo</h3></div>
                <div class="card-body">
                    <p class="text-muted small mb-2"><i class="fas fa-check text-success mr-1"></i>Email dùng để đăng nhập và nhận thông báo đơn hàng.</p>
                    <p class="text-muted small mb-2"><i class="fas fa-check text-success mr-1"></i>Số đo thời trang giúp cá nhân hóa gợi ý size khi khách mua sắm.</p>
                    <p class="text-muted small mb-0"><i class="fas fa-check text-success mr-1"></i>Mật khẩu mặc định tối thiểu 6 ký tự.</p>
                </div>
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
// Toggle mật khẩu
document.getElementById('togglePassword')?.addEventListener('click', function () {
    const input = document.getElementById('mat_khau');
    const icon = document.getElementById('eyeIcon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
});

// Xử lý preview avatar khi chọn file hoặc nhập URL
const avatarFileInput = document.getElementById('avatar_file');
const avatarUrlInput = document.getElementById('anh_dai_dien');
const avatarPreviewImg = document.getElementById('avatarPreviewImg');
const avatarPlaceholder = document.getElementById('avatarPlaceholder');
const avatarFileLabel = document.getElementById('avatarFileLabel');
const xoaAnhCheckbox = document.getElementById('xoa_anh');

function showPreview(src) {
    if (src) {
        avatarPreviewImg.src = src;
        avatarPreviewImg.classList.remove('d-none');
        avatarPlaceholder?.classList.add('d-none');
        avatarPlaceholder?.classList.remove('d-flex');
    } else {
        avatarPreviewImg.src = '';
        avatarPreviewImg.classList.add('d-none');
        avatarPlaceholder?.classList.remove('d-none');
        avatarPlaceholder?.classList.add('d-flex');
    }
}

avatarFileInput?.addEventListener('change', function (e) {
    const file = e.target.files[0];
    if (file) {
        avatarFileLabel.textContent = file.name;
        const reader = new FileReader();
        reader.onload = function (event) {
            showPreview(event.target.result);
        };
        reader.readAsDataURL(file);
        if (xoaAnhCheckbox) xoaAnhCheckbox.checked = false;
    }
});

avatarUrlInput?.addEventListener('input', function (e) {
    const url = e.target.value.trim();
    if (url && (!avatarFileInput.files || avatarFileInput.files.length === 0)) {
        showPreview(url);
        if (xoaAnhCheckbox) xoaAnhCheckbox.checked = false;
    } else if (!url && (!avatarFileInput.files || avatarFileInput.files.length === 0)) {
        showPreview('');
    }
});

xoaAnhCheckbox?.addEventListener('change', function () {
    if (this.checked) {
        showPreview('');
        avatarFileInput.value = '';
        avatarFileLabel.textContent = 'Chọn tệp ảnh...';
        avatarUrlInput.value = '';
    }
});
</script>
@endpush
@endsection
