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
            </div>
            <div class="card-footer bg-white">
                <a href="{{ route('admin.returns.index') }}" class="btn btn-default"><i class="fas fa-arrow-left mr-1"></i>Quay lại danh sách</a>
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
                        <div class="form-group mb-0">
                            <label for="phan_hoi">Phản hồi cho khách hàng</label>
                            <textarea name="phan_hoi" id="phan_hoi" rows="5" maxlength="5000" class="form-control @error('phan_hoi') is-invalid @enderror" placeholder="Ghi chú quá trình xử lý hoặc thông tin hoàn tiền...">{{ old('phan_hoi', $returnRequest->phan_hoi) }}</textarea>
                            @error('phan_hoi')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <small class="form-text text-muted">Bắt buộc khi từ chối yêu cầu hoặc ghi nhận hoàn tiền.</small>
                        </div>
                    </div>
                    <div class="card-footer bg-white">
                        <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-save mr-1"></i>Lưu cập nhật</button>
                    </div>
                </form>
            @else
                <div class="card-body">
                    <div class="alert alert-light border mb-0">
                        <i class="fas fa-check-circle text-success mr-1"></i>Yêu cầu đã kết thúc, không thể chuyển trạng thái thêm.
                    </div>
                </div>
            @endif
        </div>
        <div class="callout callout-info">
            <h5><i class="fas fa-info-circle mr-1"></i>Hoàn tiền</h5>
            <p class="mb-0 small">Ghi nhận trạng thái hoàn tiền chỉ cập nhật hồ sơ yêu cầu. Việc chuyển tiền thực tế cần thực hiện trên cổng thanh toán hoặc theo quy trình kế toán của cửa hàng.</p>
        </div>
    </div>
</div>
@endsection
