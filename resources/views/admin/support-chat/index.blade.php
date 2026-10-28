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

        .support-return-list {
            border-bottom: 1px solid #edf0f4;
            padding: 10px 16px;
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
            data-status-template="{{ route('admin.support-chat.status.update', ['conversation' => '__ID__']) }}"
            data-return-template="{{ route('admin.returns.show', ['returnRequest' => '__ID__']) }}"
            data-create-return-template="{{ route('admin.support-chat.return-requests.store', ['conversation' => '__ID__']) }}"
            data-customers-url="{{ route('admin.support-chat.customers') }}"
            data-orders-template="{{ route('admin.support-chat.customers.orders', ['customer' => '__ID__']) }}"
            data-items-template="{{ route('admin.support-chat.orders.items', ['order' => '__ID__']) }}">
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
                        <div class="support-return-list" data-return-request-list hidden></div>
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
                    <button class="btn btn-outline-primary btn-sm mt-2" type="button" data-toggle-return-form hidden>
                        <i class="fas fa-exchange-alt mr-1"></i>Tạo phiếu đổi/trả
                    </button>
                    <form class="border rounded p-3 mt-2" data-return-form hidden>
                        @csrf
                        <h6 class="font-weight-bold">Tạo phiếu từ cuộc trò chuyện</h6>
                        <div class="form-group">
                            <label for="support-return-customer-search">Tìm khách hàng</label>
                            <input id="support-return-customer-search" type="search" class="form-control"
                                autocomplete="off" placeholder="Tên, email hoặc số điện thoại" minlength="2">
                            <div class="list-group mt-1" data-customer-results></div>
                            <input type="hidden" name="customer_id" data-customer-id required>
                            <small class="form-text text-muted" data-selected-customer>Chưa chọn khách hàng.</small>
                        </div>
                        <div class="form-group">
                            <label for="support-return-order">Đơn hàng</label>
                            <select id="support-return-order" name="order_id" class="form-control" required disabled>
                                <option value="">Chọn khách hàng trước</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="support-return-item">Sản phẩm trong đơn</label>
                            <select id="support-return-item" name="chi_tiet_don_hang_id" class="form-control" required disabled>
                                <option value="">Chọn đơn hàng trước</option>
                            </select>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-sm-6">
                                <label for="support-return-type">Loại yêu cầu</label>
                                <select id="support-return-type" name="loai_yeu_cau" class="form-control" required>
                                    <option value="1">Đổi hàng</option>
                                    <option value="2">Trả hàng / hoàn tiền</option>
                                </select>
                            </div>
                            <div class="form-group col-sm-6">
                                <label for="support-return-quantity">Số lượng</label>
                                <input id="support-return-quantity" name="so_luong" type="number" min="1"
                                    value="1" class="form-control" required disabled>
                                <small class="form-text text-muted" data-available-quantity></small>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="support-return-reason">Lý do</label>
                            <textarea id="support-return-reason" name="ly_do" rows="3" maxlength="5000"
                                class="form-control" required></textarea>
                        </div>
                        <button class="btn btn-primary btn-sm" type="submit">
                            <i class="fas fa-save mr-1"></i>Lưu phiếu và báo khách
                        </button>
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
            var returnRequestList = inbox.querySelector('[data-return-request-list]');
            var error = inbox.querySelector('[data-chat-error]');
            var returnToggle = inbox.querySelector('[data-toggle-return-form]');
            var returnForm = inbox.querySelector('[data-return-form]');
            var customerSearch = inbox.querySelector('#support-return-customer-search');
            var customerResults = inbox.querySelector('[data-customer-results]');
            var customerId = inbox.querySelector('[data-customer-id]');
            var selectedCustomer = inbox.querySelector('[data-selected-customer]');
            var orderSelect = inbox.querySelector('#support-return-order');
            var itemSelect = inbox.querySelector('#support-return-item');
            var quantityInput = inbox.querySelector('#support-return-quantity');
            var availableQuantity = inbox.querySelector('[data-available-quantity]');
            var selectedButton = null;
            var lastMessageId = 0;
            var pollTimer = null;
            var customerSearchTimer = null;
            var customerSearchRequest = 0;

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

            function renderReturnRequests(tickets) {
                returnRequestList.replaceChildren();
                tickets.forEach(function (ticket) {
                    var link = document.createElement('a');
                    link.href = endpoint(inbox.dataset.returnTemplate, ticket.id);
                    link.className = 'd-block';
                    link.textContent = '#DT-' + String(ticket.id).padStart(6, '0')
                        + ' · ' + ticket.type + ' · ' + ticket.status;
                    returnRequestList.appendChild(link);
                });
                returnRequestList.hidden = returnRequestList.childElementCount === 0;
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
                    renderReturnRequests(data.return_requests || []);
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
                returnToggle.hidden = !isOpen;
                if (!isOpen) returnForm.hidden = true;
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
                returnForm.action = endpoint(inbox.dataset.createReturnTemplate, button.dataset.conversationId);
                returnForm.reset();
                customerId.value = '';
                selectedCustomer.textContent = 'Chưa chọn khách hàng.';
                customerResults.replaceChildren();
                resetSelect(orderSelect, 'Chọn khách hàng trước');
                resetSelect(itemSelect, 'Chọn đơn hàng trước');
                quantityInput.disabled = true;
                availableQuantity.textContent = '';
                returnForm.hidden = true;
                renderReturnRequests([]);
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

            function resetSelect(select, label) {
                select.replaceChildren(new Option(label, ''));
                select.disabled = true;
            }

            async function loadOrders(customer) {
                resetSelect(orderSelect, 'Đang tải đơn hàng...');
                resetSelect(itemSelect, 'Chọn đơn hàng trước');
                quantityInput.disabled = true;
                availableQuantity.textContent = '';

                try {
                    var data = await request(endpoint(inbox.dataset.ordersTemplate, customer.id));
                    orderSelect.replaceChildren(new Option('Chọn đơn hàng', ''));
                    data.orders.forEach(function (order) {
                        var label = order.ma_don_hang + ' · ' + (order.ngay_dat || '');
                        orderSelect.add(new Option(label, order.id));
                    });
                    orderSelect.disabled = data.orders.length === 0;
                    if (data.orders.length === 0) {
                        orderSelect.replaceChildren(new Option('Khách hàng chưa có đơn hàng', ''));
                    }
                } catch (requestError) {
                    error.textContent = requestError.message;
                    resetSelect(orderSelect, 'Không thể tải đơn hàng');
                }
            }

            customerSearch.addEventListener('input', function () {
                window.clearTimeout(customerSearchTimer);
                customerSearchRequest++;
                var currentSearchRequest = customerSearchRequest;
                customerId.value = '';
                selectedCustomer.textContent = 'Chưa chọn khách hàng.';
                customerResults.replaceChildren();
                resetSelect(orderSelect, 'Chọn khách hàng trước');
                resetSelect(itemSelect, 'Chọn đơn hàng trước');
                quantityInput.disabled = true;

                if (customerSearch.value.trim().length < 2) return;

                customerSearchTimer = window.setTimeout(async function () {
                    try {
                        var url = new URL(inbox.dataset.customersUrl, window.location.origin);
                        url.searchParams.set('q', customerSearch.value.trim());
                        var data = await request(url.toString());
                        if (currentSearchRequest !== customerSearchRequest) return;

                        data.customers.forEach(function (customer) {
                            var option = document.createElement('button');
                            option.type = 'button';
                            option.className = 'list-group-item list-group-item-action py-2';
                            option.textContent = customer.ho_ten + ' · ' + customer.email
                                + (customer.so_dien_thoai ? ' · ' + customer.so_dien_thoai : '');
                            option.addEventListener('click', function () {
                                customerId.value = customer.id;
                                customerSearch.value = customer.ho_ten;
                                selectedCustomer.textContent = customer.ho_ten + ' · ' + customer.email;
                                customerResults.replaceChildren();
                                loadOrders(customer);
                            });
                            customerResults.appendChild(option);
                        });
                        if (data.customers.length === 0) {
                            var empty = document.createElement('div');
                            empty.className = 'list-group-item text-muted';
                            empty.textContent = 'Không tìm thấy khách hàng.';
                            customerResults.appendChild(empty);
                        }
                    } catch (requestError) {
                        error.textContent = requestError.message;
                    }
                }, 250);
            });

            orderSelect.addEventListener('change', async function () {
                if (!orderSelect.value || !customerId.value) return;

                resetSelect(itemSelect, 'Đang tải sản phẩm...');
                quantityInput.disabled = true;
                availableQuantity.textContent = '';

                try {
                    var url = new URL(endpoint(inbox.dataset.itemsTemplate, orderSelect.value));
                    url.searchParams.set('customer_id', customerId.value);
                    var data = await request(url.toString());
                    itemSelect.replaceChildren(new Option('Chọn sản phẩm', ''));
                    data.items.forEach(function (item) {
                        itemSelect.add(new Option(
                            item.name + ' · còn ' + item.available_quantity + ' · '
                                + Number(item.unit_price).toLocaleString('vi-VN') + ' ₫',
                            item.id
                        ));
                    });
                    itemSelect.disabled = data.items.length === 0;
                    if (data.items.length === 0) {
                        itemSelect.replaceChildren(new Option('Không còn số lượng có thể đổi/trả', ''));
                    }

                    itemSelect.onchange = function () {
                        var selectedItem = data.items.find(function (item) {
                            return String(item.id) === itemSelect.value;
                        });
                        quantityInput.max = selectedItem ? selectedItem.available_quantity : 1;
                        quantityInput.value = 1;
                        quantityInput.disabled = !selectedItem;
                        availableQuantity.textContent = selectedItem
                            ? 'Tối đa ' + selectedItem.available_quantity + ' sản phẩm'
                            : '';
                    };
                } catch (requestError) {
                    error.textContent = requestError.message;
                    resetSelect(itemSelect, 'Không thể tải sản phẩm');
                }
            });

            returnToggle.addEventListener('click', function () {
                returnForm.hidden = !returnForm.hidden;
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
