@extends('layouts.admin')

@section('title', 'Xử lý yêu cầu đổi / trả')
@section('page-heading', 'Xử lý yêu cầu đổi / trả')
@section('page-subheading', 'Cập nhật trạng thái, kết quả kiểm tra và phản hồi cho khách hàng.')
@section('breadcrumb')
    <a href="{{ route('admin.returns.index') }}">Đổi / trả hàng</a>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title"><i class="fas fa-file-alt text-primary mr-2"></i>#DT-{{ str_pad((string) $returnRequest->id, 6, '0', STR_PAD_LEFT) }}</h3>
                <span class="badge badge-{{ $returnRequest->status_badge }} px-2 py-1">{{ $returnRequest->status_label }}</span>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-sm-6 mb-3">
                        <div class="text-muted small">Khách hàng</div>
                        <div class="font-weight-bold">{{ $returnRequest->recipient_name ?: ($returnRequest->account_name ?: 'Khách hàng') }}</div>
                        @if ($returnRequest->account_email)<div class="text-muted small">{{ $returnRequest->account_email }}</div>@endif
                    </div>
                    <div class="col-sm-6 mb-3">
                        <div class="text-muted small">Đơn hàng · Loại yêu cầu</div>
                        <div class="font-weight-bold">{{ $returnRequest->order_number ?: ('#'.$returnRequest->don_hang_id) }} · {{ $returnRequest->type_label }}</div>
                    </div>
                    <div class="col-sm-6 mb-3">
                        <div class="text-muted small">Sản phẩm</div>
                        <div class="font-weight-bold">{{ $returnRequest->product_name ?: 'Sản phẩm trong đơn hàng' }}</div>
                        <div class="text-muted small">
                            SL yêu cầu: {{ $returnRequest->so_luong ?? '—' }}
                            @if ($returnRequest->item_price !== null) · {{ number_format($returnRequest->item_price, 0, ',', '.') }} ₫ / sản phẩm @endif
                        </div>
                    </div>
                    <div class="col-sm-6 mb-3">
                        <div class="text-muted small">Lý do khách hàng</div>
                        <div>{{ $returnRequest->ly_do ?: 'Khách hàng chưa cung cấp lý do.' }}</div>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-white">
                <a href="{{ route('admin.returns.show', $returnRequest->id) }}" class="btn btn-default"><i class="fas fa-arrow-left mr-1"></i>Quay lại chi tiết</a>
            </div>
        </div>

        @include('admin.returns.partials.history')
    </div>

    <div class="col-lg-5">
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
                                                {{ $variant->product?->ten_san_pham ?? 'Sản phẩm' }} · {{ $variant->sku }}
                                                · {{ collect([$variant->color, $variant->size])->filter()->implode(' / ') }}
                                                · {{ number_format((float) ($variant->price ?? $variant->product?->gia_san_pham ?? 0), 0, ',', '.') }} ₫
                                                · tồn {{ $variant->stock }}
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
                        @include('admin.returns.partials.closed-notice')
                    </div>
                </div>
            @endif
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
                        && target !== {{ \App\Models\ReturnRequest::STATUS_RETURN_RECEIVED }}
                        && target !== {{ \App\Models\ReturnRequest::STATUS_REJECTED }};
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
