@extends('layouts.admin')

@section('title', 'CSKH trực tuyến')
@section('page-heading', 'CSKH trực tuyến')
@section('page-subheading', 'Tiếp nhận và phản hồi tin nhắn từ khách hàng.')
@section('breadcrumb', 'CSKH trực tuyến')

@section('styles')
    <style>
        .support-inbox [hidden] {
            display: none !important;
        }

        .support-inbox {
            display: grid;
            grid-template-columns: minmax(260px, 360px) minmax(0, 1fr);
            min-height: 620px;
            overflow: hidden;
        }

        .support-inbox-list {
            border-right: 1px solid #edf0f4;
        }

        .support-conversation-button {
            width: 100%;
            padding: 14px 16px;
            border: 0;
            border-bottom: 1px solid #edf0f4;
            background: #fff;
            text-align: left;
        }

        .support-conversation-button:hover,
        .support-conversation-button.is-active {
            background: #f5f6ff;
        }

        .support-conversation-button .support-preview {
            display: block;
            overflow: hidden;
            color: #8994a4;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .support-message-list {
            display: flex;
            flex: 1;
            flex-direction: column;
            gap: 12px;
            min-height: 360px;
            max-height: 480px;
            overflow-y: auto;
            padding: 20px;
        }

        .support-message {
            width: fit-content;
            max-width: min(80%, 620px);
            padding: 10px 14px;
            border-radius: 12px;
            background: #f0f2f6;
            overflow-wrap: anywhere;
            white-space: pre-wrap;
        }

        .support-message.is-staff {
            align-self: flex-end;
            background: #eceeff;
        }

        .support-message small {
            display: block;
            margin-top: 5px;
            color: #7d8796;
        }

        .support-order-list {
            margin-top: 6px;
            font-size: .85rem;
        }

        .support-chat-detail {
            display: flex;
            min-width: 0;
            flex-direction: column;
        }

        @media (max-width: 767.98px) {
            .support-inbox {
                grid-template-columns: 1fr;
            }

            .support-inbox-list {
                max-height: 260px;
                overflow-y: auto;
                border-right: 0;
                border-bottom: 1px solid #edf0f4;
            }
        }
    </style>
@endsection

