<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tra cứu đơn hàng | AOVU Shop</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 32px 16px; background: #f5f7fb; color: #202939; font: 16px/1.5 "Segoe UI", Arial, sans-serif; }
        .container { width: min(900px, 100%); margin: 0 auto; }
        .card { margin-bottom: 20px; padding: 24px; background: #fff; border-radius: 12px; box-shadow: 0 3px 18px rgba(31,45,61,.07); }
        h1, h2, h3, p { margin-top: 0; }
        h1 { margin-bottom: 8px; font-size: 1.6rem; }
        h2 { font-size: 1.2rem; }
        .muted { color: #728096; }
        .form { display: grid; grid-template-columns: 1fr 1fr auto; align-items: end; gap: 12px; margin-top: 20px; }
        label { display: block; margin-bottom: 6px; font-size: .88rem; font-weight: 600; }
        input { width: 100%; height: 42px; padding: 8px 11px; border: 1px solid #d8deea; border-radius: 6px; font: inherit; }
        button { height: 42px; padding: 0 18px; border: 0; border-radius: 6px; background: #5b6cff; color: #fff; font: inherit; font-weight: 600; cursor: pointer; }
        .alert { margin-top: 16px; padding: 12px 14px; border-radius: 6px; background: #fff4dc; color: #805a13; }
        .summary { display: flex; flex-wrap: wrap; justify-content: space-between; gap: 12px; align-items: center; }
        .badge { display: inline-block; padding: 5px 10px; border-radius: 20px; background: #eceeff; color: #4b5be8; font-size: .9rem; font-weight: 600; }
        .badge-warning { background: #fff4dc; color: #9b6914; }
        .badge-info { background: #e6f4fb; color: #287da2; }
        .badge-success { background: #e7f7ef; color: #23945d; }
        .badge-danger { background: #fdebed; color: #c44450; }
        .badge-primary { background: #eceeff; color: #4b5be8; }
        .item, .history { display: flex; justify-content: space-between; gap: 16px; padding: 12px 0; border-top: 1px solid #edf0f4; }
        .total { display: flex; justify-content: space-between; padding-top: 14px; border-top: 1px solid #edf0f4; font-weight: 700; }
        .history { justify-content: flex-start; }
        .dot { flex: 0 0 10px; width: 10px; height: 10px; margin-top: 7px; border-radius: 50%; background: #5b6cff; }
        @media (max-width: 650px) { .form { grid-template-columns: 1fr; } .card { padding: 18px; } }
    </style>
</head>
<body>
<main class="container">
    <section class="card">
        <h1>Tra cứu đơn hàng</h1>
        <p class="muted mb-0">Nhập mã đơn hàng cùng email hoặc số điện thoại đã dùng khi đặt hàng.</p>
        <form class="form" method="POST" action="{{ route('orders.track.lookup') }}">
            @csrf
            <div>
                <label for="ma_don_hang">Mã đơn hàng</label>
                <input id="ma_don_hang" name="ma_don_hang" value="{{ old('ma_don_hang') }}" maxlength="50" required>
            </div>
            <div>
                <label for="lien_he">Email hoặc số điện thoại</label>
                <input id="lien_he" name="lien_he" value="{{ old('lien_he') }}" maxlength="255" required>
            </div>
            <button type="submit">Tra cứu</button>
        </form>
        @if ($errors->any())
            <div class="alert" role="alert">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>
        @elseif ($searchAttempted && !$order)
            <div class="alert" role="alert">Không tìm thấy đơn hàng khớp với thông tin đã nhập. Vui lòng kiểm tra lại mã đơn và email hoặc số điện thoại.</div>
        @endif
    </section>

    @if ($order)
        <section class="card">
            <div class="summary">
                <div><h2 class="mb-1">Đơn {{ $order->ma_don_hang }}</h2><span class="muted">Đặt lúc {{ $order->ngay_dat ? \Illuminate\Support\Carbon::parse($order->ngay_dat)->format('H:i, d/m/Y') : '—' }}</span></div>
                <span class="badge badge-{{ $order->status_badge }}">{{ $order->status_label }}</span>
            </div>
            @if ((int) $order->trang_thai === \App\OrderStatus::Cancelled->value)
                <div class="alert">Đơn hàng đã hủy. Nếu đơn đã thanh toán, cửa hàng sẽ liên hệ để xử lý hoàn tiền theo phương thức thanh toán.</div>
            @endif
        </section>

        <section class="card">
            <h2>Sản phẩm</h2>
            @forelse ($items as $item)
                <div class="item">
                    <div>{{ $item->ten_san_pham ?: 'Sản phẩm' }} <span class="muted">× {{ $item->so_luong ?? 0 }}</span></div>
                    <strong>{{ number_format((float) ($item->thanh_tien ?? (($item->don_gia ?? 0) * ($item->so_luong ?? 0))), 0, ',', '.') }} ₫</strong>
                </div>
            @empty
                <p class="muted">Chưa có thông tin sản phẩm.</p>
            @endforelse
            <div class="total"><span>Tổng thanh toán</span><span>{{ number_format((float) $order->tong_tien, 0, ',', '.') }} ₫</span></div>
        </section>

        <section class="card">
            <h2>Lịch sử đơn hàng</h2>
            @foreach ($history as $entry)
                <div class="history">
                    <span class="dot" aria-hidden="true"></span>
                    <div>
                        <strong>{{ $entry->status_label }}</strong>
                        <div class="muted">{{ $entry->ngay_cap_nhat ? \Illuminate\Support\Carbon::parse($entry->ngay_cap_nhat)->format('H:i, d/m/Y') : '—' }}</div>
                        @if ($entry->ghi_chu)<div>{{ $entry->ghi_chu }}</div>@endif
                    </div>
                </div>
            @endforeach
        </section>
    @endif
</main>
</body>
</html>
