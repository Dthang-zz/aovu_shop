<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\VoucherRequest;
use App\Models\Voucher;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VoucherController extends Controller
{
    /**
     * Danh sách voucher (có filter, search, pagination).
     */
    public function index(Request $request): View
    {
        $vouchers = Voucher::query()
            ->search($request->query('q'))
            ->filterStatus($request->query('status'))
            ->filterType($request->query('type'))
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $stats = Voucher::stats();

        return view('admin.vouchers.index', [
            'vouchers'    => $vouchers,
            'totalCount'  => $stats['total'],
            'activeCount' => $stats['active'],
            'totalUsed'   => $stats['total_used'],
        ]);
    }

    /**
     * Hiển thị form tạo mới voucher.
     */
    public function create(): View
    {
        return view('admin.vouchers.form', [
            'voucher'   => new Voucher([
                'trang_thai' => true,
                'loai_giam'  => Voucher::TYPE_PERCENT,
                'da_su_dung' => 0,
            ]),
            'formTitle' => 'Thêm khuyến mãi',
        ]);
    }

    /**
     * Lưu voucher mới.
     */
    public function store(VoucherRequest $request): RedirectResponse
    {
        Voucher::create($request->validated());

        return redirect()
            ->route('admin.vouchers.index')
            ->with('success', 'Đã thêm khuyến mãi mới.');
    }

    /**
     * Hiển thị form chỉnh sửa voucher.
     */
    public function edit(Voucher $voucher): View
    {
        return view('admin.vouchers.form', [
            'voucher'   => $voucher,
            'formTitle' => 'Chỉnh sửa khuyến mãi',
        ]);
    }

    /**
     * Cập nhật voucher.
     */
    public function update(VoucherRequest $request, Voucher $voucher): RedirectResponse
    {
        $voucher->update($request->validated());

        return redirect()
            ->route('admin.vouchers.index')
            ->with('success', 'Đã cập nhật khuyến mãi.');
    }

    /**
     * Xóa voucher (từ chối nếu đã dùng trong đơn hàng).
     */
    public function destroy(Voucher $voucher): RedirectResponse
    {
        if ($voucher->hasOrders()) {
            return redirect()
                ->route('admin.vouchers.index')
                ->with('error', 'Không thể xóa voucher đã được sử dụng trong đơn hàng.');
        }

        $voucher->delete();

        return redirect()
            ->route('admin.vouchers.index')
            ->with('success', 'Đã xóa khuyến mãi.');
    }

    // ─── Các chức năng mở rộng ─────────────────────

    /**
     * Toggle trạng thái voucher (AJAX hoặc redirect).
     */
    public function toggleStatus(Voucher $voucher): JsonResponse|RedirectResponse
    {
        $voucher->update(['trang_thai' => !$voucher->trang_thai]);

        $label = $voucher->trang_thai ? 'kích hoạt' : 'tạm ngưng';

        if (request()->expectsJson()) {
            return response()->json([
                'success'    => true,
                'trang_thai' => $voucher->trang_thai,
                'label'      => $voucher->statusLabel(),
                'badge'      => $voucher->statusBadge(),
                'message'    => "Đã {$label} voucher {$voucher->ma_voucher}.",
            ]);
        }

        return redirect()
            ->route('admin.vouchers.index')
            ->with('success', "Đã {$label} voucher {$voucher->ma_voucher}.");
    }

    /**
     * Xóa hàng loạt voucher (chỉ xóa những voucher chưa dùng trong đơn hàng).
     */
    public function bulkDestroy(Request $request): RedirectResponse
    {
        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return redirect()
                ->route('admin.vouchers.index')
                ->with('error', 'Vui lòng chọn ít nhất một voucher.');
        }

        $vouchers = Voucher::whereIn('id', $ids)->get();

        $deleted  = 0;
        $skipped  = 0;

        foreach ($vouchers as $voucher) {
            if ($voucher->hasOrders()) {
                $skipped++;
                continue;
            }
            $voucher->delete();
            $deleted++;
        }

        $message = "Đã xóa {$deleted} voucher.";
        if ($skipped > 0) {
            $message .= " Bỏ qua {$skipped} voucher đã dùng trong đơn hàng.";
        }

        return redirect()
            ->route('admin.vouchers.index')
            ->with($deleted > 0 ? 'success' : 'error', $message);
    }

    /**
     * Export danh sách voucher ra CSV.
     */
    public function export(Request $request): StreamedResponse
    {
        $vouchers = Voucher::query()
            ->search($request->query('q'))
            ->filterStatus($request->query('status'))
            ->filterType($request->query('type'))
            ->orderByDesc('id')
            ->get();

        $filename = 'vouchers_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($vouchers) {
            $handle = fopen('php://output', 'w');

            // BOM cho Excel đọc UTF-8 đúng
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'Mã voucher',
                'Mô tả',
                'Loại giảm',
                'Giá trị giảm',
                'Giảm tối đa',
                'Đơn tối thiểu',
                'Số lượng',
                'Đã sử dụng',
                'Ngày bắt đầu',
                'Ngày kết thúc',
                'Trạng thái',
            ]);

            foreach ($vouchers as $voucher) {
                fputcsv($handle, [
                    $voucher->ma_voucher,
                    $voucher->mo_ta,
                    $voucher->discountTypeLabel(),
                    $voucher->gia_tri_giam,
                    $voucher->giam_toi_da,
                    $voucher->don_toi_thieu,
                    $voucher->so_luong ?? 'Không giới hạn',
                    $voucher->da_su_dung ?? 0,
                    $voucher->ngay_bat_dau?->format('d/m/Y H:i'),
                    $voucher->ngay_ket_thuc?->format('d/m/Y H:i'),
                    $voucher->statusLabel(),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=utf-8',
        ]);
    }
}
