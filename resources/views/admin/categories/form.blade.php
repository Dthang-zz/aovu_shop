@extends('layouts.admin')

@section('title', $formTitle)
@section('page-heading', $formTitle)
@section('page-subheading', 'Nhập thông tin danh mục sản phẩm.')
@section('breadcrumb')
    <a href="{{ route('admin.categories.index') }}">Danh mục</a>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-8">
        <form method="POST" action="{{ $category->exists ? route('admin.categories.update', $category) : route('admin.categories.store') }}">
            @csrf
            @if ($category->exists)
                @method('PUT')
            @endif

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-info-circle text-primary mr-2"></i>Thông tin danh mục</h3>
                </div>
                <div class="card-body">
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

                    <div class="form-group">
                        <label for="ten_danh_muc">Tên danh mục <span class="text-danger">*</span></label>
                        <input id="ten_danh_muc" type="text" name="ten_danh_muc" value="{{ old('ten_danh_muc', $category->ten_danh_muc) }}" class="form-control @error('ten_danh_muc') is-invalid @enderror" maxlength="255" required autofocus placeholder="Ví dụ: Áo nữ">
                        @error('ten_danh_muc')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-group">
                        <label for="danh_muc_cha_id">Danh mục cha</label>
                        <select id="danh_muc_cha_id" name="danh_muc_cha_id" class="form-control @error('danh_muc_cha_id') is-invalid @enderror">
                            <option value="">Không có (danh mục gốc)</option>
                            @foreach ($parents as $parent)
                                <option value="{{ $parent->id }}" @selected((string) old('danh_muc_cha_id', $category->danh_muc_cha_id) === (string) $parent->id)>{{ $parent->ten_danh_muc }}</option>
                            @endforeach
                        </select>
                        @error('danh_muc_cha_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <small class="form-text text-muted">Có thể để trống để tạo danh mục ở cấp gốc.</small>
                    </div>

                    <div class="form-group">
                        <label for="mo_ta">Mô tả</label>
                        <textarea id="mo_ta" name="mo_ta" rows="4" maxlength="5000" class="form-control @error('mo_ta') is-invalid @enderror" placeholder="Mô tả ngắn về danh mục...">{{ old('mo_ta', $category->mo_ta) }}</textarea>
                        @error('mo_ta')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-group mb-0">
                        <label for="trang_thai">Trạng thái <span class="text-danger">*</span></label>
                        <select id="trang_thai" name="trang_thai" class="form-control @error('trang_thai') is-invalid @enderror" required>
                            <option value="1" @selected((string) old('trang_thai', (int) $category->trang_thai) === '1')>Đang hiển thị</option>
                            <option value="0" @selected((string) old('trang_thai', (int) $category->trang_thai) === '0')>Đang ẩn</option>
                        </select>
                        @error('trang_thai')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="card-footer bg-white d-flex justify-content-between">
                    <a href="{{ route('admin.categories.index') }}" class="btn btn-default"><i class="fas fa-arrow-left mr-1"></i>Quay lại</a>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>{{ $category->exists ? 'Lưu thay đổi' : 'Tạo danh mục' }}</button>
                </div>
            </div>
        </form>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title"><i class="far fa-lightbulb text-warning mr-2"></i>Lưu ý</h3></div>
            <div class="card-body">
                <p class="text-muted">Danh mục có thể được tổ chức theo nhiều cấp bằng cách chọn danh mục cha.</p>
                <p class="text-muted mb-0">Khi xóa danh mục cha, danh mục con sẽ được chuyển thành danh mục gốc. Không thể xóa danh mục đang được sản phẩm sử dụng.</p>
            </div>
        </div>
    </div>
</div>
@endsection
