@extends('layouts.admin')

@section('title', 'Quản lý banner')
@section('page-heading', 'Quản lý banner')
@section('page-subheading', 'Quản lý ảnh quảng bá hiển thị tại đầu trang cửa hàng.')

@section('content')
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h3 class="card-title"><i class="far fa-image text-primary mr-2"></i>Danh sách banner</h3>
        <a href="{{ route('admin.banners.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus mr-1"></i>Thêm banner</a>
    </div>
    <div class="card-body p-0">
        @if ($banners->isEmpty())
            <div class="p-4 text-center text-muted">Chưa có banner nào.</div>
        @else
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Ảnh</th><th>Tiêu đề</th><th>Liên kết</th><th>Thứ tự</th><th>Trạng thái</th><th class="text-right">Thao tác</th></tr></thead>
                    <tbody>
                    @foreach ($banners as $banner)
                        <tr>
                            <td><img src="{{ Storage::disk('public')->url($banner->image_path) }}" alt="{{ $banner->title }}" class="product-thumb" style="width:72px;height:42px"></td>
                            <td class="font-weight-bold"><a href="{{ route('admin.banners.show', $banner) }}">{{ $banner->title }}</a></td>
                            <td>@if ($banner->link_url)<a href="{{ $banner->link_url }}" target="_blank" rel="noopener" class="text-truncate d-inline-block" style="max-width:220px">{{ $banner->link_url }}</a>@else<span class="text-muted">—</span>@endif</td>
                            <td>{{ $banner->sort_order }}</td>
                            <td><span class="badge badge-{{ $banner->is_active ? 'success' : 'secondary' }}">{{ $banner->is_active ? 'Đang hiển thị' : 'Đang ẩn' }}</span></td>
                            <td class="text-right table-actions text-nowrap">
                                <a href="{{ route('admin.banners.show', $banner) }}" class="btn btn-sm btn-default text-primary" title="Xem chi tiết"><i class="far fa-eye"></i></a>
                                <a href="{{ route('admin.banners.edit', $banner) }}" class="btn btn-sm btn-default" title="Chỉnh sửa"><i class="fas fa-edit"></i></a>
                                <form method="POST" action="{{ route('admin.banners.destroy', $banner) }}" class="d-inline" onsubmit="return confirm('Bạn có chắc muốn xóa banner này?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-default text-danger" title="Xóa"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="p-3">{{ $banners->links() }}</div>
        @endif
    </div>
</div>
@endsection
