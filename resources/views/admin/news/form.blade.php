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
                    {{-- Inside the content area so the stylesheet also loads when the page is opened through admin SPA navigation. --}}
                    <link rel="stylesheet" href="{{ asset('adminlte/plugins/summernote/summernote-bs4.min.css') }}">
                    <div class="form-group">
                        <label for="title">Tiêu đề <span class="text-danger">*</span></label>
                        <textarea id="title" name="title" rows="1" class="form-control @error('title') is-invalid @enderror" data-inline-editor data-single-line data-max-length="255">{{ old('title', $article->exists ? $article->titleHtml() : '') }}</textarea>
                        @error('title')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label for="slug">Đường dẫn</label>
                        <input id="slug" name="slug" value="{{ old('slug', $article->slug) }}" maxlength="255" class="form-control @error('slug') is-invalid @enderror" placeholder="Tự tạo từ tiêu đề nếu để trống">
                        @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label for="excerpt">Mô tả ngắn</label>
                        <textarea id="excerpt" name="excerpt" rows="3" class="form-control @error('excerpt') is-invalid @enderror" data-inline-editor data-max-length="500">{{ old('excerpt', $article->exists ? $article->excerptHtml() : '') }}</textarea>
                        @error('excerpt')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group mb-0">
                        <label for="content">Nội dung <span class="text-danger">*</span></label>
                        <textarea id="content" name="content" rows="16" class="form-control @error('content') is-invalid @enderror" data-article-editor data-upload-url="{{ route('admin.news.images.store') }}">{{ old('content', $article->exists ? $article->contentHtml() : '') }}</textarea>
                        @error('content')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        <small class="form-text text-muted">Có thể định dạng chữ, chèn liên kết, bảng và hình ảnh (JPG, PNG, WebP, GIF; tối đa 5 MB mỗi ảnh).</small>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h3 class="card-title">Thông tin SEO</h3></div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="seo_title">Tiêu đề SEO</label>
                        <textarea id="seo_title" name="seo_title" rows="1" class="form-control @error('seo_title') is-invalid @enderror" data-inline-editor data-single-line data-max-length="255">{{ old('seo_title', app(\App\Services\ArticleHtmlSanitizer::class)->sanitizeInline($article->seo_title)) }}</textarea>
                        @error('seo_title')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group mb-0">
                        <label for="seo_description">Mô tả SEO</label>
                        <textarea id="seo_description" name="seo_description" rows="3" class="form-control @error('seo_description') is-invalid @enderror" data-inline-editor data-max-length="500">{{ old('seo_description', app(\App\Services\ArticleHtmlSanitizer::class)->sanitizeInline($article->seo_description, true)) }}</textarea>
                        @error('seo_description')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                    <small class="form-text text-muted mt-2">Google chỉ đọc chữ của Tiêu đề SEO và Mô tả SEO; định dạng (đậm, màu…) sẽ được bỏ khi đưa vào thẻ tiêu đề và mô tả của trang. Nên viết tiêu đề SEO khoảng 60 ký tự và mô tả khoảng 160 ký tự.</small>
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
                        <img src="{{ Storage::disk('public')->url($article->featured_image) }}" alt="Ảnh {{ $article->plainTitle() }}" class="img-fluid rounded mb-3">
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

@section('scripts')
    <script src="{{ asset('adminlte/plugins/summernote/summernote-bs4.min.js') }}"></script>
    <script src="{{ asset('adminlte/plugins/summernote/lang/summernote-vi-VN.min.js') }}"></script>
    <script>
        (function () {
            var $ = window.jQuery;
            if (!$ || !$.fn.summernote) return;

            document.querySelectorAll('[data-inline-editor]').forEach(function (field) {
                if (field.dataset.editorReady) return;
                field.dataset.editorReady = '1';

                var singleLine = field.hasAttribute('data-single-line');
                var maxLength = Number(field.dataset.maxLength || 0);
                var $field = $(field);
                var counter = document.createElement('small');
                counter.className = 'form-text text-muted text-right';
                field.parentNode.insertBefore(counter, field.nextSibling);

                function updateCounter() {
                    var html = $field.summernote('code').replace(/<(?:br|\/p|\/div|\/li|\/h[1-6]|\/blockquote)\b[^>]*>/gi, ' ');
                    var length = $('<div>').html(html).text().replace(/\s+/g, ' ').trim().length;
                    counter.textContent = length + (maxLength ? ' / ' + maxLength : '') + ' ký tự';
                    counter.classList.toggle('text-danger', maxLength > 0 && length > maxLength);
                }

                $field.summernote({
                    lang: 'vi-VN',
                    height: singleLine ? 46 : 110,
                    minHeight: singleLine ? 46 : 80,
                    disableDragAndDrop: true,
                    dialogsInBody: true,
                    disableResizeEditor: singleLine,
                    toolbar: [
                        ['font', ['bold', 'italic', 'underline', 'strikethrough', 'clear']],
                        ['color', ['color']]
                    ],
                    callbacks: {
                        onInit: updateCounter,
                        onChange: updateCounter,
                        onImageUpload: function () {},
                        onKeydown: function (event) {
                            if (singleLine && event.keyCode === 13) {
                                event.preventDefault();
                            }
                        }
                    }
                });
            });
        }());

        (function () {
            var $ = window.jQuery;
            var textarea = document.querySelector('[data-article-editor]');
            if (!$ || !$.fn.summernote || !textarea || textarea.dataset.editorReady) return;
            textarea.dataset.editorReady = '1';

            var $editor = $(textarea);

            function uploadImage(file) {
                var data = new FormData();
                data.append('image', file);

                fetch(textarea.dataset.uploadUrl, {
                    method: 'POST',
                    body: data,
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                }).then(function (response) {
                    return response.json().then(function (body) {
                        if (!response.ok) {
                            var errors = body.errors && body.errors.image;
                            throw new Error(errors ? errors[0] : (body.message || 'Không thể tải ảnh lên.'));
                        }
                        return body;
                    });
                }).then(function (body) {
                    $editor.summernote('insertImage', body.url, function ($image) {
                        $image.attr('alt', file.name.replace(/\.[^.]+$/, ''));
                        $image.css('max-width', '100%');
                    });
                }).catch(function (error) {
                    window.alert(error.message);
                });
            }

            $editor.summernote({
                lang: 'vi-VN',
                height: 420,
                placeholder: 'Nhập nội dung bài viết...',
                dialogsInBody: true,
                toolbar: [
                    ['style', ['style']],
                    ['font', ['bold', 'italic', 'underline', 'strikethrough', 'clear']],
                    ['fontsize', ['fontsize']],
                    ['color', ['color']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['insert', ['link', 'picture', 'table', 'hr']],
                    ['view', ['fullscreen', 'codeview', 'undo', 'redo']]
                ],
                styleTags: ['p', 'h2', 'h3', 'h4', 'blockquote', 'pre'],
                callbacks: {
                    onImageUpload: function (files) {
                        Array.prototype.forEach.call(files, uploadImage);
                    }
                }
            });
        }());
    </script>
@endsection
