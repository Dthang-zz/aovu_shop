@extends('layouts.storefront')

@section('title', 'Chuyển khoản đơn hàng - AOVU Shop')

@section('content')
    @php
        $transfer = $confirmation['bank_transfer'];
        $formatMoney = fn ($amount) => number_format((int) $amount, 0, ',', '.') . '₫';
    @endphp

    <style>
        .bank-result-page { max-width: 980px; margin: 0 auto; padding: 42px 18px 76px; color: #202020; }
        .bank-result-card { background: #fff; border: 1px solid #e9e9e9; border-radius: 18px; padding: 28px; margin-bottom: 20px; }
        .bank-result-heading { text-align: center; margin-bottom: 24px; }
        .bank-result-icon { width: 58px; height: 58px; border-radius: 50%; display: grid; place-items: center; margin: 0 auto 14px; background: #fff7e6; color: #b7791f; font-size: 27px; }
        .bank-result-heading h1 { font-size: 27px; font-weight: 850; margin: 0 0 8px; }
        .bank-result-heading p { color: #6b7280; margin: 0; line-height: 1.6; }
        .bank-result-grid { display: grid; grid-template-columns: minmax(240px, .9fr) minmax(300px, 1.1fr); gap: 28px; align-items: center; }
        .bank-qr-wrap { text-align: center; }
        .bank-qr { width: min(100%, 340px); display: block; margin: 0 auto; border: 1px solid #eee; border-radius: 12px; background: white; }
        .bank-qr-caption { font-size: 12px; color: #6b7280; margin-top: 10px; }
        .bank-detail-list { border: 1px solid #e8edf3; border-radius: 12px; overflow: hidden; }
        .bank-detail-row { display: flex; align-items: flex-start; justify-content: space-between; gap: 14px; padding: 13px 15px; border-bottom: 1px solid #edf0f3; font-size: 14px; }
        .bank-detail-row:last-child { border-bottom: 0; }
        .bank-detail-row span { color: #6b7280; }
        .bank-detail-row strong { text-align: right; overflow-wrap: anywhere; }
        .bank-detail-row.amount { background: #f8fafc; }
        .bank-detail-row.amount strong { color: #d62828; font-size: 20px; }
        .bank-copy { border: 1px solid #d1d5db; background: white; border-radius: 7px; color: #374151; padding: 5px 9px; font-size: 12px; cursor: pointer; white-space: nowrap; }
        .bank-copy:hover { background: #f3f4f6; }
        .bank-warning { margin-top: 18px; padding: 15px 16px; border-radius: 10px; background: #fff8e8; border: 1px solid #f5dfaa; color: #7c5918; font-size: 13px; line-height: 1.65; }
        .bank-actions { display: flex; justify-content: center; gap: 10px; flex-wrap: wrap; margin-top: 24px; }
        .bank-action-link { display: inline-block; border-radius: 9px; padding: 12px 18px; text-decoration: none; font-weight: 700; font-size: 13px; }
        .bank-action-primary { background: #171717; color: #fff; }
        .bank-action-secondary { border: 1px solid #ddd; color: #333; }
        @media (max-width: 720px) { .bank-result-card { padding: 20px; } .bank-result-grid { grid-template-columns: 1fr; } .bank-result-heading h1 { font-size: 23px; } }
    </style>

    <div class="bank-result-page">
        <div class="bank-result-heading">
            <div class="bank-result-icon" aria-hidden="true">₫</div>
            <h1>Đơn hàng đã được tạo!</h1>
            <p>Vui lòng quét mã QR hoặc chuyển khoản theo thông tin bên dưới để hoàn tất thanh toán.</p>
        </div>

        <section class="bank-result-card">
            <div class="bank-result-grid">
                <div class="bank-qr-wrap">
                    <img class="bank-qr" src="{{ $transfer['qr_url'] }}" alt="Mã QR chuyển khoản cho đơn hàng {{ $confirmation['order_code'] }}">
                    <div class="bank-qr-caption">Mở ứng dụng ngân hàng và quét mã QR này</div>
                </div>

                <div>
                    <h2 style="font-size:19px;font-weight:800;margin:0 0 15px;">Thông tin thanh toán</h2>
                    <div class="bank-detail-list">
                        <div class="bank-detail-row">
                            <span>Mã đơn hàng</span>
                            <strong>{{ $confirmation['order_code'] }}</strong>
                        </div>
                        <div class="bank-detail-row">
                            <span>Ngân hàng</span>
                            <strong>{{ $transfer['bank_name'] }}</strong>
                        </div>
                        <div class="bank-detail-row">
                            <span>Số tài khoản</span>
                            <strong>{{ $transfer['account_number'] }}</strong>
                        </div>
                        <div class="bank-detail-row">
                            <span>Chủ tài khoản</span>
                            <strong>{{ $transfer['account_name'] }}</strong>
                        </div>
                        <div class="bank-detail-row">
                            <span>Nội dung chuyển khoản</span>
                            <strong>{{ $transfer['transfer_content'] }}</strong>
                            <button type="button" class="bank-copy" data-copy="{{ $transfer['transfer_content'] }}">Sao chép</button>
                        </div>
                        <div class="bank-detail-row amount">
                            <span>Số tiền cần chuyển</span>
                            <strong>{{ $formatMoney($transfer['amount']) }}</strong>
                        </div>
                    </div>
                    <div class="bank-warning">
                        <strong>Lưu ý:</strong> Vui lòng chuyển đúng số tiền và giữ nguyên nội dung chuyển khoản.
                        Đơn hàng hiện ở trạng thái <strong>Chưa thanh toán</strong>; AOVU Shop sẽ xác nhận sau khi kiểm tra giao dịch thực nhận.
                    </div>
                </div>
            </div>

            <div class="bank-actions">
                <a href="{{ route('shop.products.index') }}" class="bank-action-link bank-action-primary">Tiếp tục mua sắm</a>
                <a href="{{ route('shop.cart') }}" class="bank-action-link bank-action-secondary">Về cửa hàng</a>
            </div>
        </section>
    </div>

    <script>
        document.addEventListener('click', function (event) {
            const button = event.target.closest('[data-copy]');
            if (!button) return;
            const value = button.getAttribute('data-copy');
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(value).then(() => {
                    const oldText = button.textContent;
                    button.textContent = 'Đã sao chép';
                    setTimeout(() => button.textContent = oldText, 1400);
                });
            } else {
                const input = document.createElement('textarea');
                input.value = value;
                document.body.appendChild(input);
                input.select();
                document.execCommand('copy');
                input.remove();
                button.textContent = 'Đã sao chép';
            }
        });
    </script>
@endsection
