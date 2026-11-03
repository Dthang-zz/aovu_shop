@if ($returnRequest->trang_thai === \App\Models\ReturnRequest::STATUS_INSPECTED
    && $returnRequest->loai_yeu_cau === \App\Models\ReturnRequest::TYPE_RETURN)
    <i class="fas fa-info-circle text-info mr-1"></i>
    Hàng đã kiểm tra. Tài chính xác nhận chuyển khoản hoàn tiền cho khách trong
    <a href="{{ route('admin.finance.refunds.index') }}">Ví hoàn tiền</a>.
@else
    <i class="fas fa-check-circle text-success mr-1"></i>Yêu cầu đã kết thúc, không thể chuyển trạng thái thêm.
@endif
