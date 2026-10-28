@extends('layouts.admin')

@section('title', 'Ví hoàn tiền')
@section('page-heading', 'Ví hoàn tiền AOVU')
@section('page-subheading', 'Theo dõi yêu cầu hoàn tiền và dữ liệu đối soát hiện có.')
@section('breadcrumb')
    <span>Quản lý tài chính</span>
@endsection

@section('content')
    <div class="alert alert-warning" role="alert">
        <h5><i class="fas fa-unlink mr-1"></i>Chưa kết nối nhà cung cấp thanh toán</h5>
        <p class="mb-0">
            Ví hiện ở chế độ chỉ đọc. Chưa có dữ liệu số dư được đối soát; hệ thống không thể chuyển tiền hoặc xác nhận
            giao dịch thành công.
        </p>
    </div>

    <div class="row">
        <div class="col-lg-3 col-md-6">
            <div class="small-box bg-secondary">
                <div class="inner">
                    <h3 class="text-lg">Chưa đối soát</h3>
                    <p>Số dư khả dụng</p>
                </div>
                <div class="icon"><i class="fas fa-wallet"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ number_format($pendingCount, 0, ',', '.') }}</h3>
                    <p>Đã kiểm tra, chờ xét duyệt hoàn</p>
                </div>
                <div class="icon"><i class="fas fa-hourglass-half"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3 class="text-lg">{{ number_format($refundedThisMonth, 0, ',', '.') }} ₫</h3>
                    <p>Đã ghi nhận trong tháng</p>
                </div>
                <div class="icon"><i class="fas fa-receipt"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="small-box bg-light">
                <div class="inner">
                    <h3 class="text-lg">Chưa có dữ liệu</h3>
                    <p>Giao dịch thất bại</p>
                </div>
                <div class="icon"><i class="fas fa-exclamation-circle"></i></div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-clipboard-list text-warning mr-2"></i>Yêu cầu chờ xét duyệt hoàn tiền</h3>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Phiếu / đơn hàng</th>
                        <th>Khách hàng</th>
                        <th>Sản phẩm</th>
                        <th>Số tiền</th>
                        <th>Trạng thái</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pendingRefunds as $refund)
                        <tr>
                            <td>
                                <a href="{{ route('admin.returns.show', $refund->id) }}">
                                    #DT-{{ str_pad((string) $refund->id, 6, '0', STR_PAD_LEFT) }}
                                </a>
                                <div class="small text-muted">{{ $refund->order_number ?: 'Đơn #—' }}</div>
                            </td>
                            <td>{{ $refund->customer_name ?: 'Khách hàng' }}</td>
                            <td>{{ $refund->product_name ?: 'Sản phẩm trong đơn hàng' }}</td>
                            <td>
                                {{ $refund->refund_amount !== null
                                    ? number_format($refund->refund_amount, 0, ',', '.').' ₫'
                                    : 'Chưa chốt' }}
                            </td>
                            <td><span class="badge badge-warning">Chờ xét duyệt tài chính</span></td>
                            <td>
                                <a class="btn btn-sm btn-outline-secondary"
                                    href="{{ route('admin.returns.show', $refund->id) }}">Xem phiếu</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                Chưa có phiếu trả hàng đã kiểm tra đang chờ xét duyệt hoàn tiền.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($pendingRefunds->hasPages())
            <div class="card-footer">{{ $pendingRefunds->links() }}</div>
        @endif
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-history text-primary mr-2"></i>Lịch sử ghi nhận hoàn tiền</h3>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Thời điểm</th>
                        <th>Phiếu / đơn hàng</th>
                        <th>Người nhận</th>
                        <th>Số tiền ghi nhận</th>
                        <th>Mã tham chiếu</th>
                        <th>Kết quả</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($refundHistory as $refund)
                        <tr>
                            <td>{{ \Illuminate\Support\Carbon::parse($refund->refunded_at)->format('d/m/Y H:i') }}</td>
                            <td>
                                <a href="{{ route('admin.returns.show', $refund->id) }}">
                                    #DT-{{ str_pad((string) $refund->id, 6, '0', STR_PAD_LEFT) }}
                                </a>
                                <div class="small text-muted">{{ $refund->order_number ?: 'Đơn #—' }}</div>
                            </td>
                            <td>{{ $refund->customer_name ?: 'Khách hàng' }}</td>
                            <td>{{ $refund->refund_amount !== null ? number_format($refund->refund_amount, 0, ',', '.').' ₫' : '—' }}</td>
                            <td>{{ $refund->refund_reference ?: '—' }}</td>
                            <td><span class="badge badge-secondary">Đã ghi nhận nội bộ</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Chưa có dữ liệu hoàn tiền được ghi nhận.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($refundHistory->hasPages())
            <div class="card-footer">{{ $refundHistory->links() }}</div>
        @endif
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-exchange-alt text-info mr-2"></i>Sổ giao dịch nguồn tiền</h3>
                </div>
                <div class="card-body">
                    <p class="mb-2">Chưa có sổ giao dịch đã đối soát để tính số dư khả dụng.</p>
                    <p class="text-muted small mb-0">
                        Các khoản ghi nhận lịch sử từ quy trình đổi/trả không thay thế dữ liệu xác nhận từ ngân hàng hoặc
                        quy trình đối soát.
                    </p>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-plug text-secondary mr-2"></i>Cấu hình chuyển tiền</h3>
                </div>
                <div class="card-body">
                    <span class="badge badge-secondary mb-2">Chưa kết nối</span>
                    <p class="mb-0">
                        Chưa cấu hình nhà cung cấp. Các thao tác ghi sổ, chuyển tiền và xác nhận giao dịch đang bị khóa
                        cho đến khi có tích hợp và phân quyền tài chính.
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection
