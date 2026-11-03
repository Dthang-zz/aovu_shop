@extends('layouts.admin')

@section('title', 'Chi tiết banner')
@section('page-heading', 'Chi tiết banner')
@section('page-subheading', 'Xem ảnh, liên kết và trạng thái hiển thị của banner.')
@section('breadcrumb')
    <a href="{{ route('admin.banners.index') }}">Banner</a>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title"><i class="far fa-image text-primary mr-2"></i>{{ $banner->title }}</h3>
                <span class="badge badge-{{ $banner->is_active ? 'success' : 'secondary' }} px-2 py-1">{{ $banner->is_active ? 'Đang hiển thị' : 'Đang ẩn' }}</span>
            </div>
            <div class="card-body">
                <a href="{{ Storage::disk('public')->url($banner->image_path) }}" target="_blank" rel="noopener noreferrer">
                    <img src="{{ Storage::disk('public')->url($banner->image_path) }}" alt="{{ $banner->title }}" class="img-fluid rounded border w-100">
                </a>
                <div class="small text-muted mt-2">Bấm vào ảnh để xem kích thước gốc.</div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-info-circle text-primary mr-2"></i>Thông tin banner</h3></div>
            <div class="card-body">
                <dl class="mb-0">
                    <dt>Tiêu đề</dt>
                    <dd>{{ $banner->title }}</dd>
                    <dt>Liên kết</dt>
                    <dd class="text-break">
                        @if ($banner->link_url)
                            <a href="{{ $banner->link_url }}" target="_blank" rel="noopener noreferrer">{{ $banner->link_url }}</a>
                        @else
                            <span class="text-muted">Không có liên kết</span>
                        @endif
                    </dd>
                    <dt>Thứ tự hiển thị</dt>
                    <dd>{{ $banner->sort_order }}</dd>
                    <dt>Trạng thái</dt>
                    <dd>{{ $banner->is_active ? 'Đang hiển thị trên cửa hàng' : 'Đang ẩn khỏi cửa hàng' }}</dd>
                    <dt>Ngày tạo</dt>
                    <dd>{{ $banner->created_at?->format('d/m/Y H:i') ?? '—' }}</dd>
                    <dt>Cập nhật lần cuối</dt>
                    <dd class="mb-0">{{ $banner->updated_at?->format('d/m/Y H:i') ?? '—' }}</dd>
                </dl>
            </div>
            <div class="card-footer bg-white d-flex flex-wrap">
                <a href="{{ route('admin.banners.index') }}" class="btn btn-default mr-2 mb-1"><i class="fas fa-arrow-left mr-1"></i>Danh sách</a>
                <a href="{{ route('admin.banners.edit', $banner) }}" class="btn btn-primary mr-2 mb-1"><i class="fas fa-edit mr-1"></i>Chỉnh sửa</a>
                <form method="POST" action="{{ route('admin.banners.destroy', $banner) }}" class="d-inline mb-1" onsubmit="return confirm('Bạn có chắc muốn xóa banner này?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger"><i class="fas fa-trash mr-1"></i>Xóa</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
