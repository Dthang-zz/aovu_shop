@extends('layouts.admin')

@section('title', 'Chi tiết yêu cầu đổi / trả')
@section('page-heading', 'Chi tiết yêu cầu')
@section('page-subheading', 'Xem thông tin yêu cầu và cập nhật kết quả xử lý.')
@section('breadcrumb')
    <a href="{{ route('admin.returns.index') }}">Đổi / trả hàng</a>
@endsection

@section('content')
@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle mr-1"></i>{{ session('success') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Đóng"><span aria-hidden="true">&times;</span></button>
    </div>
@endif

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title"><i class="fas fa-file-alt text-primary mr-2"></i>#DT-{{ str_pad((string) $returnRequest->id, 6, '0', STR_PAD_LEFT) }}</h3>
                <span class="badge badge-{{ $returnRequest->status_badge }} px-2 py-1">{{ $returnRequest->status_label }}</span>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-sm-6 mb-3">
                        <div class="text-muted small">Khách hàng</div>
                        <div class="font-weight-bold">{{ $returnRequest->recipient_name ?: ($returnRequest->account_name ?: 'Khách hàng') }}</div>
                        @if ($returnRequest->account_email)<div class="text-muted small">{{ $returnRequest->account_email }}</div>@endif
                    </div>
                    <div class="col-sm-6 mb-3">
                        <div class="text-muted small">Đơn hàng</div>
                        <div class="font-weight-bold">{{ $returnRequest->order_number ?: ('#'.$returnRequest->don_hang_id) }}</div>
                        @if ($returnRequest->order_total !== null)<div class="text-muted small">Tổng đơn: {{ number_format($returnRequest->order_total, 0, ',', '.') }} ₫</div>@endif
                    </div>
                    <div class="col-sm-6 mb-3">
                        <div class="text-muted small">Loại yêu cầu</div>
                        <div class="font-weight-bold">{{ $returnRequest->type_label }}</div>
                    </div>
                    <div class="col-sm-6 mb-3">
                        <div class="text-muted small">Ngày gửi</div>
                        <div class="font-weight-bold">{{ $returnRequest->ngay_tao ? \Illuminate\Support\Carbon::parse($returnRequest->ngay_tao)->format('d/m/Y H:i') : '—' }}</div>
                    </div>
                </div>

                @if ($returnRequest->conversation_id)
                    <div class="alert alert-info d-flex flex-wrap justify-content-between align-items-center">
                        <span><i class="fas fa-comments mr-1"></i>Phiếu được tạo từ cuộc trò chuyện CSKH.</span>
                        <a class="btn btn-sm btn-outline-primary"
                            href="{{ route('admin.support-chat.index', ['conversation_id' => $returnRequest->conversation_id]) }}">
                            Mở hội thoại
                        </a>
                    </div>
                @endif

                <h6 class="font-weight-bold border-top pt-3">Sản phẩm yêu cầu</h6>
                <div class="d-flex justify-content-between align-items-center bg-light rounded p-3 mb-3">
                    <div>
                        <div class="font-weight-bold">{{ $returnRequest->product_name ?: 'Sản phẩm trong đơn hàng' }}</div>
                        <small class="text-muted">Mã chi tiết đơn: {{ $returnRequest->chi_tiet_don_hang_id ?? '—' }}</small>
                    </div>
                    <div class="text-right">
                        <div>SL yêu cầu: <strong>{{ $returnRequest->so_luong ?? '—' }}</strong></div>
                        @if ($returnRequest->item_price !== null)<small class="text-muted">{{ number_format($returnRequest->item_price, 0, ',', '.') }} ₫ / sản phẩm</small>@endif
                    </div>
                </div>

                <h6 class="font-weight-bold">Lý do khách hàng</h6>
                <p class="text-muted">{{ $returnRequest->ly_do ?: 'Khách hàng chưa cung cấp lý do.' }}</p>

                @if ($returnRequest->hinh_anh)
                    @php
                        $evidenceUrl = \Illuminate\Support\Str::startsWith($returnRequest->hinh_anh, ['http://', 'https://'])
                            ? $returnRequest->hinh_anh
                            : \Illuminate\Support\Facades\Storage::disk('public')->url($returnRequest->hinh_anh);
                    @endphp
                    <h6 class="font-weight-bold">Ảnh minh chứng</h6>
                    <a href="{{ $evidenceUrl }}" target="_blank" rel="noopener noreferrer">
                        <img src="{{ $evidenceUrl }}" alt="Ảnh minh chứng yêu cầu" class="img-fluid rounded mb-3" style="max-height: 280px">
                    </a>
                @endif

                @if ($returnRequest->phan_hoi)
                    <h6 class="font-weight-bold border-top pt-3">Phản hồi gần nhất</h6>
                    <p class="text-muted mb-0">{{ $returnRequest->phan_hoi }}</p>
                @endif

                @if ($returnRequest->workflow)
                    <h6 class="font-weight-bold border-top pt-3">Thông tin xử lý</h6>
                    <dl class="row mb-0">
                        <dt class="col-sm-5">Khách đã đồng ý</dt>
                        <dd class="col-sm-7">{{ $returnRequest->workflow->customer_agreed ? 'Đã xác nhận' : 'Chưa xác nhận' }}</dd>
                        <dt class="col-sm-5">Đã kiểm tra chính sách</dt>
                        <dd class="col-sm-7">{{ $returnRequest->workflow->policy_reviewed ? 'Đã kiểm tra' : 'Chưa kiểm tra' }}</dd>
                        @if ($returnRequest->workflow->policy_notes)
                            <dt class="col-sm-5">Ghi chú chính sách</dt>
                            <dd class="col-sm-7">{{ $returnRequest->workflow->policy_notes }}</dd>
                        @endif
                        <dt class="col-sm-5">Thu hồi sản phẩm</dt>
                        <dd class="col-sm-7">{{ $returnRequest->workflow->collection_waived ? 'Được miễn' : 'Cần thu hồi' }}</dd>
                        @if ($returnRequest->workflow->received_at)
                            <dt class="col-sm-5">Đã nhận hàng</dt>
                            <dd class="col-sm-7">{{ $returnRequest->workflow->received_at->format('d/m/Y H:i') }}</dd>
                        @endif
                        @if ($returnRequest->workflow->inspection_notes)
                            <dt class="col-sm-5">Kết quả kiểm tra</dt>
                            <dd class="col-sm-7">{{ $returnRequest->workflow->inspection_notes }}</dd>
                        @endif
                        @if ($returnRequest->workflow->replacement_name)
                            <dt class="col-sm-5">Sản phẩm thay thế</dt>
                            <dd class="col-sm-7">
                                {{ $returnRequest->workflow->replacement_name }}
                                ({{ number_format($returnRequest->workflow->replacement_price, 0, ',', '.') }} ₫)
                            </dd>
                            <dt class="col-sm-5">Chênh lệch giá</dt>
                            <dd class="col-sm-7">
                                {{ number_format($returnRequest->workflow->price_difference, 0, ',', '.') }} ₫
                                · {{ $returnRequest->workflow->price_difference_settled ? 'Đã xử lý' : 'Chưa xử lý' }}
                            </dd>
                        @endif
                        @if ($returnRequest->workflow->tracking_code)
                            <dt class="col-sm-5">Mã vận đơn đổi</dt>
                            <dd class="col-sm-7">{{ $returnRequest->workflow->tracking_code }}</dd>
                        @endif
                        @if ($returnRequest->workflow->delivered_at)
                            <dt class="col-sm-5">Khách nhận hàng đổi</dt>
                            <dd class="col-sm-7">{{ $returnRequest->workflow->delivered_at->format('d/m/Y H:i') }}</dd>
                        @endif
                        @if ($returnRequest->workflow->refund_amount !== null)
                            <dt class="col-sm-5">Số tiền đã hoàn</dt>
                            <dd class="col-sm-7">{{ number_format($returnRequest->workflow->refund_amount, 0, ',', '.') }} ₫</dd>
                            <dt class="col-sm-5">Phương thức / mã giao dịch</dt>
                            <dd class="col-sm-7">
                                {{ [
                                    'bank_transfer' => 'Chuyển khoản',
                                    'cash' => 'Tiền mặt',
                                    'original_payment' => 'Phương thức thanh toán ban đầu',
                                    'other' => 'Khác',
                                ][$returnRequest->workflow->refund_method] ?? '—' }}
                                · {{ $returnRequest->workflow->refund_reference ?: 'Không có mã tham chiếu' }}
                            </dd>
                            @if ($returnRequest->workflow->refunded_at)
                                <dt class="col-sm-5">Thời điểm xác nhận hoàn</dt>
                                <dd class="col-sm-7">{{ $returnRequest->workflow->refunded_at->format('d/m/Y H:i') }}</dd>
                            @endif
                        @endif
                    </dl>
                @endif
            </div>
            <div class="card-footer bg-white">
                <a href="{{ route('admin.returns.index') }}" class="btn btn-default"><i class="fas fa-arrow-left mr-1"></i>Quay lại danh sách</a>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-history text-primary mr-2"></i>Lịch sử xử lý</h3>
            </div>
            <div class="card-body">
                @forelse ($returnRequest->history as $historyItem)
                    <div class="d-flex border-bottom pb-3 mb-3">
                        <div class="mr-3 text-primary"><i class="fas fa-circle"></i></div>
                        <div>
                            <strong>
                                {{ $historyItem->from_status === null ? 'Tạo phiếu' : (\App\Models\ReturnRequest::statuses()[$historyItem->from_status] ?? 'Trạng thái cũ') }}
                                @if ($historyItem->from_status !== null) → @endif
                                {{ \App\Models\ReturnRequest::statuses()[$historyItem->to_status] ?? 'Không xác định' }}
                            </strong>
                            <div class="small text-muted">
                                {{ $historyItem->actor_name }} · {{ $historyItem->created_at->format('d/m/Y H:i') }}
                            </div>
                            @if ($historyItem->note)
                                <div class="mt-1">{{ $historyItem->note }}</div>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-muted mb-0">Chưa có lịch sử xử lý.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-tasks text-primary mr-2"></i>Cập nhật xử lý</h3></div>
            @if ($errors->any())
                <div class="card-body pb-0">
                    <div class="alert alert-danger" role="alert">
                        <ul class="mb-0 pl-3">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif
            @if ($returnRequest->allowed_statuses)
                <form method="POST" action="{{ route('admin.returns.update', $returnRequest->id) }}">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <div class="form-group">
                            <label for="trang_thai">Trạng thái tiếp theo</label>
                            <select name="trang_thai" id="trang_thai" class="form-control @error('trang_thai') is-invalid @enderror" required>
                                @foreach ($returnRequest->allowed_statuses as $status)
                                    <option value="{{ $status }}" @selected((string) old('trang_thai') === (string) $status)>{{ \App\Models\ReturnRequest::statuses()[$status] }}</option>
                                @endforeach
                            </select>
                            @error('trang_thai')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="border rounded p-3 mb-3" data-stage="approval">
                            <h6 class="font-weight-bold">Xác nhận trước khi duyệt</h6>
                            <div class="custom-control custom-checkbox mb-2">
                                <input type="hidden" name="customer_agreed" value="0">
                                <input type="checkbox" class="custom-control-input" id="customer_agreed"
                                    name="customer_agreed" value="1"
                                    @checked(old('customer_agreed', $returnRequest->workflow?->customer_agreed ?? false))>
                                <label class="custom-control-label" for="customer_agreed">Đã ghi nhận khách đồng ý với phương án</label>
                            </div>
                            <div class="custom-control custom-checkbox mb-2">
                                <input type="hidden" name="policy_reviewed" value="0">
                                <input type="checkbox" class="custom-control-input" id="policy_reviewed"
                                    name="policy_reviewed" value="1"
                                    @checked(old('policy_reviewed', $returnRequest->workflow?->policy_reviewed ?? false))>
                                <label class="custom-control-label" for="policy_reviewed">Đã kiểm tra chính sách đổi/trả</label>
                            </div>
                            <label for="policy_notes">Ghi chú chính sách</label>
                            <textarea name="policy_notes" id="policy_notes" rows="2" maxlength="2000"
                                class="form-control">{{ old('policy_notes', $returnRequest->workflow?->policy_notes) }}</textarea>
                        </div>
                        <div class="custom-control custom-checkbox mb-3">
                            <input type="hidden" name="collection_waived" value="0">
                            <input type="checkbox" class="custom-control-input" id="collection_waived"
                                name="collection_waived" value="1"
                                @checked(old('collection_waived', $returnRequest->workflow?->collection_waived ?? false))>
                            <label class="custom-control-label" for="collection_waived">Miễn thu hồi hàng theo chính sách</label>
                        </div>
                        <div class="form-group" data-stage="inspection">
                            <label for="inspection_notes">Kết quả kiểm tra sản phẩm</label>
                            <textarea name="inspection_notes" id="inspection_notes" rows="2" maxlength="2000"
                                class="form-control">{{ old('inspection_notes', $returnRequest->workflow?->inspection_notes) }}</textarea>
                        </div>
                        @if ($returnRequest->loai_yeu_cau === \App\Models\ReturnRequest::TYPE_EXCHANGE)
                            <div class="border rounded p-3 mb-3" data-stage="exchange">
                                <h6 class="font-weight-bold">Sản phẩm đổi và giao hàng</h6>
                                <div class="form-group">
                                    <label for="replacement_variant_id">Sản phẩm thay thế</label>
                                    <select name="replacement_variant_id" id="replacement_variant_id" class="form-control">
                                        <option value="">Giữ lựa chọn hiện tại</option>
                                        @foreach ($returnRequest->replacement_variants as $variant)
                                            <option value="{{ $variant->id }}"
                                                @selected((string) old('replacement_variant_id', $returnRequest->workflow?->replacement_variant_id) === (string) $variant->id)>
                                                {{ $variant->ten_san_pham }} · {{ $variant->ten_mau }} · {{ $variant->ten_kich_thuoc }}
                                                · {{ number_format($variant->gia_bien_the, 0, ',', '.') }} ₫
                                                · tồn {{ $variant->so_luong_ton }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="custom-control custom-checkbox mb-2">
                                    <input type="hidden" name="price_difference_settled" value="0">
                                    <input type="checkbox" class="custom-control-input" id="price_difference_settled"
                                        name="price_difference_settled" value="1"
                                        @checked(old('price_difference_settled', $returnRequest->workflow?->price_difference_settled ?? false))>
                                    <label class="custom-control-label" for="price_difference_settled">Đã xử lý chênh lệch giá (nếu có)</label>
                                </div>
                                <div class="form-group">
                                    <label for="price_difference_note">Cách xử lý chênh lệch</label>
                                    <input name="price_difference_note" id="price_difference_note" maxlength="255"
                                        class="form-control" value="{{ old('price_difference_note', $returnRequest->workflow?->price_difference_note) }}">
                                </div>
                                <div class="form-group">
                                    <label for="tracking_code">Mã vận đơn hàng đổi</label>
                                    <input name="tracking_code" id="tracking_code" maxlength="100" class="form-control"
                                        value="{{ old('tracking_code', $returnRequest->workflow?->tracking_code) }}">
                                </div>
                                @if ($returnRequest->trang_thai === \App\Models\ReturnRequest::STATUS_EXCHANGE_SHIPPED)
                                    <div class="custom-control custom-checkbox">
                                        <input type="hidden" name="replacement_delivered" value="0">
                                        <input type="checkbox" class="custom-control-input" id="replacement_delivered"
                                            name="replacement_delivered" value="1" @checked(old('replacement_delivered'))>
                                        <label class="custom-control-label" for="replacement_delivered">Xác nhận khách đã nhận hàng đổi</label>
                                    </div>
                                @endif
                            </div>
                        @endif
                        <div class="form-group mb-0">
                            <label for="phan_hoi">Phản hồi cho khách hàng</label>
                            <textarea name="phan_hoi" id="phan_hoi" rows="5" maxlength="5000" class="form-control @error('phan_hoi') is-invalid @enderror" placeholder="Ghi chú quá trình xử lý hoặc thông tin hoàn tiền...">{{ old('phan_hoi', $returnRequest->phan_hoi) }}</textarea>
                            @error('phan_hoi')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <small class="form-text text-muted">Ghi chú này sẽ lưu vào lịch sử và gửi cho khách qua hội thoại CSKH.</small>
                        </div>
                    </div>
                    <div class="card-footer bg-white">
                        <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-save mr-1"></i>Lưu cập nhật</button>
                    </div>
                </form>
            @else
                <div class="card-body">
                    <div class="alert alert-light border mb-0">
                        @if ($returnRequest->trang_thai === \App\Models\ReturnRequest::STATUS_INSPECTED
                            && $returnRequest->loai_yeu_cau === \App\Models\ReturnRequest::TYPE_RETURN)
                            <i class="fas fa-info-circle text-info mr-1"></i>
                            Hàng đã kiểm tra. Yêu cầu hoàn tiền cần được xét duyệt riêng trong
                            <a href="{{ route('admin.finance.refunds.index') }}">Ví hoàn tiền</a>.
                            Hiện các thao tác tài chính đang ở chế độ chỉ đọc.
                        @else
                            <i class="fas fa-check-circle text-success mr-1"></i>Yêu cầu đã kết thúc, không thể chuyển trạng thái thêm.
                        @endif
                    </div>
                </div>
            @endif
        </div>
        <div class="callout callout-info">
            <h5><i class="fas fa-info-circle mr-1"></i>Hoàn tiền</h5>
            <p class="mb-0 small">Yêu cầu được duyệt và hàng được kiểm tra không đồng nghĩa giao dịch đã hoàn tất. Chỉ cập nhật hoàn tiền khi có xác nhận giao dịch từ nhà cung cấp hoặc đối soát hợp lệ.</p>
        </div>
    </div>
</div>
@endsection

@section('scripts')
    <script>
        (function () {
            var status = document.getElementById('trang_thai');
            if (!status) return;

            var approval = document.querySelector('[data-stage="approval"]');
            var inspection = document.querySelector('[data-stage="inspection"]');
            var exchange = document.querySelector('[data-stage="exchange"]');
            function updateFields() {
                var target = Number(status.value);
                if (approval) approval.hidden = target !== {{ \App\Models\ReturnRequest::STATUS_APPROVED }};
                if (inspection) {
                    inspection.hidden = target !== {{ \App\Models\ReturnRequest::STATUS_INSPECTED }}
                        && target !== {{ \App\Models\ReturnRequest::STATUS_RETURN_RECEIVED }};
                }
                if (exchange) {
                    exchange.hidden = target !== {{ \App\Models\ReturnRequest::STATUS_EXCHANGE_SHIPPED }}
                        && target !== {{ \App\Models\ReturnRequest::STATUS_COMPLETED }};
                }
            }

            status.addEventListener('change', updateFields);
            updateFields();
        }());
    </script>
@endsection
