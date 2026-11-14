<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\ReturnRequest;
use App\Models\SupportConversation;
use App\Models\SupportMessage;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class CustomerCareController extends Controller
{
    public function index(Request $request): View
    {
        $trackedOrder = null;
        $trackedItems = collect();
        $trackedReturnRequests = collect();

        if ($request->filled('ma_don_hang')) {
            $code = trim((string) $request->query('ma_don_hang'));
            $phone = trim((string) $request->query('so_dien_thoai'));

            if (Schema::hasTable('don_hangs')) {
                $query = Order::query()->where('ma_don_hang', $code);
                if ($phone !== '') {
                    $query->where('sdt_nguoi_nhan', 'like', "%{$phone}%");
                }
                $trackedOrder = $query->first();

                if ($trackedOrder && Schema::hasTable('chi_tiet_don_hangs')) {
                    $trackedItems = DB::table('chi_tiet_don_hangs')
                        ->where('don_hang_id', $trackedOrder->id)
                        ->get();
                }

                if ($trackedOrder && Schema::hasTable('yeu_cau_doi_tras')) {
                    $trackedReturnRequests = ReturnRequest::query()
                        ->where('don_hang_id', $trackedOrder->id)
                        ->get();
                }
            }
        }

        return view('storefront.cskh.index', [
            'trackedOrder' => $trackedOrder,
            'trackedItems' => $trackedItems,
            'trackedReturnRequests' => $trackedReturnRequests,
            'searched' => $request->has('ma_don_hang'),
        ]);
    }

    public function submitInquiry(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ho_ten' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'so_dien_thoai' => ['required', 'string', 'max:15'],
            'chu_de' => ['required', 'string', 'max:100'],
            'noi_dung' => ['required', 'string', 'max:2000'],
        ], [
            'ho_ten.required' => 'Vui lòng nhập họ và tên của bạn.',
            'ho_ten.max' => 'Họ tên không được vượt quá 100 ký tự.',
            'email.required' => 'Vui lòng cung cấp email liên hệ.',
            'email.email' => 'Địa chỉ email không đúng định dạng.',
            'so_dien_thoai.required' => 'Vui lòng nhập số điện thoại để CSKH liên hệ.',
            'so_dien_thoai.max' => 'Số điện thoại không quá 15 ký tự.',
            'chu_de.required' => 'Vui lòng chọn chủ đề yêu cầu.',
            'noi_dung.required' => 'Vui lòng nhập nội dung chi tiết cần hỗ trợ.',
            'noi_dung.max' => 'Nội dung không vượt quá 2000 ký tự.',
        ]);

        if (Schema::hasTable('support_conversations') && Schema::hasTable('support_messages')) {
            $token = $request->session()->get('support_chat_token');
            if (! is_string($token) || $token === '') {
                $token = Str::random(64);
                $request->session()->put('support_chat_token', $token);
            }
            $visitorTokenHash = hash('sha256', $token);

            $conversation = SupportConversation::query()
                ->where('visitor_token_hash', $visitorTokenHash)
                ->where('status', SupportConversation::STATUS_OPEN)
                ->latest('id')
                ->first();

            if (! $conversation) {
                $conversation = SupportConversation::query()->create([
                    'visitor_token_hash' => $visitorTokenHash,
                    'customer_name' => trim($data['ho_ten']),
                    'customer_email' => strtolower(trim($data['email'])),
                    'status' => SupportConversation::STATUS_OPEN,
                ]);
            }

            $formattedBody = sprintf(
                "📌 [YÊU CẦU CSKH - %s]\n👤 Khách hàng: %s\n📞 SĐT: %s\n✉️ Email: %s\n\nNội dung: %s",
                trim($data['chu_de']),
                trim($data['ho_ten']),
                trim($data['so_dien_thoai']),
                trim($data['email']),
                trim($data['noi_dung'])
            );

            $conversation->messages()->create([
                'sender_type' => SupportMessage::SENDER_CUSTOMER,
                'sender_name' => trim($data['ho_ten']),
                'body' => $formattedBody,
            ]);
            $conversation->touch();
        }

        return redirect()->route('cskh.index')
            ->with('success', 'Yêu cầu CSKH của bạn đã được ghi nhận thành công! Tư vấn viên AOVU Shop sẽ hỗ trợ bạn ngay qua cửa sổ chat bên dưới.')
            ->with('open_support_chat', true);
    }
}
