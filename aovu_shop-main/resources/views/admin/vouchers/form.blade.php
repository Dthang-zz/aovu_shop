@extends('layouts.admin')

@section('title', $formTitle)
@section('page-heading', $formTitle)
@section('page-subheading', 'Nhập thông tin mã khuyến mãi.')
@section('breadcrumb')
    <a href="{{ route('admin.vouchers.index') }}">Khuyến mãi</a>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-8">
        <form method="POST" action="{{ $voucher->exists ? route('admin.vouchers.update', $voucher) : route('admin.vouchers.store') }}" id="voucherForm">
            @csrf
            @if ($voucher->exists)
                @method('PUT')
            @endif

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-info-circle text-primary mr-2"></i>Thông tin voucher</h3>
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

                    <div class="form-group">
                        <label for="ma_voucher">Mã voucher <span class="text-danger">*</span></label>
                        <input id="ma_voucher" type="text" name="ma_voucher" value="{{ old('ma_voucher', $voucher->ma_voucher) }}" class="form-control @error('ma_voucher') is-invalid @enderror" maxlength="50" required autofocus placeholder="Ví dụ: THANG10, WELCOME50" style="text-transform: uppercase">
                        @error('ma_voucher')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <small class="form-text text-muted">Chỉ chứa chữ cái, số, dấu gạch ngang và gạch dưới.</small>
                    </div>

                    <div class="form-group">
                        <label for="mo_ta">Mô tả</label>
                        <input id="mo_ta" type="text" name="mo_ta" value="{{ old('mo_ta', $voucher->mo_ta) }}" class="form-control @error('mo_ta') is-invalid @enderror" maxlength="255" placeholder="Ví dụ: Giảm giá tháng 10 cho toàn bộ sản phẩm">
                        @error('mo_ta')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="loai_giam">Loại giảm giá <span class="text-danger">*</span></label>
                                <select id="loai_giam" name="loai_giam" class="form-control @error('loai_giam') is-invalid @enderror" required>
                                    @foreach (\App\Models\Voucher::discountTypes() as $value => $label)
                                        <option value="{{ $value }}" @selected((string) old('loai_giam', $voucher->loai_giam) === (string) $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('loai_giam')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="gia_tri_giam">Giá trị giảm <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input id="gia_tri_giam" type="number" name="gia_tri_giam" value="{{ old('gia_tri_giam', $voucher->gia_tri_giam) }}" class="form-control @error('gia_tri_giam') is-invalid @enderror" min="1" required placeholder="Ví dụ: 15 (%) hoặc 50000 (₫)">
                                    <div class="input-group-append">
                                        <span class="input-group-text" id="discountUnit">%</span>
                                    </div>
                                    @error('gia_tri_giam')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <small class="form-text text-muted" id="discountHint">Giá trị từ 1 đến 100.</small>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group" id="giamToiDaGroup">
                                <label for="giam_toi_da">Giảm tối đa (₫)</label>
                                <input id="giam_toi_da" type="number" name="giam_toi_da" value="{{ old('giam_toi_da', $voucher->giam_toi_da) }}" class="form-control @error('giam_toi_da') is-invalid @enderror" min="0" placeholder="Để trống nếu không giới hạn">
                                @error('giam_toi_da')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <small class="form-text text-muted">Áp dụng khi loại giảm là phần trăm.</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="don_toi_thieu">Đơn tối thiểu (₫)</label>
                                <input id="don_toi_thieu" type="number" name="don_toi_thieu" value="{{ old('don_toi_thieu', $voucher->don_toi_thieu) }}" class="form-control @error('don_toi_thieu') is-invalid @enderror" min="0" placeholder="Để trống nếu không yêu cầu">
                                @error('don_toi_thieu')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="so_luong">Số lượng phát hành</label>
                        <input id="so_luong" type="number" name="so_luong" value="{{ old('so_luong', $voucher->so_luong) }}" class="form-control @error('so_luong') is-invalid @enderror" min="1" placeholder="Để trống nếu không giới hạn">
                        @error('so_luong')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        @if ($voucher->exists && $voucher->da_su_dung > 0)
                            <small class="form-text text-warning">
                                <i class="fas fa-info-circle mr-1"></i>Đã sử dụng {{ number_format($voucher->da_su_dung) }} lượt. Số lượng mới không được nhỏ hơn con số này.
                            </small>
                        @endif
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="ngay_bat_dau">Ngày bắt đầu</label>
                                <input id="ngay_bat_dau" type="datetime-local" name="ngay_bat_dau" value="{{ old('ngay_bat_dau', $voucher->ngay_bat_dau?->format('Y-m-d\TH:i')) }}" class="form-control @error('ngay_bat_dau') is-invalid @enderror">
                                @error('ngay_bat_dau')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="ngay_ket_thuc">Ngày kết thúc</label>
                                <input id="ngay_ket_thuc" type="datetime-local" name="ngay_ket_thuc" value="{{ old('ngay_ket_thuc', $voucher->ngay_ket_thuc?->format('Y-m-d\TH:i')) }}" class="form-control @error('ngay_ket_thuc') is-invalid @enderror">
                                @error('ngay_ket_thuc')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-0">
                        <label for="trang_thai">Trạng thái <span class="text-danger">*</span></label>
                        <select id="trang_thai" name="trang_thai" class="form-control @error('trang_thai') is-invalid @enderror" required>
                            <option value="1" @selected((string) old('trang_thai', (int) $voucher->trang_thai) === '1')>Đang hoạt động</option>
                            <option value="0" @selected((string) old('trang_thai', (int) $voucher->trang_thai) === '0')>Tạm ngưng</option>
                        </select>
                        @error('trang_thai')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="card-footer bg-white d-flex justify-content-between">
                    <a href="{{ route('admin.vouchers.index') }}" class="btn btn-default"><i class="fas fa-arrow-left mr-1"></i>Quay lại</a>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>{{ $voucher->exists ? 'Lưu thay đổi' : 'Tạo voucher' }}</button>
                </div>
            </div>
        </form>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title"><i class="far fa-lightbulb text-warning mr-2"></i>Lưu ý</h3></div>
            <div class="card-body">
                <p class="text-muted"><strong>Loại giảm theo %:</strong> Nhập giá trị từ 1–100. Có thể đặt mức giảm tối đa để giới hạn số tiền giảm.</p>
                <p class="text-muted"><strong>Loại giảm cố định:</strong> Nhập số tiền giảm trực tiếp (VNĐ). Trường "Giảm tối đa" sẽ không áp dụng.</p>
                <p class="text-muted mb-0">Không thể xóa voucher đã được sử dụng trong đơn hàng. Hãy chuyển trạng thái sang "Tạm ngưng" thay vì xóa.</p>
            </div>
        </div>

        @if ($voucher->exists)
        <div class="card">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-chart-bar text-info mr-2"></i>Thống kê sử dụng</h3></div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-1">
                    <span class="text-muted small">Đã sử dụng</span>
                    <span class="font-weight-bold">{{ number_format($voucher->da_su_dung ?? 0) }} / {{ $voucher->so_luong !== null ? number_format($voucher->so_luong) : '∞' }}</span>
                </div>
                @if ($voucher->usagePercent() !== null)
                <div class="progress mb-3" style="height: 6px">
                    @php
                        $pct = $voucher->usagePercent();
                        $barColor = $pct >= 90 ? 'bg-danger' : ($pct >= 70 ? 'bg-warning' : 'bg-success');
                    @endphp
                    <div class="progress-bar {{ $barColor }}" style="width: {{ $pct }}%"></div>
                </div>
                @endif
                <div class="d-flex justify-content-between mb-1">
                    <span class="text-muted small">Trạng thái</span>
                    <span class="badge badge-{{ $voucher->statusBadge() }} px-2 py-1">{{ $voucher->statusLabel() }}</span>
                </div>
                @if ($voucher->remainingQuantity() !== null)
                <div class="d-flex justify-content-between">
                    <span class="text-muted small">Còn lại</span>
                    <span class="font-weight-bold {{ $voucher->remainingQuantity() === 0 ? 'text-danger' : 'text-success' }}">{{ number_format($voucher->remainingQuantity()) }}</span>
                </div>
                @endif
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const loaiGiam     = document.getElementById('loai_giam');
    const giaTriGiam   = document.getElementById('gia_tri_giam');
    const discountUnit = document.getElementById('discountUnit');
    const discountHint = document.getElementById('discountHint');
    const giamToiDa    = document.getElementById('giamToiDaGroup');

    function syncDiscountType() {
        const isPercent = loaiGiam.value === '{{ \App\Models\Voucher::TYPE_PERCENT }}';
        if (isPercent) {
            discountUnit.textContent = '%';
            discountHint.textContent = 'Giá trị từ 1 đến 100.';
            giaTriGiam.max = 100;
            giamToiDa.style.opacity = '1';
            giamToiDa.querySelector('input').disabled = false;
        } else {
            discountUnit.textContent = '₫';
            discountHint.textContent = 'Nhập số tiền giảm (VNĐ).';
            giaTriGiam.removeAttribute('max');
            giamToiDa.style.opacity = '0.5';
            giamToiDa.querySelector('input').disabled = true;
        }
    }

    loaiGiam.addEventListener('change', syncDiscountType);
    syncDiscountType();
});
</script>
@endsection
