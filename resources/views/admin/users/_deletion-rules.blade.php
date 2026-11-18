<button type="button" class="btn btn-default btn-sm" data-toggle="modal" data-target="#customerDeletionRules"
    title="Quy tắc xóa tài khoản" aria-label="Xem quy tắc xóa tài khoản">
    <i class="fas fa-book-open mr-1"></i>Quy tắc xóa tài khoản
</button>

<div class="modal fade" id="customerDeletionRules" tabindex="-1" role="dialog" aria-labelledby="customerDeletionRulesTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="customerDeletionRulesTitle">
                    <i class="fas fa-book-open text-primary mr-2"></i>Quy tắc xóa tài khoản khách hàng
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Đóng">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead>
                            <tr>
                                <th>Trạng thái dữ liệu</th>
                                <th>Được xóa?</th>
                                <th>Cách xử lý</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Chưa có đơn hàng</td>
                                <td><span class="badge badge-success">Có</span></td>
                                <td>Cho phép xóa tài khoản.</td>
                            </tr>
                            <tr>
                                <td>Có đơn chờ xác nhận</td>
                                <td><span class="badge badge-danger">Không</span></td>
                                <td>Xử lý đơn trước hoặc khóa tài khoản.</td>
                            </tr>
                            <tr>
                                <td>Có đơn đang xử lý / đang giao</td>
                                <td><span class="badge badge-danger">Không</span></td>
                                <td>Giữ tài khoản để tiếp tục xử lý đơn.</td>
                            </tr>
                            <tr>
                                <td>Có đơn đã hoàn thành</td>
                                <td><span class="badge badge-danger">Không</span></td>
                                <td>Giữ lịch sử đơn hàng; có thể khóa tài khoản.</td>
                            </tr>
                            <tr>
                                <td>Có đơn đã hủy</td>
                                <td><span class="badge badge-danger">Không</span></td>
                                <td>Có thể khóa tài khoản, không xóa.</td>
                            </tr>
                            <tr>
                                <td>Có yêu cầu đổi/trả đang xử lý</td>
                                <td><span class="badge badge-danger">Không</span></td>
                                <td>Giữ tài khoản để xử lý yêu cầu hoặc khóa tài khoản.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>
