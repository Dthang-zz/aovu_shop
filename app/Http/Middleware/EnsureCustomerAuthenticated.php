<?php

namespace App\Http\Middleware;

use App\Models\TaiKhoan;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCustomerAuthenticated
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $customerId = $request->session()->get('customer_id');

        if (! $customerId) {
            return redirect()
                ->route('customer.login')
                ->with('info', 'Vui lòng đăng nhập tài khoản để tiếp tục.');
        }

        $customer = TaiKhoan::query()->find($customerId);

        if (! $customer || $customer->trang_thai === false) {
            $request->session()->forget('customer_id');

            return redirect()
                ->route('customer.login')
                ->withErrors(['email' => 'Tài khoản không tồn tại hoặc đã bị khóa.']);
        }

        return $next($request);
    }
}
