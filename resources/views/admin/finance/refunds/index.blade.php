@extends('layouts.admin')

@section('title', 'Ví hoàn tiền')
@section('page-heading', 'Ví hoàn tiền AOVU')
@section('page-subheading', 'Theo dõi yêu cầu hoàn tiền và dữ liệu đối soát hiện có.')
@section('breadcrumb')
    <span>Quản lý tài chính</span>
@endsection

@section('content')
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle mr-1"></i>{{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Đóng"><span aria-hidden="true">&times;</span></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <ul class="mb-0 pl-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-4 col-md-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ number_format($pendingCount, 0, ',', '.') }}</h3>
                    <p>Đã kiểm tra, chờ chuyển khoản</p>
                </div>
                <div class="icon"><i class="fas fa-hourglass-half"></i></div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3 class="text-lg">{{ number_format($refundedThisMonth, 0, ',', '.') }} ₫</h3>
                    <p>Đã ghi nhận trong tháng</p>
                </div>
                <div class="icon"><i class="fas fa-receipt"></i></div>
            </div>
        </div>
        <div class="col-lg-4 col-md-12">
            <div class="small-box bg-light">
                <div class="inner">
                    <h3>{{ number_format($inProgressCount, 0, ',', '.') }}</h3>
                    <p>Phiếu trả hàng đang xử lý</p>
                </div>
                <div class="icon"><i class="fas fa-truck-loading"></i></div>
                <a href="#refunds-in-progress" class="small-box-footer text-dark">Xem tiến độ <i class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-clipboard-list text-warning mr-2"></i>Yêu cầu chờ chuyển khoản</h3>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Phiếu / đơn hàng</th>
                        <th>Khách hàng</th>
                        <th>Sản phẩm</th>
                        <th>Giá trị hàng trả</th>
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
                                {{ number_format($refund->refundable_amount, 0, ',', '.') }} ₫
                                <div class="small text-muted">{{ $refund->so_luong }} sản phẩm, đã trừ voucher phân bổ</div>
                            </td>
                            <td><span class="badge badge-warning">Chờ chuyển khoản thủ công</span></td>
                            <td class="text-right text-nowrap">
                                <a class="btn btn-sm btn-outline-secondary"
                                    href="{{ route('admin.returns.show', $refund->id) }}">Xem phiếu</a>
                                <button type="button" class="btn btn-sm btn-primary" data-toggle="modal"
                                    data-target="#manual-refund-{{ $refund->id }}">
                                    <i class="fas fa-check mr-1"></i>Đã chuyển
                                </button>
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

    <div class="card" id="refunds-in-progress">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-truck-loading text-info mr-2"></i>Phiếu trả hàng đang xử lý</h3>
            <div class="small text-muted mt-1 clearfix" style="clear: both">
                Phiếu chỉ chuyển sang chờ chuyển khoản khi CSKH cập nhật trạng thái <strong>Đã kiểm tra hàng</strong>.
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Phiếu / đơn hàng</th>
                        <th>Khách hàng</th>
                        <th>Sản phẩm</th>
                        <th>Dự kiến hoàn</th>
                        <th>Trạng thái</th>
                        <th>Bước còn thiếu</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($inProgressRefunds as $refund)
                        <tr>
                            <td>
                                <a href="{{ route('admin.returns.show', $refund->id) }}">
                                    #DT-{{ str_pad((string) $refund->id, 6, '0', STR_PAD_LEFT) }}
                                </a>
                                <div class="small text-muted">{{ $refund->order_number ?: 'Đơn #—' }}</div>
                            </td>
                            <td>{{ $refund->customer_name ?: 'Khách hàng' }}</td>
                            <td>{{ $refund->product_name ?: 'Sản phẩm trong đơn hàng' }}</td>
                            <td>{{ number_format($refund->refundable_amount, 0, ',', '.') }} ₫</td>
                            <td><span class="badge badge-{{ $refund->status_badge }}">{{ $refund->status_label }}</span></td>
                            <td class="small">{{ $refund->next_step }}</td>
                            <td class="text-right text-nowrap">
                                <a class="btn btn-sm btn-outline-primary"
                                    href="{{ route('admin.returns.edit', $refund->id) }}">Xử lý phiếu</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                Không có phiếu trả hàng nào đang chờ xử lý.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($inProgressRefunds->hasPages())
            <div class="card-footer">{{ $inProgressRefunds->links() }}</div>
        @endif
    </div>

    @foreach ($pendingRefunds as $refund)
        <div class="modal fade" id="manual-refund-{{ $refund->id }}" tabindex="-1" role="dialog"
            aria-labelledby="manual-refund-title-{{ $refund->id }}" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <form method="POST" action="{{ route('admin.finance.refunds.confirm-manual-payment', $refund->id) }}" class="modal-content">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="manual-refund-title-{{ $refund->id }}">Xác nhận đã chuyển khoản</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Đóng"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-3">
                            Xác nhận bạn đã chuyển khoản ngoài hệ thống cho <strong>{{ $refund->customer_name ?: 'khách hàng' }}</strong>
                            của phiếu <strong>#DT-{{ str_pad((string) $refund->id, 6, '0', STR_PAD_LEFT) }}</strong>.
                        </p>
                        <div class="form-group">
                            <label for="refund-amount-{{ $refund->id }}">Số tiền đã chuyển <span class="text-danger">*</span></label>
                            <input id="refund-amount-{{ $refund->id }}" name="refund_amount" type="number" min="1" max="{{ $refund->refundable_amount }}"
                                value="{{ old('refund_amount', $refund->refundable_amount) }}" required class="form-control" inputmode="numeric">
                            <small class="form-text text-muted">
                                Tối đa {{ number_format($refund->refundable_amount, 0, ',', '.') }} ₫ (giá trị khách đã thanh toán cho hàng trả).
                            </small>
                        </div>
                        <div class="form-group">
                            <label for="refund-reference-{{ $refund->id }}">Mã giao dịch / ghi chú</label>
                            <input id="refund-reference-{{ $refund->id }}" name="refund_reference" type="text" maxlength="100"
                                value="{{ old('refund_reference') }}" class="form-control" placeholder="Ví dụ: MB-20261103-001">
                        </div>
                        <div class="custom-control custom-checkbox">
                            <input id="refund-confirmed-{{ $refund->id }}" name="confirmed" value="1" type="checkbox" required class="custom-control-input">
                            <label class="custom-control-label" for="refund-confirmed-{{ $refund->id }}">Tôi xác nhận đã chuyển khoản cho khách hàng.</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-check mr-1"></i>Xác nhận đã chuyển</button>
                    </div>
                </form>
            </div>
        </div>
    @endforeach

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
                            <td>
                                <span class="badge badge-{{ $refund->refund_method === 'manual_bank_transfer' ? 'success' : 'secondary' }}">
                                    {{ $refund->refund_method === 'manual_bank_transfer' ? 'Đã xác nhận thủ công' : 'Đã ghi nhận' }}
                                </span>
                            </td>
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
@endsection
