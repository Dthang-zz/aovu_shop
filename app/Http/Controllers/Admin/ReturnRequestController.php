<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReturnRequest as ReturnRequestModel;
use App\Models\ReturnRequestHistory;
use App\Models\ReturnRequestWorkflow;
use App\Models\SupportConversation;
use App\Models\SupportMessage;
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

        $counts = DB::table('yeu_cau_doi_tras as returns')
            ->leftJoin('support_return_workflows as workflow', 'workflow.return_request_id', '=', 'returns.id')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN returns.trang_thai = ? THEN 1 ELSE 0 END) as pending', [ReturnRequestModel::STATUS_PENDING])
            ->selectRaw('SUM(CASE WHEN returns.trang_thai IN (?, ?) THEN 1 ELSE 0 END) as processing', [
                ReturnRequestModel::STATUS_PROCESSING,
                ReturnRequestModel::STATUS_APPROVED,
            ])
            ->selectRaw('SUM(CASE WHEN workflow.refunded_at IS NOT NULL OR returns.trang_thai = ? THEN 1 ELSE 0 END) as refunds', [
                ReturnRequestModel::STATUS_REFUNDED,
            ])
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

    public function create(): View
    {
        return view('admin.returns.create');
    }

    public function show(int $returnRequest): View
    {
        $returnRequest = $this->requestQuery()
            ->where('returns.id', $returnRequest)
            ->firstOrFail();
        $returnRequest->workflow = ReturnRequestWorkflow::query()
            ->where('return_request_id', $returnRequest->id)
            ->first();
        $returnRequest->history = ReturnRequestHistory::query()
            ->where('return_request_id', $returnRequest->id)
            ->oldest('created_at')
            ->oldest('id')
            ->get();
        $returnRequest->conversation_id = ReturnRequestWorkflow::query()
            ->where('return_request_id', $returnRequest->id)
            ->value('conversation_id');
        $returnRequest->status_label = ReturnRequestModel::statuses()[$returnRequest->trang_thai] ?? 'Không xác định';
        $returnRequest->status_badge = ReturnRequestModel::statusBadges()[$returnRequest->trang_thai] ?? 'secondary';
        $returnRequest->type_label = $this->typeLabel($returnRequest->loai_yeu_cau);
        $returnRequest->allowed_statuses = $this->allowedStatuses(
            (int) $returnRequest->trang_thai,
            (int) $returnRequest->loai_yeu_cau,
            (bool) ($returnRequest->workflow?->collection_waived ?? false),
        );
        $returnRequest->replacement_variants = DB::table('bien_thes as variants')
            ->leftJoin('san_phams as products', 'products.id', '=', 'variants.san_pham_id')
            ->leftJoin('mau_sacs as colors', 'colors.id', '=', 'variants.mau_sac_id')
            ->leftJoin('kich_thuocs as sizes', 'sizes.id', '=', 'variants.kich_thuoc_id')
            ->where('variants.trang_thai', 1)
            ->where('variants.so_luong_ton', '>', 0)
            ->orderBy('products.ten_san_pham')
            ->orderBy('variants.sku')
            ->get([
                'variants.id',
                'variants.sku',
                'variants.gia_bien_the',
                'variants.so_luong_ton',
                'products.ten_san_pham',
                'colors.ten_mau',
                'sizes.ten_kich_thuoc',
            ]);

        return view('admin.returns.show', compact('returnRequest'));
    }

    public function update(Request $request, int $returnRequest): RedirectResponse
    {
        $data = $request->validate([
            'trang_thai' => ['required', 'integer', Rule::in(array_keys(ReturnRequestModel::statuses()))],
            'phan_hoi' => ['nullable', 'string', 'max:5000'],
            'customer_agreed' => ['nullable', 'boolean'],
            'policy_reviewed' => ['nullable', 'boolean'],
            'policy_notes' => ['nullable', 'string', 'max:2000'],
            'collection_waived' => ['nullable', 'boolean'],
            'inspection_notes' => ['nullable', 'string', 'max:2000'],
            'replacement_variant_id' => ['nullable', 'integer', 'exists:bien_thes,id'],
            'price_difference_settled' => ['nullable', 'boolean'],
            'price_difference_note' => ['nullable', 'string', 'max:255'],
            'tracking_code' => ['nullable', 'string', 'max:100'],
            'replacement_delivered' => ['nullable', 'boolean'],
        ], [
            'trang_thai.required' => 'Vui lòng chọn trạng thái xử lý.',
            'trang_thai.in' => 'Trạng thái yêu cầu không hợp lệ.',
            'phan_hoi.max' => 'Phản hồi không được vượt quá 5000 ký tự.',
        ]);

        DB::transaction(function () use ($returnRequest, $data): void {
            $record = ReturnRequestModel::query()
                ->whereKey($returnRequest)
                ->lockForUpdate()
                ->firstOrFail();
            $targetStatus = (int) $data['trang_thai'];
            if ($targetStatus === ReturnRequestModel::STATUS_REFUNDED) {
                throw ValidationException::withMessages([
                    'trang_thai' => 'Ghi nhận hoàn tiền đang tạm khóa cho đến khi ví hoàn tiền được tích hợp và phân quyền.',
                ]);
            }

            $workflow = ReturnRequestWorkflow::query()
                ->where('return_request_id', $record->getKey())
                ->lockForUpdate()
                ->first();

            if ($workflow === null) {
                $workflow = ReturnRequestWorkflow::query()->create([
                    'return_request_id' => $record->getKey(),
                ]);
            }

            $collectionWaived = array_key_exists('collection_waived', $data)
                ? (bool) $data['collection_waived']
                : (bool) $workflow->collection_waived;
            $allowedStatuses = $this->allowedStatuses(
                (int) $record->trang_thai,
                (int) $record->loai_yeu_cau,
                $collectionWaived,
            );

            if (! in_array($targetStatus, $allowedStatuses, true)) {
                throw ValidationException::withMessages([
                    'trang_thai' => 'Không thể chuyển yêu cầu từ trạng thái hiện tại sang trạng thái đã chọn.',
                ]);
            }

            $customerAgreed = array_key_exists('customer_agreed', $data)
                ? (bool) $data['customer_agreed']
                : (bool) $workflow->customer_agreed;
            $policyReviewed = array_key_exists('policy_reviewed', $data)
                ? (bool) $data['policy_reviewed']
                : (bool) $workflow->policy_reviewed;
            $policyNotes = trim((string) ($data['policy_notes'] ?? $workflow->policy_notes ?? ''));
            $inspectionNotes = trim((string) ($data['inspection_notes'] ?? $workflow->inspection_notes ?? ''));
            $staffReply = trim((string) ($data['phan_hoi'] ?? ''));

            if ($targetStatus === ReturnRequestModel::STATUS_APPROVED) {
                if (! $customerAgreed) {
                    throw ValidationException::withMessages([
                        'customer_agreed' => 'Cần xác nhận khách hàng đã đồng ý trước khi duyệt.',
                    ]);
                }

                if (! $policyReviewed || $policyNotes === '') {
                    throw ValidationException::withMessages([
                        'policy_reviewed' => 'Cần xác nhận và ghi chú việc kiểm tra chính sách đổi/trả.',
                    ]);
                }
            }

            if ($targetStatus === ReturnRequestModel::STATUS_REJECTED
                && $staffReply === '') {
                throw ValidationException::withMessages([
                    'phan_hoi' => 'Vui lòng nhập lý do từ chối để khách hàng được thông báo.',
                ]);
            }

            if ($targetStatus === ReturnRequestModel::STATUS_INSPECTED && $inspectionNotes === '') {
                throw ValidationException::withMessages([
                    'inspection_notes' => 'Vui lòng ghi nhận kết quả kiểm tra sản phẩm.',
                ]);
            }

            $workflowChanges = [
                'customer_agreed' => $customerAgreed,
                'customer_agreed_at' => $customerAgreed
                    ? ($workflow->customer_agreed_at ?? now())
                    : null,
                'policy_reviewed' => $policyReviewed,
                'policy_notes' => $policyNotes !== '' ? $policyNotes : null,
                'collection_waived' => $collectionWaived,
                'inspection_notes' => $inspectionNotes !== '' ? $inspectionNotes : null,
                'price_difference_settled' => array_key_exists('price_difference_settled', $data)
                    ? (bool) $data['price_difference_settled']
                    : (bool) $workflow->price_difference_settled,
                'price_difference_note' => trim((string) (
                    $data['price_difference_note'] ?? $workflow->price_difference_note ?? ''
                )) ?: null,
                'tracking_code' => trim((string) ($data['tracking_code'] ?? $workflow->tracking_code ?? '')) ?: null,
            ];

            if (isset($data['replacement_variant_id'])) {
                $replacement = DB::table('bien_thes as variants')
                    ->leftJoin('san_phams as products', 'products.id', '=', 'variants.san_pham_id')
                    ->where('variants.id', $data['replacement_variant_id'])
                    ->where('variants.trang_thai', 1)
                    ->lockForUpdate()
                    ->first([
                        'variants.id',
                        'variants.sku',
                        'variants.gia_bien_the',
                        'variants.so_luong_ton',
                        'products.ten_san_pham',
                    ]);

                if ($replacement === null) {
                    throw ValidationException::withMessages([
                        'replacement_variant_id' => 'Sản phẩm thay thế không tồn tại.',
                    ]);
                }

                $originalItem = DB::table('chi_tiet_don_hangs')
                    ->where('id', $record->chi_tiet_don_hang_id)
                    ->first(['don_gia']);
                $originalUnitPrice = (int) ($originalItem->don_gia ?? 0);
                $replacementUnitPrice = (int) ($replacement->gia_bien_the ?? 0);
                $quantity = (int) ($record->so_luong ?? 0);
                $workflowChanges['replacement_variant_id'] = $replacement->id;
                $workflowChanges['replacement_name'] = trim(
                    ($replacement->ten_san_pham ?: 'Sản phẩm')
                    .' · '.$replacement->sku
                );
                $workflowChanges['replacement_price'] = $replacementUnitPrice;
                $workflowChanges['price_difference'] = ($replacementUnitPrice - $originalUnitPrice) * $quantity;
            }

            if ($targetStatus === ReturnRequestModel::STATUS_RETURN_RECEIVED) {
                $workflowChanges['received_at'] = $workflow->received_at ?? now();
            }

            if ($targetStatus === ReturnRequestModel::STATUS_EXCHANGE_SHIPPED) {
                if (! $workflow->replacement_variant_id && empty($workflowChanges['replacement_variant_id'])) {
                    throw ValidationException::withMessages([
                        'replacement_variant_id' => 'Vui lòng chọn sản phẩm thay thế trước khi gửi hàng.',
                    ]);
                }

                $replacementVariantId = $workflowChanges['replacement_variant_id']
                    ?? $workflow->replacement_variant_id;
                $replacement = DB::table('bien_thes')
                    ->where('id', $replacementVariantId)
                    ->where('trang_thai', 1)
                    ->lockForUpdate()
                    ->first(['id', 'so_luong_ton']);
                $quantity = (int) ($record->so_luong ?? 0);

                if ($replacement === null || (int) ($replacement->so_luong_ton ?? 0) < $quantity) {
                    throw ValidationException::withMessages([
                        'replacement_variant_id' => 'Tồn kho sản phẩm thay thế không đủ số lượng.',
                    ]);
                }

                $trackingCode = $workflowChanges['tracking_code'];
                if ($trackingCode === null) {
                    throw ValidationException::withMessages([
                        'tracking_code' => 'Vui lòng nhập mã vận đơn hàng đổi.',
                    ]);
                }

                $priceDifference = (int) ($workflowChanges['price_difference'] ?? $workflow->price_difference);
                $differenceSettled = $workflowChanges['price_difference_settled'];
                if ($priceDifference !== 0
                    && (! $differenceSettled || $workflowChanges['price_difference_note'] === null)) {
                    throw ValidationException::withMessages([
                        'price_difference_settled' => 'Cần xác nhận và ghi chú việc xử lý chênh lệch giá.',
                    ]);
                }

                DB::table('bien_thes')
                    ->where('id', $replacementVariantId)
                    ->where('so_luong_ton', '>=', $quantity)
                    ->decrement('so_luong_ton', $quantity);
            }

            if ($targetStatus === ReturnRequestModel::STATUS_COMPLETED
                && (int) $record->loai_yeu_cau === ReturnRequestModel::TYPE_EXCHANGE) {
                if (! (bool) ($data['replacement_delivered'] ?? false)) {
                    throw ValidationException::withMessages([
                        'replacement_delivered' => 'Vui lòng xác nhận khách đã nhận được hàng đổi.',
                    ]);
                }

                $workflowChanges['delivered_at'] = now();
            }

            $fromStatus = (int) $record->trang_thai;
            $record->update([
                'trang_thai' => $targetStatus,
                'phan_hoi' => $staffReply !== '' ? $staffReply : $record->phan_hoi,
            ]);

            $workflow->update($workflowChanges);
            ReturnRequestHistory::query()->create([
                'return_request_id' => $record->getKey(),
                'from_status' => $fromStatus,
                'to_status' => $targetStatus,
                'actor_name' => 'CSKH',
                'note' => $staffReply !== '' ? $staffReply : $policyNotes,
                'created_at' => now(),
            ]);

            $this->notifyCustomer($workflow, $record, $staffReply);
        });

        return redirect()
            ->route('admin.returns.show', $returnRequest)
            ->with('success', 'Đã cập nhật trạng thái yêu cầu.');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedIntakeData($request);
        $returnRequest = $this->createRequest($data);

        return redirect()
            ->route('admin.returns.show', $returnRequest)
            ->with('success', 'Đã tạo phiếu đổi/trả #DT-'.str_pad(
                (string) $returnRequest->getKey(),
                6,
                '0',
                STR_PAD_LEFT,
            ).'.');
    }

    public function storeFromConversation(Request $request, SupportConversation $conversation): RedirectResponse
    {
        abort_unless($conversation->status === SupportConversation::STATUS_OPEN, 409);

        $returnRequest = $this->createRequest($this->validatedIntakeData($request), $conversation);

        return redirect()
            ->route('admin.support-chat.index', [
                'status' => SupportConversation::STATUS_OPEN,
                'conversation_id' => $conversation->getKey(),
            ])
            ->with('success', 'Đã tạo phiếu đổi/trả #DT-'.str_pad(
                (string) $returnRequest->getKey(),
                6,
                '0',
                STR_PAD_LEFT,
            ).'.');
    }

    /**
     * @return array{customer_id: int, order_id: int, chi_tiet_don_hang_id: int, loai_yeu_cau: int, so_luong: int, ly_do: string}
     */
    private function validatedIntakeData(Request $request): array
    {
        return $request->validate([
            'customer_id' => ['required', 'integer', 'exists:tai_khoans,id'],
            'order_id' => ['required', 'integer', 'exists:don_hangs,id'],
            'chi_tiet_don_hang_id' => ['required', 'integer', 'exists:chi_tiet_don_hangs,id'],
            'loai_yeu_cau' => ['required', 'integer', Rule::in([
                ReturnRequestModel::TYPE_EXCHANGE,
                ReturnRequestModel::TYPE_RETURN,
            ])],
            'so_luong' => ['required', 'integer', 'min:1'],
            'ly_do' => ['required', 'string', 'max:5000'],
        ], [
            'customer_id.required' => 'Vui lòng chọn khách hàng.',
            'order_id.required' => 'Vui lòng chọn đơn hàng.',
            'chi_tiet_don_hang_id.required' => 'Vui lòng chọn sản phẩm trong đơn hàng.',
            'so_luong.min' => 'Số lượng phải ít nhất là 1.',
            'ly_do.required' => 'Vui lòng nhập lý do đổi/trả.',
        ]);
    }

    /**
     * @param  array{customer_id: int, order_id: int, chi_tiet_don_hang_id: int, loai_yeu_cau: int, so_luong: int, ly_do: string}  $data
     */
    private function createRequest(array $data, ?SupportConversation $conversation = null): ReturnRequestModel
    {
        if ($conversation !== null) {
            abort_unless($conversation->status === SupportConversation::STATUS_OPEN, 409);
        }

        return DB::transaction(function () use ($data, $conversation): ReturnRequestModel {
            $order = DB::table('don_hangs')
                ->where('id', $data['order_id'])
                ->where('tai_khoan_id', $data['customer_id'])
                ->lockForUpdate()
                ->first(['id', 'tai_khoan_id']);

            if ($order === null) {
                throw ValidationException::withMessages([
                    'order_id' => 'Đơn hàng không thuộc khách hàng đã chọn.',
                ]);
            }

            $customer = DB::table('tai_khoans')
                ->where('id', $data['customer_id'])
                ->first(['id', 'email']);
            if ($conversation !== null
                && filled($conversation->customer_email)
                && strcasecmp((string) $conversation->customer_email, (string) $customer->email) !== 0) {
                throw ValidationException::withMessages([
                    'customer_id' => 'Email khách hàng không khớp với cuộc trò chuyện đang chọn.',
                ]);
            }

            $orderItem = DB::table('chi_tiet_don_hangs')
                ->where('id', $data['chi_tiet_don_hang_id'])
                ->where('don_hang_id', $order->id)
                ->lockForUpdate()
                ->first(['id', 'so_luong']);
            if ($orderItem === null) {
                throw ValidationException::withMessages([
                    'chi_tiet_don_hang_id' => 'Sản phẩm không thuộc đơn hàng đã chọn.',
                ]);
            }

            $alreadyRequested = (int) DB::table('yeu_cau_doi_tras')
                ->where('chi_tiet_don_hang_id', $orderItem->id)
                ->where('trang_thai', '!=', ReturnRequestModel::STATUS_REJECTED)
                ->sum('so_luong');
            $availableQuantity = (int) ($orderItem->so_luong ?? 0) - $alreadyRequested;
            if ($data['so_luong'] > $availableQuantity) {
                throw ValidationException::withMessages([
                    'so_luong' => 'Số lượng vượt quá số sản phẩm còn có thể đổi/trả ('
                        .max(0, $availableQuantity).').',
                ]);
            }

            $returnRequest = ReturnRequestModel::query()->create([
                'don_hang_id' => $order->id,
                'chi_tiet_don_hang_id' => $orderItem->id,
                'tai_khoan_id' => $customer->id,
                'loai_yeu_cau' => $data['loai_yeu_cau'],
                'so_luong' => $data['so_luong'],
                'ly_do' => trim($data['ly_do']),
                'ngay_tao' => now(),
                'trang_thai' => ReturnRequestModel::STATUS_PENDING,
            ]);
            $workflow = ReturnRequestWorkflow::query()->create([
                'return_request_id' => $returnRequest->getKey(),
                'conversation_id' => $conversation?->getKey(),
            ]);
            ReturnRequestHistory::query()->create([
                'return_request_id' => $returnRequest->getKey(),
                'from_status' => null,
                'to_status' => ReturnRequestModel::STATUS_PENDING,
                'actor_name' => 'CSKH',
                'note' => $conversation !== null
                    ? 'Tạo phiếu đổi/trả từ cuộc trò chuyện CSKH.'
                    : 'Tạo phiếu đổi/trả thủ công từ chức năng Đổi / trả hàng.',
                'created_at' => now(),
            ]);
            if ($conversation !== null) {
                $conversation->messages()->create([
                    'sender_type' => SupportMessage::SENDER_STAFF,
                    'sender_name' => 'CSKH',
                    'body' => 'Đã tạo phiếu #DT-'.str_pad((string) $returnRequest->getKey(), 6, '0', STR_PAD_LEFT)
                        .' ('.$returnRequest->typeLabel().'). Trạng thái: '
                        .$returnRequest->statusLabel().'.',
                ]);
                $conversation->touch();
            }

            return $returnRequest;
        });
    }

    private function requestQuery()
    {
        return DB::table('yeu_cau_doi_tras as returns')
            ->leftJoin('don_hangs as orders', 'orders.id', '=', 'returns.don_hang_id')
            ->leftJoin('chi_tiet_don_hangs as order_items', 'order_items.id', '=', 'returns.chi_tiet_don_hang_id')
            ->leftJoin('tai_khoans as accounts', 'accounts.id', '=', 'returns.tai_khoan_id')
            ->leftJoin('support_return_workflows as workflow', 'workflow.return_request_id', '=', 'returns.id')
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
                'workflow.conversation_id',
            ]);
    }

    /**
     * @return array<int, int>
     */
    private function allowedStatuses(int $currentStatus, int $requestType, bool $collectionWaived = false): array
    {
        return ReturnRequestModel::allowedStatuses($currentStatus, $requestType, $collectionWaived);
    }

    private function notifyCustomer(
        ReturnRequestWorkflow $workflow,
        ReturnRequestModel $returnRequest,
        string $staffReply,
    ): void {
        if ($workflow->conversation_id === null) {
            return;
        }

        $statusLabel = ReturnRequestModel::statuses()[$returnRequest->trang_thai]
            ?? 'Không xác định';
        $message = 'Phiếu #DT-'.str_pad((string) $returnRequest->getKey(), 6, '0', STR_PAD_LEFT)
            .' ('.$returnRequest->typeLabel().') đã cập nhật: '.$statusLabel.'.';

        if ($staffReply !== '') {
            $message .= ' '.$staffReply;
        }

        $conversation = SupportConversation::query()->find($workflow->conversation_id);
        $conversation?->messages()->create([
            'sender_type' => SupportMessage::SENDER_STAFF,
            'sender_name' => 'CSKH',
            'body' => $message,
        ]);
        $conversation?->touch();
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
