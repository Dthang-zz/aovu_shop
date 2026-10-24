<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReturnRequest as ReturnRequestModel;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ReturnRequestController extends Controller
{
    public function index(Request $request): View
    {
        $query = $this->requestQuery();

        if ($request->filled('q')) {
            $term = trim((string) $request->query('q'));
            $query->where(function ($query) use ($term) {
                $query->where('returns.id', 'like', "%{$term}%")
                    ->orWhere('orders.ma_don_hang', 'like', "%{$term}%")
                    ->orWhere('orders.ten_nguoi_nhan', 'like', "%{$term}%")
                    ->orWhere('accounts.ho_ten', 'like', "%{$term}%")
                    ->orWhere('returns.ly_do', 'like', "%{$term}%");
            });
        }

        if ($request->filled('status') && is_numeric($request->query('status'))) {
            $query->where('returns.trang_thai', (int) $request->query('status'));
        }

        if ($request->filled('type') && is_numeric($request->query('type'))) {
            $query->where('returns.loai_yeu_cau', (int) $request->query('type'));
        }

        $counts = ReturnRequestModel::query()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN trang_thai = ? THEN 1 ELSE 0 END) as pending', [ReturnRequestModel::STATUS_PENDING])
            ->selectRaw('SUM(CASE WHEN trang_thai IN (?, ?) THEN 1 ELSE 0 END) as processing', [
                ReturnRequestModel::STATUS_PROCESSING,
                ReturnRequestModel::STATUS_APPROVED,
            ])
            ->selectRaw('SUM(CASE WHEN trang_thai = ? THEN 1 ELSE 0 END) as refunds', [ReturnRequestModel::STATUS_REFUNDED])
            ->first();

        return view('admin.returns.index', [
            'requests' => $query->orderByDesc('returns.ngay_tao')
                ->orderByDesc('returns.id')
                ->paginate(10)
                ->withQueryString(),
            'statuses' => ReturnRequestModel::statuses(),
            'statusBadges' => ReturnRequestModel::statusBadges(),
            'counts' => $counts,
        ]);
    }

    public function show(int $returnRequest): View
    {
        $returnRequest = $this->requestQuery()
            ->where('returns.id', $returnRequest)
            ->firstOrFail();

        $returnRequest->status_label = ReturnRequestModel::statuses()[$returnRequest->trang_thai] ?? 'Không xác định';
        $returnRequest->status_badge = ReturnRequestModel::statusBadges()[$returnRequest->trang_thai] ?? 'secondary';
        $returnRequest->type_label = $this->typeLabel($returnRequest->loai_yeu_cau);
        $returnRequest->allowed_statuses = $this->allowedStatuses(
            (int) $returnRequest->trang_thai,
            (int) $returnRequest->loai_yeu_cau
        );

        return view('admin.returns.show', compact('returnRequest'));
    }

    public function update(Request $request, int $returnRequest): RedirectResponse
    {
        $data = $request->validate([
            'trang_thai' => ['required', 'integer', Rule::in(array_keys(ReturnRequestModel::statuses()))],
            'phan_hoi' => ['nullable', 'string', 'max:5000'],
        ], [
            'trang_thai.required' => 'Vui lòng chọn trạng thái xử lý.',
            'trang_thai.in' => 'Trạng thái yêu cầu không hợp lệ.',
            'phan_hoi.max' => 'Phản hồi không được vượt quá 5000 ký tự.',
        ]);

        DB::transaction(function () use ($returnRequest, $data) {
            $record = ReturnRequestModel::query()
                ->whereKey($returnRequest)
                ->lockForUpdate()
                ->firstOrFail();

            $targetStatus = (int) $data['trang_thai'];
            $allowedStatuses = $this->allowedStatuses(
                (int) $record->trang_thai,
                (int) $record->loai_yeu_cau
            );

            if (!in_array($targetStatus, $allowedStatuses, true)) {
                throw ValidationException::withMessages([
                    'trang_thai' => 'Không thể chuyển yêu cầu từ trạng thái hiện tại sang trạng thái đã chọn.',
                ]);
            }

            if ($targetStatus === ReturnRequestModel::STATUS_REJECTED
                && trim((string) ($data['phan_hoi'] ?? '')) === '') {
                throw ValidationException::withMessages([
                    'phan_hoi' => 'Vui lòng nhập lý do từ chối để khách hàng được thông báo.',
                ]);
            }

            if ($targetStatus === ReturnRequestModel::STATUS_REFUNDED
                && trim((string) ($data['phan_hoi'] ?? '')) === '') {
                throw ValidationException::withMessages([
                    'phan_hoi' => 'Vui lòng ghi chú việc hoàn tiền trước khi xác nhận.',
                ]);
            }

            $record->update([
                'trang_thai' => $targetStatus,
                'phan_hoi' => trim((string) ($data['phan_hoi'] ?? '')) ?: null,
            ]);
        });

        return redirect()
            ->route('admin.returns.show', $returnRequest)
            ->with('success', 'Đã cập nhật trạng thái yêu cầu.');
    }

    private function requestQuery()
    {
        return DB::table('yeu_cau_doi_tras as returns')
            ->leftJoin('don_hangs as orders', 'orders.id', '=', 'returns.don_hang_id')
            ->leftJoin('chi_tiet_don_hangs as order_items', 'order_items.id', '=', 'returns.chi_tiet_don_hang_id')
            ->leftJoin('tai_khoans as accounts', 'accounts.id', '=', 'returns.tai_khoan_id')
            ->select([
                'returns.*',
                'orders.ma_don_hang as order_number',
                'orders.ten_nguoi_nhan as recipient_name',
                'orders.tong_tien as order_total',
                'order_items.ten_san_pham as product_name',
                'order_items.don_gia as item_price',
                'order_items.so_luong as order_item_quantity',
                'accounts.ho_ten as account_name',
                'accounts.email as account_email',
            ]);
    }

    /**
     * @return array<int, int>
     */
    private function allowedStatuses(int $currentStatus, int $requestType): array
    {
        return match ($currentStatus) {
            ReturnRequestModel::STATUS_PENDING => [
                ReturnRequestModel::STATUS_PROCESSING,
                ReturnRequestModel::STATUS_REJECTED,
            ],
            ReturnRequestModel::STATUS_PROCESSING => [
                ReturnRequestModel::STATUS_APPROVED,
                ReturnRequestModel::STATUS_REJECTED,
            ],
            ReturnRequestModel::STATUS_APPROVED => $requestType === ReturnRequestModel::TYPE_RETURN
                ? [ReturnRequestModel::STATUS_COMPLETED, ReturnRequestModel::STATUS_REFUNDED]
                : [ReturnRequestModel::STATUS_COMPLETED],
            default => [],
        };
    }

    private function typeLabel(int $requestType): string
    {
        return match ($requestType) {
            ReturnRequestModel::TYPE_EXCHANGE => 'Đổi hàng',
            ReturnRequestModel::TYPE_RETURN => 'Trả hàng',
            default => 'Yêu cầu khác',
        };
    }
}
