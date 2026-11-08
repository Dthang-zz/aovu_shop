@php
    $returnErrors = $errors->getBag(\App\Http\Controllers\Admin\OrderReturnController::ERROR_BAG);
    $returnStatuses = \App\Models\ReturnRequest::statuses();
    $returnStatusBadges = \App\Models\ReturnRequest::statusBadges();
    $returnTypes = \App\Models\ReturnRequest::types();
@endphp

<div class="card" id="returns">
    <div class="card-header d-flex align-items-center flex-wrap">
        <h3 class="card-title"><i class="fas fa-exchange-alt text-warning mr-2"></i>Đổi / trả hàng</h3>
        <div class="card-tools ml-auto small text-muted">
            @if ($returnDeadline)
                @if ($returnDeadline->isPast())
                    <span class="text-danger">Đã quá hạn đổi/trả ({{ $returnDeadline->format('d/m/Y') }})</span>
                @else
                    Hạn đổi/trả: <strong>{{ $returnDeadline->format('d/m/Y') }}</strong>
                @endif
            @else
                Chỉ áp dụng khi đơn đã hoàn thành
            @endif
        </div>
    </div>
    <div class="card-body">
        @if (session('return_success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle mr-1"></i>{{ session('return_success') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Đóng"><span aria-hidden="true">&times;</span></button>
            </div>
        @endif
        @if ($returnErrors->any())
            <div class="alert alert-danger" role="alert">
                <ul class="mb-0 pl-3">@foreach ($returnErrors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        @forelse ($returnRequests as $returnRequest)
            @php
                $actions = \App\Models\ReturnRequest::allowedActions((int) $returnRequest->trang_thai);
                $isReturn = (int) $returnRequest->loai_yeu_cau === \App\Models\ReturnRequest::TYPE_RETURN;
                $updateUrl = route('admin.orders.returns.update', [$order->id, $returnRequest->id]);
            @endphp
            <div class="border rounded p-3 mb-3">
                <div class="d-flex justify-content-between align-items-start flex-wrap">
                    <div>
                        <strong>{{ \App\Http\Controllers\Admin\OrderReturnController::code($returnRequest->id) }}</strong>
                        <span class="badge badge-light border ml-1">{{ $returnTypes[$returnRequest->loai_yeu_cau] ?? 'Yêu cầu khác' }}</span>
                        <div class="mt-1">{{ $returnRequest->product_name }} × {{ $returnRequest->so_luong }}</div>
                    </div>
                    <span class="badge badge-{{ $returnStatusBadges[$returnRequest->trang_thai] ?? 'secondary' }} px-2 py-1">{{ $returnStatuses[$returnRequest->trang_thai] ?? 'Không xác định' }}</span>
                </div>

                <dl class="row small mb-0 mt-2">
                    <dt class="col-sm-3 text-muted font-weight-normal">Lý do</dt>
                    <dd class="col-sm-9 text-break">{{ $returnRequest->ly_do ?: '—' }}</dd>
                    @if (filled($returnRequest->phan_hoi))
                        <dt class="col-sm-3 text-muted font-weight-normal">Phản hồi</dt>
                        <dd class="col-sm-9 text-break">{{ $returnRequest->phan_hoi }}</dd>
                    @endif
                    @if ($returnRequest->so_tien_hoan)
                        <dt class="col-sm-3 text-muted font-weight-normal">Đã hoàn tiền</dt>
                        <dd class="col-sm-9 text-danger">{{ number_format($returnRequest->so_tien_hoan, 0, ',', '.') }} ₫@if ($returnRequest->ma_giao_dich_hoan) · Mã GD: {{ $returnRequest->ma_giao_dich_hoan }}@endif</dd>
                    @endif
                    @if ($returnRequest->replacement_label)
                        <dt class="col-sm-3 text-muted font-weight-normal">Hàng đổi</dt>
                        <dd class="col-sm-9">{{ $returnRequest->replacement_label }}</dd>
                    @endif
                    <dt class="col-sm-3 text-muted font-weight-normal">Mốc thời gian</dt>
                    <dd class="col-sm-9">
                        Tạo {{ $returnRequest->ngay_tao?->format('d/m/Y H:i') ?? '—' }}
                        @if ($returnRequest->ngay_nhan_hang) · Nhận hàng {{ $returnRequest->ngay_nhan_hang->format('d/m/Y H:i') }}@endif
                        @if ($returnRequest->ngay_hoan_tat) · Hoàn tất {{ $returnRequest->ngay_hoan_tat->format('d/m/Y H:i') }}@endif
                    </dd>
                </dl>

                @if ($actions)
                    <div class="d-flex flex-wrap mt-2">
                        @if (in_array(\App\Models\ReturnRequest::ACTION_APPROVE, $actions, true))
                            <form method="POST" action="{{ $updateUrl }}" class="mr-2 mb-1">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="thao_tac" value="approve">
                                <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-check mr-1"></i>Duyệt</button>
                            </form>
                        @endif
                        @if (in_array(\App\Models\ReturnRequest::ACTION_RECEIVE, $actions, true))
                            <form method="POST" action="{{ $updateUrl }}" class="mr-2 mb-1">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="thao_tac" value="receive">
                                <button type="submit" class="btn btn-sm btn-info"><i class="fas fa-box-open mr-1"></i>Đã nhận hàng</button>
                            </form>
                        @endif
                        @if (in_array(\App\Models\ReturnRequest::ACTION_COMPLETE, $actions, true))
                            <button type="button" class="btn btn-sm btn-success mr-2 mb-1" data-toggle="collapse" data-target="#return-complete-{{ $returnRequest->id }}" aria-expanded="false" aria-controls="return-complete-{{ $returnRequest->id }}">
                                <i class="fas fa-flag-checkered mr-1"></i>{{ $isReturn ? 'Hoàn tiền & hoàn tất' : 'Gửi hàng đổi & hoàn tất' }}
                            </button>
                        @endif
                        @if (in_array(\App\Models\ReturnRequest::ACTION_REJECT, $actions, true))
                            <button type="button" class="btn btn-sm btn-outline-danger mb-1" data-toggle="collapse" data-target="#return-reject-{{ $returnRequest->id }}" aria-expanded="false" aria-controls="return-reject-{{ $returnRequest->id }}">
                                <i class="fas fa-times mr-1"></i>Từ chối
                            </button>
                        @endif
                    </div>

                    @if (in_array(\App\Models\ReturnRequest::ACTION_COMPLETE, $actions, true))
                        <form method="POST" action="{{ $updateUrl }}" class="collapse border-top pt-3 mt-2" id="return-complete-{{ $returnRequest->id }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="thao_tac" value="complete">
                            @if ($isReturn)
                                <div class="form-row">
                                    <div class="form-group col-sm-6">
                                        <label for="refund-amount-{{ $returnRequest->id }}">Số tiền đã hoàn (₫)</label>
                                        <input type="number" name="so_tien_hoan" id="refund-amount-{{ $returnRequest->id }}" class="form-control form-control-sm" min="1" max="{{ $returnRequest->refundable_amount }}" value="{{ $returnRequest->refundable_amount }}" required>
                                        <small class="form-text text-muted">Tối đa {{ number_format($returnRequest->refundable_amount, 0, ',', '.') }} ₫ (giá trị hàng trả sau voucher).</small>
                                    </div>
                                    <div class="form-group col-sm-6">
                                        <label for="refund-reference-{{ $returnRequest->id }}">Mã giao dịch</label>
                                        <input type="text" name="ma_giao_dich_hoan" id="refund-reference-{{ $returnRequest->id }}" class="form-control form-control-sm" maxlength="100" placeholder="Tuỳ chọn">
                                    </div>
                                </div>
                            @else
                                <div class="form-group">
                                    <label for="replacement-{{ $returnRequest->id }}">Biến thể gửi cho khách</label>
                                    <select name="bien_the_thay_the_id" id="replacement-{{ $returnRequest->id }}" class="form-control form-control-sm" required>
                                        <option value="">Chọn biến thể còn hàng</option>
                                        @foreach ($replacementVariants as $variant)
                                            <option value="{{ $variant->id }}" @disabled($variant->stock < $returnRequest->so_luong)>
                                                {{ $variant->product?->ten_san_pham ?: 'Sản phẩm' }} · {{ $variant->sku }}{{ collect([$variant->color, $variant->size])->filter()->isNotEmpty() ? ' ('.collect([$variant->color, $variant->size])->filter()->implode(' / ').')' : '' }} · tồn {{ $variant->stock }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                            <div class="form-group">
                                <label for="complete-note-{{ $returnRequest->id }}">Ghi chú cho khách</label>
                                <textarea name="phan_hoi" id="complete-note-{{ $returnRequest->id }}" rows="2" maxlength="5000" class="form-control form-control-sm" placeholder="Tuỳ chọn"></textarea>
                            </div>
                            <small class="d-block text-muted mb-2">Hàng khách gửi lại sẽ được nhập lại kho của biến thể ban đầu.</small>
                            <button type="submit" class="btn btn-sm btn-success"><i class="fas fa-save mr-1"></i>Xác nhận hoàn tất</button>
                        </form>
                    @endif

                    @if (in_array(\App\Models\ReturnRequest::ACTION_REJECT, $actions, true))
                        <form method="POST" action="{{ $updateUrl }}" class="collapse border-top pt-3 mt-2" id="return-reject-{{ $returnRequest->id }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="thao_tac" value="reject">
                            <div class="form-group">
                                <label for="reject-reason-{{ $returnRequest->id }}">Lý do từ chối</label>
                                <textarea name="phan_hoi" id="reject-reason-{{ $returnRequest->id }}" rows="2" maxlength="5000" class="form-control form-control-sm" required placeholder="{{ (int) $returnRequest->trang_thai === \App\Models\ReturnRequest::STATUS_RETURN_RECEIVED ? 'Ví dụ: sản phẩm đã qua sử dụng, không đạt điều kiện đổi/trả' : 'Lý do sẽ được ghi lại để báo khách' }}"></textarea>
                            </div>
                            <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-times mr-1"></i>Xác nhận từ chối</button>
                        </form>
                    @endif
                @endif
            </div>
        @empty
            <p class="text-muted mb-0">Đơn hàng chưa có yêu cầu đổi/trả nào.</p>
        @endforelse
    </div>

    <div class="card-footer bg-white">
        @if ($canCreateReturn)
            <button type="button" class="btn btn-outline-primary btn-sm" data-toggle="collapse" data-target="#return-create" aria-expanded="{{ $returnErrors->any() && old('chi_tiet_don_hang_id') ? 'true' : 'false' }}" aria-controls="return-create">
                <i class="fas fa-plus mr-1"></i>Tạo yêu cầu đổi/trả
            </button>
            <form method="POST" action="{{ route('admin.orders.returns.store', $order->id) }}" id="return-create" class="collapse {{ $returnErrors->any() && old('chi_tiet_don_hang_id') ? 'show' : '' }} mt-3">
                @csrf
                <div class="form-group">
                    <label for="return-item">Sản phẩm</label>
                    <select name="chi_tiet_don_hang_id" id="return-item" class="form-control" required>
                        <option value="">Chọn sản phẩm trong đơn</option>
                        @foreach ($returnableItems as $item)
                            <option value="{{ $item->id }}" @selected((string) old('chi_tiet_don_hang_id') === (string) $item->id)>{{ $item->ten_san_pham ?: 'Sản phẩm' }} · còn đổi/trả được {{ $item->available_return_quantity }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-row">
                    <div class="form-group col-sm-6">
                        <label for="return-type">Loại yêu cầu</label>
                        <select name="loai_yeu_cau" id="return-type" class="form-control" required>
                            @foreach ($returnTypes as $type => $label)
                                <option value="{{ $type }}" @selected((string) old('loai_yeu_cau') === (string) $type)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-sm-6">
                        <label for="return-quantity">Số lượng</label>
                        <input type="number" name="so_luong" id="return-quantity" class="form-control" min="1" max="{{ $returnableItems->max('available_return_quantity') }}" value="{{ old('so_luong', 1) }}" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="return-reason">Lý do</label>
                    <textarea name="ly_do" id="return-reason" rows="3" maxlength="5000" class="form-control" required>{{ old('ly_do') }}</textarea>
                </div>
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save mr-1"></i>Lưu yêu cầu</button>
            </form>
        @elseif ((int) $order->trang_thai !== \App\Models\Order::STATUS_COMPLETED)
            <span class="small text-muted">Có thể tạo yêu cầu đổi/trả sau khi đơn hàng hoàn thành.</span>
        @elseif ($returnDeadline && $returnDeadline->isPast())
            <span class="small text-muted">Đơn hàng đã quá hạn {{ \App\Models\ReturnRequest::RETURN_WINDOW_DAYS }} ngày đổi/trả.</span>
        @else
            <span class="small text-muted">Tất cả sản phẩm trong đơn đã có yêu cầu đổi/trả.</span>
        @endif
    </div>
</div>
