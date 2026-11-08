<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\SupportConversation;
use App\Models\SupportMessage;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
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
            'customer_orders' => $this->customerOrders($conversation),
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
     * Latest orders of the customer behind the conversation, matched by the chat email
     * against their account or the order's recipient email, so staff can open the order to handle returns.
     *
     * @return Collection<int, array{id: int, code: string, ordered_at: ?string, status: string, total: int, url: string}>
     */
    private function customerOrders(SupportConversation $conversation): Collection
    {
        $email = Str::lower(trim((string) $conversation->customer_email));
        if ($email === '' || ! Schema::hasTable('don_hangs')) {
            return collect();
        }

        $accountId = Schema::hasTable('tai_khoans') && Schema::hasColumn('tai_khoans', 'email')
            ? DB::table('tai_khoans')->whereRaw('LOWER(email) = ?', [$email])->value('id')
            : null;
        $matchesRecipientEmail = Schema::hasColumn('don_hangs', 'email_nguoi_nhan');

        if ($accountId === null && ! $matchesRecipientEmail) {
            return collect();
        }

        return DB::table('don_hangs')
            ->where(function ($query) use ($accountId, $email, $matchesRecipientEmail): void {
                if ($accountId !== null) {
                    $query->where('tai_khoan_id', $accountId);
                }
                if ($matchesRecipientEmail) {
                    $query->orWhereRaw('LOWER(email_nguoi_nhan) = ?', [$email]);
                }
            })
            ->orderByDesc('ngay_dat')
            ->orderByDesc('id')
            ->limit(10)
            ->get(['id', 'ma_don_hang', 'ngay_dat', 'trang_thai', 'tong_tien'])
            ->map(fn (object $order): array => [
                'id' => (int) $order->id,
                'code' => (string) $order->ma_don_hang,
                'ordered_at' => $order->ngay_dat ? Carbon::parse($order->ngay_dat)->format('d/m/Y') : null,
                'status' => Order::statuses()[$order->trang_thai] ?? 'Không xác định',
                'total' => (int) $order->tong_tien,
                'url' => route('admin.orders.show', $order->id),
            ]);
    }
}
