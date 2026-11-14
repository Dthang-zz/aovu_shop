@extends('layouts.storefront')

@section('title', 'Trung tâm Chăm sóc khách hàng')

@section('content')
    <!-- Banner Hero CSKH -->
    <div class="card storefront-card text-white mb-4 overflow-hidden" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);">
        <div class="card-body p-4 p-md-5">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <span class="badge badge-primary px-3 py-2 mb-3 font-weight-normal"><i class="fas fa-heart mr-1"></i>AOVU Care Center</span>
                    <h1 class="h2 font-weight-bold mb-3">Trung tâm Chăm sóc & Hỗ trợ Khách hàng</h1>
                    <p class="lead mb-4 text-light">Chúng tôi luôn sẵn sàng lắng nghe, tư vấn tận tâm và đồng hành cùng bạn trong suốt trải nghiệm mua sắm tại AOVU Shop.</p>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="#tra-cuu" class="btn btn-primary mr-2 mb-2"><i class="fas fa-search mr-1"></i>Tra cứu đơn hàng</a>
                        <a href="#lien-he" class="btn btn-outline-light mr-2 mb-2"><i class="fas fa-envelope-open-text mr-1"></i>Gửi yêu cầu CSKH</a>
                        <button type="button" class="btn btn-success mb-2" onclick="var b=document.getElementById('support-chat-toggle')||document.querySelector('.support-chat-btn'); if(b) b.click();">
                            <i class="fas fa-comments mr-1"></i>Chat trực tuyến
                        </button>
                    </div>
                </div>
                <div class="col-lg-4 d-none d-lg-block text-center">
                    <i class="fas fa-headset fa-8x text-primary" style="opacity: 0.85;"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- 4 Kênh hỗ trợ chính -->
    <div class="row mb-5">
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card storefront-card h-100 text-center p-3">
                <div class="card-body">
                    <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 56px; height: 56px;">
                        <i class="fas fa-phone-alt fa-lg"></i>
                    </div>
                    <h6 class="font-weight-bold mb-1">Hotline CSKH</h6>
                    <p class="text-primary font-weight-bold mb-1">1900 6868</p>
                    <small class="text-muted">8:00 - 22:00 (Cả tuần & Ngày lễ)</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card storefront-card h-100 text-center p-3">
                <div class="card-body">
                    <div class="bg-success text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 56px; height: 56px;">
                        <i class="fas fa-comment-dots fa-lg"></i>
                    </div>
                    <h6 class="font-weight-bold mb-1">Chat trực tuyến</h6>
                    <p class="text-success font-weight-bold mb-1">Hỗ trợ tức thì</p>
                    <small class="text-muted">Nhân viên tư vấn túc trực 24/7</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card storefront-card h-100 text-center p-3">
                <div class="card-body">
                    <div class="bg-info text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 56px; height: 56px;">
                        <i class="fas fa-envelope fa-lg"></i>
                    </div>
                    <h6 class="font-weight-bold mb-1">Hộp thư CSKH</h6>
                    <p class="text-info font-weight-bold mb-1 text-truncate">cskh@aovushop.vn</p>
                    <small class="text-muted">Phản hồi trong vòng 2 giờ</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card storefront-card h-100 text-center p-3">
                <div class="card-body">
                    <div class="bg-warning text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 56px; height: 56px;">
                        <i class="fas fa-store-alt fa-lg"></i>
                    </div>
                    <h6 class="font-weight-bold mb-1">Showroom chính</h6>
                    <p class="text-dark font-weight-bold mb-1">123 Cầu Giấy, HN</p>
                    <small class="text-muted">Tiếp nhận đổi trả & bảo hành</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Section: Tra cứu đơn hàng -->
    <section id="tra-cuu" class="mb-5 pt-2">
        <div class="card storefront-card border-left border-primary" style="border-left-width: 6px !important;">
            <div class="card-header bg-white py-3">
                <div class="d-flex align-items-center">
                    <i class="fas fa-truck text-primary mr-2 fa-lg"></i>
                    <h2 class="h5 font-weight-bold mb-0">Tra cứu tình trạng đơn hàng</h2>
                </div>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">Nhập mã đơn hàng (ví dụ: <span class="badge badge-light">DH-202611...</span>) được gửi trong email hoặc màn hình xác nhận để kiểm tra trạng thái giao hàng và chi tiết sản phẩm.</p>
                <form action="{{ route('cskh.index') }}" method="GET" class="form-row align-items-center">
                    <div class="col-md-5 mb-2">
                        <label class="sr-only" for="ma_don_hang">Mã đơn hàng</label>
                        <div class="input-group">
                            <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-barcode"></i></span></div>
                            <input type="text" id="ma_don_hang" name="ma_don_hang" class="form-control" placeholder="Nhập mã đơn hàng (*)" value="{{ request('ma_don_hang') }}" required>
                        </div>
                    </div>
                    <div class="col-md-4 mb-2">
                        <label class="sr-only" for="so_dien_thoai">Số điện thoại</label>
                        <div class="input-group">
                            <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-phone"></i></span></div>
                            <input type="text" id="so_dien_thoai" name="so_dien_thoai" class="form-control" placeholder="Số điện thoại nhận hàng (tùy chọn)" value="{{ request('so_dien_thoai') }}">
                        </div>
                    </div>
                    <div class="col-md-3 mb-2">
                        <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-search mr-1"></i>Tra cứu ngay</button>
                    </div>
                </form>

                @if ($searched)
                    <div class="mt-4 pt-3 border-top">
                        @if ($trackedOrder)
                            <div class="card border bg-light shadow-none mb-0">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center flex-wrap mb-3 border-bottom pb-2">
                                        <div>
                                            <h5 class="font-weight-bold text-primary mb-0">Đơn hàng #{{ $trackedOrder->ma_don_hang }}</h5>
                                            <small class="text-muted">Ngày đặt: {{ $trackedOrder->ngay_dat ? \Carbon\Carbon::parse($trackedOrder->ngay_dat)->format('d/m/Y H:i') : 'Đang cập nhật' }}</small>
                                        </div>
                                        <div class="mt-2 mt-sm-0">
                                            @php
                                                $statusLabels = [
                                                    0 => ['badge' => 'warning', 'text' => 'Chờ xác nhận'],
                                                    1 => ['badge' => 'info', 'text' => 'Đang chuẩn bị hàng'],
                                                    2 => ['badge' => 'primary', 'text' => 'Đang giao hàng'],
                                                    3 => ['badge' => 'success', 'text' => 'Giao thành công'],
                                                    4 => ['badge' => 'danger', 'text' => 'Đã hủy'],
                                                ];
                                                $status = $statusLabels[(int) $trackedOrder->trang_thai] ?? ['badge' => 'secondary', 'text' => 'Đang xử lý'];
                                            @endphp
                                            <span class="badge badge-{{ $status['badge'] }} px-3 py-2 font-weight-normal mr-2">
                                                Trạng thái: {{ $status['text'] }}
                                            </span>
                                            <span class="badge badge-{{ $trackedOrder->trang_thai_thanh_toan ? 'success' : 'secondary' }} px-3 py-2 font-weight-normal">
                                                {{ $trackedOrder->trang_thai_thanh_toan ? 'Đã thanh toán' : 'Chưa thanh toán (COD)' }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-md-6 mb-2">
                                            <small class="text-muted d-block">Người nhận hàng:</small>
                                            <strong>{{ $trackedOrder->ten_nguoi_nhan }}</strong> ({{ $trackedOrder->sdt_nguoi_nhan }})
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <small class="text-muted d-block">Địa chỉ nhận hàng:</small>
                                            <span>{{ $trackedOrder->dia_chi_nguoi_nhan }}</span>
                                        </div>
                                    </div>

                                    <!-- Danh sách sản phẩm -->
                                    <h6 class="font-weight-bold mb-2">Chi tiết sản phẩm trong đơn:</h6>
                                    <div class="table-responsive bg-white rounded border mb-3">
                                        <table class="table table-sm mb-0">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>Sản phẩm</th>
                                                    <th class="text-center">Số lượng</th>
                                                    <th class="text-right">Đơn giá</th>
                                                    <th class="text-right">Thành tiền</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse ($trackedItems as $item)
                                                    <tr>
                                                        <td>{{ $item->ten_san_pham }}</td>
                                                        <td class="text-center">{{ $item->so_luong }}</td>
                                                        <td class="text-right">{{ number_format($item->don_gia, 0, ',', '.') }} ₫</td>
                                                        <td class="text-right font-weight-bold">{{ number_format($item->thanh_tien, 0, ',', '.') }} ₫</td>
                                                    </tr>
                                                @empty
                                                    <tr><td colspan="4" class="text-center text-muted py-2">Chưa có chi tiết sản phẩm.</td></tr>
                                                @endforelse
                                            </tbody>
                                            <tfoot>
                                                <tr>
                                                    <th colspan="3" class="text-right">Tổng thanh toán:</th>
                                                    <th class="text-right text-danger h6 mb-0">{{ number_format($trackedOrder->tong_tien, 0, ',', '.') }} ₫</th>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>

                                    @if ($trackedReturnRequests->isNotEmpty())
                                        <div class="alert alert-info mb-0">
                                            <h6 class="font-weight-bold mb-1"><i class="fas fa-undo-alt mr-1"></i>Yêu cầu đổi / trả cho đơn hàng này:</h6>
                                            @foreach ($trackedReturnRequests as $rr)
                                                <div class="small">
                                                    - Mã yêu cầu #{{ $rr->id }} | Loại: <strong>{{ method_exists($rr, 'typeLabel') ? $rr->typeLabel() : 'Đổi trả' }}</strong> | Trạng thái: <strong>{{ method_exists($rr, 'statusLabel') ? $rr->statusLabel() : 'Đang xử lý' }}</strong>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @else
                            <div class="alert alert-warning mb-0">
                                <i class="fas fa-exclamation-circle mr-2"></i>Không tìm thấy thông tin đơn hàng với thông tin đã nhập. Vui lòng kiểm tra lại mã đơn hàng hoặc liên hệ CSKH để được hỗ trợ tra cứu.
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </section>

    <!-- Section: Gửi yêu cầu hỗ trợ / Phản hồi ý kiến -->
    <div class="row mb-5">
        <div class="col-lg-6 mb-4" id="lien-he">
            <div class="card storefront-card h-100">
                <div class="card-header bg-white py-3">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-paper-plane text-success mr-2 fa-lg"></i>
                        <h2 class="h5 font-weight-bold mb-0">Gửi yêu cầu hỗ trợ CSKH</h2>
                    </div>
                </div>
                <div class="card-body">
                    <p class="text-muted small">Bạn có thắc mắc về sản phẩm, chính sách giao hàng hoặc cần hỗ trợ đổi trả? Hãy gửi thông tin, tư vấn viên của chúng tôi sẽ liên hệ lại ngay.</p>
                    <form action="{{ route('cskh.inquiry.store') }}" method="POST">
                        @csrf
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="ho_ten">Họ và tên <span class="text-danger">*</span></label>
                                <input type="text" id="ho_ten" name="ho_ten" class="form-control" value="{{ old('ho_ten', $currentCustomer?->ho_ten) }}" required maxlength="100" placeholder="Nguyễn Văn A">
                            </div>
                            <div class="form-group col-md-6">
                                <label for="so_dien_thoai">Số điện thoại <span class="text-danger">*</span></label>
                                <input type="text" id="so_dien_thoai" name="so_dien_thoai" class="form-control" value="{{ old('so_dien_thoai', $currentCustomer?->so_dien_thoai) }}" required maxlength="15" placeholder="0901234567">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="email">Email liên hệ <span class="text-danger">*</span></label>
                            <input type="email" id="email" name="email" class="form-control" value="{{ old('email', $currentCustomer?->email) }}" required maxlength="255" placeholder="email@example.com">
                        </div>
                        <div class="form-group">
                            <label for="chu_de">Chủ đề cần hỗ trợ <span class="text-danger">*</span></label>
                            <select id="chu_de" name="chu_de" class="form-control" required>
                                <option value="">-- Chọn chủ đề --</option>
                                <option value="Tư vấn chọn size & sản phẩm" @selected(old('chu_de') == 'Tư vấn chọn size & sản phẩm')>Tư vấn chọn size & sản phẩm</option>
                                <option value="Kiểm tra tiến độ đơn hàng" @selected(old('chu_de') == 'Kiểm tra tiến độ đơn hàng')>Kiểm tra tiến độ đơn hàng</option>
                                <option value="Yêu cầu đổi / trả hàng" @selected(old('chu_de') == 'Yêu cầu đổi / trả hàng')>Yêu cầu đổi / trả hàng</option>
                                <option value="Góp ý & phản ánh chất lượng" @selected(old('chu_de') == 'Góp ý & phản ánh chất lượng')>Góp ý & phản ánh chất lượng</option>
                                <option value="Khác" @selected(old('chu_de') == 'Khác')>Khác</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="noi_dung">Nội dung chi tiết <span class="text-danger">*</span></label>
                            <textarea id="noi_dung" name="noi_dung" rows="4" class="form-control" required maxlength="2000" placeholder="Vui lòng mô tả chi tiết yêu cầu của bạn...">{{ old('noi_dung') }}</textarea>
                        </div>
                        <button type="submit" class="btn btn-success btn-block py-2">
                            <i class="fas fa-paper-plane mr-2"></i>Gửi yêu cầu CSKH
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Section: Bảng hướng dẫn chọn size -->
        <div class="col-lg-6 mb-4" id="size-guide">
            <div class="card storefront-card h-100">
                <div class="card-header bg-white py-3">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-ruler-combined text-info mr-2 fa-lg"></i>
                        <h2 class="h5 font-weight-bold mb-0">Bảng quy chuẩn chọn size AOVU</h2>
                    </div>
                </div>
                <div class="card-body">
                    <p class="text-muted small">Kích cỡ trang phục được thiết kế phù hợp với vóc dáng người Việt Nam. Nếu phân vân giữa 2 size, chúng tôi khuyên bạn nên chọn size lớn hơn.</p>
                    <ul class="nav nav-pills mb-3" id="sizeTab" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active py-1 px-3" id="ao-nam-tab" data-toggle="pill" href="#ao-nam" role="tab">Áo Nam</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link py-1 px-3" id="ao-nu-tab" data-toggle="pill" href="#ao-nu" role="tab">Áo Nữ</a>
                        </li>
                    </ul>
                    <div class="tab-content" id="sizeTabContent">
                        <div class="tab-pane fade show active" id="ao-nam" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm text-center mb-0 small">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Size</th>
                                            <th>Chiều cao (cm)</th>
                                            <th>Cân nặng (kg)</th>
                                            <th>Vòng ngực (cm)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr><td><strong>S</strong></td><td>1m55 - 1m64</td><td>48 - 56 kg</td><td>86 - 90</td></tr>
                                        <tr><td><strong>M</strong></td><td>1m65 - 1m70</td><td>57 - 65 kg</td><td>90 - 94</td></tr>
                                        <tr><td><strong>L</strong></td><td>1m71 - 1m76</td><td>66 - 73 kg</td><td>94 - 98</td></tr>
                                        <tr><td><strong>XL</strong></td><td>1m77 - 1m82</td><td>74 - 82 kg</td><td>98 - 104</td></tr>
                                        <tr><td><strong>XXL</strong></td><td>1m80 - 1m88</td><td>83 - 92 kg</td><td>104 - 110</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="ao-nu" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm text-center mb-0 small">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Size</th>
                                            <th>Chiều cao (cm)</th>
                                            <th>Cân nặng (kg)</th>
                                            <th>Vòng ngực (cm)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr><td><strong>S</strong></td><td>1m50 - 1m56</td><td>40 - 47 kg</td><td>80 - 84</td></tr>
                                        <tr><td><strong>M</strong></td><td>1m57 - 1m62</td><td>48 - 53 kg</td><td>84 - 88</td></tr>
                                        <tr><td><strong>L</strong></td><td>1m63 - 1m68</td><td>54 - 60 kg</td><td>88 - 92</td></tr>
                                        <tr><td><strong>XL</strong></td><td>1m65 - 1m72</td><td>61 - 68 kg</td><td>92 - 96</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="alert alert-light border mt-3 mb-0 small">
                        <i class="fas fa-lightbulb text-warning mr-1"></i>Bạn vẫn chưa chắc chắn? Hãy bấm nút <strong>Chat trực tuyến</strong> ở góc màn hình, nhân viên tư vấn sẽ chọn size chuẩn xác cho bạn trong 30 giây!
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Section: Câu hỏi thường gặp (FAQ) -->
    <section id="faq" class="mb-4">
        <div class="card storefront-card">
            <div class="card-header bg-white py-3">
                <div class="d-flex align-items-center">
                    <i class="fas fa-question-circle text-warning mr-2 fa-lg"></i>
                    <h2 class="h5 font-weight-bold mb-0">Câu hỏi thường gặp (FAQ)</h2>
                </div>
            </div>
            <div class="card-body">
                <div class="accordion" id="faqAccordion">
                    <div class="card border mb-2 shadow-none">
                        <div class="card-header bg-white p-0" id="faq1">
                            <h3 class="mb-0">
                                <button class="btn btn-link btn-block text-left font-weight-bold text-dark py-3 px-4 d-flex justify-content-between align-items-center" type="button" data-toggle="collapse" data-target="#collapse1" aria-expanded="true" aria-controls="collapse1">
                                    <span>1. Chính sách đổi / trả sản phẩm của AOVU Shop như thế nào?</span>
                                    <i class="fas fa-chevron-down small text-muted"></i>
                                </button>
                            </h3>
                        </div>
                        <div id="collapse1" class="collapse show" aria-labelledby="faq1" data-parent="#faqAccordion">
                            <div class="card-body text-muted small pt-0">
                                AOVU Shop hỗ trợ đổi / trả hàng trong vòng <strong>07 ngày</strong> kể từ ngày bạn nhận được sản phẩm. Sản phẩm đổi trả cần giữ nguyên tem mác, chưa qua giặt là hoặc sử dụng. Chúng tôi hỗ trợ đổi size, đổi màu hoặc hoàn tiền nếu sản phẩm bị lỗi sản xuất.
                            </div>
                        </div>
                    </div>

                    <div class="card border mb-2 shadow-none">
                        <div class="card-header bg-white p-0" id="faq2">
                            <h3 class="mb-0">
                                <button class="btn btn-link btn-block text-left font-weight-bold text-dark py-3 px-4 d-flex justify-content-between align-items-center collapsed" type="button" data-toggle="collapse" data-target="#collapse2" aria-expanded="false" aria-controls="collapse2">
                                    <span>2. Thời gian giao hàng và phí vận chuyển mất bao lâu?</span>
                                    <i class="fas fa-chevron-down small text-muted"></i>
                                </button>
                            </h3>
                        </div>
                        <div id="collapse2" class="collapse" aria-labelledby="faq2" data-parent="#faqAccordion">
                            <div class="card-body text-muted small pt-0">
                                - Khu vực nội thành Hà Nội & TP. Hồ Chí Minh: 1 - 2 ngày làm việc.<br>
                                - Các tỉnh thành khác: 2 - 4 ngày làm việc.<br>
                                - <strong>Miễn phí vận chuyển toàn quốc</strong> cho tất cả đơn hàng nhân dịp ra mắt hệ thống mới!
                            </div>
                        </div>
                    </div>

                    <div class="card border mb-2 shadow-none">
                        <div class="card-header bg-white p-0" id="faq3">
                            <h3 class="mb-0">
                                <button class="btn btn-link btn-block text-left font-weight-bold text-dark py-3 px-4 d-flex justify-content-between align-items-center collapsed" type="button" data-toggle="collapse" data-target="#collapse3" aria-expanded="false" aria-controls="collapse3">
                                    <span>3. Tôi có được kiểm tra hàng trước khi thanh toán (đồng kiểm) không?</span>
                                    <i class="fas fa-chevron-down small text-muted"></i>
                                </button>
                            </h3>
                        </div>
                        <div id="collapse3" class="collapse" aria-labelledby="faq3" data-parent="#faqAccordion">
                            <div class="card-body text-muted small pt-0">
                                Hoàn toàn có! Bạn có quyền mở hộp kiểm tra đúng mẫu mã, màu sắc, size trước khi thanh toán tiền cho nhân viên giao hàng (COD).
                            </div>
                        </div>
                    </div>

                    <div class="card border mb-0 shadow-none">
                        <div class="card-header bg-white p-0" id="faq4">
                            <h3 class="mb-0">
                                <button class="btn btn-link btn-block text-left font-weight-bold text-dark py-3 px-4 d-flex justify-content-between align-items-center collapsed" type="button" data-toggle="collapse" data-target="#collapse4" aria-expanded="false" aria-controls="collapse4">
                                    <span>4. Làm thế nào để áp dụng mã giảm giá (Voucher)?</span>
                                    <i class="fas fa-chevron-down small text-muted"></i>
                                </button>
                            </h3>
                        </div>
                        <div id="collapse4" class="collapse" aria-labelledby="faq4" data-parent="#faqAccordion">
                            <div class="card-body text-muted small pt-0">
                                Tại bước <strong>Thanh toán</strong>, bạn chỉ cần nhập mã giảm giá vào ô <em>Mã voucher</em> rồi nhấn nút <strong>Áp dụng</strong>. Hệ thống sẽ tự động tính toán số tiền giảm ngay lập tức vào tổng thanh toán của bạn.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
