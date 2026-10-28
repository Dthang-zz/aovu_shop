@extends('layouts.admin')

@section('page-heading', 'Thêm biến thể sản phẩm')
@section('page-subheading', 'Tạo phiên bản mới cho sản phẩm.')
@section('breadcrumb', 'Thêm biến thể')

@section('content')
    <div class="card card-primary">
        <div class="card-header">
            <h3 class="card-title">Thông tin biến thể mới</h3>
        </div>
        <form action="{{ route('admin.variants.store') }}" method="POST">
            @csrf
            <div class="card-body">
                <div class="form-group">
                    <label for="product_id">Chọn sản phẩm <span class="text-danger">*</span></label>
                    <select id="product_id" name="product_id" class="form-control @error('product_id') is-invalid @enderror" required>
                        <option value="">-- Chọn sản phẩm --</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}" {{ old('product_id') == $product->id ? 'selected' : '' }}>
                                {{ $product->ten_san_pham }} (Mã: {{ $product->ma_san_pham }})
                            </option>
                        @endforeach
                    </select>
                    @error('product_id')
                        <span class="error invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="row">
                    <div class="col-md-6 form-group">
                        <label for="color">Màu sắc</label>
                        <input id="color" type="text" name="color" maxlength="255" class="form-control @error('color') is-invalid @enderror" value="{{ old('color') }}"
                            placeholder="Ví dụ: Trắng, Đen, Xanh...">
                        @error('color')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6 form-group">
                        <label for="size">Kích thước</label>
                        <input id="size" type="text" name="size" maxlength="255" class="form-control @error('size') is-invalid @enderror" value="{{ old('size') }}"
                            placeholder="Ví dụ: S, M, L, XL...">
                        @error('size')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="form-group">
                    <label>Mã SKU <small class="text-muted">(Để trống hệ thống sẽ tự động tạo)</small></label>
                    <input type="text" name="sku" maxlength="255" class="form-control @error('sku') is-invalid @enderror"
                        value="{{ old('sku') }}" placeholder="Tự động tạo nếu để trống">
                    @error('sku')
                        <span class="error invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>Giá riêng biến thể (đ) <small class="text-muted">(Để trống nếu bằng giá sản phẩm
                                chính)</small></label>
                        <input type="number" name="price" min="0" max="9999999999.99" step="0.01" class="form-control @error('price') is-invalid @enderror" value="{{ old('price') }}"
                            placeholder="Ví dụ: 250000">
                        @error('price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Số lượng kho <span class="text-danger">*</span></label>
                        <input type="number" name="stock" min="0" max="2147483647" step="1" required class="form-control @error('stock') is-invalid @enderror"
                            value="{{ old('stock', 0) }}">
                        @error('stock')
                            <span class="error invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="form-group">
                    <label for="status">Trạng thái</label>
                    <select id="status" name="status" class="form-control @error('status') is-invalid @enderror" required>
                        @foreach ($statuses as $status => $label)
                            <option value="{{ $status }}" @selected(old('status', 'active') === $status)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="card-footer">
                <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Lưu biến thể</button>
                <a href="{{ route('admin.variants.index') }}" class="btn btn-default float-right">Quay lại</a>
            </div>
        </form>
    </div>
@endsection
