<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WalletController extends Controller
{
    /**
     * Danh sách ví hoàn tiền (search, filter, pagination).
     */
    public function index(Request $request): View
    {
        $query = DB::table('vi_hoan_tiens')
            ->leftJoin('tai_khoans as accounts', 'accounts.id', '=', 'vi_hoan_tiens.tai_khoan_id')
            ->select([
                'vi_hoan_tiens.*',
                'accounts.ho_ten as account_name',
                'accounts.email as account_email',
                'accounts.so_dien_thoai as account_phone',
                'accounts.anh_dai_dien as account_avatar',
            ]);

        if ($request->filled('q')) {
            $term = trim((string) $request->query('q'));
            $query->where(function ($q) use ($term) {
                $q->where('accounts.ho_ten', 'like', "%{$term}%")
                  ->orWhere('accounts.email', 'like', "%{$term}%")
                  ->orWhere('accounts.so_dien_thoai', 'like', "%{$term}%");
            });
        }

        if ($request->filled('status') && is_numeric($request->query('status'))) {
            $query->where('vi_hoan_tiens.trang_thai', (int) $request->query('status'));
        }

        // Thống kê tổng quan
        $stats = DB::table('vi_hoan_tiens')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN trang_thai = 1 THEN 1 ELSE 0 END) as active')
            ->selectRaw('SUM(CASE WHEN trang_thai = 0 THEN 1 ELSE 0 END) as locked')
            ->selectRaw('SUM(so_du) as total_balance')
            ->first();

        $totalTransactions = DB::table('giao_dich_vis')->count();

        return view('admin.wallets.index', [
            'wallets'           => $query->orderByDesc('vi_hoan_tiens.so_du')
                                         ->orderByDesc('vi_hoan_tiens.id')
                                         ->paginate(10)
                                         ->withQueryString(),
            'totalCount'        => $stats->total ?? 0,
            'activeCount'       => $stats->active ?? 0,
            'lockedCount'       => $stats->locked ?? 0,
            'totalBalance'      => $stats->total_balance ?? 0,
            'totalTransactions' => $totalTransactions,
        ]);
    }

    /**
     * Chi tiết ví + lịch sử giao dịch.
     */
    public function show(int $wallet, Request $request): View
    {
        $walletRecord = DB::table('vi_hoan_tiens')
            ->leftJoin('tai_khoans as accounts', 'accounts.id', '=', 'vi_hoan_tiens.tai_khoan_id')
            ->select([
                'vi_hoan_tiens.*',
                'accounts.ho_ten as account_name',
                'accounts.email as account_email',
                'accounts.so_dien_thoai as account_phone',
                'accounts.anh_dai_dien as account_avatar',
            ])
            ->where('vi_hoan_tiens.id', $wallet)
            ->first();

        abort_unless($walletRecord, 404);

        $transactionsQuery = DB::table('giao_dich_vis')
            ->leftJoin('tai_khoans as admins', 'admins.id', '=', 'giao_dich_vis.nguoi_thuc_hien_id')
            ->leftJoin('don_hangs as orders', 'orders.id', '=', 'giao_dich_vis.don_hang_id')
            ->select([
                'giao_dich_vis.*',
                'admins.ho_ten as admin_name',
                'orders.ma_don_hang as order_number',
            ])
            ->where('giao_dich_vis.vi_id', $wallet);

        if ($request->filled('type') && is_numeric($request->query('type'))) {
            $transactionsQuery->where('giao_dich_vis.loai_giao_dich', (int) $request->query('type'));
        }

        // Thống kê giao dịch ví
        $txStats = DB::table('giao_dich_vis')
            ->where('vi_id', $wallet)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN loai_giao_dich = 1 THEN so_tien ELSE 0 END) as total_refunded')
            ->selectRaw('SUM(CASE WHEN loai_giao_dich = 2 THEN so_tien ELSE 0 END) as total_used')
            ->first();

        return view('admin.wallets.show', [
            'wallet'        => $walletRecord,
            'transactions'  => $transactionsQuery
                ->orderByDesc('giao_dich_vis.ngay_tao')
                ->orderByDesc('giao_dich_vis.id')
                ->paginate(15)
                ->withQueryString(),
            'txStats'       => $txStats,
        ]);
    }

    /**
     * Nạp tiền / điều chỉnh số dư ví (admin thực hiện thủ công).
     */
    public function adjust(Request $request, int $wallet): RedirectResponse
    {
        $data = $request->validate([
            'loai_giao_dich' => ['required', 'integer', 'in:1,2,3'],
            'so_tien'        => ['required', 'numeric', 'min:1000', 'max:999999999'],
            'ghi_chu'        => ['nullable', 'string', 'max:500'],
        ], [
            'loai_giao_dich.required' => 'Vui lòng chọn loại giao dịch.',
            'loai_giao_dich.in'       => 'Loại giao dịch không hợp lệ.',
            'so_tien.required'        => 'Vui lòng nhập số tiền.',
            'so_tien.min'             => 'Số tiền tối thiểu là 1.000 ₫.',
            'so_tien.max'             => 'Số tiền không hợp lệ.',
            'ghi_chu.max'             => 'Ghi chú không được vượt quá 500 ký tự.',
        ]);

        DB::transaction(function () use ($wallet, $data) {
            $record = Wallet::query()
                ->whereKey($wallet)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $record->isActive()) {
                throw ValidationException::withMessages([
                    'so_tien' => 'Ví đang bị khóa, không thể thực hiện giao dịch.',
                ]);
            }

            $amount         = (float) $data['so_tien'];
            $transactionType = (int) $data['loai_giao_dich'];

            // Nếu sử dụng từ ví hoặc điều chỉnh giảm => trừ tiền
            $isDebit = $transactionType === Wallet::TRANSACTION_USAGE;

            if ($isDebit && $record->so_du < $amount) {
                throw ValidationException::withMessages([
                    'so_tien' => 'Số dư ví không đủ để thực hiện giao dịch này (hiện tại: ' . number_format($record->so_du, 0, ',', '.') . ' ₫).',
                ]);
            }

            $balanceBefore = (float) $record->so_du;
            $balanceAfter  = $isDebit
                ? $balanceBefore - $amount
                : $balanceBefore + $amount;

            $record->update([
                'so_du'        => $balanceAfter,
                'ngay_cap_nhat' => now(),
            ]);

            WalletTransaction::create([
                'vi_id'              => $record->id,
                'loai_giao_dich'     => $transactionType,
                'so_tien'            => $isDebit ? -$amount : $amount,
                'so_du_truoc'        => $balanceBefore,
                'so_du_sau'          => $balanceAfter,
                'ghi_chu'            => trim((string) ($data['ghi_chu'] ?? '')) ?: null,
                'nguoi_thuc_hien_id' => null, // Chưa có auth, có thể thêm sau
                'ngay_tao'           => now(),
            ]);
        });

        $typeLabels = Wallet::transactionTypes();
        $typeLabel  = $typeLabels[(int) $data['loai_giao_dich']] ?? 'Giao dịch';

        return redirect()
            ->route('admin.wallets.show', $wallet)
            ->with('success', "Đã thực hiện: {$typeLabel} – " . number_format($data['so_tien'], 0, ',', '.') . ' ₫.');
    }

    /**
     * Toggle trạng thái ví (khóa / mở khóa).
     */
    public function toggleStatus(int $wallet): JsonResponse|RedirectResponse
    {
        $record = Wallet::findOrFail($wallet);

        $record->update([
            'trang_thai'    => $record->isActive() ? Wallet::STATUS_LOCKED : Wallet::STATUS_ACTIVE,
            'ngay_cap_nhat' => now(),
        ]);

        $label = $record->isActive() ? 'mở khóa' : 'khóa';

        if (request()->expectsJson()) {
            return response()->json([
                'success'    => true,
                'trang_thai' => $record->trang_thai,
                'label'      => $record->statusLabel(),
                'badge'      => $record->statusBadge(),
                'message'    => "Đã {$label} ví.",
            ]);
        }

        return redirect()
            ->route('admin.wallets.index')
            ->with('success', "Đã {$label} ví.");
    }

    /**
     * Export danh sách giao dịch ra CSV.
     */
    public function exportTransactions(int $wallet): StreamedResponse
    {
        $walletRecord = Wallet::findOrFail($wallet);

        $transactions = DB::table('giao_dich_vis')
            ->leftJoin('tai_khoans as admins', 'admins.id', '=', 'giao_dich_vis.nguoi_thuc_hien_id')
            ->leftJoin('don_hangs as orders', 'orders.id', '=', 'giao_dich_vis.don_hang_id')
            ->select([
                'giao_dich_vis.*',
                'admins.ho_ten as admin_name',
                'orders.ma_don_hang as order_number',
            ])
            ->where('giao_dich_vis.vi_id', $wallet)
            ->orderByDesc('giao_dich_vis.ngay_tao')
            ->get();

        $filename = 'wallet_' . $walletRecord->id . '_transactions_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($transactions) {
            $handle = fopen('php://output', 'w');

            // BOM cho Excel đọc UTF-8 đúng
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'Mã GD',
                'Loại giao dịch',
                'Số tiền',
                'Số dư trước',
                'Số dư sau',
                'Đơn hàng liên quan',
                'Ghi chú',
                'Người thực hiện',
                'Ngày tạo',
            ]);

            $typeLabels = Wallet::transactionTypes();

            foreach ($transactions as $tx) {
                fputcsv($handle, [
                    '#GD-' . str_pad((string) $tx->id, 6, '0', STR_PAD_LEFT),
                    $typeLabels[$tx->loai_giao_dich] ?? 'Không xác định',
                    number_format($tx->so_tien, 0, ',', '.') . ' ₫',
                    number_format($tx->so_du_truoc, 0, ',', '.') . ' ₫',
                    number_format($tx->so_du_sau, 0, ',', '.') . ' ₫',
                    $tx->order_number ?? '—',
                    $tx->ghi_chu ?? '',
                    $tx->admin_name ?? 'Hệ thống',
                    $tx->ngay_tao ? \Illuminate\Support\Carbon::parse($tx->ngay_tao)->format('d/m/Y H:i') : '—',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=utf-8',
        ]);
    }
}
