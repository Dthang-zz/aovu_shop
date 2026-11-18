@extends('layouts.admin')

@section('title', $promotion->exists ? 'Chỉnh sửa khuyến mãi' : 'Tạo khuyến mãi')
@section('page-heading', $promotion->exists ? 'Chỉnh sửa khuyến mãi' : 'Tạo chương trình khuyến mãi')
@section('breadcrumb')
    <a href="{{ route('admin.promotions.index') }}">Khuyến mãi</a>
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-8">
            <form method="POST" action="{{ $promotion->exists ? route('admin.promotions.update', $promotion) : route('admin.promotions.store') }}">
                @csrf
                @if ($promotion->exists)
                    @method('PUT')
                @endif
                <div class="card">
                    <div class="card-header"><h3 class="card-title">Thông tin chương trình</h3></div>
                    <div class="card-body">
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                            </div>
                        @endif
                        <div class="form-group">
                            <label for="ten_chuong_trinh">Tên chương trình</label>
                            <input id="ten_chuong_trinh" name="ten_chuong_trinh" class="form-control" maxlength="255" required value="{{ old('ten_chuong_trinh', $promotion->ten_chuong_trinh) }}">
                        </div>
                        <div class="form-group">
                            <label for="phan_tram_giam">Mức giảm (%)</label>
                            <input id="phan_tram_giam" type="number" name="phan_tram_giam" class="form-control" min="0.01" max="100" step="0.01" required value="{{ old('phan_tram_giam', $promotion->phan_tram_giam) }}">
                            <small class="form-text text-muted">Giá khuyến mãi được tính từ giá bán tại thời điểm đặt hàng.</small>
                        </div>
                        <div class="form-group">
                            <label for="san_pham_ids">Sản phẩm áp dụng</label>
                            <select id="san_pham_ids" name="san_pham_ids[]" class="form-control" multiple required size="8">
                                @foreach ($products as $product)
                                    <option value="{{ $product->id }}" @selected(in_array($product->id, old('san_pham_ids', $selectedProductIds)))>
                                        {{ $product->ten_san_pham }} ({{ $product->ma_san_pham }})
                                    </option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">Giữ Ctrl (Windows) hoặc Command (Mac) để chọn nhiều sản phẩm.</small>
                        </div>
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label for="ngay_bat_dau">Bắt đầu</label>
                                <input id="ngay_bat_dau" type="datetime-local" name="ngay_bat_dau" class="form-control" required value="{{ old('ngay_bat_dau', $promotion->ngay_bat_dau?->format('Y-m-d\TH:i')) }}">
                            </div>
                            <div class="col-md-6 form-group">
                                <label for="ngay_ket_thuc">Kết thúc</label>
                                <input id="ngay_ket_thuc" type="datetime-local" name="ngay_ket_thuc" class="form-control" required value="{{ old('ngay_ket_thuc', $promotion->ngay_ket_thuc?->format('Y-m-d\TH:i')) }}">
                            </div>
                        </div>
                        <div class="custom-control custom-switch">
                            <input type="hidden" name="trang_thai" value="0">
                            <input id="trang_thai" type="checkbox" name="trang_thai" value="1" class="custom-control-input" @checked(old('trang_thai', $promotion->trang_thai ?? true))>
                            <label class="custom-control-label" for="trang_thai">Bật chương trình</label>
                        </div>
                    </div>
                    <div class="card-footer d-flex justify-content-between">
                        <a href="{{ route('admin.promotions.index') }}" class="btn btn-default">Hủy</a>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>Lưu chương trình</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
