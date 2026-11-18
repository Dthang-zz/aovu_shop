<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\VoucherRequest;
use App\Models\Voucher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VoucherController extends Controller
{
    /**
     * Danh sách voucher.
     */
    public function index(Request $request): View
    {
        $vouchers = Voucher::query()
            ->search($request->input('q'))
            ->filterStatus($request->input('status'))
            ->filterType($request->input('type'))
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $stats = Voucher::stats();

        return view('admin.vouchers.index', [
            'vouchers' => $vouchers,
            'totalCount' => $stats['total'],
            'activeCount' => $stats['active'],
            'totalUsed' => $stats['total_used'],
        ]);
    }

    /**
     * Form thêm voucher.
     */
    public function create(): View
    {
        $voucher = new Voucher([
            'trang_thai' => true,
            'loai_giam' => Voucher::TYPE_PERCENT,
            'da_su_dung' => 0,
        ]);

        return view('admin.vouchers.form', [
            'voucher' => $voucher,
            'formTitle' => 'Thêm mã giảm giá',
        ]);
    }

    /**
     * Lưu voucher mới.
     */
    public function store(VoucherRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $data['ma_voucher'] = strtoupper(trim($data['ma_voucher']));
        $data['da_su_dung'] = 0;

        Voucher::create($data);

        return redirect()
            ->route('admin.vouchers.index')
            ->with('success', 'Thêm mã giảm giá thành công.');
    }

    /**
     * Form chỉnh sửa voucher.
     */
    public function edit(Voucher $voucher): View
    {
        return view('admin.vouchers.form', [
            'voucher' => $voucher,
            'formTitle' => 'Cập nhật mã giảm giá',
        ]);
    }

    /**
     * Cập nhật voucher.
     */
    public function update(
        VoucherRequest $request,
        Voucher $voucher
    ): RedirectResponse {
        $data = $request->validated();

        $data['ma_voucher'] = strtoupper(trim($data['ma_voucher']));

        // Không cho phép ghi đè số lượt đã sử dụng từ form.
        unset($data['da_su_dung']);

        $voucher->update($data);

        return redirect()
            ->route('admin.vouchers.index')
            ->with('success', 'Cập nhật mã giảm giá thành công.');
    }

    /**
     * Xóa một voucher.
     */
    public function destroy(Voucher $voucher): RedirectResponse
    {
        if ($voucher->hasOrders()) {
            return redirect()
                ->route('admin.vouchers.index')
                ->with(
                    'error',
                    'Không thể xóa mã giảm giá đã được sử dụng trong đơn hàng.'
                );
        }

        $voucher->delete();

        return redirect()
            ->route('admin.vouchers.index')
            ->with('success', 'Xóa mã giảm giá thành công.');
    }

    /**
     * Bật/tắt trạng thái voucher.
     */
    public function toggleStatus(Voucher $voucher): JsonResponse|RedirectResponse
    {
        $voucher->trang_thai = ! $voucher->trang_thai;
        $voucher->save();

        $message = $voucher->trang_thai
            ? 'Đã bật mã giảm giá.'
            : 'Đã tắt mã giảm giá.';

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'trang_thai' => (bool) $voucher->trang_thai,
                'label' => $voucher->statusLabel(),
                'badge' => $voucher->statusBadge(),
                'message' => $message,
            ]);
        }

        return redirect()
            ->route('admin.vouchers.index')
            ->with('success', $message);
    }

    /**
     * Xóa nhiều voucher.
     */
    public function bulkDestroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'distinct', 'exists:vouchers,id'],
        ], [
            'ids.required' => 'Vui lòng chọn ít nhất một mã giảm giá.',
            'ids.array' => 'Danh sách mã giảm giá không hợp lệ.',
            'ids.min' => 'Vui lòng chọn ít nhất một mã giảm giá.',
            'ids.*.exists' => 'Một mã giảm giá được chọn không tồn tại.',
        ]);

        $ids = $validated['ids'];

        $deletedCount = 0;
        $skippedCount = 0;

        DB::transaction(function () use (
            $ids,
            &$deletedCount,
            &$skippedCount
        ) {
            $vouchers = Voucher::query()
                ->whereIn('id', $ids)
                ->lockForUpdate()
                ->get();

            foreach ($vouchers as $voucher) {
                if ($voucher->hasOrders()) {
                    $skippedCount++;
                    continue;
                }

                $voucher->delete();
                $deletedCount++;
            }
        });

        if ($deletedCount === 0 && $skippedCount > 0) {
            return redirect()
                ->route('admin.vouchers.index')
                ->with(
                    'error',
                    'Không thể xóa các mã đã được sử dụng trong đơn hàng.'
                );
        }

        $message = "Đã xóa {$deletedCount} mã giảm giá.";

        if ($skippedCount > 0) {
            $message .= " Bỏ qua {$skippedCount} mã đã được sử dụng trong đơn hàng.";
        }

        return redirect()
            ->route('admin.vouchers.index')
            ->with('success', $message);
    }

    /**
     * Xuất danh sách voucher ra CSV.
     */
    public function export(Request $request): StreamedResponse
    {
        $vouchers = Voucher::query()
            ->search($request->input('q'))
            ->filterStatus($request->input('status'))
            ->filterType($request->input('type'))
            ->orderByDesc('id')
            ->get();

        $fileName = 'danh-sach-ma-giam-gia-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($vouchers) {
            $output = fopen('php://output', 'w');

            // BOM giúp Excel đọc đúng tiếng Việt UTF-8.
            fwrite($output, "\xEF\xBB\xBF");

            fputcsv($output, [
                'Mã giảm giá',
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
                fputcsv($output, [
                    $voucher->ma_voucher,
                    $voucher->mo_ta,
                    $voucher->discountTypeLabel(),
                    $voucher->gia_tri_giam,
                    $voucher->giam_toi_da,
                    $voucher->don_toi_thieu,
                    $voucher->so_luong,
                    $voucher->da_su_dung,
                    $voucher->ngay_bat_dau?->format('d/m/Y H:i'),
                    $voucher->ngay_ket_thuc?->format('d/m/Y H:i'),
                    $voucher->statusLabel(),
                ]);
            }

            fclose($output);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
