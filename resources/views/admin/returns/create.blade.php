@extends('layouts.admin')

@section('title', 'Tạo phiếu đổi / trả')
@section('page-heading', 'Tạo phiếu đổi / trả')
@section('page-subheading', 'Lập yêu cầu thủ công cho khách hàng và đơn hàng đã chọn.')
@section('breadcrumb')
    <a href="{{ route('admin.returns.index') }}">Đổi / trả hàng</a>
    <span class="mx-1">/</span>
    Tạo phiếu
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-8">
            <form method="POST" action="{{ route('admin.returns.store') }}">
                @csrf
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-file-circle-plus text-primary mr-2"></i>Thông tin phiếu</h3>
                    </div>
                    <div class="card-body">
                        @if ($errors->any())
                            <div class="alert alert-danger" role="alert">
                                <ul class="mb-0 pl-3">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="form-group">
                            <label for="return-customer-search">Khách hàng <span class="text-danger">*</span></label>
                            <input id="return-customer-search" type="search" class="form-control"
                                autocomplete="off" minlength="2" placeholder="Tìm theo tên, email hoặc số điện thoại"
                                value="{{ old('customer_search') }}">
                            <input type="hidden" name="customer_id" value="{{ old('customer_id') }}" required>
                            <div class="list-group mt-1" data-customer-results></div>
                            <small class="form-text text-muted" data-selected-customer>
                                @if (old('customer_id'))
                                    Khách hàng đã chọn. Tìm lại để thay đổi.
                                @else
                                    Chọn khách hàng trong kết quả tìm kiếm.
                                @endif
                            </small>
                            @error('customer_id')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group">
                            <label for="return-order">Đơn hàng <span class="text-danger">*</span></label>
                            <select id="return-order" name="order_id" class="form-control" required disabled>
                                <option value="">Chọn khách hàng trước</option>
                            </select>
                            @error('order_id')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group">
                            <label for="return-item">Sản phẩm trong đơn <span class="text-danger">*</span></label>
                            <select id="return-item" name="chi_tiet_don_hang_id" class="form-control" required disabled>
                                <option value="">Chọn đơn hàng trước</option>
                            </select>
                            <small class="form-text text-muted" data-available-quantity></small>
                            @error('chi_tiet_don_hang_id')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="return-type">Loại yêu cầu <span class="text-danger">*</span></label>
                                <select id="return-type" name="loai_yeu_cau" class="form-control" required>
                                    <option value="1" @selected(old('loai_yeu_cau', '1') === '1')>Đổi hàng</option>
                                    <option value="2" @selected(old('loai_yeu_cau') === '2')>Trả hàng / hoàn tiền</option>
                                </select>
                                @error('loai_yeu_cau')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                            <div class="form-group col-md-6">
                                <label for="return-quantity">Số lượng <span class="text-danger">*</span></label>
                                <input id="return-quantity" name="so_luong" type="number" min="1"
                                    value="{{ old('so_luong', 1) }}" class="form-control" required disabled>
                                @error('so_luong')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="form-group mb-0">
                            <label for="return-reason">Lý do / nội dung khách xác nhận <span class="text-danger">*</span></label>
                            <textarea id="return-reason" name="ly_do" rows="5" maxlength="5000"
                                class="form-control" required placeholder="Ghi rõ lý do đổi/trả và nội dung khách đã trao đổi...">{{ old('ly_do') }}</textarea>
                            @error('ly_do')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="card-footer bg-white d-flex justify-content-between">
                        <a href="{{ route('admin.returns.index') }}" class="btn btn-default">Hủy</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-1"></i>Tạo phiếu
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <div class="col-lg-4">
            <div class="card card-outline card-info">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-info-circle mr-1"></i>Sau khi tạo phiếu</h3>
                </div>
                <div class="card-body">
                    <p class="mb-2">Phiếu sẽ được tạo ở trạng thái <strong>Chờ tiếp nhận</strong> và lưu trong lịch sử xử lý.</p>
                    <p class="mb-2">Tiếp tục tại trang chi tiết để ghi nhận khách đồng ý, kiểm tra chính sách, nhận hàng, đổi sản phẩm hoặc hoàn tiền.</p>
                    <p class="text-muted small mb-0">Phiếu tạo thủ công không tự gắn vào hội thoại. Muốn liên kết chat, hãy tạo phiếu từ hộp thư CSKH.</p>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        (function () {
            var searchUrl = @json(route('admin.support-chat.customers'));
            var ordersTemplate = @json(route('admin.support-chat.customers.orders', ['customer' => '__ID__']));
            var itemsTemplate = @json(route('admin.support-chat.orders.items', ['order' => '__ID__']));
            var search = document.getElementById('return-customer-search');
            var customerId = document.querySelector('[name="customer_id"]');
            var customerResults = document.querySelector('[data-customer-results]');
            var selectedCustomer = document.querySelector('[data-selected-customer]');
            var orderSelect = document.getElementById('return-order');
            var itemSelect = document.getElementById('return-item');
            var quantity = document.getElementById('return-quantity');
            var availableQuantity = document.querySelector('[data-available-quantity]');
            var searchTimer = null;
            var searchGeneration = 0;
            var selectedOrderGeneration = 0;

            function endpoint(template, id) {
                return template.replace('__ID__', encodeURIComponent(id));
            }

            function resetSelect(select, label) {
                select.replaceChildren(new Option(label, ''));
                select.disabled = true;
            }

            async function getJson(url) {
                var response = await fetch(url, {
                    headers: { 'Accept': 'application/json' }
                });
                var data = await response.json();

                if (!response.ok) {
                    var message = data.errors ? Object.values(data.errors)[0][0] : 'Không thể tải dữ liệu.';
                    throw new Error(message);
                }

                return data;
            }

            async function loadOrders(customer, oldOrderId) {
                resetSelect(orderSelect, 'Đang tải đơn hàng...');
                resetSelect(itemSelect, 'Chọn đơn hàng trước');
                quantity.disabled = true;
                availableQuantity.textContent = '';

                try {
                    var data = await getJson(endpoint(ordersTemplate, customer.id));
                    orderSelect.replaceChildren(new Option('Chọn đơn hàng', ''));
                    data.orders.forEach(function (order) {
                        orderSelect.add(new Option(
                            order.ma_don_hang + ' · ' + (order.ngay_dat || ''),
                            order.id
                        ));
                    });
                    orderSelect.disabled = data.orders.length === 0;
                    if (data.orders.length === 0) {
                        orderSelect.replaceChildren(new Option('Khách hàng chưa có đơn hàng', ''));
                    } else if (oldOrderId) {
                        orderSelect.value = oldOrderId;
                        if (orderSelect.value) orderSelect.dispatchEvent(new Event('change'));
                    }
                } catch (requestError) {
                    resetSelect(orderSelect, requestError.message);
                }
            }

            search.addEventListener('input', function () {
                window.clearTimeout(searchTimer);
                var generation = ++searchGeneration;
                customerId.value = '';
                selectedCustomer.textContent = 'Chọn khách hàng trong kết quả tìm kiếm.';
                customerResults.replaceChildren();
                resetSelect(orderSelect, 'Chọn khách hàng trước');
                resetSelect(itemSelect, 'Chọn đơn hàng trước');
                quantity.disabled = true;
                availableQuantity.textContent = '';

                if (search.value.trim().length < 2) return;

                searchTimer = window.setTimeout(async function () {
                    try {
                        var url = new URL(searchUrl, window.location.origin);
                        url.searchParams.set('q', search.value.trim());
                        var data = await getJson(url.toString());
                        if (generation !== searchGeneration) return;

                        data.customers.forEach(function (customer) {
                            var option = document.createElement('button');
                            option.type = 'button';
                            option.className = 'list-group-item list-group-item-action py-2';
                            option.textContent = customer.ho_ten + ' · ' + customer.email
                                + (customer.so_dien_thoai ? ' · ' + customer.so_dien_thoai : '');
                            option.addEventListener('click', function () {
                                customerId.value = customer.id;
                                search.value = customer.ho_ten;
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
                        if (generation === searchGeneration) {
                            customerResults.textContent = requestError.message;
                        }
                    }
                }, 250);
            });

            orderSelect.addEventListener('change', async function () {
                if (!orderSelect.value || !customerId.value) return;

                var generation = ++selectedOrderGeneration;
                resetSelect(itemSelect, 'Đang tải sản phẩm...');
                quantity.disabled = true;
                availableQuantity.textContent = '';

                try {
                    var url = new URL(endpoint(itemsTemplate, orderSelect.value));
                    url.searchParams.set('customer_id', customerId.value);
                    var data = await getJson(url.toString());
                    if (generation !== selectedOrderGeneration) return;

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
                        itemSelect.replaceChildren(new Option('Không còn số lượng được đổi/trả', ''));
                    }

                    itemSelect.onchange = function () {
                        var item = data.items.find(function (candidate) {
                            return String(candidate.id) === itemSelect.value;
                        });
                        quantity.max = item ? item.available_quantity : 1;
                        quantity.value = 1;
                        quantity.disabled = !item;
                        availableQuantity.textContent = item
                            ? 'Còn tối đa ' + item.available_quantity + ' sản phẩm có thể đổi/trả.'
                            : '';
                    };
                    itemSelect.dispatchEvent(new Event('change'));
                } catch (requestError) {
                    if (generation === selectedOrderGeneration) {
                        resetSelect(itemSelect, requestError.message);
                    }
                }
            });

            @if (old('customer_id'))
                loadOrders({ id: @json(old('customer_id')) }, @json(old('order_id')));
            @endif
        }());
    </script>
@endsection
