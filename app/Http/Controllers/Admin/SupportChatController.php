<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReturnRequest;
use App\Models\SupportConversation;
use App\Models\SupportMessage;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SupportChatController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'status' => ['nullable', 'string', Rule::in([
                SupportConversation::STATUS_OPEN,
                SupportConversation::STATUS_CLOSED,
            ])],
        ]);
        $status = $filters['status'] ?? SupportConversation::STATUS_OPEN;
        $conversations = SupportConversation::query()
            ->with('latestMessage')
            ->where('status', $status)
            ->latest('updated_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.support-chat.index', [
            'conversations' => $conversations,
            'status' => $status,
            'selectedConversationId' => $request->integer('conversation_id'),
            'openCount' => SupportConversation::query()
                ->where('status', SupportConversation::STATUS_OPEN)
                ->count(),
        ]);
    }

    public function messages(Request $request, SupportConversation $conversation): JsonResponse
    {
        $filters = $request->validate([
            'after_id' => ['nullable', 'integer', 'min:0'],
        ]);
        $messages = $conversation->messages()
            ->when(isset($filters['after_id']), fn ($query) => $query->where('id', '>', $filters['after_id']))
            ->oldest('id')
            ->limit(100)
            ->get();

        return response()->json([
            'conversation' => [
                'id' => $conversation->id,
                'customer_name' => $conversation->customer_name,
                'customer_email' => $conversation->customer_email,
                'status' => $conversation->status,
            ],
            'messages' => $messages->map(fn (SupportMessage $message): array => $this->formatMessage($message)),
            'return_requests' => $this->returnRequestSummaries($conversation),
        ]);
    }

    public function customers(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:100'],
        ]);
        $term = trim($filters['q']);
        $customers = DB::table('tai_khoans')
            ->where(function ($query) use ($term): void {
                $query->where('ho_ten', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('so_dien_thoai', 'like', "%{$term}%");
            })
            ->orderBy('ho_ten')
            ->limit(20)
            ->get(['id', 'ho_ten', 'email', 'so_dien_thoai']);

        return response()->json(['customers' => $customers]);
    }

    public function orders(int $customer): JsonResponse
    {
        $orders = DB::table('don_hangs')
            ->where('tai_khoan_id', $customer)
            ->latest('ngay_dat')
            ->limit(50)
            ->get(['id', 'ma_don_hang', 'ngay_dat', 'ten_nguoi_nhan']);

        return response()->json(['orders' => $orders]);
    }

    public function orderItems(Request $request, int $order): JsonResponse
    {
        $data = $request->validate([
            'customer_id' => ['required', 'integer', 'exists:tai_khoans,id'],
        ]);
        $orderBelongsToCustomer = DB::table('don_hangs')
            ->where('id', $order)
            ->where('tai_khoan_id', $data['customer_id'])
            ->exists();

        abort_unless($orderBelongsToCustomer, 404);

        $orderItems = DB::table('chi_tiet_don_hangs')
            ->where('don_hang_id', $order)
            ->orderBy('id')
            ->get(['id', 'ten_san_pham', 'don_gia', 'thanh_tien', 'so_luong', 'bien_the_id']);
        $returnedQuantities = DB::table('yeu_cau_doi_tras')
            ->whereIn('chi_tiet_don_hang_id', $orderItems->pluck('id'))
            ->where('trang_thai', '!=', ReturnRequest::STATUS_REJECTED)
            ->select('chi_tiet_don_hang_id')
            ->selectRaw('SUM(so_luong) as requested_quantity')
            ->groupBy('chi_tiet_don_hang_id')
            ->pluck('requested_quantity', 'chi_tiet_don_hang_id');

        return response()->json([
            'items' => $orderItems
                ->map(function ($item) use ($returnedQuantities): array {
                    $orderedQuantity = (int) ($item->so_luong ?? 0);

                    return [
                        'id' => $item->id,
                        'name' => $item->ten_san_pham ?: 'Sản phẩm trong đơn hàng',
                        'ordered_quantity' => $orderedQuantity,
                        'available_quantity' => max(
                            0,
                            $orderedQuantity - (int) ($returnedQuantities[$item->id] ?? 0),
                        ),
                        'unit_price' => (int) ($item->don_gia ?? 0),
                        'line_total' => (int) ($item->thanh_tien ?? 0),
                        'variant_id' => $item->bien_the_id,
                    ];
                })
                ->filter(fn (array $item): bool => $item['available_quantity'] > 0)
                ->values(),
        ]);
    }

    public function storeMessage(Request $request, SupportConversation $conversation): JsonResponse
    {
        abort_unless($conversation->status === SupportConversation::STATUS_OPEN, 409);

        $data = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ], [
            'message.required' => 'Vui lòng nhập nội dung phản hồi.',
            'message.max' => 'Tin nhắn không được vượt quá 2000 ký tự.',
        ]);
        $message = $conversation->messages()->create([
            'sender_type' => SupportMessage::SENDER_STAFF,
            'sender_name' => 'CSKH',
            'body' => trim($data['message']),
        ]);
        $conversation->touch();

        return response()->json([
            'messages' => [$this->formatMessage($message)],
        ], 201);
    }

    public function updateStatus(Request $request, SupportConversation $conversation): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'string', Rule::in([
                SupportConversation::STATUS_OPEN,
                SupportConversation::STATUS_CLOSED,
            ])],
        ]);
        $conversation->update(['status' => $data['status']]);

        return redirect()
            ->route('admin.support-chat.index', ['status' => $data['status']])
            ->with('success', $data['status'] === SupportConversation::STATUS_CLOSED
                ? 'Đã đóng hội thoại.'
                : 'Đã mở lại hội thoại.');
    }

    /**
     * @return array{id: int, sender_type: string, sender_name: ?string, body: string, created_at: string}
     */
    private function formatMessage(SupportMessage $message): array
    {
        return [
            'id' => $message->id,
            'sender_type' => $message->sender_type,
            'sender_name' => $message->sender_name,
            'body' => $message->body,
            'created_at' => $message->created_at->format('H:i'),
        ];
    }

    /**
     * @return Collection<int, array{id: int, type: string, status: string}>
     */
    private function returnRequestSummaries(SupportConversation $conversation): Collection
    {
        return $conversation->returnWorkflows()
            ->with('returnRequest')
            ->get()
            ->map(fn ($workflow): array => [
                'id' => $workflow->return_request_id,
                'type' => $workflow->returnRequest?->typeLabel() ?? 'Đổi / trả hàng',
                'status' => $workflow->returnRequest?->statusLabel() ?? 'Không xác định',
            ]);
    }
}
