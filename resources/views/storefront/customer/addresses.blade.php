@extends('layouts.storefront')

@section('title', 'Sổ địa chỉ nhận hàng')

@section('content')
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h1 class="h3 font-weight-bold mb-1">Sổ địa chỉ nhận hàng</h1>
                <p class="text-muted mb-0">Quản lý các địa chỉ giao nhận của bạn để đặt hàng nhanh chóng hơn.</p>
            </div>
            <button type="button" class="btn btn-primary mt-2 mt-sm-0" data-toggle="modal" data-target="#addAddressModal">
                <i class="fas fa-plus mr-1"></i>Thêm địa chỉ mới
            </button>
        </div>
    </div>

    <div class="row">
        <!-- Sidebar tài khoản -->
        <div class="col-lg-4 mb-4">
            <div class="list-group storefront-card overflow-hidden">
                <a href="{{ route('customer.profile') }}" class="list-group-item list-group-item-action">
                    <i class="fas fa-id-card text-primary mr-2"></i>Hồ sơ cá nhân
                </a>
                <a href="{{ route('customer.addresses.index') }}" class="list-group-item list-group-item-action active font-weight-bold">
                    <i class="fas fa-map-marked-alt mr-2"></i>Sổ địa chỉ nhận hàng
                    <span class="badge badge-light border float-right">{{ $addresses->count() }}</span>
                </a>
                <a href="{{ route('customer.orders') }}" class="list-group-item list-group-item-action">
                    <i class="fas fa-box-open text-warning mr-2"></i>Đơn hàng của tôi
                </a>
                <a href="{{ route('shop.wishlist.index') }}" class="list-group-item list-group-item-action">
                    <i class="fas fa-heart text-danger mr-2"></i>Sản phẩm yêu thích
                </a>
            </div>
        </div>

        <!-- Danh sách địa chỉ -->
        <div class="col-lg-8">
            @if ($errors->any())
                <div class="alert alert-danger py-2 small mb-3">
                    <ul class="mb-0 pl-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($addresses->isEmpty())
                <div class="card storefront-card text-center py-5">
                    <div class="card-body">
                        <i class="fas fa-map-marker-alt fa-4x text-muted mb-3"></i>
                        <h5 class="font-weight-bold mb-2">Bạn chưa lưu địa chỉ nhận hàng nào</h5>
                        <p class="text-muted small mb-4">Hãy thêm địa chỉ giao hàng để chúng tôi có thể phục vụ và giao các kiện hàng nhanh chóng nhất!</p>
                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addAddressModal">
                            <i class="fas fa-plus mr-1"></i>Thêm địa chỉ đầu tiên
                        </button>
                    </div>
                </div>
            @else
                <div class="row">
                    @foreach ($addresses as $address)
                        <div class="col-12 mb-3">
                            <div class="card storefront-card {{ $address->mac_dinh ? 'border border-success' : '' }}">
                                <div class="card-body p-4">
                                    <div class="d-flex justify-content-between align-items-start flex-wrap mb-2">
                                        <div>
                                            <h5 class="font-weight-bold mb-1">
                                                {{ $address->ten_nguoi_nhan }}
                                                @if ($address->mac_dinh)
                                                    <span class="badge badge-success ml-2 font-weight-normal"><i class="fas fa-check mr-1"></i>Địa chỉ mặc định</span>
                                                @endif
                                            </h5>
                                            <span class="text-muted small"><i class="fas fa-phone-alt mr-1"></i>{{ $address->sdt_nguoi_nhan }}</span>
                                        </div>

                                        <div class="mt-2 mt-sm-0">
                                            @if (! $address->mac_dinh)
                                                <form action="{{ route('customer.addresses.set-default', $address) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-outline-success btn-sm mr-1" title="Đặt địa chỉ này làm mặc định">
                                                        Đặt làm mặc định
                                                    </button>
                                                </form>
                                            @endif
                                            <button type="button" class="btn btn-outline-primary btn-sm mr-1" data-toggle="modal" data-target="#editAddressModal{{ $address->id }}">
                                                <i class="fas fa-edit"></i> Sửa
                                            </button>
                                            <form action="{{ route('customer.addresses.destroy', $address) }}" method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa địa chỉ này?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger btn-sm">
                                                    <i class="fas fa-trash-alt"></i> Xóa
                                                </button>
                                            </form>
                                        </div>
                                    </div>

                                    <div class="text-secondary small mt-2">
                                        <i class="fas fa-map-marker-alt text-danger mr-1"></i>
                                        <strong>{{ $address->dia_chi_chi_tiet }}</strong>, {{ $address->phuong_xa }}, {{ $address->quan_huyen }}, {{ $address->tinh_thanh }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Modal Chỉnh sửa địa chỉ -->
                        <div class="modal fade" id="editAddressModal{{ $address->id }}" tabindex="-1" aria-labelledby="editAddressLabel{{ $address->id }}" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <form action="{{ route('customer.addresses.update', $address) }}" method="POST">
                                        @csrf
                                        @method('PUT')
                                        <div class="modal-header">
                                            <h5 class="modal-title font-weight-bold" id="editAddressLabel{{ $address->id }}">Chỉnh sửa địa chỉ nhận hàng</h5>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="form-row">
                                                <div class="form-group col-md-6">
                                                    <label class="small font-weight-bold">Tên người nhận <span class="text-danger">*</span></label>
                                                    <input type="text" name="ten_nguoi_nhan" class="form-control" value="{{ old('ten_nguoi_nhan', $address->ten_nguoi_nhan) }}" required maxlength="100">
                                                </div>
                                                <div class="form-group col-md-6">
                                                    <label class="small font-weight-bold">Số điện thoại <span class="text-danger">*</span></label>
                                                    <input type="text" name="sdt_nguoi_nhan" class="form-control" value="{{ old('sdt_nguoi_nhan', $address->sdt_nguoi_nhan) }}" required maxlength="15">
                                                </div>
                                            </div>
                                            <div class="form-row">
                                                <div class="form-group col-md-4">
                                                    <label class="small font-weight-bold">Tỉnh / Thành <span class="text-danger">*</span></label>
                                                    <input type="text" name="tinh_thanh" class="form-control" value="{{ old('tinh_thanh', $address->tinh_thanh) }}" required maxlength="100">
                                                </div>
                                                <div class="form-group col-md-4">
                                                    <label class="small font-weight-bold">Quận / Huyện <span class="text-danger">*</span></label>
                                                    <input type="text" name="quan_huyen" class="form-control" value="{{ old('quan_huyen', $address->quan_huyen) }}" required maxlength="100">
                                                </div>
                                                <div class="form-group col-md-4">
                                                    <label class="small font-weight-bold">Phường / Xã <span class="text-danger">*</span></label>
                                                    <input type="text" name="phuong_xa" class="form-control" value="{{ old('phuong_xa', $address->phuong_xa) }}" required maxlength="100">
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="small font-weight-bold">Địa chỉ chi tiết (số nhà, tên đường) <span class="text-danger">*</span></label>
                                                <input type="text" name="dia_chi_chi_tiet" class="form-control" value="{{ old('dia_chi_chi_tiet', $address->dia_chi_chi_tiet) }}" required maxlength="255">
                                            </div>
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input" id="mac_dinh_edit{{ $address->id }}" name="mac_dinh" value="1" @checked(old('mac_dinh', $address->mac_dinh))>
                                                <label class="custom-control-label small" for="mac_dinh_edit{{ $address->id }}">Đặt làm địa chỉ nhận hàng mặc định</label>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                                            <button type="submit" class="btn btn-primary font-weight-bold">Lưu thay đổi</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <!-- Modal Thêm địa chỉ mới -->
    <div class="modal fade" id="addAddressModal" tabindex="-1" aria-labelledby="addAddressModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="{{ route('customer.addresses.store') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title font-weight-bold" id="addAddressModalLabel">Thêm địa chỉ nhận hàng mới</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label class="small font-weight-bold">Tên người nhận <span class="text-danger">*</span></label>
                                <input type="text" name="ten_nguoi_nhan" class="form-control" value="{{ old('ten_nguoi_nhan', $currentCustomer?->ho_ten) }}" required maxlength="100" placeholder="Nguyễn Văn A">
                            </div>
                            <div class="form-group col-md-6">
                                <label class="small font-weight-bold">Số điện thoại <span class="text-danger">*</span></label>
                                <input type="text" name="sdt_nguoi_nhan" class="form-control" value="{{ old('sdt_nguoi_nhan', $currentCustomer?->so_dien_thoai) }}" required maxlength="15" placeholder="0901234567">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label class="small font-weight-bold">Tỉnh / Thành <span class="text-danger">*</span></label>
                                <input type="text" name="tinh_thanh" class="form-control" value="{{ old('tinh_thanh') }}" required maxlength="100" placeholder="Hà Nội">
                            </div>
                            <div class="form-group col-md-4">
                                <label class="small font-weight-bold">Quận / Huyện <span class="text-danger">*</span></label>
                                <input type="text" name="quan_huyen" class="form-control" value="{{ old('quan_huyen') }}" required maxlength="100" placeholder="Cầu Giấy">
                            </div>
                            <div class="form-group col-md-4">
                                <label class="small font-weight-bold">Phường / Xã <span class="text-danger">*</span></label>
                                <input type="text" name="phuong_xa" class="form-control" value="{{ old('phuong_xa') }}" required maxlength="100" placeholder="Dịch Vọng Hậu">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="small font-weight-bold">Địa chỉ chi tiết (số nhà, ngõ/ngách, tên đường) <span class="text-danger">*</span></label>
                            <input type="text" name="dia_chi_chi_tiet" class="form-control" value="{{ old('dia_chi_chi_tiet') }}" required maxlength="255" placeholder="Số 123 đường Cầu Giấy">
                        </div>
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="mac_dinh_add" name="mac_dinh" value="1" @checked(old('mac_dinh') || $addresses->isEmpty())>
                            <label class="custom-control-label small" for="mac_dinh_add">Đặt làm địa chỉ nhận hàng mặc định</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-primary font-weight-bold"><i class="fas fa-save mr-1"></i>Lưu địa chỉ</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
