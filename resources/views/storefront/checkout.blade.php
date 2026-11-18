@extends('layouts.storefront')

@section('title', 'Thanh toán - AOVU Shop')

@section('content')
    @php
        $lines = $quote['lines'] ?? [];
        $grossTotal = (int) ($quote['gross_total'] ?? 0);
        $promotionDiscount = (int) ($quote['promotion_discount'] ?? 0);
        $voucherDiscount = (int) ($quote['voucher_discount'] ?? 0);
        $totalDiscount = (int) ($quote['total_discount'] ?? 0);
        $total = (int) ($quote['total'] ?? 0);

        $formatMoney = fn($amount) => number_format((int) $amount, 0, ',', '.') . '₫';

        $selectedPaymentMethod = old('phuong_thuc_thanh_toan_id', optional($paymentMethods->first())->id);
    @endphp

    <style>
        .checkout-page {
            max-width: 1200px;
            margin: 0 auto;
            padding: 36px 18px 70px;
            color: #202020;
        }

        .checkout-heading {
            margin-bottom: 30px;
        }

        .checkout-heading h1 {
            font-size: 30px;
            font-weight: 800;
            letter-spacing: -1px;
        }

        .checkout-heading p {
            color: #777;
            margin-top: 7px;
        }

        .checkout-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.5fr) minmax(320px, .9fr);
            gap: 28px;
            align-items: start;
        }

        .checkout-card {
            background: #fff;
            border: 1px solid #eaeaea;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
        }

        .checkout-card h2 {
            font-size: 19px;
            font-weight: 750;
            margin-bottom: 20px;
        }

        .checkout-label {
            display: block;
            font-size: 13px;
            font-weight: 650;
            margin-bottom: 7px;
        }

        .checkout-input {
            width: 100%;
            border: 1px solid #dedede;
            border-radius: 9px;
            padding: 12px 14px;
            font-size: 14px;
            outline: none;
            transition: .2s;
            background: #fff;
        }

        .checkout-input:focus {
            border-color: #171717;
            box-shadow: 0 0 0 3px rgba(0, 0, 0, .05);
        }

        .checkout-field {
            margin-bottom: 17px;
        }

        .checkout-error {
            color: #dc2626;
            font-size: 12px;
            margin-top: 5px;
        }

        .checkout-product {
            display: flex;
            gap: 14px;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }

        .checkout-product:last-child {
            border-bottom: 0;
            padding-bottom: 0;
        }

        .checkout-product-image {
            width: 76px;
            height: 90px;
            flex-shrink: 0;
            border-radius: 9px;
            background: #f5f5f5;
            object-fit: cover;
        }

        .checkout-product-info {
            flex: 1;
            min-width: 0;
        }

        .checkout-product-name {
            font-size: 14px;
            font-weight: 700;
            line-height: 1.5;
        }

        .checkout-product-meta {
            color: #888;
            font-size: 12px;
            margin-top: 5px;
        }

        .checkout-product-price {
            font-size: 13px;
            font-weight: 700;
            margin-top: 8px;
        }

        .checkout-payment-option {
            display: flex;
            gap: 12px;
            padding: 15px;
            border: 1px solid #e5e5e5;
            border-radius: 10px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: .2s;
        }

        .checkout-payment-option:has(input:checked) {
            border-color: #171717;
            background: #fafafa;
        }

        .checkout-payment-option input {
            accent-color: #171717;
            margin-top: 4px;
        }

        .checkout-payment-option strong {
            display: block;
            font-size: 14px;
        }

        .checkout-payment-option small {
            display: block;
            color: #888;
            font-size: 12px;
            margin-top: 4px;
        }

        .checkout-summary-row {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 14px;
            font-size: 14px;
            color: #666;
        }

        .checkout-summary-row strong {
            color: #222;
            font-weight: 650;
        }

        .checkout-total {
            border-top: 1px solid #eee;
            padding-top: 18px;
            margin-top: 18px;
        }

        .checkout-total span {
            font-size: 15px;
            font-weight: 750;
            color: #222;
        }

        .checkout-total strong {
            font-size: 23px;
            font-weight: 850;
            color: #d62828;
        }

        .checkout-submit {
            width: 100%;
            border: 0;
            background: #171717;
            color: #fff;
            border-radius: 9px;
            padding: 15px 20px;
            font-weight: 750;
            margin-top: 22px;
            cursor: pointer;
            transition: .2s;
        }

        .checkout-submit:hover {
            background: #383838;
        }

        .checkout-submit:disabled {
            opacity: .5;
            cursor: not-allowed;
        }

        .checkout-back {
            display: inline-block;
            color: #666;
            font-size: 13px;
            margin-top: 17px;
            text-decoration: none;
        }

        .checkout-back:hover {
            color: #111;
        }

        .checkout-alert {
            padding: 14px 16px;
            border-radius: 9px;
            margin-bottom: 20px;
            font-size: 13px;
        }

        .checkout-alert-error {
            background: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }

        .checkout-voucher {
            margin: 18px 0 20px;
            padding: 16px;
            border: 1px dashed #d7d7d7;
            border-radius: 11px;
            background: #fafafa;
        }

        .checkout-voucher-row {
            display: flex;
            gap: 8px;
            align-items: stretch;
        }

        .checkout-voucher-row .checkout-input {
            min-width: 0;
            flex: 1;
            text-transform: uppercase;
        }

        .checkout-voucher-button {
            border: 0;
            border-radius: 9px;
            padding: 0 17px;
            background: #171717;
            color: #fff;
            font-size: 12px;
            font-weight: 750;
            cursor: pointer;
            white-space: nowrap;
        }

        .checkout-voucher-button:hover {
            background: #383838;
        }

        .checkout-voucher-remove-button {
            border: 1px solid #fecaca;
            border-radius: 9px;
            padding: 0 14px;
            background: #fff;
            color: #b91c1c;
            font-size: 12px;
            font-weight: 750;
            cursor: pointer;
            white-space: nowrap;
        }

        .checkout-voucher-remove-button:hover {
            background: #fef2f2;
        }

        .checkout-voucher-remove-button:disabled,
        .checkout-voucher-button:disabled {
            opacity: .65;
            cursor: wait;
        }

        .checkout-voucher-message {
            margin-top: 8px;
            font-size: 12px;
            line-height: 1.5;
        }

        .checkout-voucher-message.success {
            color: #15803d;
        }

        .checkout-voucher-message.error {
            color: #b91c1c;
        }

        @media(max-width: 480px) {
            .checkout-voucher-row {
                flex-direction: column;
            }

            .checkout-voucher-button,
            .checkout-voucher-remove-button {
                min-height: 42px;
            }
        }

        .checkout-bank-hint {
            margin-top: 14px;
            padding: 16px;
            border: 1px solid #dbeafe;
            border-radius: 11px;
            background: #f7fbff;
            color: #243247;
        }

        .checkout-bank-hint-title {
            font-weight: 750;
            margin-bottom: 8px;
            color: #173b70;
        }

        .checkout-bank-hint-row {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding: 5px 0;
            font-size: 13px;
        }

        .checkout-bank-hint-row strong {
            text-align: right;
            overflow-wrap: anywhere;
        }

        .checkout-bank-hint-note {
            margin: 9px 0 0;
            color: #64748b;
            font-size: 12px;
            line-height: 1.55;
        }

        @media(max-width: 800px) {
            .checkout-grid {
                grid-template-columns: 1fr;
            }

            .checkout-heading h1 {
                font-size: 25px;
            }

            .checkout-card {
                padding: 19px;
            }
        }
    </style>

    <div class="checkout-page">

        <div class="checkout-heading">
            <h1>Thanh toán</h1>
            <p>Hoàn tất thông tin để đặt hàng tại AOVU Shop.</p>
        </div>

        @if ($errors->any())
            <div class="checkout-alert checkout-alert-error">
                <strong>Không thể hoàn tất đơn hàng.</strong>
                <ul style="padding-left:18px; margin-top:7px; list-style:disc;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (session('error'))
            <div class="checkout-alert checkout-alert-error">
                {{ session('error') }}
            </div>
        @endif

        <form action="{{ route('shop.checkout.store') }}" method="POST">
            @csrf

            <div class="checkout-grid">

                {{-- THÔNG TIN KHÁCH HÀNG --}}
                <div>

                    <section class="checkout-card">
                        <h2>01. Thông tin nhận hàng</h2>

                        <div class="checkout-field">
                            <label class="checkout-label" for="ten_nguoi_nhan">
                                Họ và tên người nhận *
                            </label>
                            <input class="checkout-input" type="text" id="ten_nguoi_nhan" name="ten_nguoi_nhan"
                                value="{{ old('ten_nguoi_nhan') }}" placeholder="Nhập họ và tên" maxlength="150" required>
                            @error('ten_nguoi_nhan')
                                <div class="checkout-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="checkout-field">
                            <label class="checkout-label" for="email_nguoi_nhan">
                                Email *
                            </label>
                            <input class="checkout-input" type="email" id="email_nguoi_nhan" name="email_nguoi_nhan"
                                value="{{ old('email_nguoi_nhan') }}" placeholder="example@gmail.com" maxlength="150"
                                required>
                            @error('email_nguoi_nhan')
                                <div class="checkout-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="checkout-field">
                            <label class="checkout-label" for="sdt_nguoi_nhan">
                                Số điện thoại *
                            </label>
                            <input class="checkout-input" type="tel" id="sdt_nguoi_nhan" name="sdt_nguoi_nhan"
                                value="{{ old('sdt_nguoi_nhan') }}" placeholder="Nhập số điện thoại" maxlength="20"
                                required>
                            @error('sdt_nguoi_nhan')
                                <div class="checkout-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="checkout-field">
                            <label class="checkout-label" for="dia_chi_nguoi_nhan">
                                Địa chỉ nhận hàng *
                            </label>
                            <textarea class="checkout-input" id="dia_chi_nguoi_nhan" name="dia_chi_nguoi_nhan" rows="3" maxlength="500"
                                placeholder="Số nhà, đường, phường/xã, quận/huyện, tỉnh/thành phố" required>{{ old('dia_chi_nguoi_nhan') }}</textarea>
                            @error('dia_chi_nguoi_nhan')
                                <div class="checkout-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="checkout-field" style="margin-bottom:0;">
                            <label class="checkout-label" for="ghi_chu">
                                Ghi chú đơn hàng
                            </label>
                            <textarea class="checkout-input" id="ghi_chu" name="ghi_chu" rows="3" maxlength="1000"
                                placeholder="Ghi chú thêm cho người bán (nếu có)">{{ old('ghi_chu') }}</textarea>
                            @error('ghi_chu')
                                <div class="checkout-error">{{ $message }}</div>
                            @enderror
                        </div>
                    </section>

                    {{-- PHƯƠNG THỨC THANH TOÁN --}}
                    <section class="checkout-card">
                        <h2>02. Phương thức thanh toán</h2>

                        @forelse ($paymentMethods as $method)
                            @php
                                $methodNameLower = \Illuminate\Support\Str::lower((string) $method->ten_phuong_thuc);
                                $isBankTransferMethod = \Illuminate\Support\Str::contains($methodNameLower, [
                                    'chuyển khoản',
                                    'ngân hàng',
                                    'bank transfer',
                                ]);
                            @endphp
                            <label class="checkout-payment-option">
                                <input type="radio" class="checkout-payment-method" name="phuong_thuc_thanh_toan_id"
                                    value="{{ $method->id }}"
                                    data-bank-transfer="{{ $isBankTransferMethod ? '1' : '0' }}"
                                    {{ (string) $selectedPaymentMethod === (string) $method->id ? 'checked' : '' }}
                                    required>

                                <span>
                                    <strong>{{ $method->ten_phuong_thuc }}</strong>
                                    <small>
                                        {{ $method->mo_ta ?: 'Thanh toán theo phương thức đã chọn.' }}
                                    </small>
                                </span>
                            </label>
                        @empty
                            <div class="checkout-alert checkout-alert-error">
                                Hiện chưa có phương thức thanh toán nào khả dụng.
                                Vui lòng liên hệ cửa hàng.
                            </div>
                        @endforelse

                        <div id="checkout-bank-transfer-hint" class="checkout-bank-hint" style="display:none;">
                            <div class="checkout-bank-hint-title">Thông tin chuyển khoản AOVU Shop</div>
                            <div class="checkout-bank-hint-row">
                                <span>Ngân hàng</span>
                                <strong>{{ $bankTransfer['bank_name'] ?? 'BIDV' }}</strong>
                            </div>
                            <div class="checkout-bank-hint-row">
                                <span>Số tài khoản</span>
                                <strong>{{ $bankTransfer['account_number'] ?? '' }}</strong>
                            </div>
                            <div class="checkout-bank-hint-row">
                                <span>Chủ tài khoản</span>
                                <strong>{{ $bankTransfer['account_name'] ?? '' }}</strong>
                            </div>
                            <p class="checkout-bank-hint-note">
                                Sau khi nhấn “Xác nhận đặt hàng”, AOVU sẽ tạo mã QR riêng theo số tiền và mã đơn hàng.
                                Vui lòng chuyển khoản đúng nội dung hiển thị ở bước tiếp theo. Đơn hàng chỉ được xác nhận đã
                                thanh toán sau khi cửa hàng đối soát.
                            </p>
                        </div>

                        @error('phuong_thuc_thanh_toan_id')
                            <div class="checkout-error">{{ $message }}</div>
                        @enderror
                    </section>

                </div>

                {{-- TÓM TẮT ĐƠN HÀNG --}}
                <div>
                    <section class="checkout-card">
                        <h2>Đơn hàng của bạn</h2>

                        @forelse ($lines as $line)
                            @php
                                $product = $line['product'];

                                $image = $product->anh_dai_dien ?? ($product->hinh_anh ?? null);
                            @endphp

                            <div class="checkout-product">

                                @if ($image)
                                    <img class="checkout-product-image"
                                        src="{{ \Illuminate\Support\Str::startsWith($image, ['http://', 'https://']) ? $image : asset('storage/' . ltrim($image, '/')) }}"
                                        alt="{{ $product->ten_san_pham }}">
                                @else
                                    <div class="checkout-product-image"
                                        style="display:flex;align-items:center;justify-content:center;color:#aaa;font-size:12px;">
                                        AOVU
                                    </div>
                                @endif

                                <div class="checkout-product-info">
                                    <div class="checkout-product-name">
                                        {{ $product->ten_san_pham }}
                                    </div>
                                    @if (!empty($line['variant_label']))
                                        <div class="checkout-product-meta">{{ $line['variant_label'] }}</div>
                                    @endif

                                    <div class="checkout-product-meta">
                                        Số lượng: {{ $line['quantity'] }}
                                    </div>

                                    <div class="checkout-product-price">
                                        {{ $formatMoney($line['line_total']) }}
                                    </div>
                                </div>

                            </div>
                        @empty
                            <p style="color:#888;font-size:13px;">
                                Không có sản phẩm trong đơn hàng.
                            </p>
                        @endforelse

                        {{-- ÁP DỤNG MÃ GIẢM GIÁ --}}
                        <div class="checkout-voucher">
                            <label class="checkout-label" for="voucher_code">Mã giảm giá</label>
                            <div class="checkout-voucher-row">
                                <input class="checkout-input" type="text" id="voucher_code"
                                    value="{{ old('voucher_code', $voucherCode ?? ($quote['voucher_code'] ?? '')) }}"
                                    placeholder="Nhập mã voucher" maxlength="50" autocomplete="off">
                                <button type="button" class="checkout-voucher-button" id="apply-voucher-button">
                                    ÁP DỤNG
                                </button>
                                <button type="button" class="checkout-voucher-remove-button" id="remove-voucher-button"
                                    style="{{ !empty($quote['voucher_code']) ? '' : 'display:none;' }}">
                                    GỠ MÃ
                                </button>
                            </div>
                            <div id="checkout-voucher-message"
                                class="checkout-voucher-message {{ session('success') || !empty($quote['voucher_code']) ? 'success' : ($errors->has('voucher_code') ? 'error' : '') }}"
                                role="status" aria-live="polite">
                                @if (session('success'))
                                    {{ session('success') }}
                                @elseif ($errors->has('voucher_code'))
                                    {{ $errors->first('voucher_code') }}
                                @elseif (!empty($quote['voucher_code']))
                                    Đang áp dụng mã: {{ $quote['voucher_code'] }}
                                @else
                                    Nhập mã giảm giá và nhấn Áp dụng để cập nhật tổng tiền.
                                @endif
                            </div>
                        </div>

                        <div style="border-top:1px solid #eee;margin-top:20px;padding-top:20px;">

                            <div class="checkout-summary-row">
                                <span>Tạm tính</span>
                                <strong id="checkout-gross-total">{{ $formatMoney($grossTotal) }}</strong>
                            </div>

                            <div class="checkout-summary-row" id="checkout-promotion-discount-row"
                                style="{{ $promotionDiscount > 0 ? '' : 'display:none;' }}">
                                <span>Giảm giá khuyến mãi</span>
                                <strong id="checkout-promotion-discount" style="color:#16a34a;">
                                    -{{ $formatMoney($promotionDiscount) }}
                                </strong>
                            </div>

                            <div class="checkout-summary-row" id="checkout-voucher-discount-row"
                                style="{{ $voucherDiscount > 0 ? '' : 'display:none;' }}">
                                <span>Giảm giá voucher</span>
                                <strong id="checkout-voucher-discount" style="color:#16a34a;">
                                    -{{ $formatMoney($voucherDiscount) }}
                                </strong>
                            </div>

                            <div class="checkout-summary-row" id="checkout-voucher-code-row"
                                style="{{ !empty($quote['voucher_code']) ? '' : 'display:none;' }}">
                                <span>Mã voucher</span>
                                <strong id="checkout-voucher-code-label">{{ $quote['voucher_code'] ?? '' }}</strong>
                            </div>

                            <div class="checkout-summary-row">
                                <span>Phí vận chuyển</span>
                                <strong>Miễn phí</strong>
                            </div>

                            <div class="checkout-summary-row checkout-total">
                                <span>Tổng thanh toán</span>
                                <strong id="checkout-total">{{ $formatMoney($total) }}</strong>
                            </div>

                            <p style="font-size:12px;color:#888;margin-top:12px;line-height:1.6;">
                                Bằng việc đặt hàng, bạn xác nhận các thông tin
                                nhận hàng và thanh toán đã chính xác.
                            </p>

                            <button type="submit" class="checkout-submit"
                                {{ count($lines) === 0 || $paymentMethods->isEmpty() ? 'disabled' : '' }}>
                                XÁC NHẬN ĐẶT HÀNG
                            </button>

                            <a href="{{ route('shop.cart') }}" class="checkout-back">
                                ← Quay lại giỏ hàng
                            </a>

                        </div>
                    </section>
                </div>

            </div>
            <input type="hidden" name="voucher_code"
                value="{{ old('voucher_code', $voucherCode ?? ($quote['voucher_code'] ?? '')) }}"
                id="checkout-voucher-code">
        </form>
    </div>

    {{-- Form riêng để áp dụng voucher, không lồng form vào form đặt hàng. --}}
    <form id="apply-voucher-form" action="{{ route('shop.checkout.voucher.apply') }}" method="POST"
        style="display:none;">
        @csrf
        <input type="hidden" name="voucher_code" id="apply-voucher-code">
    </form>
    <form id="remove-voucher-form" action="{{ route('shop.checkout.voucher.remove') }}" method="POST"
        style="display:none;">
        @csrf
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const bankHint = document.getElementById('checkout-bank-transfer-hint');
            const paymentRadios = document.querySelectorAll('.checkout-payment-method');

            function updateBankTransferHint() {
                if (!bankHint) return;
                const selected = document.querySelector('.checkout-payment-method:checked');
                bankHint.style.display = selected && selected.dataset.bankTransfer === '1' ? 'block' : 'none';
            }

            paymentRadios.forEach(function(radio) {
                radio.addEventListener('change', updateBankTransferHint);
            });
            updateBankTransferHint();

            const applyButton = document.getElementById('apply-voucher-button');
            const removeButton = document.getElementById('remove-voucher-button');
            const voucherInput = document.getElementById('voucher_code');
            const applyForm = document.getElementById('apply-voucher-form');
            const removeForm = document.getElementById('remove-voucher-form');
            const applyInput = document.getElementById('apply-voucher-code');
            const orderInput = document.getElementById('checkout-voucher-code');

            if (!applyButton || !removeButton || !voucherInput || !applyForm || !removeForm || !applyInput) return;

            const messageBox = document.getElementById('checkout-voucher-message');

            function formatMoney(amount) {
                return new Intl.NumberFormat('vi-VN').format(Number(amount || 0)) + '₫';
            }

            function setVoucherMessage(message, type) {
                if (!messageBox) return;
                messageBox.textContent = message;
                messageBox.classList.remove('success', 'error');
                if (type) messageBox.classList.add(type);
            }

            function updateSummary(data) {
                const gross = document.getElementById('checkout-gross-total');
                const promotionRow = document.getElementById('checkout-promotion-discount-row');
                const promotionValue = document.getElementById('checkout-promotion-discount');
                const voucherRow = document.getElementById('checkout-voucher-discount-row');
                const voucherValue = document.getElementById('checkout-voucher-discount');
                const voucherCodeRow = document.getElementById('checkout-voucher-code-row');
                const voucherCodeLabel = document.getElementById('checkout-voucher-code-label');
                const total = document.getElementById('checkout-total');

                if (gross) gross.textContent = formatMoney(data.gross_total);
                if (promotionValue) promotionValue.textContent = '-' + formatMoney(data.promotion_discount);
                if (promotionRow) promotionRow.style.display = Number(data.promotion_discount) > 0 ? '' : 'none';
                if (voucherValue) voucherValue.textContent = '-' + formatMoney(data.voucher_discount);
                if (voucherRow) voucherRow.style.display = Number(data.voucher_discount) > 0 ? '' : 'none';
                if (voucherCodeLabel) voucherCodeLabel.textContent = data.voucher_code || '';
                if (voucherCodeRow) voucherCodeRow.style.display = data.voucher_code ? '' : 'none';
                removeButton.style.display = data.voucher_code ? '' : 'none';
                if (total) total.textContent = formatMoney(data.total);
            }

            async function applyVoucher() {
                const code = voucherInput.value.trim().toUpperCase();
                if (!code) {
                    setVoucherMessage('Vui lòng nhập mã giảm giá.', 'error');
                    voucherInput.focus();
                    return;
                }

                const token = applyForm.querySelector('input[name="_token"]')?.value;
                applyButton.disabled = true;
                applyButton.textContent = 'ĐANG KIỂM TRA...';

                try {
                    const response = await fetch(applyForm.action, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': token || ''
                        },
                        body: new URLSearchParams({ voucher_code: code })
                    });

                    const data = await response.json();
                    if (!response.ok || !data.success) {
                        const validationMessage = data.errors?.voucher_code?.[0];
                        throw new Error(validationMessage || data.message || 'Không thể áp dụng mã giảm giá.');
                    }

                    voucherInput.value = data.voucher_code;
                    if (orderInput) orderInput.value = data.voucher_code;
                    updateSummary(data);
                    setVoucherMessage(data.message || 'Áp dụng mã giảm giá thành công.', 'success');
                } catch (error) {
                    setVoucherMessage(error.message || 'Có lỗi xảy ra khi áp dụng mã giảm giá.', 'error');
                } finally {
                    applyButton.disabled = false;
                    applyButton.textContent = 'ÁP DỤNG';
                }
            }

            async function removeVoucher() {
                const token = removeForm.querySelector('input[name="_token"]')?.value;
                removeButton.disabled = true;
                removeButton.textContent = 'ĐANG GỠ...';

                try {
                    const response = await fetch(removeForm.action, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': token || ''
                        },
                        body: new URLSearchParams()
                    });

                    const data = await response.json();
                    if (!response.ok || !data.success) {
                        throw new Error(data.message || 'Không thể gỡ mã giảm giá.');
                    }

                    voucherInput.value = '';
                    if (orderInput) orderInput.value = '';
                    applyInput.value = '';
                    updateSummary(data);
                    setVoucherMessage(data.message || 'Đã gỡ mã giảm giá.', 'success');
                } catch (error) {
                    setVoucherMessage(error.message || 'Có lỗi xảy ra khi gỡ mã giảm giá.', 'error');
                } finally {
                    removeButton.disabled = false;
                    removeButton.textContent = 'GỠ MÃ';
                }
            }

            applyButton.addEventListener('click', applyVoucher);
            removeButton.addEventListener('click', removeVoucher);

            voucherInput.addEventListener('keydown', function(event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    applyVoucher();
                }
            });
        });
    </script>
@endsection
