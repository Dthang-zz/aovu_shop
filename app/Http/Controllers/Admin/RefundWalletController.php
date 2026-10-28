<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReturnRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;

class RefundWalletController extends Controller
{
    public function index(): View
    {
        $returnDetails = DB::table('yeu_cau_doi_tras as returns')
            ->leftJoin('don_hangs as orders', 'orders.id', '=', 'returns.don_hang_id')
            ->leftJoin('chi_tiet_don_hangs as order_items', 'order_items.id', '=', 'returns.chi_tiet_don_hang_id')
            ->leftJoin('tai_khoans as accounts', 'accounts.id', '=', 'returns.tai_khoan_id')
            ->leftJoin('support_return_workflows as workflow', 'workflow.return_request_id', '=', 'returns.id')
            ->select([
                'returns.id',
                'returns.trang_thai',
                'returns.ngay_tao',
                'returns.so_luong',
                'orders.ma_don_hang as order_number',
                'accounts.ho_ten as customer_name',
                'order_items.ten_san_pham as product_name',
                'workflow.refund_amount',
                'workflow.refund_method',
                'workflow.refund_reference',
                'workflow.refunded_at',
            ])
            ->where('returns.loai_yeu_cau', ReturnRequest::TYPE_RETURN);

        $pendingRefunds = (clone $returnDetails)
            ->where('returns.trang_thai', ReturnRequest::STATUS_INSPECTED)
            ->orderByDesc('returns.ngay_tao')
            ->orderByDesc('returns.id')
            ->paginate(10, ['*'], 'pending_page');

        $refundHistory = (clone $returnDetails)
            ->whereNotNull('workflow.refunded_at')
            ->orderByDesc('workflow.refunded_at')
            ->orderByDesc('returns.id')
            ->paginate(10, ['*'], 'history_page');

        $refundedThisMonth = DB::table('support_return_workflows as workflow')
            ->join('yeu_cau_doi_tras as returns', 'returns.id', '=', 'workflow.return_request_id')
            ->where('returns.loai_yeu_cau', ReturnRequest::TYPE_RETURN)
            ->whereNotNull('refunded_at')
            ->whereBetween('workflow.refunded_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('workflow.refund_amount');

        return view('admin.finance.refunds.index', [
            'pendingRefunds' => $pendingRefunds,
            'refundHistory' => $refundHistory,
            'pendingCount' => (clone $returnDetails)
                ->where('returns.trang_thai', ReturnRequest::STATUS_INSPECTED)
                ->count(),
            'refundedThisMonth' => (int) $refundedThisMonth,
        ]);
    }
}
