<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Đăng ký | {{ config('app.name', 'AOVU Shop') }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 24px; background: #fbfaf8; color: #282421; font-family: Inter, "Segoe UI", Arial, sans-serif; }
        .auth-card { width: min(100%, 460px); padding: 38px; background: #fff; border: 1px solid #eeeae5; }
        .brand { display: block; margin-bottom: 28px; color: #282421; font-family: Georgia, serif; font-size: 28px; font-weight: 700; letter-spacing: .06em; text-align: center; text-decoration: none; }
        h1 { margin: 0 0 8px; font-family: Georgia, serif; font-size: 30px; font-weight: 500; text-align: center; }
        .intro { margin: 0 0 26px; color: #77716c; font-size: 14px; text-align: center; }
        .field { display: grid; gap: 7px; margin-bottom: 17px; }
        label { font-size: 13px; font-weight: 650; }
        input { width: 100%; min-height: 44px; padding: 10px 12px; border: 1px solid #ded9d3; border-radius: 2px; font: inherit; }
        input:focus { outline: 2px solid #8c6b55; outline-offset: 1px; }
        .button { width: 100%; min-height: 45px; margin-top: 5px; border: 1px solid #282421; background: #282421; color: #fff; cursor: pointer; font: inherit; font-size: 14px; font-weight: 700; }
        .errors { margin-bottom: 18px; padding: 12px; background: #fff3f1; color: #8d3026; font-size: 13px; }
        .auth-footer { margin: 22px 0 0; color: #77716c; font-size: 13px; text-align: center; }
        .auth-footer a, .back-link { color: #72523d; font-weight: 650; }
        .back-link { display: block; margin-top: 18px; font-size: 13px; text-align: center; text-decoration: none; }
        @media (max-width: 480px) { .auth-card { padding: 28px 22px; } }
    </style>
</head>
<body>
    <main class="auth-card">
        <a class="brand" href="{{ route('storefront.index') }}">{{ config('app.name', 'AOVU') }}</a>
        <h1>Tạo tài khoản</h1>
        <p class="intro">Đăng ký tài khoản khách hàng để mua sắm thuận tiện hơn.</p>

        @if ($errors->any())
            <div class="errors" role="alert">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('register') }}">
            @csrf
            <div class="field">
                <label for="ho_ten">Họ và tên</label>
                <input id="ho_ten" name="ho_ten" type="text" value="{{ old('ho_ten') }}" maxlength="50" autocomplete="name" required autofocus>
            </div>
            <div class="field">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required>
            </div>
            <div class="field">
                <label for="password">Mật khẩu</label>
                <input id="password" name="password" type="password" minlength="8" autocomplete="new-password" required>
            </div>
            <div class="field">
                <label for="password_confirmation">Xác nhận mật khẩu</label>
                <input id="password_confirmation" name="password_confirmation" type="password" minlength="8" autocomplete="new-password" required>
            </div>
            <button class="button" type="submit">Đăng ký</button>
        </form>

        <p class="auth-footer">Đã có tài khoản? <a href="{{ route('login') }}">Đăng nhập</a></p>
        <a class="back-link" href="{{ route('storefront.index') }}">← Quay lại cửa hàng</a>
    </main>
</body>
</html>
