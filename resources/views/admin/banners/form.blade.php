@extends('layouts.admin')

@section('title', $formTitle)
@section('page-heading', $formTitle)
@section('page-subheading', 'Cập nhật hình ảnh, liên kết và trạng thái hiển thị.')
@section('breadcrumb')
    <a href="{{ route('admin.banners.index') }}">Banner</a>
@endsection

@section('content')
<form method="POST" enctype="multipart/form-data" action="{{ $banner->exists ? route('admin.banners.update', $banner) : route('admin.banners.store') }}">
    @csrf
    @if ($banner->exists)
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
                <div class="card-header"><h3 class="card-title"><i class="fas fa-info-circle text-primary mr-2"></i>Thông tin banner</h3></div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="ten_banner">Tên banner <span class="text-danger">*</span></label>
                        <input id="ten_banner" type="text" name="ten_banner" value="{{ old('ten_banner', $banner->ten_banner) }}" class="form-control @error('ten_banner') is-invalid @enderror" maxlength="255" required autofocus placeholder="Ví dụ: Bộ sưu tập mùa hè">
                        @error('ten_banner')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label for="lien_ket">Liên kết khi nhấn vào banner</label>
                        <input id="lien_ket" type="text" name="lien_ket" value="{{ old('lien_ket', $banner->lien_ket) }}" class="form-control @error('lien_ket') is-invalid @enderror" maxlength="2048" placeholder="Ví dụ: /collections/summer">
                        @error('lien_ket')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="thu_tu">Thứ tự hiển thị <span class="text-danger">*</span></label>
                            <input id="thu_tu" type="number" name="thu_tu" value="{{ old('thu_tu', $banner->thu_tu ?? 0) }}" class="form-control @error('thu_tu') is-invalid @enderror" min="0" max="4294967295" step="1" required>
                            @error('thu_tu')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <small class="form-text text-muted">Số nhỏ hơn sẽ được ưu tiên hiển thị trước.</small>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="trang_thai">Trạng thái <span class="text-danger">*</span></label>
                            <select id="trang_thai" name="trang_thai" class="form-control @error('trang_thai') is-invalid @enderror" required>
                                <option value="1" @selected((string) old('trang_thai', (int) ($banner->trang_thai ?? true)) === '1')>Đang hiển thị</option>
                                <option value="0" @selected((string) old('trang_thai', (int) ($banner->trang_thai ?? true)) === '0')>Đang ẩn</option>
                            </select>
                            @error('trang_thai')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="form-group mb-0">
                        <label for="hinh_anh">Ảnh banner @unless ($banner->exists)<span class="text-danger">*</span>@endunless</label>
                        <input id="hinh_anh" type="file" name="hinh_anh" accept=".jpg,.jpeg,.png,.webp" class="form-control-file @error('hinh_anh') is-invalid @enderror" @unless ($banner->exists) required @endunless>
                        @error('hinh_anh')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        <small class="form-text text-muted">Chấp nhận JPG, PNG hoặc WebP, dung lượng tối đa 5 MB. Để trống khi chỉnh sửa để giữ ảnh hiện tại.</small>
                    </div>
                    @if ($banner->hinh_anh)
                        <div class="mt-3">
                            <img src="{{ Storage::disk('public')->url($banner->hinh_anh) }}" alt="{{ $banner->ten_banner ?: 'Ảnh banner hiện tại' }}" class="img-fluid rounded border" style="max-height: 240px">
                        </div>
                    @endif
                </div>
                <div class="card-footer bg-white d-flex justify-content-between">
                    <a href="{{ route('admin.banners.index') }}" class="btn btn-default"><i class="fas fa-arrow-left mr-1"></i>Quay lại</a>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>{{ $banner->exists ? 'Lưu thay đổi' : 'Tạo banner' }}</button>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><h3 class="card-title"><i class="far fa-lightbulb text-warning mr-2"></i>Lưu ý</h3></div>
                <div class="card-body">
                    <p class="text-muted">Banner hiển thị theo thứ tự tăng dần. Banner đang ẩn sẽ không được tính là nội dung hiển thị.</p>
                    <p class="text-muted mb-0">Khi thay ảnh hoặc xóa banner, ảnh cũ trong kho lưu trữ sẽ được dọn tự động.</p>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection
