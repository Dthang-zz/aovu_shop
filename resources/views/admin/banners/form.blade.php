@extends('layouts.admin')

@section('title', $banner->exists ? 'Chỉnh sửa banner' : 'Thêm banner')
@section('page-heading', $banner->exists ? 'Chỉnh sửa banner' : 'Thêm banner')
@section('page-subheading', 'Ảnh banner được hiển thị ở đầu trang cửa hàng.')

@section('content')
<form method="POST" action="{{ $banner->exists ? route('admin.banners.update', $banner) : route('admin.banners.store') }}" enctype="multipart/form-data">
    @csrf
    @if ($banner->exists) @method('PUT') @endif
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Thông tin banner</h3></div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="title">Tiêu đề <span class="text-danger">*</span></label>
                        <input id="title" name="title" value="{{ old('title', $banner->title) }}" maxlength="255" required class="form-control @error('title') is-invalid @enderror">
                        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label for="link_url">Liên kết khi nhấp</label>
                        <input id="link_url" type="url" name="link_url" value="{{ old('link_url', $banner->link_url) }}" maxlength="2048" placeholder="https://example.com/khuyen-mai" class="form-control @error('link_url') is-invalid @enderror">
                        @error('link_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group mb-0">
                        <label for="image">Ảnh banner {{ $banner->exists ? '' : '*' }}</label>
                        <input id="image" type="file" name="image" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" {{ $banner->exists ? '' : 'required' }} class="form-control-file @error('image') is-invalid @enderror">
                        @error('image')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        <small class="form-text text-muted">JPG, PNG hoặc WebP; tối đa 5 MB. Khuyến nghị ảnh ngang tỉ lệ 16:6.</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Hiển thị</h3></div>
                <div class="card-body">
                    @if ($banner->image_path)
                        <img src="{{ Storage::disk('public')->url($banner->image_path) }}" alt="{{ $banner->title }}" class="img-fluid rounded mb-3">
                    @endif
                    <div class="form-group">
                        <label for="sort_order">Thứ tự <span class="text-danger">*</span></label>
                        <input id="sort_order" type="number" min="0" max="100000" name="sort_order" value="{{ old('sort_order', $banner->sort_order ?? 0) }}" required class="form-control @error('sort_order') is-invalid @enderror">
                        @error('sort_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <small class="form-text text-muted">Số nhỏ hơn sẽ hiển thị trước.</small>
                    </div>
                    <div class="custom-control custom-switch">
                        <input id="is_active" type="checkbox" name="is_active" value="1" class="custom-control-input" @checked(old('is_active', $banner->is_active ?? true))>
                        <label class="custom-control-label" for="is_active">Hiển thị trên cửa hàng</label>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="d-flex justify-content-between mb-4">
        <a href="{{ route('admin.banners.index') }}" class="btn btn-default"><i class="fas fa-arrow-left mr-1"></i>Quay lại</a>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>Lưu banner</button>
    </div>
</form>
@endsection
