<style>
    #support-chat-widget [hidden] {
        display: none !important;
    }

    #support-chat-widget {
        position: fixed;
        z-index: 1050;
        right: 24px;
        bottom: 24px;
        font-family: inherit;
    }

    #support-chat-widget .support-chat-launcher {
        width: 58px;
        height: 58px;
        border: 0;
        border-radius: 999px;
        background: #5b6cff;
        color: #fff;
        box-shadow: 0 8px 24px rgba(48, 65, 130, .28);
        font-size: 1.25rem;
    }

    #support-chat-widget .support-chat-panel {
        display: flex;
        flex-direction: column;
        width: min(360px, calc(100vw - 32px));
        height: min(500px, calc(100vh - 110px));
        margin-bottom: 12px;
        overflow: hidden;
        border-radius: 14px;
        background: #fff;
        box-shadow: 0 12px 40px rgba(31, 45, 61, .2);
    }

    #support-chat-widget .support-chat-header {
        padding: 16px 18px;
        background: #18212f;
        color: #fff;
    }

    #support-chat-widget .support-chat-body {
        display: flex;
        flex: 1;
        flex-direction: column;
        min-height: 0;
        padding: 14px;
    }

    #support-chat-widget .support-chat-messages {
        flex: 1;
        overflow-y: auto;
        padding: 4px 2px 12px;
    }

    #support-chat-widget .support-chat-returns {
        padding: 8px 10px;
        border-radius: 8px;
        background: #f7f8fb;
        font-size: .85rem;
    }

    #support-chat-widget .support-chat-message {
        width: fit-content;
        max-width: 88%;
        margin: 0 0 10px;
        padding: 9px 12px;
        border-radius: 12px;
        background: #f0f2f6;
        overflow-wrap: anywhere;
        white-space: pre-wrap;
    }

    #support-chat-widget .support-chat-message.is-customer {
        margin-left: auto;
        background: #eceeff;
    }

    #support-chat-widget .support-chat-message small {
        display: block;
        margin-top: 4px;
        color: #7d8796;
    }

    #support-chat-widget .support-chat-error {
        min-height: 20px;
        color: #c44450;
        font-size: .85rem;
    }

    @media (max-width: 575.98px) {
        #support-chat-widget {
            right: 16px;
            bottom: 16px;
        }
    }
</style>

<div id="support-chat-widget"
    data-read-url="{{ route('support-chat.index') }}"
    data-send-url="{{ route('support-chat.store') }}">
    <section class="support-chat-panel" aria-label="Trò chuyện với CSKH" hidden>
        <div class="support-chat-header d-flex justify-content-between align-items-center">
            <div>
                <strong>CSKH AOVU Shop</strong>
                <div class="small text-white-50">Chúng tôi sẵn sàng hỗ trợ bạn</div>
            </div>
            <button type="button" class="btn btn-link text-white p-1" data-chat-close aria-label="Thu nhỏ khung chat">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="support-chat-body">
            <div class="support-chat-error" data-chat-error role="status" aria-live="polite"></div>
            <div class="support-chat-returns mb-2" data-chat-returns hidden aria-live="polite"></div>
            <div class="support-chat-messages" data-chat-messages aria-live="polite"></div>
            <p class="text-muted small mb-2" data-chat-closed hidden>
                Hội thoại này đã được đóng. Bạn có thể bắt đầu một cuộc trò chuyện mới.
            </p>
            <button type="button" class="btn btn-outline-primary btn-sm mb-2" data-chat-new hidden>
                Bắt đầu trò chuyện mới
            </button>
            <form data-chat-start>
                <div class="form-group mb-2">
                    <label class="sr-only" for="support-chat-name">Tên của bạn</label>
                    <input id="support-chat-name" name="name" class="form-control" maxlength="100"
                        placeholder="Tên của bạn" autocomplete="name" required>
                </div>
                <div class="form-group mb-2">
                    <label class="sr-only" for="support-chat-email">Email</label>
                    <input id="support-chat-email" name="email" type="email" class="form-control" maxlength="255"
                        placeholder="Email (không bắt buộc)" autocomplete="email">
                </div>
                <div class="form-group mb-2">
                    <label class="sr-only" for="support-chat-first-message">Tin nhắn</label>
                    <textarea id="support-chat-first-message" name="message" class="form-control" rows="2"
                        maxlength="2000" placeholder="Bạn cần chúng tôi hỗ trợ điều gì?" required></textarea>
                </div>
                <button class="btn btn-primary btn-block" type="submit">Gửi tin nhắn</button>
            </form>
            <form data-chat-reply hidden>
                <div class="input-group">
                    <label class="sr-only" for="support-chat-reply-message">Tin nhắn</label>
                    <textarea id="support-chat-reply-message" name="message" class="form-control" rows="2"
                        maxlength="2000" placeholder="Nhập tin nhắn..." required></textarea>
                    <div class="input-group-append">
                        <button class="btn btn-primary" type="submit" aria-label="Gửi tin nhắn">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </section>
    <button class="support-chat-launcher" type="button" aria-label="Mở trò chuyện với CSKH" aria-expanded="false">
        <i class="fas fa-comments"></i>
    </button>