@section('content')
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
        <div class="btn-group mb-2" role="group" aria-label="Lọc hội thoại">
            <a href="{{ route('admin.support-chat.index', ['status' => 'open']) }}"
                class="btn {{ $status === 'open' ? 'btn-primary' : 'btn-outline-primary' }}">
                Đang mở <span class="badge badge-light ml-1">{{ number_format($openCount) }}</span>
            </a>
            <a href="{{ route('admin.support-chat.index', ['status' => 'closed']) }}"
                class="btn {{ $status === 'closed' ? 'btn-primary' : 'btn-outline-primary' }}">
                Đã đóng
            </a>
        </div>
        <span class="text-muted small mb-2">Tự động cập nhật tin nhắn mỗi 5 giây</span>
    </div>

    <div class="card">
        <div class="support-inbox"
            data-messages-template="{{ route('admin.support-chat.messages', ['conversation' => '__ID__']) }}"
            data-send-template="{{ route('admin.support-chat.messages.store', ['conversation' => '__ID__']) }}"
            data-status-template="{{ route('admin.support-chat.status.update', ['conversation' => '__ID__']) }}">
            <div class="support-inbox-list" aria-label="Danh sách hội thoại">
                @forelse ($conversations as $conversation)
                    <button type="button" class="support-conversation-button"
                        data-conversation-id="{{ $conversation->id }}"
                        data-conversation-name="{{ $conversation->customer_name }}"
                        data-conversation-email="{{ $conversation->customer_email }}"
                        data-conversation-status="{{ $conversation->status }}"
                        aria-pressed="false">
                        <span class="d-flex justify-content-between align-items-center">
                            <strong>{{ $conversation->customer_name }}</strong>
                            <small class="text-muted">{{ $conversation->updated_at->format('d/m H:i') }}</small>
                        </span>
                        <span class="support-preview">
                            {{ \Illuminate\Support\Str::limit($conversation->latestMessage?->body ?? 'Chưa có tin nhắn', 75) }}
                        </span>
                        @if ($conversation->customer_email)
                            <small class="text-muted">{{ $conversation->customer_email }}</small>
                        @endif
                    </button>
                @empty
                    <div class="p-4 text-center text-muted">Chưa có hội thoại nào trong mục này.</div>
                @endforelse
            </div>

            <section class="support-chat-detail" aria-label="Nội dung hội thoại">
                <header class="d-flex flex-wrap justify-content-between align-items-center border-bottom p-3">
                    <div>
                        <strong data-selected-name>Chọn một hội thoại</strong>
                        <div class="text-muted small" data-selected-email></div>
                        <div class="support-order-list" data-customer-order-list hidden>
                            <div class="text-muted small">Đơn hàng của khách (mở đơn để xử lý đổi/trả):</div>
                            <div data-customer-order-links></div>
                        </div>
                    </div>
                    <form method="POST" data-status-form hidden>
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="closed" data-status-value>
                        <button class="btn btn-outline-secondary btn-sm" type="submit" data-status-button></button>
                    </form>
                </header>

                <div class="support-message-list" data-message-list aria-live="polite">
                    <p class="text-center text-muted m-auto">Tin nhắn sẽ hiển thị tại đây.</p>
                </div>

                <div class="border-top p-3">
                    <div class="text-danger small mb-2" data-chat-error role="status" aria-live="polite"></div>
                    <form class="input-group" data-reply-form hidden>
                        <label class="sr-only" for="support-admin-reply">Nội dung phản hồi</label>
                        <textarea id="support-admin-reply" name="message" class="form-control" rows="2"
                            maxlength="2000" placeholder="Nhập phản hồi cho khách hàng..." required></textarea>
                        <div class="input-group-append">
                            <button class="btn btn-primary" type="submit" aria-label="Gửi phản hồi">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </section>
        </div>
    </div>

    <div class="mt-3">{{ $conversations->links() }}</div>
@endsection

