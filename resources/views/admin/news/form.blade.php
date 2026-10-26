@extends('layouts.admin')

@section('title', $article->exists ? 'Chỉnh sửa bài viết' : 'Thêm bài viết')
@section('page-heading', $article->exists ? 'Chỉnh sửa bài viết' : 'Thêm bài viết')
@section('page-subheading', 'Quản lý nội dung, hình ảnh, SEO và trạng thái xuất bản.')

@section('content')
<form method="POST" action="{{ $article->exists ? route('admin.news.update', $article) : route('admin.news.store') }}" enctype="multipart/form-data">
    @csrf
    @if ($article->exists)
        @method('PUT')
    @endif
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Nội dung bài viết</h3></div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="title">Tiêu đề <span class="text-danger">*</span></label>
                        <input id="title" name="title" value="{{ old('title', $article->title) }}" maxlength="255" required class="form-control @error('title') is-invalid @enderror">
                        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label for="slug">Đường dẫn</label>
                        <input id="slug" name="slug" value="{{ old('slug', $article->slug) }}" maxlength="255" class="form-control @error('slug') is-invalid @enderror" placeholder="Tự tạo từ tiêu đề nếu để trống">
                        @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label for="excerpt">Mô tả ngắn</label>
                        <textarea id="excerpt" name="excerpt" rows="3" maxlength="500" class="form-control @error('excerpt') is-invalid @enderror">{{ old('excerpt', $article->excerpt) }}</textarea>
                        @error('excerpt')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group mb-0">
                        <label for="content">Nội dung <span class="text-danger">*</span></label>
                        <textarea id="content" name="content" rows="16" required class="form-control @error('content') is-invalid @enderror">{{ old('content', $article->content) }}</textarea>
                        @error('content')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h3 class="card-title">Thông tin SEO</h3></div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="seo_title">Tiêu đề SEO</label>
                        <input id="seo_title" name="seo_title" value="{{ old('seo_title', $article->seo_title) }}" maxlength="255" class="form-control @error('seo_title') is-invalid @enderror">
                        @error('seo_title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group mb-0">
                        <label for="seo_description">Mô tả SEO</label>
                        <textarea id="seo_description" name="seo_description" rows="3" maxlength="500" class="form-control @error('seo_description') is-invalid @enderror">{{ old('seo_description', $article->seo_description) }}</textarea>
                        @error('seo_description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Xuất bản</h3></div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="status">Trạng thái <span class="text-danger">*</span></label>
                        <select id="status" name="status" required class="form-control @error('status') is-invalid @enderror">
                            @foreach ($statuses as $status => $label)
                                <option value="{{ $status }}" @selected(old('status', $article->status ?? \App\Models\NewsArticle::STATUS_DRAFT) === $status)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label for="category_id">Danh mục</label>
                        <select id="category_id" name="category_id" class="form-control @error('category_id') is-invalid @enderror">
                            <option value="">Chưa phân loại</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected((string) old('category_id', $article->category_id) === (string) $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                        @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    @if ($article->exists)
                        <div class="small text-muted">Ngày xuất bản: {{ $article->published_at?->format('d/m/Y H:i') ?? 'Chưa xuất bản' }}</div>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h3 class="card-title">Ảnh đại diện</h3></div>
                <div class="card-body">
                    @if ($article->featured_image)
                        <img src="{{ Storage::disk('public')->url($article->featured_image) }}" alt="Ảnh {{ $article->title }}" class="img-fluid rounded mb-3">
                    @endif
                    <div class="form-group mb-0">
                        <label for="featured_image">{{ $article->featured_image ? 'Thay ảnh' : 'Chọn ảnh' }}</label>
                        <input id="featured_image" type="file" name="featured_image" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" class="form-control-file @error('featured_image') is-invalid @enderror">
                        @error('featured_image')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        <small class="form-text text-muted">JPG, PNG hoặc WebP; tối đa 5 MB.</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="d-flex justify-content-between mb-4">
        <a href="{{ route('admin.news.index') }}" class="btn btn-default"><i class="fas fa-arrow-left mr-1"></i>Quay lại</a>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>Lưu bài viết</button>
    </div>
</form>
@endsection
