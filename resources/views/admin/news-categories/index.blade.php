@extends('layouts.admin')

@section('title', 'Danh mục tin tức')
@section('page-heading', 'Danh mục tin tức')
@section('page-subheading', 'Tổ chức bài viết theo chủ đề để khách hàng dễ theo dõi.')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title"><i class="fas fa-folder text-primary mr-2"></i>Danh sách danh mục</h3>
        <a href="{{ route('admin.news-categories.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus mr-1"></i>Thêm danh mục</a>
    </div>
    <div class="card-body">
        @if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
        @if ($categories->isEmpty())
            <div class="text-center text-muted py-5">Chưa có danh mục tin tức.</div>
        @else
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Tên danh mục</th><th>Đường dẫn</th><th>Số bài viết</th><th>Trạng thái</th><th class="text-right">Thao tác</th></tr></thead>
                    <tbody>
                    @foreach ($categories as $category)
                        <tr>
                            <td><strong>{{ $category->name }}</strong><div class="small text-muted">{{ $category->description }}</div></td>
                            <td>{{ $category->slug }}</td>
                            <td>{{ number_format($category->articles_count) }}</td>
                            <td><span class="badge badge-{{ $category->is_active ? 'success' : 'secondary' }}">{{ $category->is_active ? 'Đang hiển thị' : 'Đang ẩn' }}</span></td>
                            <td class="text-right text-nowrap">
                                <a href="{{ route('admin.news-categories.edit', $category) }}" class="btn btn-sm btn-default" title="Chỉnh sửa"><i class="fas fa-edit"></i></a>
                                <form method="POST" action="{{ route('admin.news-categories.destroy', $category) }}" class="d-inline" onsubmit="return confirm('Bạn có chắc muốn xóa danh mục này?');">
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
            {{ $categories->links() }}
        @endif
    </div>
</div>
<a href="{{ route('admin.news.index') }}" class="btn btn-default"><i class="fas fa-arrow-left mr-1"></i>Quay lại bài viết</a>
@endsection