@section('scripts')
    <script>
        (function () {
            var inbox = document.querySelector('.support-inbox');
            if (!inbox) return;

            var buttons = Array.from(inbox.querySelectorAll('[data-conversation-id]'));
            var messageList = inbox.querySelector('[data-message-list]');
            var replyForm = inbox.querySelector('[data-reply-form]');
            var statusForm = inbox.querySelector('[data-status-form]');
            var statusValue = inbox.querySelector('[data-status-value]');
            var statusButton = inbox.querySelector('[data-status-button]');
            var selectedName = inbox.querySelector('[data-selected-name]');
            var selectedEmail = inbox.querySelector('[data-selected-email]');
            var customerOrderList = inbox.querySelector('[data-customer-order-list]');
            var customerOrderLinks = inbox.querySelector('[data-customer-order-links]');
            var error = inbox.querySelector('[data-chat-error]');
            var selectedButton = null;
            var lastMessageId = 0;
            var pollTimer = null;

            function endpoint(template, id) {
                return template.replace('__ID__', encodeURIComponent(id));
            }

            function appendMessage(message) {
                if (messageList.querySelector('[data-message-id="' + message.id + '"]')) return;

                var bubble = document.createElement('div');
                bubble.className = 'support-message'
                    + (message.sender_type === 'staff' ? ' is-staff' : '');
                bubble.dataset.messageId = message.id;

                var body = document.createElement('div');
                body.textContent = message.body;
                bubble.appendChild(body);

                var details = document.createElement('small');
                details.textContent = (message.sender_name || (message.sender_type === 'staff' ? 'CSKH' : 'Khách hàng'))
                    + ' · ' + message.created_at;
                bubble.appendChild(details);
                messageList.appendChild(bubble);
                lastMessageId = Math.max(lastMessageId, Number(message.id));
                messageList.scrollTop = messageList.scrollHeight;
            }

            function renderCustomerOrders(orders) {
                customerOrderLinks.replaceChildren();
                orders.forEach(function (order) {
                    var link = document.createElement('a');
                    link.href = order.url + '#returns';
                    link.className = 'd-block';
                    link.textContent = order.code
                        + (order.ordered_at ? ' · ' + order.ordered_at : '')
                        + ' · ' + order.status
                        + ' · ' + Number(order.total).toLocaleString('vi-VN') + ' ₫';
                    customerOrderLinks.appendChild(link);
                });
                customerOrderList.hidden = orders.length === 0;
            }

            async function request(url, options) {
                var response = await fetch(url, Object.assign({
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                }, options || {}));
                var data = await response.json();

                if (!response.ok) {
                    var firstError = data.errors ? Object.values(data.errors)[0][0] : 'Không thể cập nhật hội thoại.';
                    throw new Error(firstError);
                }

                return data;
            }

            async function refresh() {
                if (!selectedButton) return;

                try {
                    var url = new URL(endpoint(inbox.dataset.messagesTemplate, selectedButton.dataset.conversationId));
                    url.searchParams.set('after_id', lastMessageId);
                    var data = await request(url.toString());

                    if (data.conversation.status !== selectedButton.dataset.conversationStatus) {
                        selectedButton.dataset.conversationStatus = data.conversation.status;
                        updateStatusControls();
                    }
                    data.messages.forEach(appendMessage);
                    renderCustomerOrders(data.customer_orders || []);
                    error.textContent = '';
                } catch (requestError) {
                    error.textContent = requestError.message;
                }
            }

            function updateStatusControls() {
                var isOpen = selectedButton.dataset.conversationStatus === 'open';
                statusForm.hidden = false;
                statusValue.value = isOpen ? 'closed' : 'open';
                statusButton.textContent = isOpen ? 'Đóng hội thoại' : 'Mở lại hội thoại';
                replyForm.hidden = !isOpen;
            }

            function selectConversation(button) {
                if (pollTimer) window.clearInterval(pollTimer);
                selectedButton = button;
                selectedButton.setAttribute('aria-pressed', 'true');
                buttons.forEach(function (item) {
                    item.classList.toggle('is-active', item === button);
                    if (item !== button) item.setAttribute('aria-pressed', 'false');
                });
                selectedName.textContent = button.dataset.conversationName;
                selectedEmail.textContent = button.dataset.conversationEmail || '';
                statusForm.action = endpoint(inbox.dataset.statusTemplate, button.dataset.conversationId);
                renderCustomerOrders([]);
                lastMessageId = 0;
                messageList.replaceChildren();
                error.textContent = '';
                updateStatusControls();
                refresh();
                pollTimer = window.setInterval(refresh, 5000);
            }

            buttons.forEach(function (button) {
                button.addEventListener('click', function () {
                    selectConversation(button);
                });
            });

            statusForm.addEventListener('submit', function (event) {
                event.preventDefault();
                var submittedStatus = statusValue.value;

                fetch(statusForm.action, {
                    method: 'PATCH',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ status: submittedStatus })
                }).then(function (response) {
                    if (!response.ok) throw new Error('Không thể cập nhật trạng thái hội thoại.');
                    window.location.href = '{{ route('admin.support-chat.index') }}?status=' + submittedStatus;
                }).catch(function (requestError) {
                    error.textContent = requestError.message;
                });
            });

            replyForm.addEventListener('submit', async function (event) {
                event.preventDefault();
                if (!selectedButton) return;

                error.textContent = '';
                var values = new FormData(replyForm);

                try {
                    var data = await request(endpoint(
                        inbox.dataset.sendTemplate,
                        selectedButton.dataset.conversationId
                    ), {
                        method: 'POST',
                        body: JSON.stringify({ message: values.get('message') })
                    });
                    replyForm.reset();
                    data.messages.forEach(appendMessage);
                } catch (requestError) {
                    error.textContent = requestError.message;
                }
            });

            var requestedConversationId = new URLSearchParams(window.location.search).get('conversation_id');
            var initialConversation = buttons.find(function (button) {
                return button.dataset.conversationId === requestedConversationId;
            }) || buttons[0];
            if (initialConversation) selectConversation(initialConversation);
        }());
    </script>
@endsection
