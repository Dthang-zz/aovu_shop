<?php

namespace App\Http\Middleware;


use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $account = $request->user();
        if (! $account) return redirect()->route('login');
        $roleName = mb_strtolower(trim((string) $account->chucVu?->ten_chuc_vu));
        $normalized = match ($roleName) {
            'admin', 'administrator', 'quản trị', 'quản trị viên' => 'admin',
            'người dùng', 'user' => 'user',
            'khách hàng', 'customer' => 'customer',
            default => $roleName,
        };
        if ($normalized !== $role || ! $account->trang_thai) {
            abort(403, 'Bạn không có quyền truy cập khu vực này.');
        }
        return $next($request);
    }
}
