@extends('layouts.admin')

@section('title', 'Quản lý bài viết')
@section('page-heading', 'Quản lý bài viết')
@section('page-subheading', 'Tạo, cập nhật và quản lý trạng thái tin tức của cửa hàng.')

@section('content')
<div class="row">
    <div class="col-md-4">
        <div class="small-box bg-white">
            <div class="inner"><h3>{{ number_format($articles->total()) }}</h3><p>Bài viết theo bộ lọc</p></div>
            <div class="icon"><i class="far fa-newspaper text-primary"></i></div>
        </div>
    </div>
    @foreach ($statuses as $status => $label)
        <div class="col-md-4">
            <div class="small-box bg-white">
                <div class="inner"><h3>{{ number_format($counts[$status] ?? 0) }}</h3><p>{{ $label }}</p></div>
                <div class="icon"><i class="fas fa-file-alt text-secondary"></i></div>
            </div>
        </div>
    @endforeach
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title"><i class="far fa-newspaper text-primary mr-2"></i>Danh sách bài viết</h3>
        <div>
            <a href="{{ route('admin.news-categories.index') }}" class="btn btn-default btn-sm mr-1"><i class="fas fa-folder mr-1"></i>Danh mục</a>
            <a href="{{ route('admin.news.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus mr-1"></i>Thêm bài viết</a>
        </div>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('admin.news.index') }}" class="form-row align-items-end mb-3">
            <div class="form-group col-md-5">
                <label for="q">Tìm bài viết</label>
                <input id="q" type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Tiêu đề hoặc mô tả ngắn">
            </div>
            <div class="form-group col-md-3">
                <label for="category_id">Danh mục</label>
                <select id="category_id" name="category_id" class="form-control">
                    <option value="">Tất cả danh mục</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-2">
                <label for="status">Trạng thái</label>
                <select id="status" name="status" class="form-control">
                    <option value="">Tất cả trạng thái</option>
                    @foreach ($statuses as $status => $label)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-2">
                <button type="submit" class="btn btn-primary mr-1"><i class="fas fa-search mr-1"></i>Lọc</button>
                <a href="{{ route('admin.news.index') }}" class="btn btn-default">Xóa lọc</a>
            </div>
        </form>

        @if ($articles->isEmpty())
            <div class="text-center text-muted py-5">Không tìm thấy bài viết phù hợp.</div>
        @else
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Bài viết</th><th>Danh mục</th><th>Trạng thái</th><th>Ngày đăng</th><th class="text-right">Thao tác</th></tr></thead>
                    <tbody>
                    @foreach ($articles as $article)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    @if ($article->featured_image)
                                        <img src="{{ Storage::disk('public')->url($article->featured_image) }}" alt="" class="product-thumb mr-2">
                                    @endif
                                    <div><strong>{{ $article->title }}</strong><div class="small text-muted">{{ $article->excerpt }}</div></div>
                                </div>
                            </td>
                            <td>{{ $article->category?->name ?? 'Chưa phân loại' }}</td>
                            <td><span class="badge badge-{{ $article->status === 'published' ? 'success' : ($article->status === 'hidden' ? 'secondary' : 'warning') }}">{{ $statuses[$article->status] }}</span></td>
                            <td>{{ $article->published_at?->format('d/m/Y H:i') ?? '—' }}</td>
                            <td class="text-right table-actions text-nowrap">
                                @if ($article->exists)
                                    <a href="{{ route('admin.news.preview', $article) }}" target="_blank" rel="noopener" class="btn btn-sm btn-default" title="Xem trước"><i class="far fa-eye"></i></a>
                                @endif
                                <a href="{{ route('admin.news.edit', $article) }}" class="btn btn-sm btn-default" title="Chỉnh sửa"><i class="fas fa-edit"></i></a>
                                <form method="POST" action="{{ route('admin.news.destroy', $article) }}" class="d-inline" onsubmit="return confirm('Bạn có chắc muốn xóa bài viết này?');">
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
            {{ $articles->links() }}
        @endif
    </div>
</div>
@endsection
