@extends('layouts.admin')

@section('title', 'Ví hoàn tiền')
@section('page-heading', 'Ví hoàn tiền')
@section('page-subheading', 'Quản lý số dư ví hoàn tiền của khách hàng, theo dõi giao dịch nạp – trừ.')
@section('breadcrumb', 'Ví hoàn tiền')

@section('styles')
<style>
    .wallet-stat-card {
        position: relative;
        overflow: hidden;
        border-radius: 12px;
        padding: 1.25rem 1.5rem;
        color: #fff;
        min-height: 110px;
    }

    .wallet-stat-card .stat-icon {
        position: absolute;
        right: 16px;
        top: 14px;
        font-size: 48px;
        opacity: .15;
    }

    .wallet-stat-card .stat-label {
        font-size: .78rem;
        opacity: .85;
        margin-bottom: .25rem;
    }

    .wallet-stat-card .stat-value {
        font-size: 1.65rem;
        font-weight: 700;
        line-height: 1.1;
    }

    .wallet-stat-card.bg-gradient-primary {
        background: linear-gradient(135deg, #5b6cff 0%, #7c4dff 100%);
    }

    .wallet-stat-card.bg-gradient-success {
        background: linear-gradient(135deg, #23945d 0%, #2ec27e 100%);
    }

    .wallet-stat-card.bg-gradient-warning {
        background: linear-gradient(135deg, #e9a319 0%, #f5c542 100%);
    }

    .wallet-stat-card.bg-gradient-info {
        background: linear-gradient(135deg, #287da2 0%, #43b3df 100%);
    }

    .wallet-avatar {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid #eef0f3;
    }

    .wallet-avatar-placeholder {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: linear-gradient(135deg, #5b6cff 0%, #7c4dff 100%);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: .85rem;
    }

    .balance-positive {
        color: #23945d;
        font-weight: 600;
    }

    .balance-zero {
        color: #8994a4;
    }

    .toggle-btn {
        transition: all .15s ease;
    }

    .toggle-btn:hover {
        transform: scale(1.05);
    }
</style>
@endsection

@section('content')
{{-- Stat Cards --}}
<div class="row">
    <div class="col-lg-3 col-sm-6 mb-3">
        <div class="wallet-stat-card bg-gradient-primary">
            <div class="stat-icon"><i class="fas fa-wallet"></i></div>
            <div class="stat-label">Tổng ví</div>
            <div class="stat-value">{{ number_format($totalCount) }}</div>
        </div>
    </div>
    <div class="col-lg-3 col-sm-6 mb-3">
        <div class="wallet-stat-card bg-gradient-success">
            <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
            <div class="stat-label">Đang hoạt động</div>
            <div class="stat-value">{{ number_format($activeCount) }}</div>
        </div>
    </div>
    <div class="col-lg-3 col-sm-6 mb-3">
        <div class="wallet-stat-card bg-gradient-info">
            <div class="stat-icon"><i class="fas fa-coins"></i></div>
            <div class="stat-label">Tổng số dư</div>
            <div class="stat-value">{{ number_format($totalBalance, 0, ',', '.') }} <small style="font-size:.65rem">₫</small></div>
        </div>
    </div>
    <div class="col-lg-3 col-sm-6 mb-3">
        <div class="wallet-stat-card bg-gradient-warning">
            <div class="stat-icon"><i class="fas fa-exchange-alt"></i></div>
            <div class="stat-label">Tổng giao dịch</div>
            <div class="stat-value">{{ number_format($totalTransactions) }}</div>
        </div>
    </div>
</div>

{{-- Flash messages --}}
@if (session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle mr-1"></i>{{ session('success') }}
    <button type="button" class="close" data-dismiss="alert" aria-label="Đóng"><span aria-hidden="true">&times;</span></button>
</div>
@endif
@if (session('error'))
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="fas fa-exclamation-circle mr-1"></i>{{ session('error') }}
    <button type="button" class="close" data-dismiss="alert" aria-label="Đóng"><span aria-hidden="true">&times;</span></button>
</div>
@endif

{{-- Main table --}}
<div class="card">
    <div class="card-header py-3">
        <div class="row align-items-center">
            <div class="col-md-4">
                <h3 class="card-title"><i class="fas fa-wallet text-primary mr-2"></i>Danh sách ví hoàn tiền</h3>
            </div>
            <div class="col-md-8 mt-3 mt-md-0">
                <form method="GET" action="{{ route('admin.wallets.index') }}" class="d-flex flex-wrap justify-content-md-end">
                    <div class="input-group input-group-sm mr-2 mb-1" style="max-width: 260px">
                        <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Tên, email, SĐT khách hàng..." aria-label="Tìm ví">
                        <div class="input-group-append"><button class="btn btn-default" type="submit" aria-label="Tìm"><i class="fas fa-search"></i></button></div>
                    </div>
                    <select name="status" class="form-control form-control-sm mr-2 mb-1" style="max-width: 155px" aria-label="Lọc trạng thái" onchange="this.form.requestSubmit()">
                        <option value="">Tất cả trạng thái</option>
                        <option value="1" @selected(request('status')==='1')>Hoạt động</option>
                        <option value="0" @selected(request('status')==='0')>Đã khóa</option>
                    </select>
                    <a href="{{ route('admin.wallets.index') }}" class="btn btn-default btn-sm mb-1" title="Xóa bộ lọc"><i class="fas fa-undo"></i></a>
                </form>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th class="pl-3">Khách hàng</th>
                        <th>Email</th>
                        <th>Số điện thoại</th>
                        <th class="text-right">Số dư</th>
                        <th>Trạng thái</th>
                        <th>Cập nhật</th>
                        <th class="text-right pr-3">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($wallets as $w)
                    <tr>
                        <td class="pl-3">
                            <div class="d-flex align-items-center">
                                @if ($w->account_avatar)
                                    <img src="{{ asset('storage/' . $w->account_avatar) }}" alt="" class="wallet-avatar mr-2">
                                @else
                                    <div class="wallet-avatar-placeholder mr-2">
                                        {{ mb_strtoupper(mb_substr($w->account_name ?? 'K', 0, 1)) }}
                                    </div>
                                @endif
                                <div>
                                    <div class="font-weight-bold text-dark">{{ $w->account_name ?: 'Khách hàng' }}</div>
                                    <small class="text-muted">Ví #{{ $w->id }}</small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="text-muted">{{ $w->account_email ?: '—' }}</span>
                        </td>
                        <td>{{ $w->account_phone ?: '—' }}</td>
                        <td class="text-right">
                            @if ($w->so_du > 0)
                                <span class="balance-positive">{{ number_format($w->so_du, 0, ',', '.') }} ₫</span>
                            @else
                                <span class="balance-zero">0 ₫</span>
                            @endif
                        </td>
                        <td>
                            @if ($w->trang_thai == 1)
                                <span class="badge badge-success px-2 py-1">Hoạt động</span>
                            @else
                                <span class="badge badge-secondary px-2 py-1">Đã khóa</span>
                            @endif
                        </td>
                        <td>
                            <span class="text-muted small">{{ $w->ngay_cap_nhat ? \Illuminate\Support\Carbon::parse($w->ngay_cap_nhat)->format('d/m/Y H:i') : '—' }}</span>
                        </td>
                        <td class="text-right pr-3 table-actions">
                            <a href="{{ route('admin.wallets.show', $w->id) }}" class="btn btn-default btn-sm text-primary" title="Xem chi tiết">
                                <i class="far fa-eye"></i>
                            </a>
                            <form method="POST" action="{{ route('admin.wallets.toggle', $w->id) }}" class="d-inline" onsubmit="return confirm('Bạn chắc muốn {{ $w->trang_thai == 1 ? 'khóa' : 'mở khóa' }} ví này?')">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-default btn-sm toggle-btn {{ $w->trang_thai == 1 ? 'text-warning' : 'text-success' }}" title="{{ $w->trang_thai == 1 ? 'Khóa ví' : 'Mở khóa ví' }}">
                                    <i class="fas {{ $w->trang_thai == 1 ? 'fa-lock' : 'fa-unlock' }}"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5">
                            <i class="fas fa-wallet text-muted mb-2" style="font-size: 2rem"></i>
                            <div class="font-weight-bold">Chưa có ví hoàn tiền nào</div>
                            <div class="text-muted small">Ví sẽ được tạo tự động khi có hoàn tiền đầu tiên cho khách hàng.</div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if ($wallets->hasPages())
    <div class="card-footer bg-white d-flex justify-content-between align-items-center">
        <span class="text-muted small">Hiển thị {{ $wallets->firstItem() }}–{{ $wallets->lastItem() }} trên {{ $wallets->total() }} ví</span>
        {{ $wallets->links() }}
    </div>
    @endif
</div>

<div class="text-muted small mt-2">
    <i class="fas fa-info-circle mr-1"></i>Ví hoàn tiền lưu trữ số dư khuyến mãi / hoàn tiền cho khách hàng. Tiền trong ví có thể dùng để trừ khi đặt đơn hàng mới.
</div>
@endsection
