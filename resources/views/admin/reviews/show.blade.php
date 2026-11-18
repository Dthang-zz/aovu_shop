@extends('layouts.admin')

@section('title', 'Chi tiết đánh giá')
@section('page-heading', 'Chi tiết đánh giá')
@section('page-subheading', 'Kiểm tra nội dung, hình ảnh và đơn hàng liên quan.')
@section('breadcrumb')
    <a href="{{ route('admin.reviews.index') }}">Đánh giá</a>
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
                <h3 class="card-title"><i class="fas fa-star text-warning mr-2"></i>Đánh giá #{{ $review->id }}</h3>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-sm-6 mb-3">
                        <div class="text-muted small">Khách hàng</div>
                        <div class="font-weight-bold">{{ $review->customer_name ?: 'Khách hàng' }}</div>
                        @if ($review->customer_email)
                            <div class="text-muted small">{{ $review->customer_email }}</div>
                        @endif
                    </div>
                    <div class="col-sm-6 mb-3">
                        <div class="text-muted small">Sản phẩm được đánh giá</div>
                        <div class="font-weight-bold">{{ $review->product_name ?: 'Sản phẩm đã xóa' }}</div>
                    </div>
                    <div class="col-sm-6 mb-3">
                        <div class="text-muted small">Số sao</div>
                        <div class="text-warning" aria-label="{{ $review->so_sao ?? 0 }} sao">
                            @for ($star = 1; $star <= 5; $star++)
                                <i class="{{ $star <= (int) $review->so_sao ? 'fas' : 'far' }} fa-star"></i>
                            @endfor
                            <span class="text-muted ml-1">{{ $review->so_sao ?? '—' }}/5</span>
                        </div>
                    </div>
                    <div class="col-sm-6 mb-3">
                        <div class="text-muted small">Ngày gửi</div>
                        <div class="font-weight-bold">{{ $review->ngay_dang ? \Illuminate\Support\Carbon::parse($review->ngay_dang)->format('d/m/Y H:i') : '—' }}</div>
                    </div>
                </div>

                <h6 class="font-weight-bold border-top pt-3">Nội dung đánh giá</h6>
                <p class="text-break">{{ $review->noi_dung ?: 'Khách hàng chưa cung cấp nội dung.' }}</p>

                @if ($review->photos !== [])
                    <h6 class="font-weight-bold border-top pt-3">Hình ảnh đính kèm</h6>
                    <div class="d-flex flex-wrap" style="gap: .75rem">
                        @foreach ($review->photos as $photo)
                            <a href="{{ $photo }}" target="_blank" rel="noopener noreferrer">
                                <img src="{{ $photo }}" alt="Ảnh đính kèm đánh giá #{{ $review->id }}" class="img-thumbnail" style="width: 150px;height: 150px;object-fit: cover">
                            </a>
                        @endforeach
                    </div>
                @endif

                @if ($review->phan_hoi)
                    <h6 class="font-weight-bold border-top pt-3 mt-3">Phản hồi hiện tại của cửa hàng</h6>
                    <p class="text-muted mb-0">{{ $review->phan_hoi }}</p>
                @endif
            </div>
            <div class="card-footer bg-white">
                <a href="{{ route('admin.reviews.index') }}" class="btn btn-default"><i class="fas fa-arrow-left mr-1"></i>Quay lại danh sách</a>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-receipt text-primary mr-2"></i>Thông tin đơn hàng liên quan</h3>
            </div>
            <div class="card-body">
                @if ($review->chi_tiet_don_hang_id)
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="text-muted small">Mã đơn hàng</div>
                            <div class="font-weight-bold">{{ $review->order_number ?: '#'.$review->chi_tiet_don_hang_id }}</div>
                            @if ($review->order_date)
                                <div class="text-muted small">{{ \Illuminate\Support\Carbon::parse($review->order_date)->format('d/m/Y H:i') }}</div>
                            @endif
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="text-muted small">Tổng tiền đơn hàng</div>
                            <div class="font-weight-bold">{{ $review->order_total !== null ? number_format((float) $review->order_total, 0, ',', '.').' ₫' : '—' }}</div>
                        </div>
                    </div>
                    <div class="bg-light rounded p-3">
                        <div class="font-weight-bold">{{ $review->purchased_product_name ?: $review->product_name ?: 'Sản phẩm trong đơn hàng' }}</div>
                        <small class="text-muted">Mã chi tiết đơn: {{ $review->chi_tiet_don_hang_id }}</small>
                        <div class="text-muted small mt-1">
                            Số lượng: {{ $review->purchased_item_quantity ?? '—' }}
                            @if ($review->purchased_item_price !== null)
                                · Đơn giá: {{ number_format((float) $review->purchased_item_price, 0, ',', '.') }} ₫
                            @endif
                        </div>
                    </div>
                @else
                    <p class="text-muted mb-0">Đánh giá không gắn với chi tiết đơn hàng.</p>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-reply text-primary mr-2"></i>Phản hồi khách hàng</h3>
            </div>
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
            <form method="POST" action="{{ route('admin.reviews.update', $review->id) }}">
                @csrf
                @method('PUT')
                <div class="card-body">
                    <div class="form-group mb-0">
                        <label for="phan_hoi">Phản hồi của cửa hàng</label>
                        <textarea id="phan_hoi" name="phan_hoi" rows="6" maxlength="5000" class="form-control @error('phan_hoi') is-invalid @enderror" placeholder="Viết phản hồi cho khách hàng...">{{ old('phan_hoi', $review->phan_hoi) }}</textarea>
                        @error('phan_hoi')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <small class="form-text text-muted">Đánh giá hiển thị công khai ngay sau khi gửi.</small>
                    </div>
                </div>
                <div class="card-footer bg-white">
                    <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-reply mr-1"></i>Lưu phản hồi</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