</div>

<script>
    (function () {
        var widget = document.getElementById('support-chat-widget');
        if (!widget) return;

        var panel = widget.querySelector('.support-chat-panel');
        var launcher = widget.querySelector('.support-chat-launcher');
        var messageList = widget.querySelector('[data-chat-messages]');
        var returnList = widget.querySelector('[data-chat-returns]');
        var startForm = widget.querySelector('[data-chat-start]');
        var replyForm = widget.querySelector('[data-chat-reply]');
        var error = widget.querySelector('[data-chat-error]');
        var closedNote = widget.querySelector('[data-chat-closed]');
        var newChatButton = widget.querySelector('[data-chat-new]');
        var conversation = null;
        var lastMessageId = 0;
        var pollTimer = null;

        function appendMessage(message) {
            if (messageList.querySelector('[data-message-id="' + message.id + '"]')) return;

            var bubble = document.createElement('div');
            bubble.className = 'support-chat-message'
                + (message.sender_type === 'customer' ? ' is-customer' : '');
            bubble.dataset.messageId = message.id;

            var body = document.createElement('div');
            body.textContent = message.body;
            bubble.appendChild(body);

            var time = document.createElement('small');
            time.textContent = (message.sender_name ? message.sender_name + ' · ' : '') + message.created_at;
            bubble.appendChild(time);
            messageList.appendChild(bubble);
            lastMessageId = Math.max(lastMessageId, Number(message.id));
            messageList.scrollTop = messageList.scrollHeight;
        }

        function renderState(data, resetMessages) {
            if (resetMessages) {
                conversation = data.conversation;
                lastMessageId = 0;
                messageList.replaceChildren();
            } else if (data.conversation) {
                conversation = data.conversation;
            }

            (data.messages || []).forEach(appendMessage);
            returnList.replaceChildren();
            (data.return_requests || []).forEach(function (ticket) {
                var summary = document.createElement('div');
                summary.className = 'mb-1';
                summary.textContent = '#DT-' + String(ticket.id).padStart(6, '0')
                    + ' · ' + ticket.type + ' · ' + ticket.status;
                returnList.appendChild(summary);
            });
            returnList.hidden = returnList.childElementCount === 0;
            var isClosed = conversation && conversation.status === 'closed';
            var hasConversation = Boolean(conversation);
            startForm.hidden = hasConversation;
            replyForm.hidden = !hasConversation || isClosed;
            closedNote.hidden = !isClosed;
            newChatButton.hidden = !isClosed;
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
                var firstError = data.errors ? Object.values(data.errors)[0][0] : 'Không thể gửi tin nhắn lúc này.';
                throw new Error(firstError);
            }

            return data;
        }

        async function refresh() {
            try {
                var url = new URL(widget.dataset.readUrl, window.location.origin);
                if (conversation) url.searchParams.set('after_id', lastMessageId);
                var data = await request(url.toString());

                if (data.conversation && (!conversation || data.conversation.id !== conversation.id)) {
                    renderState(data, true);
                } else {
                    renderState(data, false);
                }
                error.textContent = '';
            } catch (requestError) {
                error.textContent = requestError.message;
            }
        }

        function setOpen(isOpen) {
            panel.hidden = !isOpen;
            launcher.setAttribute('aria-expanded', String(isOpen));
            if (pollTimer) window.clearInterval(pollTimer);
            pollTimer = isOpen ? window.setInterval(refresh, 5000) : null;
            if (isOpen) refresh();
        }

        launcher.addEventListener('click', function () {
            setOpen(panel.hidden);
        });
        widget.querySelector('[data-chat-close]').addEventListener('click', function () {
            setOpen(false);
        });
        newChatButton.addEventListener('click', function () {
            conversation = null;
            lastMessageId = 0;
            messageList.replaceChildren();
            renderState({ conversation: null, messages: [] }, true);
            widget.querySelector('#support-chat-name').focus();
        });

        startForm.addEventListener('submit', async function (event) {
            event.preventDefault();
            error.textContent = '';
            var values = new FormData(startForm);

            try {
                var data = await request(widget.dataset.sendUrl, {
                    method: 'POST',
                    body: JSON.stringify({
                        name: values.get('name'),
                        email: values.get('email'),
                        message: values.get('message')
                    })
                });
                startForm.reset();
                renderState(data, true);
            } catch (requestError) {
                error.textContent = requestError.message;
            }
        });

        replyForm.addEventListener('submit', async function (event) {
            event.preventDefault();
            error.textContent = '';
            var values = new FormData(replyForm);

            try {
                var data = await request(widget.dataset.sendUrl, {
                    method: 'POST',
                    body: JSON.stringify({ message: values.get('message') })
                });
                replyForm.reset();
                renderState(data, false);
            } catch (requestError) {
                error.textContent = requestError.message;
            }
        });
    }());
</script>
