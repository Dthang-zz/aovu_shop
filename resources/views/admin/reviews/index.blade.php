@extends('layouts.admin')

@section('title', 'Quản lý đánh giá')
@section('page-heading', 'Đánh giá sản phẩm')
@section('page-subheading', 'Theo dõi đánh giá và phản hồi khách hàng về sản phẩm.')
@section('breadcrumb', 'Đánh giá')

@section('content')
@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle mr-1"></i>{{ session('success') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Đóng"><span aria-hidden="true">&times;</span></button>
    </div>
@endif

<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-white">
            <div class="inner">
                <p class="text-muted mb-1">Tổng lượt đánh giá</p>
                <h3>{{ number_format((int) $statistics->total) }}</h3>
            </div>
            <div class="icon text-primary"><i class="fas fa-star"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-white">
            <div class="inner">
                <p class="text-muted mb-1">Sản phẩm có đánh giá</p>
                <h3>{{ number_format((int) $statistics->products_reviewed) }}</h3>
            </div>
            <div class="icon text-info"><i class="fas fa-tshirt"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-white">
            <div class="inner">
                <p class="text-muted mb-1">Đã phản hồi</p>
                <h3>{{ number_format((int) $statistics->replied) }}</h3>
            </div>
            <div class="icon text-success"><i class="fas fa-reply"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-white">
            <div class="inner">
                <p class="text-muted mb-1">Điểm trung bình</p>
                <h3>
                    {{ $statistics->average_rating !== null ? number_format((float) $statistics->average_rating, 1, ',', '.') : '—' }}
                    <small class="text-muted">/ 5</small>
                </h3>
            </div>
            <div class="icon text-info"><i class="fas fa-star-half-alt"></i></div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-filter text-primary mr-2"></i>Tìm kiếm và lọc</h3>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('admin.reviews.index') }}">
            <div class="form-row align-items-end">
                <div class="form-group col-lg-4 col-md-6">
                    <label for="q">Từ khóa</label>
                    <input type="search" id="q" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control" placeholder="Nội dung, khách hàng, đơn hàng...">
                </div>
                <div class="form-group col-lg-3 col-md-6">
                    <label for="product_id">Sản phẩm</label>
                    <select id="product_id" name="product_id" class="form-control">
                        <option value="">Tất cả sản phẩm</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}" @selected((string) ($filters['product_id'] ?? '') === (string) $product->id)>{{ $product->ten_san_pham }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-lg-2 col-md-6">
                    <label for="stars">Số sao</label>
                    <select id="stars" name="stars" class="form-control">
                        <option value="">Tất cả số sao</option>
                        @for ($star = 5; $star >= 1; $star--)
                            <option value="{{ $star }}" @selected((string) ($filters['stars'] ?? '') === (string) $star)>{{ $star }} sao</option>
                        @endfor
                    </select>
                </div>
            </div>
            <div class="form-row align-items-end mb-0">
                <div class="form-group col-lg-3 col-md-6 mb-lg-0">
                    <label for="from">Từ ngày</label>
                    <input type="date" id="from" name="from" value="{{ $filters['from'] ?? '' }}" class="form-control">
                </div>
                <div class="form-group col-lg-3 col-md-6 mb-lg-0">
                    <label for="to">Đến ngày</label>
                    <input type="date" id="to" name="to" value="{{ $filters['to'] ?? '' }}" class="form-control">
                </div>
                <div class="form-group col-lg-6 col-md-12 mb-0">
                    <button type="submit" class="btn btn-primary mr-1" aria-label="Áp dụng bộ lọc"><i class="fas fa-search"></i></button>
                    <a href="{{ route('admin.reviews.index') }}" class="btn btn-default" aria-label="Xóa bộ lọc"><i class="fas fa-redo"></i></a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title"><i class="far fa-comments text-primary mr-2"></i>Danh sách đánh giá</h3>
        <span class="text-muted small">{{ number_format($reviews->total()) }} kết quả</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                <tr>
                    <th class="pl-3">Khách hàng / Nội dung</th>
                    <th>Sản phẩm</th>
                    <th>Đánh giá</th>
                    <th>Đơn hàng</th>
                    <th>Ngày gửi</th>
                    <th class="text-right pr-3">Thao tác</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($reviews as $review)
                    <tr>
                        <td class="pl-3" style="min-width: 230px">
                            <div class="font-weight-bold text-dark">{{ $review->customer_name ?: 'Khách hàng' }}</div>
                            <div class="small text-muted">{{ \Illuminate\Support\Str::limit($review->noi_dung ?: 'Không có nội dung.', 110) }}</div>
                        </td>
                        <td>{{ $review->product_name ?: 'Sản phẩm đã xóa' }}</td>
                        <td class="text-nowrap">
                            <span class="text-warning" aria-label="{{ $review->so_sao ?? 0 }} sao">
                                @for ($star = 1; $star <= 5; $star++)
                                    <i class="{{ $star <= (int) $review->so_sao ? 'fas' : 'far' }} fa-star"></i>
                                @endfor
                            </span>
                            <small class="text-muted">{{ $review->so_sao ?? '—' }}/5</small>
                        </td>
                        <td>{{ $review->order_number ?: '—' }}</td>
                        <td>{{ $review->ngay_dang ? \Illuminate\Support\Carbon::parse($review->ngay_dang)->format('d/m/Y H:i') : '—' }}</td>
                        <td class="text-right pr-3">
                            <a href="{{ route('admin.reviews.show', $review->id) }}" class="btn btn-default btn-sm text-primary" aria-label="Xem chi tiết đánh giá">
                                <i class="far fa-eye"></i><span class="ml-1">Chi tiết</span>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-5">
                            <i class="far fa-comment-dots fa-2x d-block mb-2"></i>Không tìm thấy đánh giá phù hợp.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if ($reviews->hasPages())
        <div class="card-footer bg-white">{{ $reviews->links() }}</div>
    @endif
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-chart-bar text-primary mr-2"></i>Thống kê theo sản phẩm</h3>
        <div class="small text-muted mt-1">Số lượt và điểm trung bình được tính từ toàn bộ đánh giá.</div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                <tr>
                    <th class="pl-3">Sản phẩm</th>
                    <th>Số lượt đánh giá</th>
                    <th>Điểm trung bình</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($productStatistics as $productStatistic)
                    <tr>
                        <td class="pl-3">{{ $productStatistic->ten_san_pham }}</td>
                        <td>{{ number_format((int) $productStatistic->review_count) }}</td>
                        <td class="text-warning">
                            <i class="fas fa-star"></i>
                            <strong class="text-dark">{{ number_format((float) $productStatistic->average_rating, 1, ',', '.') }}</strong>
                            <span class="text-muted">/ 5</span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="text-center text-muted py-4">Chưa có đánh giá nào.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
