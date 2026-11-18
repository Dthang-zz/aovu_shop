@extends('layouts.admin')

@section('title', $category->exists ? 'Chỉnh sửa danh mục' : 'Thêm danh mục')
@section('page-heading', $category->exists ? 'Chỉnh sửa danh mục tin tức' : 'Thêm danh mục tin tức')
@section('page-subheading', 'Tạo các chủ đề như Tin thời trang, Mẹo phối đồ hoặc Tin cửa hàng.')

@section('content')
<div class="row">
    <div class="col-lg-8">
        <form method="POST" action="{{ $category->exists ? route('admin.news-categories.update', $category) : route('admin.news-categories.store') }}">
            @csrf
            @if ($category->exists)
                @method('PUT')
            @endif
            <div class="card">
                <div class="card-header"><h3 class="card-title">Thông tin danh mục</h3></div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="name">Tên danh mục <span class="text-danger">*</span></label>
                        <input id="name" name="name" value="{{ old('name', $category->name) }}" maxlength="120" required class="form-control @error('name') is-invalid @enderror">
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label for="slug">Đường dẫn</label>
                        <input id="slug" name="slug" value="{{ old('slug', $category->slug) }}" maxlength="140" class="form-control @error('slug') is-invalid @enderror" placeholder="Tự tạo từ tên nếu để trống">
                        @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label for="description">Mô tả</label>
                        <textarea id="description" name="description" rows="4" maxlength="1000" class="form-control @error('description') is-invalid @enderror">{{ old('description', $category->description) }}</textarea>
                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" id="is_active" name="is_active" value="1" class="custom-control-input" @checked(old('is_active', $category->exists ? $category->is_active : true))>
                        <label for="is_active" class="custom-control-label">Hiển thị danh mục trên trang tin tức</label>
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-between">
                    <a href="{{ route('admin.news-categories.index') }}" class="btn btn-default">Quay lại</a>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>Lưu danh mục</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
