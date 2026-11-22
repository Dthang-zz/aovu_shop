@extends('layouts.admin')

@section('title', $formTitle)

@section('content')
    <div class="container-fluid">

        {{-- Tiêu đề --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-1">{{ $formTitle }}</h1>
                <p class="text-muted mb-0">
                    Thiết lập thông tin và điều kiện áp dụng mã giảm giá.
                </p>
            </div>

            <a href="{{ route('admin.vouchers.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left mr-1"></i>
                Quay lại
            </a>
        </div>

        {{-- Thông báo lỗi --}}
        @if ($errors->any())
            <div class="alert alert-danger">
                <strong>Vui lòng kiểm tra lại thông tin:</strong>

                <ul class="mb-0 mt-2">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @php
            $isEdit = $voucher->exists;

            $formAction = $isEdit ? route('admin.vouchers.update', $voucher->id) : route('admin.vouchers.store');
        @endphp

        <form action="{{ $formAction }}" method="POST">
            @csrf

            @if ($isEdit)
                @method('PUT')
            @endif

            <div class="row">

                {{-- Cột thông tin chính --}}
                <div class="col-lg-8">

                    {{-- Thông tin voucher --}}
                    <div class="card card-primary card-outline">

                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-ticket-alt mr-1"></i>
                                Thông tin mã giảm giá
                            </h3>
                        </div>

                        <div class="card-body">

                            <div class="form-group">
                                <label for="ma_voucher">
                                    Mã giảm giá
                                    <span class="text-danger">*</span>
                                </label>

                                <input type="text" name="ma_voucher" id="ma_voucher"
                                    class="form-control text-uppercase @error('ma_voucher') is-invalid @enderror"
                                    value="{{ old('ma_voucher', $voucher->ma_voucher) }}"
                                    placeholder="Ví dụ: SALE20, TET2026" maxlength="50" required>

                                @error('ma_voucher')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                                <small class="form-text text-muted">
                                    Chỉ sử dụng chữ cái không dấu, số, dấu gạch ngang
                                    hoặc gạch dưới.
                                </small>
                            </div>

                            <div class="form-group">
                                <label for="mo_ta">Mô tả</label>

                                <textarea name="mo_ta" id="mo_ta" rows="3" maxlength="255"
                                    class="form-control @error('mo_ta') is-invalid @enderror" placeholder="Nhập mô tả cho mã giảm giá...">{{ old('mo_ta', $voucher->mo_ta) }}</textarea>

                                @error('mo_ta')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                        </div>
                    </div>

                    {{-- Thiết lập giảm giá --}}
                    <div class="card card-primary card-outline">

                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-percent mr-1"></i>
                                Thiết lập giảm giá
                            </h3>
                        </div>

                        <div class="card-body">

                            <div class="form-group">
                                <label for="loai_giam">
                                    Loại giảm giá
                                    <span class="text-danger">*</span>
                                </label>

                                <select name="loai_giam" id="loai_giam"
                                    class="form-control @error('loai_giam') is-invalid @enderror" required>
                                    @foreach (\App\Models\Voucher::discountTypes() as $key => $label)
                                        <option value="{{ $key }}" @selected((string) old('loai_giam', $voucher->loai_giam ?? \App\Models\Voucher::TYPE_PERCENT) === (string) $key)>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>

                                @error('loai_giam')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="gia_tri_giam">
                                    Giá trị giảm
                                    <span class="text-danger">*</span>
                                </label>

                                <div class="input-group">
                                    <input type="number" name="gia_tri_giam" id="gia_tri_giam"
                                        class="form-control @error('gia_tri_giam') is-invalid @enderror"
                                        value="{{ old('gia_tri_giam', $voucher->gia_tri_giam) }}" min="1"
                                        step="1" required>

                                    <div class="input-group-append">
                                        <span class="input-group-text" id="discountUnit">%</span>
                                    </div>

                                    @error('gia_tri_giam')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>

                                <small class="form-text text-muted" id="discountHint">
                                    Nhập tỷ lệ giảm từ 1 đến 100%.
                                </small>
                            </div>

                            <div class="form-group" id="maxDiscountGroup">
                                <label for="giam_toi_da">
                                    Số tiền giảm tối đa (VNĐ)
                                </label>

                                <input type="number" name="giam_toi_da" id="giam_toi_da"
                                    class="form-control @error('giam_toi_da') is-invalid @enderror"
                                    value="{{ old('giam_toi_da', $voucher->giam_toi_da) }}" min="0" step="1"
                                    placeholder="Ví dụ: 100000">

                                @error('giam_toi_da')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                                <small class="form-text text-muted">
                                    Chỉ áp dụng khi chọn giảm theo phần trăm.
                                </small>
                            </div>

                            <div class="form-group">
                                <label for="don_toi_thieu">
                                    Giá trị đơn hàng tối thiểu (VNĐ)
                                </label>

                                <input type="number" name="don_toi_thieu" id="don_toi_thieu"
                                    class="form-control @error('don_toi_thieu') is-invalid @enderror"
                                    value="{{ old('don_toi_thieu', $voucher->don_toi_thieu) }}" min="0"
                                    step="1" placeholder="Ví dụ: 300000">

                                @error('don_toi_thieu')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                                <small class="form-text text-muted">
                                    Để trống hoặc nhập 0 nếu không yêu cầu giá trị đơn hàng tối thiểu.
                                </small>
                            </div>

                        </div>
                    </div>

                    {{-- Số lượng và thời hạn --}}
                    <div class="card card-primary card-outline">

                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-calendar-alt mr-1"></i>
                                Số lượng và thời hạn
                            </h3>
                        </div>

                        <div class="card-body">

                            <div class="form-group">
                                <label for="so_luong">
                                    Số lượng mã giảm giá
                                </label>

                                <input type="number" name="so_luong" id="so_luong"
                                    class="form-control @error('so_luong') is-invalid @enderror"
                                    value="{{ old('so_luong', $voucher->so_luong) }}"
                                    min="{{ $isEdit ? max(1, (int) $voucher->da_su_dung) : 1 }}" step="1"
                                    placeholder="Để trống nếu không giới hạn">

                                @error('so_luong')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                                @if ($isEdit)
                                    <small class="form-text text-muted">
                                        Đã sử dụng: {{ number_format($voucher->da_su_dung) }} lượt.
                                        Số lượng mới không được thấp hơn số lượt đã sử dụng.
                                    </small>
                                @else
                                    <small class="form-text text-muted">
                                        Để trống nếu muốn khách hàng sử dụng không giới hạn số lượt.
                                    </small>
                                @endif
                            </div>

                            <div class="form-group">
                                <label for="ngay_bat_dau">
                                    Ngày bắt đầu
                                </label>

                                <input type="datetime-local" name="ngay_bat_dau" id="ngay_bat_dau"
                                    class="form-control @error('ngay_bat_dau') is-invalid @enderror"
                                    value="{{ old('ngay_bat_dau', $voucher->ngay_bat_dau?->format('Y-m-d\TH:i')) }}">

                                @error('ngay_bat_dau')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                                <small class="form-text text-muted">
                                    Để trống nếu voucher có hiệu lực ngay khi được bật.
                                </small>
                            </div>

                            <div class="form-group">
                                <label for="ngay_ket_thuc">
                                    Ngày kết thúc
                                </label>

                                <input type="datetime-local" name="ngay_ket_thuc" id="ngay_ket_thuc"
                                    class="form-control @error('ngay_ket_thuc') is-invalid @enderror"
                                    value="{{ old('ngay_ket_thuc', $voucher->ngay_ket_thuc?->format('Y-m-d\TH:i')) }}">

                                @error('ngay_ket_thuc')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                                <small class="form-text text-muted">
                                    Để trống nếu voucher không có ngày hết hạn.
                                </small>
                            </div>

                        </div>
                    </div>

                </div>

                {{-- Cột bên phải --}}
                <div class="col-lg-4">

                    {{-- Trạng thái --}}
                    <div class="card card-success card-outline">

                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-toggle-on mr-1"></i>
                                Trạng thái
                            </h3>
                        </div>

                        <div class="card-body">

                            <div class="form-group mb-0">
                                <label for="trang_thai">
                                    Trạng thái mã giảm giá
                                </label>

                                <select name="trang_thai" id="trang_thai"
                                    class="form-control @error('trang_thai') is-invalid @enderror" required>
                                    <option value="1" @selected((string) old('trang_thai', $voucher->trang_thai ?? 1) === '1')>
                                        Đang bật
                                    </option>

                                    <option value="0" @selected((string) old('trang_thai', $voucher->trang_thai ?? 1) === '0')>
                                        Đã tắt
                                    </option>
                                </select>

                                @error('trang_thai')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                        </div>
                    </div>

                    {{-- Hướng dẫn --}}
                    <div class="card card-info card-outline">

                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-info-circle mr-1"></i>
                                Hướng dẫn
                            </h3>
                        </div>

                        <div class="card-body">

                            <p>
                                <strong>Giảm theo phần trăm:</strong>
                                Ví dụ nhập 20 để giảm 20% giá trị đơn hàng.
                            </p>

                            <p>
                                <strong>Giảm số tiền cố định:</strong>
                                Ví dụ nhập 50000 để giảm 50.000 VNĐ.
                            </p>

                            <p>
                                <strong>Giảm tối đa:</strong>
                                Chỉ sử dụng khi chọn giảm theo phần trăm.
                            </p>

                            <p class="mb-0">
                                <strong>Số lượng:</strong>
                                Để trống nếu không giới hạn lượt sử dụng.
                            </p>

                        </div>
                    </div>

                    {{-- Thống kê khi sửa --}}
                    @if ($isEdit)
                        <div class="card card-secondary card-outline">

                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-chart-bar mr-1"></i>
                                    Thống kê sử dụng
                                </h3>
                            </div>

                            <div class="card-body">

                                <div class="d-flex justify-content-between mb-2">
                                    <span>Đã sử dụng</span>

                                    <strong>
                                        {{ number_format($voucher->da_su_dung) }}
                                    </strong>
                                </div>

                                <div class="d-flex justify-content-between mb-2">
                                    <span>Còn lại</span>

                                    <strong>
                                        {{ $voucher->remainingQuantity() !== null ? number_format($voucher->remainingQuantity()) : 'Không giới hạn' }}
                                    </strong>
                                </div>

                                @if ($voucher->so_luong)
                                    <div class="progress mb-2">
                                        <div class="progress-bar bg-info" role="progressbar"
                                            style="width: {{ $voucher->usagePercent() }}%"
                                            aria-valuenow="{{ $voucher->usagePercent() }}" aria-valuemin="0"
                                            aria-valuemax="100">
                                            {{ $voucher->usagePercent() }}%
                                        </div>
                                    </div>
                                @endif

                                <div class="text-muted small">
                                    Mã: {{ $voucher->ma_voucher }}
                                </div>

                            </div>
                        </div>
                    @endif

                    {{-- Nút lưu --}}
                    <div class="card">
                        <div class="card-body">

                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-save mr-1"></i>
                                {{ $isEdit ? 'Cập nhật mã giảm giá' : 'Tạo mã giảm giá' }}
                            </button>

                            <a href="{{ route('admin.vouchers.index') }}" class="btn btn-outline-secondary btn-block">
                                Hủy
                            </a>

                        </div>
                    </div>

                </div>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const typeSelect = document.getElementById('loai_giam');
            const discountInput = document.getElementById('gia_tri_giam');
            const discountUnit = document.getElementById('discountUnit');
            const discountHint = document.getElementById('discountHint');
            const maxDiscountGroup = document.getElementById('maxDiscountGroup');
            const maxDiscountInput = document.getElementById('giam_toi_da');

            function updateDiscountFields() {
                const isPercent = typeSelect.value === '1';

                if (isPercent) {
                    discountUnit.textContent = '%';
                    discountInput.max = '100';

                    discountHint.textContent =
                        'Nhập tỷ lệ giảm từ 1 đến 100%.';

                    maxDiscountGroup.style.display = '';
                    maxDiscountInput.disabled = false;
                } else {
                    discountUnit.textContent = 'VNĐ';
                    discountInput.removeAttribute('max');

                    discountHint.textContent =
                        'Nhập số tiền muốn giảm, tính theo VNĐ.';

                    maxDiscountGroup.style.display = 'none';
                    maxDiscountInput.disabled = true;
                }
            }

            typeSelect.addEventListener('change', updateDiscountFields);

            updateDiscountFields();
        });
    </script>
@endpush
