@extends('layouts.admin')

@section('page-heading', 'Chỉnh sửa biến thể')
@section('page-subheading', 'Cập nhật thông tin chi tiết phiên bản sản phẩm.')
@section('breadcrumb', 'Chỉnh sửa')

@section('content')
    <div class="card card-warning">
        <div class="card-header">
            <h3 class="card-title">Cập nhật thông tin biến thể #{{ $variant->id }}</h3>
        </div>
        <form action="{{ route('admin.variants.update', $variant->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="card-body">
                <div class="form-group">
                    <label>Chọn sản phẩm <span class="text-danger">*</span></label>
                    <select name="product_id" class="form-control @error('product_id') is-invalid @enderror">
                        <option value="">-- Chọn sản phẩm --</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}"
                                {{ old('product_id', $variant->product_id) == $product->id ? 'selected' : '' }}>
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
                        <label>Màu sắc</label>
                        <input type="text" name="color" class="form-control"
                            value="{{ old('color', $variant->color) }}" placeholder="Ví dụ: Trắng, Đen...">
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Kích thước</label>
                        <input type="text" name="size" class="form-control" value="{{ old('size', $variant->size) }}"
                            placeholder="Ví dụ: S, M, L...">
                    </div>
                </div>

                <div class="form-group">
                    <label>Mã SKU <small class="text-muted">(Để trống hệ thống sẽ tự động tạo lại)</small></label>
                    <input type="text" name="sku" class="form-control @error('sku') is-invalid @enderror"
                        value="{{ old('sku', $variant->sku) }}">
                    @error('sku')
                        <span class="error invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>Giá riêng biến thể (đ)</label>
                        <input type="number" name="price" class="form-control"
                            value="{{ old('price', $variant->price) }}">
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Số lượng kho <span class="text-danger">*</span></label>
                        <input type="number" name="stock" class="form-control @error('stock') is-invalid @enderror"
                            value="{{ old('stock', $variant->stock) }}">
                        @error('stock')
                            <span class="error invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="form-group">
                    <label>Trạng thái</label>
                    <select name="status" class="form-control">
                        <option value="active" {{ old('status', $variant->status) == 'active' ? 'selected' : '' }}>Đang bán
                        </option>
                        <option value="inactive" {{ old('status', $variant->status) == 'inactive' ? 'selected' : '' }}>Tạm
                            ngưng</option>
                    </select>
                </div>
            </div>

            <div class="card-footer">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Cập nhật biến thể</button>
                <a href="{{ route('admin.variants.index') }}" class="btn btn-default float-right">Hủy bỏ</a>
            </div>
        </form>
    </div>
@endsection
