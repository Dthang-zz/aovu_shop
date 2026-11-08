@extends('layouts.admin')

@section('title', $voucher->exists ? 'Chỉnh sửa mã giảm giá' : 'Tạo mã giảm giá')
@section('page-heading', $voucher->exists ? 'Chỉnh sửa mã '.$voucher->ma_voucher : 'Tạo mã giảm giá')
@section('breadcrumb')
    <a href="{{ route('admin.promotions.index') }}">Khuyến mãi</a>
@endsection

@section('content')
    @php
        $discountType = (int) old('loai_giam', $voucher->loai_giam ?? \App\Models\Voucher::TYPE_PERCENT);
        $isPercent = $discountType === \App\Models\Voucher::TYPE_PERCENT;
    @endphp
    <div class="row">
        <div class="col-lg-8">
            <form method="POST" action="{{ $voucher->exists ? route('admin.promotions.update', $voucher) : route('admin.promotions.store') }}">
                @csrf
                @if ($voucher->exists)
                    @method('PUT')
                @endif
                <div class="card">
                    <div class="card-header"><h3 class="card-title">Thông tin mã giảm giá</h3></div>
                    <div class="card-body">
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                            </div>
                        @endif
                        <div class="form-row">
                            <div class="col-md-5 form-group">
                                <label for="ma_voucher">Mã giảm giá</label>
                                <input id="ma_voucher" name="ma_voucher" class="form-control text-uppercase @error('ma_voucher') is-invalid @enderror" maxlength="50" required placeholder="VD: SALE10" value="{{ old('ma_voucher', $voucher->ma_voucher) }}">
                                <small class="form-text text-muted">Khách nhập mã này ở bước thanh toán. Không phân biệt hoa thường.</small>
                            </div>
                            <div class="col-md-7 form-group">
                                <label for="mo_ta">Mô tả</label>
                                <input id="mo_ta" name="mo_ta" class="form-control @error('mo_ta') is-invalid @enderror" maxlength="255" placeholder="VD: Giảm 10% cho đơn từ 300.000 ₫" value="{{ old('mo_ta', $voucher->mo_ta) }}">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="d-block">Hình thức giảm giá</label>
                            @foreach (\App\Models\Voucher::types() as $type => $label)
                                <div class="custom-control custom-radio custom-control-inline">
                                    <input type="radio" id="loai_giam_{{ $type }}" name="loai_giam" value="{{ $type }}" class="custom-control-input" data-discount-type @checked($discountType === $type)>
                                    <label class="custom-control-label" for="loai_giam_{{ $type }}">{{ $label }}</label>
                                </div>
                            @endforeach
                        </div>

                        <div class="form-row">
                            <div class="col-md-4 form-group">
                                <label for="gia_tri_giam" data-discount-value-label>{{ $isPercent ? 'Mức giảm (%)' : 'Số tiền giảm (₫)' }}</label>
                                <input id="gia_tri_giam" type="number" name="gia_tri_giam" class="form-control @error('gia_tri_giam') is-invalid @enderror" min="1" step="1" @if ($isPercent) max="100" @endif required value="{{ old('gia_tri_giam', $voucher->gia_tri_giam !== null ? (int) $voucher->gia_tri_giam : null) }}">
                            </div>
                            <div class="col-md-4 form-group" data-percent-only @if (! $isPercent) hidden @endif>
                                <label for="giam_toi_da">Giảm tối đa (₫)</label>
                                <input id="giam_toi_da" type="number" name="giam_toi_da" class="form-control @error('giam_toi_da') is-invalid @enderror" min="1" step="1" placeholder="Không giới hạn" value="{{ old('giam_toi_da', $voucher->giam_toi_da) }}" @disabled(! $isPercent)>
                            </div>
                            <div class="col-md-4 form-group">
                                <label for="don_toi_thieu">Đơn tối thiểu (₫)</label>
                                <input id="don_toi_thieu" type="number" name="don_toi_thieu" class="form-control @error('don_toi_thieu') is-invalid @enderror" min="0" step="1" placeholder="Không yêu cầu" value="{{ old('don_toi_thieu', $voucher->don_toi_thieu) }}">
                                <small class="form-text text-muted">Tổng tiền hàng phải đạt mức này mới dùng được mã.</small>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="col-md-4 form-group">
                                <label for="so_luong">Số lượt sử dụng</label>
                                <input id="so_luong" type="number" name="so_luong" class="form-control @error('so_luong') is-invalid @enderror" min="{{ max(1, (int) ($voucher->da_su_dung ?? 0)) }}" step="1" placeholder="Không giới hạn" value="{{ old('so_luong', $voucher->so_luong) }}">
                                @if ($voucher->exists)
                                    <small class="form-text text-muted">Đã dùng {{ number_format($voucher->da_su_dung ?? 0) }} lượt.</small>
                                @endif
                            </div>
                            <div class="col-md-4 form-group">
                                <label for="ngay_bat_dau">Bắt đầu</label>
                                <input id="ngay_bat_dau" type="datetime-local" name="ngay_bat_dau" class="form-control @error('ngay_bat_dau') is-invalid @enderror" required value="{{ old('ngay_bat_dau', $voucher->ngay_bat_dau?->format('Y-m-d\TH:i')) }}">
                            </div>
                            <div class="col-md-4 form-group">
                                <label for="ngay_ket_thuc">Kết thúc</label>
                                <input id="ngay_ket_thuc" type="datetime-local" name="ngay_ket_thuc" class="form-control @error('ngay_ket_thuc') is-invalid @enderror" required value="{{ old('ngay_ket_thuc', $voucher->ngay_ket_thuc?->format('Y-m-d\TH:i')) }}">
                            </div>
                        </div>

                        <div class="custom-control custom-switch">
                            <input type="hidden" name="trang_thai" value="0">
                            <input id="trang_thai" type="checkbox" name="trang_thai" value="1" class="custom-control-input" @checked(old('trang_thai', $voucher->trang_thai ?? true))>
                            <label class="custom-control-label" for="trang_thai">Bật mã giảm giá</label>
                        </div>
                    </div>
                    <div class="card-footer d-flex justify-content-between">
                        <a href="{{ route('admin.promotions.index') }}" class="btn btn-default">Hủy</a>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>Lưu mã giảm giá</button>
                    </div>
                </div>
            </form>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><h3 class="card-title"><i class="fas fa-info-circle text-primary mr-2"></i>Cách áp dụng</h3></div>
                <div class="card-body small text-muted">
                    <p>Mã được trừ trên <strong>tổng tiền hàng</strong> của giỏ hàng, mỗi đơn chỉ dùng một mã.</p>
                    <p><strong>Giảm theo %</strong>: có thể giới hạn số tiền giảm tối đa.</p>
                    <p class="mb-0"><strong>Giảm theo số tiền</strong>: trừ thẳng, không vượt quá tổng tiền hàng.</p>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        (function () {
            var radios = document.querySelectorAll('[data-discount-type]');
            if (!radios.length) return;

            function toggleDiscountFields() {
                var selected = document.querySelector('[data-discount-type]:checked');
                var isPercent = !selected || selected.value === '{{ \App\Models\Voucher::TYPE_PERCENT }}';
                var valueInput = document.getElementById('gia_tri_giam');
                var capInput = document.getElementById('giam_toi_da');

                document.querySelector('[data-discount-value-label]').textContent = isPercent ? 'Mức giảm (%)' : 'Số tiền giảm (₫)';
                if (isPercent) {
                    valueInput.max = 100;
                } else {
                    valueInput.removeAttribute('max');
                }
                document.querySelector('[data-percent-only]').hidden = !isPercent;
                capInput.disabled = !isPercent;
            }

            radios.forEach(function (radio) {
                radio.addEventListener('change', toggleDiscountFields);
            });
            toggleDiscountFields();
        }());
    </script>
@endsection
