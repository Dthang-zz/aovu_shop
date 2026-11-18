@extends('layouts.admin')

@section('title', 'Chi tiết khách hàng — ' . $user->ho_ten)
@section('page-heading', $user->ho_ten)
@section('page-subheading', 'Thông tin chi tiết tài khoản khách hàng.')
@section('breadcrumb')
    <a href="{{ route('admin.users.index') }}">Khách hàng</a>
@endsection

@section('content')
<div class="row">
    {{-- Cột trái: Avatar & thao tác --}}
    <div class="col-lg-4 col-md-5">
        <div class="card">
            <div class="card-body text-center py-4">
                @if ($user->anh_dai_dien)
                    <img src="{{ $user->avatarUrl() }}" alt="{{ $user->ho_ten }}" class="img-circle img-fluid mb-3" style="width:100px;height:100px;object-fit:cover;">
                @else
                    <div class="img-circle bg-gradient-primary d-inline-flex align-items-center justify-content-center text-white font-weight-bold mx-auto mb-3" style="width:90px;height:90px;font-size:2rem;">
                        {{ mb_strtoupper(mb_substr($user->ho_ten ?? '?', 0, 1)) }}
                    </div>
                @endif
                <h5 class="font-weight-bold mb-1">{{ $user->ho_ten }}</h5>
                <p class="text-muted mb-2">{{ $user->email }}</p>
                @if ($user->chucVu)
                    <span class="badge badge-light border px-3 py-1">{{ $user->chucVu->ten_chuc_vu }}</span>
                @endif
                <div class="mt-2">
                    @if ($user->trang_thai)
                        <span class="badge badge-success px-3 py-1"><i class="fas fa-circle mr-1" style="font-size:.5rem;vertical-align:middle;"></i>Hoạt động</span>
                    @else
                        <span class="badge badge-danger px-3 py-1"><i class="fas fa-circle mr-1" style="font-size:.5rem;vertical-align:middle;"></i>Đã khóa</span>
                    @endif
                </div>
            </div>
            <div class="card-footer bg-white text-center">
                <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-primary btn-sm mr-1"><i class="far fa-edit mr-1"></i>Chỉnh sửa</a>
                <form method="POST" action="{{ route('admin.users.toggle-status', $user) }}" class="d-inline">
                    @csrf
                    @method('PATCH')
                    @if ($user->trang_thai)
                        <button type="submit" class="btn btn-warning btn-sm" onclick="return confirm('Bạn có chắc muốn khóa tài khoản này?')">
                            <i class="fas fa-lock mr-1"></i>Khóa
                        </button>
                    @else
                        <button type="submit" class="btn btn-success btn-sm">
                            <i class="fas fa-lock-open mr-1"></i>Mở khóa
                        </button>
                    @endif
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-shopping-bag text-info mr-2"></i>Thống kê đơn hàng</h3></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <tr>
                        <td class="text-muted pl-3">Tổng đơn hàng</td>
                        <td class="font-weight-bold">{{ number_format($orderStats->tong_don) }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted pl-3">Tổng chi tiêu</td>
                        <td class="font-weight-bold text-primary">₫ {{ number_format($orderStats->tong_chi_tieu ?? 0, 0, ',', '.') }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    {{-- Cột phải: thông tin chi tiết --}}
    <div class="col-lg-8 col-md-7">
        <div class="card">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-id-card text-primary mr-2"></i>Thông tin cá nhân</h3></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <tbody>
                        <tr>
                            <td class="text-muted font-weight-bold pl-3" style="width:35%">Họ tên</td>
                            <td>{{ $user->ho_ten }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted font-weight-bold pl-3">Email</td>
                            <td>{{ $user->email }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted font-weight-bold pl-3">Số điện thoại</td>
                            <td>{{ $user->so_dien_thoai ?? '—' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted font-weight-bold pl-3">Ngày sinh</td>
                            <td>{{ $user->ngay_sinh?->format('d/m/Y') ?? '—' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted font-weight-bold pl-3">Giới tính</td>
                            <td>
                                @if ($user->gioi_tinh === 1) Nam
                                @elseif ($user->gioi_tinh === 0) Nữ
                                @elseif ($user->gioi_tinh === 2) Khác
                                @else —
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted font-weight-bold pl-3">Chức vụ</td>
                            <td>{{ $user->chucVu?->ten_chuc_vu ?? '—' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted font-weight-bold pl-3">Trạng thái</td>
                            <td>
                                @if ($user->trang_thai)
                                    <span class="badge badge-success px-2 py-1">Hoạt động</span>
                                @else
                                    <span class="badge badge-danger px-2 py-1">Đã khóa</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted font-weight-bold pl-3">Ngày tạo</td>
                            <td>{{ $user->ngay_tao?->format('d/m/Y H:i') ?? '—' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-receipt text-primary mr-2"></i>Tất cả đơn hàng</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th class="pl-3">Mã đơn hàng</th>
                                <th>Ngày đặt</th>
                                <th>Tổng tiền</th>
                                <th>Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($orders as $order)
                                <tr>
                                    <td class="pl-3">
                                        <a href="{{ route('admin.orders.show', $order->id) }}">{{ $order->ma_don_hang ?? '#'.$order->id }}</a>
                                    </td>
                                    <td>{{ $order->ngay_dat ? \Illuminate\Support\Carbon::parse($order->ngay_dat)->format('d/m/Y H:i') : '—' }}</td>
                                    <td>₫ {{ number_format((float) ($order->tong_tien ?? 0), 0, ',', '.') }}</td>
                                    <td><span class="badge badge-{{ $order->status_badge }}">{{ $order->status_label }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-4">Khách hàng chưa có đơn hàng.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="far fa-comment-dots text-info mr-2"></i>Bình luận / đánh giá của khách hàng</h3>
            </div>
            <div class="card-body">
                @forelse ($reviews as $review)
                    <div class="{{ $loop->last ? '' : 'border-bottom mb-3 pb-3' }}">
                        <div class="d-flex justify-content-between flex-wrap">
                            <strong>{{ $review->ten_san_pham ?? 'Sản phẩm đã xóa' }}</strong>
                            <span class="text-warning" aria-label="{{ $review->so_sao }} sao">
                                @for ($star = 0; $star < (int) $review->so_sao; $star++)
                                    <i class="fas fa-star"></i>
                                @endfor
                                <span class="text-muted small ml-1">{{ $review->so_sao ?? '—' }}/5</span>
                            </span>
                        </div>
                        <p class="mb-1 mt-2">{{ $review->noi_dung ?: 'Không có nội dung bình luận.' }}</p>
                        <small class="text-muted">
                            {{ $review->ngay_dang ? \Illuminate\Support\Carbon::parse($review->ngay_dang)->format('d/m/Y H:i') : 'Không rõ ngày đăng' }}
                            · Trạng thái: {{ $review->trang_thai ?? '—' }}
                        </small>
                    </div>
                @empty
                    <p class="text-muted text-center mb-0">Khách hàng chưa có bình luận hoặc đánh giá.</p>
                @endforelse
            </div>
        </div>

        <div class="d-flex justify-content-between">
            <a href="{{ route('admin.users.index') }}" class="btn btn-default">
                <i class="fas fa-arrow-left mr-1"></i>Quay lại danh sách
            </a>
            <div>
                @include('admin.users._deletion-rules')
                <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="d-inline"
                    onsubmit="return confirm('Bạn có chắc muốn xóa khách hàng này? Thao tác không thể hoàn tác.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger btn-sm">
                        <i class="far fa-trash-alt mr-1"></i>Xóa tài khoản
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
