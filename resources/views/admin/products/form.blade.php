@extends('layouts.admin')

@section('title', $formTitle)
@section('page-heading', $formTitle)
@section('page-subheading', 'Nhập thông tin, giá bán và hình ảnh sản phẩm.')
@section('breadcrumb')
    <a href="{{ route('admin.products.index') }}">Sản phẩm</a>
@endsection

@section('content')
@php
    $variantRows = old('variants');
    if (!is_array($variantRows)) {
        $variantRows = $variants->map(fn ($variant) => $variant->toArray())->all();
    }
@endphp
<form method="POST" enctype="multipart/form-data" action="{{ $product->exists ? route('admin.products.update', $product) : route('admin.products.store') }}">
    @csrf
    @if ($product->exists)
        @method('PUT')
    @endif

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <div class="font-weight-bold mb-1">Vui lòng kiểm tra lại thông tin:</div>
            <ul class="mb-0 pl-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header"><h3 class="card-title"><i class="fas fa-info-circle text-primary mr-2"></i>Thông tin cơ bản</h3></div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="ten_san_pham">Tên sản phẩm <span class="text-danger">*</span></label>
                        <input id="ten_san_pham" type="text" name="ten_san_pham" value="{{ old('ten_san_pham', $product->ten_san_pham) }}" class="form-control @error('ten_san_pham') is-invalid @enderror" maxlength="255" required autofocus placeholder="Ví dụ: Áo sơ mi Linen">
                        @error('ten_san_pham')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="ma_san_pham">Mã sản phẩm / SKU <span class="text-danger">*</span></label>
                            <input id="ma_san_pham" type="text" name="ma_san_pham" value="{{ old('ma_san_pham', $product->ma_san_pham) }}" class="form-control @error('ma_san_pham') is-invalid @enderror" maxlength="50" required placeholder="Ví dụ: AO-LINEN-01">
                            @error('ma_san_pham')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group col-md-6">
                            <label for="danh_muc_id">Danh mục</label>
                            <select id="danh_muc_id" name="danh_muc_id" class="form-control @error('danh_muc_id') is-invalid @enderror">
                                <option value="">Chưa phân loại</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" @selected((string) old('danh_muc_id', $product->danh_muc_id) === (string) $category->id)>{{ $category->ten_danh_muc }}{{ $category->trang_thai ? '' : ' (đang ẩn)' }}</option>
                                @endforeach
                            </select>
                            @error('danh_muc_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="gia_san_pham">Giá bán (₫) <span class="text-danger">*</span></label>
                            <input id="gia_san_pham" type="number" name="gia_san_pham" value="{{ old('gia_san_pham', $product->gia_san_pham) }}" class="form-control @error('gia_san_pham') is-invalid @enderror" min="0" max="999999999999" step="1" required placeholder="459000">
                            @error('gia_san_pham')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group col-md-6">
                            <label for="gia_khuyen_mai">Giá khuyến mãi (₫)</label>
                            <input id="gia_khuyen_mai" type="number" name="gia_khuyen_mai" value="{{ old('gia_khuyen_mai', $product->gia_khuyen_mai) }}" class="form-control @error('gia_khuyen_mai') is-invalid @enderror" min="0" max="999999999999" step="1" placeholder="Để trống nếu không giảm giá">
                            @error('gia_khuyen_mai')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <small class="form-text text-muted">Giá khuyến mãi phải thấp hơn giá bán.</small>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="mo_ta">Mô tả sản phẩm</label>
                        <textarea id="mo_ta" name="mo_ta" rows="7" maxlength="20000" class="form-control @error('mo_ta') is-invalid @enderror" placeholder="Nhập mô tả, chất liệu, hướng dẫn sử dụng...">{{ old('mo_ta', $product->mo_ta) }}</textarea>
                        @error('mo_ta')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label for="chat_lieu">Chất liệu</label>
                        <textarea id="chat_lieu" name="chat_lieu" rows="3" maxlength="10000" class="form-control @error('chat_lieu') is-invalid @enderror" placeholder="Ví dụ: 100% linen tự nhiên, mềm nhẹ và thoáng khí.">{{ old('chat_lieu', $product->chat_lieu) }}</textarea>
                        @error('chat_lieu')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group mb-0">
                        <label for="huong_dan_su_dung">Hướng dẫn sử dụng</label>
                        <textarea id="huong_dan_su_dung" name="huong_dan_su_dung" rows="4" maxlength="10000" class="form-control @error('huong_dan_su_dung') is-invalid @enderror" placeholder="Ví dụ: Giặt máy ở chế độ nhẹ, không dùng thuốc tẩy, phơi nơi thoáng mát.">{{ old('huong_dan_su_dung', $product->huong_dan_su_dung) }}</textarea>
                        @error('huong_dan_su_dung')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h3 class="card-title"><i class="fas fa-layer-group text-primary mr-2"></i>Biến thể sản phẩm</h3>
                    <button type="button" class="btn btn-outline-primary btn-sm" id="add-variant">
                        <i class="fas fa-plus mr-1"></i>Thêm biến thể
                    </button>
                </div>
                <div class="card-body">
                    <p class="text-muted small">Thiết lập màu sắc, kích thước, SKU, giá riêng, tồn kho và trạng thái. Để trống SKU, hệ thống sẽ tự tạo mã.</p>
                    <div id="variant-list">
                        @foreach ($variantRows as $index => $variantRow)
                            <div class="border rounded p-3 mb-3 variant-row">
                                @if (!empty($variantRow['id']))
                                    <input type="hidden" name="variants[{{ $index }}][id]" value="{{ $variantRow['id'] }}">
                                @endif
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <strong>Biến thể {{ $loop->iteration }}</strong>
                                    <button type="button" class="btn btn-link text-danger p-0 remove-variant">Xóa dòng</button>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-4">
                                        <label>SKU</label>
                                        <input type="text" name="variants[{{ $index }}][sku]" value="{{ $variantRow['sku'] ?? '' }}" maxlength="255" class="form-control" placeholder="Tự tạo nếu để trống">
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label>Màu sắc</label>
                                        <input type="text" name="variants[{{ $index }}][color]" value="{{ $variantRow['color'] ?? '' }}" maxlength="255" class="form-control" placeholder="Ví dụ: Đen">
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label>Kích thước</label>
                                        <input type="text" name="variants[{{ $index }}][size]" value="{{ $variantRow['size'] ?? '' }}" maxlength="255" class="form-control" placeholder="Ví dụ: M">
                                    </div>
                                </div>
                                <div class="form-row mb-0">
                                    <div class="form-group col-md-4">
                                        <label>Giá riêng (₫)</label>
                                        <input type="number" name="variants[{{ $index }}][price]" value="{{ $variantRow['price'] ?? '' }}" min="0" max="9999999999.99" step="0.01" class="form-control" placeholder="Theo giá sản phẩm">
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label>Tồn kho <span class="text-danger">*</span></label>
                                        <input type="number" name="variants[{{ $index }}][stock]" value="{{ $variantRow['stock'] ?? 0 }}" min="0" step="1" required class="form-control">
                                    </div>
                                    <div class="form-group col-md-4 mb-0">
                                        <label>Trạng thái <span class="text-danger">*</span></label>
                                        <select name="variants[{{ $index }}][status]" class="form-control" required>
                                            @foreach ($variantStatuses as $status => $label)
                                                <option value="{{ $status }}" @selected(($variantRow['status'] ?? 'active') === $status)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div id="no-variants" class="text-center text-muted py-3 {{ $variantRows === [] ? '' : 'd-none' }}">
                        Chưa có biến thể. Chọn “Thêm biến thể” để cấu hình.
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><h3 class="card-title"><i class="far fa-image text-primary mr-2"></i>Hình ảnh sản phẩm</h3></div>
                <div class="card-body">
                    @if ($product->images->isNotEmpty())
                        <div class="row">
                            @foreach ($product->images as $image)
                                <div class="col-6 mb-3">
                                    <div class="border rounded p-2 h-100">
                                        <img src="{{ Storage::disk('public')->url($image->link_hinh_anh) }}" alt="Ảnh {{ $product->ten_san_pham }}" class="img-fluid rounded w-100" style="height: 110px; object-fit: cover">
                                        @if ($product->hinh_anh === $image->link_hinh_anh)
                                            <span class="badge badge-primary mt-2">Ảnh đại diện</span>
                                        @endif
                                        <div class="custom-control custom-checkbox mt-2">
                                            <input type="checkbox" id="remove_image_{{ $image->id }}" name="xoa_hinh_anh_ids[]" value="{{ $image->id }}" class="custom-control-input" @checked(in_array((string) $image->id, array_map('strval', old('xoa_hinh_anh_ids', [])), true))>
                                            <label class="custom-control-label small" for="remove_image_{{ $image->id }}">Xóa ảnh này</label>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center text-muted py-4 mb-3 bg-light rounded">
                            <i class="far fa-image mb-2" style="font-size: 2.5rem"></i>
                            <div>Chưa có ảnh sản phẩm.</div>
                        </div>
                    @endif
                    <div class="form-group mb-0">
                        <label for="hinh_anh">{{ $product->images->isNotEmpty() ? 'Thêm ảnh sản phẩm' : 'Chọn ảnh sản phẩm' }}</label>
                        <div class="custom-file">
                            <input type="file" id="hinh_anh" name="hinh_anh[]" class="custom-file-input @error('hinh_anh') is-invalid @enderror" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" multiple>
                            <label class="custom-file-label" for="hinh_anh">Chọn một hoặc nhiều ảnh</label>
                        </div>
                        @error('hinh_anh')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        @error('hinh_anh.*')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        <small class="form-text text-muted">JPG, PNG hoặc WebP; tối đa 5 MB mỗi ảnh. Có thể tải lên tối đa 10 ảnh mỗi lần.</small>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h3 class="card-title"><i class="fas fa-sliders-h text-primary mr-2"></i>Trạng thái</h3></div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="trang_thai">Trạng thái kinh doanh <span class="text-danger">*</span></label>
                        <select id="trang_thai" name="trang_thai" class="form-control @error('trang_thai') is-invalid @enderror" required>
                            <option value="1" @selected((string) old('trang_thai', (int) $product->trang_thai) === '1')>Đang kinh doanh</option>
                            <option value="0" @selected((string) old('trang_thai', (int) $product->trang_thai) === '0')>Đang ẩn</option>
                        </select>
                        @error('trang_thai')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" id="noi_bat" name="noi_bat" value="1" class="custom-control-input" @checked((bool) old('noi_bat', $product->noi_bat))>
                        <label class="custom-control-label" for="noi_bat">Đánh dấu sản phẩm nổi bật</label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between mb-4">
        <a href="{{ route('admin.products.index') }}" class="btn btn-default"><i class="fas fa-arrow-left mr-1"></i>Quay lại</a>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>{{ $product->exists ? 'Lưu thay đổi' : 'Tạo sản phẩm' }}</button>
    </div>
</form>

<template id="variant-row-template">
    <div class="border rounded p-3 mb-3 variant-row">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <strong>Biến thể mới</strong>
            <button type="button" class="btn btn-link text-danger p-0 remove-variant">Xóa dòng</button>
        </div>
        <div class="form-row">
            <div class="form-group col-md-4">
                <label>SKU</label>
                <input type="text" name="variants[__INDEX__][sku]" maxlength="255" class="form-control" placeholder="Tự tạo nếu để trống">
            </div>
            <div class="form-group col-md-4">
                <label>Màu sắc</label>
                <input type="text" name="variants[__INDEX__][color]" maxlength="255" class="form-control" placeholder="Ví dụ: Đen">
            </div>
            <div class="form-group col-md-4">
                <label>Kích thước</label>
                <input type="text" name="variants[__INDEX__][size]" maxlength="255" class="form-control" placeholder="Ví dụ: M">
            </div>
        </div>
        <div class="form-row mb-0">
            <div class="form-group col-md-4">
                <label>Giá riêng (₫)</label>
                <input type="number" name="variants[__INDEX__][price]" min="0" max="9999999999.99" step="0.01" class="form-control" placeholder="Theo giá sản phẩm">
            </div>
            <div class="form-group col-md-4">
                <label>Tồn kho <span class="text-danger">*</span></label>
                <input type="number" name="variants[__INDEX__][stock]" value="0" min="0" step="1" required class="form-control">
            </div>
            <div class="form-group col-md-4 mb-0">
                <label>Trạng thái <span class="text-danger">*</span></label>
                <select name="variants[__INDEX__][status]" class="form-control" required>
                    @foreach ($variantStatuses as $status => $label)
                        <option value="{{ $status }}" @selected($status === 'active')>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
</template>
@endsection

@section('scripts')
<script>
    (function () {
        var variantList = document.getElementById('variant-list');
        var variantTemplate = document.getElementById('variant-row-template');
        var emptyVariants = document.getElementById('no-variants');
        var nextVariantIndex = {{ $variantRows === [] ? 0 : max(array_keys($variantRows)) + 1 }};

        document.getElementById('add-variant').addEventListener('click', function () {
            var row = variantTemplate.content.cloneNode(true);
            row.querySelectorAll('[name]').forEach(function (field) {
                field.name = field.name.replace('__INDEX__', nextVariantIndex);
            });
            nextVariantIndex += 1;
            variantList.appendChild(row);
            emptyVariants.classList.add('d-none');
        });

        variantList.addEventListener('click', function (event) {
            if (!event.target.closest('.remove-variant')) return;

            event.target.closest('.variant-row').remove();
            emptyVariants.classList.toggle('d-none', variantList.querySelector('.variant-row') !== null);
        });

        var imageInput = document.getElementById('hinh_anh');
        if (!imageInput) return;

        imageInput.addEventListener('change', function () {
            var label = imageInput.nextElementSibling;
            var count = imageInput.files.length;
            label.textContent = count === 0
                ? 'Chọn một hoặc nhiều ảnh'
                : count === 1 ? imageInput.files[0].name : count + ' ảnh đã chọn';
        });
    })();
</script>
@endsection
