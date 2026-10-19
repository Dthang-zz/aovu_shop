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
                    <label>Chọn sản phẩm <span class="text-danger">*</span></label>
                    <select name="product_id" class="form-control @error('product_id') is-invalid @enderror">
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
                        <label>Màu sắc</label>
                        <input type="text" name="color" class="form-control" value="{{ old('color') }}"
                            placeholder="Ví dụ: Trắng, Đen, Xanh...">
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Kích thước</label>
                        <input type="text" name="size" class="form-control" value="{{ old('size') }}"
                            placeholder="Ví dụ: S, M, L, XL...">
                    </div>
                </div>

                <div class="form-group">
                    <label>Mã SKU <small class="text-muted">(Để trống hệ thống sẽ tự động tạo)</small></label>
                    <input type="text" name="sku" class="form-control @error('sku') is-invalid @enderror"
                        value="{{ old('sku') }}" placeholder="Tự động tạo nếu để trống">
                    @error('sku')
                        <span class="error invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>Giá riêng biến thể (đ) <small class="text-muted">(Để trống nếu bằng giá sản phẩm
                                chính)</small></label>
                        <input type="number" name="price" class="form-control" value="{{ old('price') }}"
                            placeholder="Ví dụ: 250000">
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Số lượng kho <span class="text-danger">*</span></label>
                        <input type="number" name="stock" class="form-control @error('stock') is-invalid @enderror"
                            value="{{ old('stock', 0) }}">
                        @error('stock')
                            <span class="error invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="form-group">
                    <label>Trạng thái</label>
                    <select name="status" class="form-control">
                        <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Đang bán</option>
                        <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Tạm ngưng</option>
                    </select>
                </div>
            </div>

            <div class="card-footer">
                <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Lưu biến thể</button>
                <a href="{{ route('admin.variants.index') }}" class="btn btn-default float-right">Quay lại</a>
            </div>
        </form>
    </div>
@endsection
