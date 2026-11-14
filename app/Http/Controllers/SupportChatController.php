<?php

namespace App\Http\Controllers;

use App\Models\SupportConversation;
use App\Models\SupportMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SupportChatController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'after_id' => ['nullable', 'integer', 'min:0'],
        ]);
        $conversation = $this->latestConversation($request);
        $messages = $conversation
            ? $conversation->messages()
                ->when(isset($filters['after_id']), fn ($query) => $query->where('id', '>', $filters['after_id']))
                ->oldest('id')
                ->limit(100)
                ->get()
            : collect();

        return response()->json([
            'conversation' => $conversation ? [
                'id' => $conversation->id,
                'customer_name' => $conversation->customer_name,
                'status' => $conversation->status,
            ] : null,
            'messages' => $messages->map(fn (SupportMessage $message): array => $this->formatMessage($message)),
            'return_requests' => $conversation
                ? $conversation->returnWorkflows()
                    ->with('returnRequest')
                    ->get()
                    ->map(fn ($workflow): array => [
                        'id' => $workflow->return_request_id,
                        'type' => $workflow->returnRequest?->typeLabel() ?? 'Đổi / trả hàng',
                        'status' => $workflow->returnRequest?->statusLabel() ?? 'Không xác định',
                    ])
                : [],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'message' => ['required', 'string', 'max:2000'],
        ], [
            'message.required' => 'Vui lòng nhập nội dung tin nhắn.',
            'message.max' => 'Tin nhắn không được vượt quá 2000 ký tự.',
            'name.max' => 'Tên không được vượt quá 100 ký tự.',
            'email.email' => 'Email không hợp lệ.',
        ]);

        $visitorTokenHash = $this->visitorTokenHash($request);
        $conversation = SupportConversation::query()
            ->where('visitor_token_hash', $visitorTokenHash)
            ->where('status', SupportConversation::STATUS_OPEN)
            ->latest('id')
            ->first();

        if ($conversation === null) {
            $name = trim((string) ($data['name'] ?? ''));

            if ($name === '') {
                throw ValidationException::withMessages([
                    'name' => 'Vui lòng nhập tên của bạn để bắt đầu trò chuyện.',
                ]);
            }

            $conversation = SupportConversation::query()->create([
                'visitor_token_hash' => $visitorTokenHash,
                'customer_name' => $name,
                'customer_email' => filled($data['email'] ?? null) ? trim($data['email']) : null,
                'status' => SupportConversation::STATUS_OPEN,
            ]);
        }

        $message = $conversation->messages()->create([
            'sender_type' => SupportMessage::SENDER_CUSTOMER,
            'body' => trim($data['message']),
        ]);
        $conversation->touch();

        return response()->json([
            'conversation' => [
                'id' => $conversation->id,
                'customer_name' => $conversation->customer_name,
                'status' => $conversation->status,
            ],
            'messages' => [$this->formatMessage($message)],
        ], 201);
    }

    private function latestConversation(Request $request): ?SupportConversation
    {
        return SupportConversation::query()
            ->where('visitor_token_hash', $this->visitorTokenHash($request))
            ->latest('id')
            ->first();
    }

    private function visitorTokenHash(Request $request): string
    {
        $token = $request->session()->get('support_chat_token');

        if (! is_string($token) || $token === '') {
            $token = Str::random(64);
            $request->session()->put('support_chat_token', $token);
        }

        return hash('sha256', $token);
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
}
