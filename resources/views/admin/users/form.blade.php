@extends('layouts.admin')

@section('title', $formTitle)
@section('page-heading', $formTitle)
@section('page-subheading', 'Nhập thông tin tài khoản người dùng.')
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

            {{-- Thông tin cơ bản --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-user text-primary mr-2"></i>Thông tin cơ bản</h3>
                </div>
                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger" role="alert">
                            <div class="font-weight-bold mb-1">Vui lòng kiểm tra lại thông tin:</div>
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
                                <label for="ho_ten">Họ tên <span class="text-danger">*</span></label>
                                <input id="ho_ten" type="text" name="ho_ten" value="{{ old('ho_ten', $user->ho_ten) }}"
                                    class="form-control @error('ho_ten') is-invalid @enderror"
                                    maxlength="50" required autofocus placeholder="Ví dụ: Nguyễn Văn A">
                                @error('ho_ten')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="email">Email <span class="text-danger">*</span></label>
                                <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}"
                                    class="form-control @error('email') is-invalid @enderror"
                                    maxlength="255" required placeholder="example@email.com">
                                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="so_dien_thoai">Số điện thoại</label>
                                <input id="so_dien_thoai" type="text" name="so_dien_thoai" value="{{ old('so_dien_thoai', $user->so_dien_thoai) }}"
                                    class="form-control @error('so_dien_thoai') is-invalid @enderror"
                                    maxlength="15" placeholder="0912 345 678">
                                @error('so_dien_thoai')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="ngay_sinh">Ngày sinh</label>
                                <input id="ngay_sinh" type="date" name="ngay_sinh" value="{{ old('ngay_sinh', $user->ngay_sinh?->format('Y-m-d')) }}"
                                    class="form-control @error('ngay_sinh') is-invalid @enderror"
                                    max="{{ now()->subDay()->format('Y-m-d') }}">
                                @error('ngay_sinh')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
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
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="anh_dai_dien">Tải ảnh đại diện lên</label>
                                <input id="anh_dai_dien" type="file" name="anh_dai_dien" accept="image/jpeg,image/png,image/webp"
                                    class="form-control-file @error('anh_dai_dien') is-invalid @enderror">
                                <small class="form-text text-muted">JPG, PNG hoặc WebP, tối đa 2 MB. Để trống nếu không muốn thay ảnh.</small>
                                @error('anh_dai_dien')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <div class="mt-2">
                                    @if ($user->avatarUrl())
                                        <img id="avatarPreview" src="{{ $user->avatarUrl() }}" alt="Ảnh đại diện hiện tại" class="img-circle" style="width:72px;height:72px;object-fit:cover;">
                                    @else
                                        <img id="avatarPreview" src="" alt="Ảnh xem trước" class="img-circle d-none" style="width:72px;height:72px;object-fit:cover;">
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Bảo mật --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-lock text-warning mr-2"></i>Bảo mật</h3>
                </div>
                <div class="card-body">
                    <div class="form-group mb-0">
                        <label for="mat_khau">
                            Mật khẩu
                            @if ($user->exists)
                                <small class="text-muted font-weight-normal">(để trống nếu không đổi)</small>
                            @else
                                <span class="text-danger">*</span>
                            @endif
                        </label>
                        <div class="input-group">
                            <input id="mat_khau" type="password" name="mat_khau"
                                class="form-control @error('mat_khau') is-invalid @enderror"
                                minlength="6" maxlength="255"
                                placeholder="{{ $user->exists ? 'Nhập mật khẩu mới nếu muốn thay đổi...' : 'Tối thiểu 6 ký tự' }}"
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
            </div>

            {{-- Phân quyền & Trạng thái --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-shield-alt text-info mr-2"></i>Phân quyền & Trạng thái</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="chuc_vu_id">Chức vụ</label>
                                <select id="chuc_vu_id" name="chuc_vu_id" class="form-control @error('chuc_vu_id') is-invalid @enderror">
                                    <option value="">-- Không có chức vụ --</option>
                                    @foreach ($chucVus as $cv)
                                        <option value="{{ $cv->id }}" @selected((string) old('chuc_vu_id', $user->chuc_vu_id) === (string) $cv->id)>{{ $cv->ten_chuc_vu }}</option>
                                    @endforeach
                                </select>
                                @error('chuc_vu_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-0">
                                <label for="trang_thai">Trạng thái <span class="text-danger">*</span></label>
                                <select id="trang_thai" name="trang_thai" class="form-control @error('trang_thai') is-invalid @enderror" required>
                                    <option value="1" @selected((string) old('trang_thai', (int) $user->trang_thai) === '1')>Hoạt động</option>
                                    <option value="0" @selected((string) old('trang_thai', (int) $user->trang_thai) === '0')>Khóa tài khoản</option>
                                </select>
                                @error('trang_thai')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-white d-flex justify-content-between">
                    <a href="{{ route('admin.users.index') }}" class="btn btn-default"><i class="fas fa-arrow-left mr-1"></i>Quay lại</a>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>{{ $user->exists ? 'Lưu thay đổi' : 'Tạo người dùng' }}</button>
                </div>
            </div>
        </form>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title"><i class="far fa-lightbulb text-warning mr-2"></i>Lưu ý</h3></div>
            <div class="card-body">
                <p class="text-muted">Email phải là duy nhất trong hệ thống và được dùng để đăng nhập.</p>
                <p class="text-muted">Mật khẩu tối thiểu 6 ký tự. Khi chỉnh sửa, để trống nếu không muốn thay đổi mật khẩu.</p>
                <p class="text-muted mb-0">Tài khoản bị khóa sẽ không thể đăng nhập vào hệ thống.</p>
            </div>
        </div>

        @if ($user->exists)
        <div class="card">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-info-circle text-info mr-2"></i>Thông tin hệ thống</h3></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <tr><td class="text-muted pl-3">ID</td><td class="font-weight-bold">#{{ $user->id }}</td></tr>
                    <tr><td class="text-muted pl-3">Ngày tạo</td><td>{{ $user->ngay_tao?->format('d/m/Y H:i') ?? '—' }}</td></tr>
                </table>
            </div>
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
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

document.getElementById('anh_dai_dien')?.addEventListener('change', function (event) {
    const file = event.target.files[0];
    const preview = document.getElementById('avatarPreview');
    if (file && preview) {
        preview.src = URL.createObjectURL(file);
        preview.classList.remove('d-none');
    }
});
</script>
@endpush
@endsection
