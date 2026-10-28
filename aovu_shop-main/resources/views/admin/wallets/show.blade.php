@extends('layouts.admin')

@section('title', 'Chi tiết ví hoàn tiền')
@section('page-heading', 'Chi tiết ví hoàn tiền')
@section('page-subheading', 'Xem thông tin ví, lịch sử giao dịch và thực hiện điều chỉnh.')
@section('breadcrumb')
    <a href="{{ route('admin.wallets.index') }}">Ví hoàn tiền</a>
@endsection

@section('styles')
<style>
    .wallet-info-card {
        border-left: 4px solid #5b6cff;
    }

    .wallet-balance-display {
        font-size: 2rem;
        font-weight: 700;
        color: #23945d;
        line-height: 1.2;
    }

    .wallet-balance-display.zero {
        color: #8994a4;
    }

    .tx-stat {
        text-align: center;
        padding: .75rem .5rem;
        border-radius: 8px;
        background: #f8f9fb;
    }

    .tx-stat .tx-stat-value {
        font-size: 1.1rem;
        font-weight: 700;
    }

    .tx-stat .tx-stat-label {
        font-size: .72rem;
        color: #8994a4;
        text-transform: uppercase;
        letter-spacing: .04em;
    }

    .tx-type-icon {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: .85rem;
    }

    .tx-type-icon.credit {
        background: #e7f7ef;
        color: #23945d;
    }

    .tx-type-icon.debit {
        background: #fdebed;
        color: #c44450;
    }

    .tx-type-icon.adjust {
        background: #e6f4fb;
        color: #287da2;
    }

    .tx-amount {
        font-weight: 600;
        font-size: .95rem;
    }

    .tx-amount.credit {
        color: #23945d;
    }

    .tx-amount.debit {
        color: #c44450;
    }

    .adjust-form-card {
        border-top: 3px solid #5b6cff;
    }

    .wallet-avatar-lg {
        width: 56px;
        height: 56px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid #eef0f3;
    }

    .wallet-avatar-lg-placeholder {
        width: 56px;
        height: 56px;
        border-radius: 50%;
        background: linear-gradient(135deg, #5b6cff 0%, #7c4dff 100%);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 1.3rem;
    }
</style>
@endsection

@section('content')
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

<div class="row">
    {{-- Left: wallet info + transactions --}}
    <div class="col-lg-8">
        {{-- Wallet Info --}}
        <div class="card wallet-info-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title"><i class="fas fa-wallet text-primary mr-2"></i>Ví #{{ $wallet->id }}</h3>
                @if ($wallet->trang_thai == 1)
                    <span class="badge badge-success px-2 py-1">Hoạt động</span>
                @else
                    <span class="badge badge-secondary px-2 py-1">Đã khóa</span>
                @endif
            </div>
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-sm-6 mb-3 mb-sm-0">
                        <div class="d-flex align-items-center">
                            @if ($wallet->account_avatar)
                                <img src="{{ asset('storage/' . $wallet->account_avatar) }}" alt="" class="wallet-avatar-lg mr-3">
                            @else
                                <div class="wallet-avatar-lg-placeholder mr-3">
                                    {{ mb_strtoupper(mb_substr($wallet->account_name ?? 'K', 0, 1)) }}
                                </div>
                            @endif
                            <div>
                                <div class="font-weight-bold" style="font-size: 1.1rem">{{ $wallet->account_name ?: 'Khách hàng' }}</div>
                                @if ($wallet->account_email)<div class="text-muted small">{{ $wallet->account_email }}</div>@endif
                                @if ($wallet->account_phone)<div class="text-muted small"><i class="fas fa-phone-alt mr-1" style="font-size:.7rem"></i>{{ $wallet->account_phone }}</div>@endif
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 text-sm-right">
                        <div class="text-muted small mb-1">Số dư hiện tại</div>
                        <div class="wallet-balance-display {{ $wallet->so_du > 0 ? '' : 'zero' }}">
                            {{ number_format($wallet->so_du, 0, ',', '.') }} <small style="font-size:.85rem">₫</small>
                        </div>
                        <div class="text-muted small mt-1">
                            Cập nhật: {{ $wallet->ngay_cap_nhat ? \Illuminate\Support\Carbon::parse($wallet->ngay_cap_nhat)->format('d/m/Y H:i') : '—' }}
                        </div>
                    </div>
                </div>

                {{-- Transaction stats --}}
                <div class="row mt-4">
                    <div class="col-4">
                        <div class="tx-stat">
                            <div class="tx-stat-value text-primary">{{ number_format($txStats->total ?? 0) }}</div>
                            <div class="tx-stat-label">Giao dịch</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="tx-stat">
                            <div class="tx-stat-value text-success">{{ number_format($txStats->total_refunded ?? 0, 0, ',', '.') }} ₫</div>
                            <div class="tx-stat-label">Tổng hoàn</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="tx-stat">
                            <div class="tx-stat-value text-danger">{{ number_format($txStats->total_used ?? 0, 0, ',', '.') }} ₫</div>
                            <div class="tx-stat-label">Đã sử dụng</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-white">
                <a href="{{ route('admin.wallets.index') }}" class="btn btn-default"><i class="fas fa-arrow-left mr-1"></i>Quay lại</a>
                <a href="{{ route('admin.wallets.export', $wallet->id) }}" class="btn btn-default float-right" title="Xuất lịch sử giao dịch"><i class="fas fa-file-csv mr-1"></i>Xuất CSV</a>
            </div>
        </div>

        {{-- Transaction History --}}
        <div class="card">
            <div class="card-header py-3">
                <div class="row align-items-center">
                    <div class="col-md-5">
                        <h3 class="card-title"><i class="fas fa-history text-primary mr-2"></i>Lịch sử giao dịch</h3>
                    </div>
                    <div class="col-md-7 mt-2 mt-md-0">
                        <form method="GET" action="{{ route('admin.wallets.show', $wallet->id) }}" class="d-flex justify-content-md-end">
                            <select name="type" class="form-control form-control-sm mr-2" style="max-width: 200px" aria-label="Lọc loại giao dịch" onchange="this.form.requestSubmit()">
                                <option value="">Tất cả loại giao dịch</option>
                                <option value="1" @selected(request('type')==='1')>Hoàn tiền vào ví</option>
                                <option value="2" @selected(request('type')==='2')>Sử dụng từ ví</option>
                                <option value="3" @selected(request('type')==='3')>Điều chỉnh thủ công</option>
                            </select>
                            <a href="{{ route('admin.wallets.show', $wallet->id) }}" class="btn btn-default btn-sm" title="Xóa bộ lọc"><i class="fas fa-undo"></i></a>
                        </form>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th class="pl-3">Mã GD</th>
                                <th>Loại</th>
                                <th class="text-right">Số tiền</th>
                                <th class="text-right">Số dư sau</th>
                                <th>Liên quan</th>
                                <th>Ghi chú</th>
                                <th>Ngày tạo</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($transactions as $tx)
                            @php
                                $isCredit = $tx->loai_giao_dich == 1 || ($tx->loai_giao_dich == 3 && $tx->so_tien > 0);
                                $typeClass = $tx->loai_giao_dich == 1 ? 'credit' : ($tx->loai_giao_dich == 2 ? 'debit' : 'adjust');
                                $txTypes = \App\Models\Wallet::transactionTypes();
                                $txIcons = \App\Models\Wallet::transactionIcons();
                            @endphp
                            <tr>
                                <td class="pl-3">
                                    <span class="font-weight-bold">#GD-{{ str_pad((string) $tx->id, 6, '0', STR_PAD_LEFT) }}</span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="tx-type-icon {{ $typeClass }} mr-2">
                                            <i class="{{ $txIcons[$tx->loai_giao_dich] ?? 'fas fa-question' }}"></i>
                                        </div>
                                        <span class="small">{{ $txTypes[$tx->loai_giao_dich] ?? 'Không xác định' }}</span>
                                    </div>
                                </td>
                                <td class="text-right">
                                    <span class="tx-amount {{ $isCredit ? 'credit' : 'debit' }}">
                                        {{ $isCredit ? '+' : '' }}{{ number_format($tx->so_tien, 0, ',', '.') }} ₫
                                    </span>
                                </td>
                                <td class="text-right">
                                    <span class="text-muted">{{ number_format($tx->so_du_sau, 0, ',', '.') }} ₫</span>
                                </td>
                                <td>
                                    @if ($tx->order_number)
                                        <span class="badge badge-light border"><i class="fas fa-shopping-bag mr-1"></i>{{ $tx->order_number }}</span>
                                    @elseif ($tx->yeu_cau_doi_tra_id)
                                        <span class="badge badge-light border"><i class="fas fa-exchange-alt mr-1"></i>#DT-{{ str_pad((string) $tx->yeu_cau_doi_tra_id, 6, '0', STR_PAD_LEFT) }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="text-muted small">{{ $tx->ghi_chu ? \Illuminate\Support\Str::limit($tx->ghi_chu, 50) : '—' }}</span>
                                    @if ($tx->admin_name)
                                        <div class="text-muted small"><i class="fas fa-user-shield mr-1" style="font-size:.65rem"></i>{{ $tx->admin_name }}</div>
                                    @endif
                                </td>
                                <td>
                                    <span class="text-muted small">{{ $tx->ngay_tao ? \Illuminate\Support\Carbon::parse($tx->ngay_tao)->format('d/m/Y H:i') : '—' }}</span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <i class="fas fa-receipt text-muted mb-2" style="font-size: 2rem"></i>
                                    <div class="font-weight-bold">Chưa có giao dịch nào</div>
                                    <div class="text-muted small">Các giao dịch sẽ xuất hiện khi ví được nạp hoặc sử dụng.</div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if ($transactions->hasPages())
            <div class="card-footer bg-white d-flex justify-content-between align-items-center">
                <span class="text-muted small">Hiển thị {{ $transactions->firstItem() }}–{{ $transactions->lastItem() }} trên {{ $transactions->total() }} giao dịch</span>
                {{ $transactions->links() }}
            </div>
            @endif
        </div>
    </div>

    {{-- Right: Adjust form --}}
    <div class="col-lg-4">
        <div class="card adjust-form-card">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-plus-circle text-primary mr-2"></i>Thực hiện giao dịch</h3></div>
            @if ($errors->any())
                <div class="card-body pb-0">
                    <div class="alert alert-danger" role="alert">
                        <ul class="mb-0 pl-3">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            @if ($wallet->trang_thai == 1)
            <form method="POST" action="{{ route('admin.wallets.adjust', $wallet->id) }}">
                @csrf
                <div class="card-body">
                    <div class="form-group">
                        <label for="loai_giao_dich">Loại giao dịch <span class="text-danger">*</span></label>
                        <select name="loai_giao_dich" id="loai_giao_dich" class="form-control @error('loai_giao_dich') is-invalid @enderror" required>
                            <option value="1" @selected(old('loai_giao_dich') == '1')>Hoàn tiền vào ví</option>
                            <option value="2" @selected(old('loai_giao_dich') == '2')>Sử dụng từ ví (trừ tiền)</option>
                            <option value="3" @selected(old('loai_giao_dich') == '3')>Điều chỉnh thủ công (cộng)</option>
                        </select>
                        @error('loai_giao_dich')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label for="so_tien">Số tiền (₫) <span class="text-danger">*</span></label>
                        <input type="number" name="so_tien" id="so_tien" class="form-control @error('so_tien') is-invalid @enderror" value="{{ old('so_tien') }}" min="1000" max="999999999" step="1000" placeholder="VD: 100000" required>
                        @error('so_tien')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <small class="form-text text-muted">Tối thiểu 1.000 ₫, tối đa 999.999.999 ₫.</small>
                    </div>
                    <div class="form-group mb-0">
                        <label for="ghi_chu">Ghi chú</label>
                        <textarea name="ghi_chu" id="ghi_chu" rows="3" maxlength="500" class="form-control @error('ghi_chu') is-invalid @enderror" placeholder="Lý do giao dịch, mã đơn hàng...">{{ old('ghi_chu') }}</textarea>
                        @error('ghi_chu')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="card-footer bg-white">
                    <button type="submit" class="btn btn-primary btn-block" onclick="return confirm('Xác nhận thực hiện giao dịch này?')">
                        <i class="fas fa-check mr-1"></i>Xác nhận giao dịch
                    </button>
                </div>
            </form>
            @else
                <div class="card-body">
                    <div class="alert alert-light border mb-0">
                        <i class="fas fa-lock text-warning mr-1"></i>Ví đang bị khóa, không thể thực hiện giao dịch. Vui lòng mở khóa ví trước.
                    </div>
                </div>
            @endif
        </div>

        {{-- Quick actions --}}
        <div class="card">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-bolt text-warning mr-2"></i>Thao tác nhanh</h3></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.wallets.toggle', $wallet->id) }}" class="mb-2">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-{{ $wallet->trang_thai == 1 ? 'warning' : 'success' }} btn-block" onclick="return confirm('Bạn chắc muốn {{ $wallet->trang_thai == 1 ? 'khóa' : 'mở khóa' }} ví này?')">
                        <i class="fas {{ $wallet->trang_thai == 1 ? 'fa-lock' : 'fa-unlock' }} mr-1"></i>
                        {{ $wallet->trang_thai == 1 ? 'Khóa ví' : 'Mở khóa ví' }}
                    </button>
                </form>
                <a href="{{ route('admin.wallets.export', $wallet->id) }}" class="btn btn-outline-secondary btn-block">
                    <i class="fas fa-file-csv mr-1"></i>Xuất lịch sử CSV
                </a>
            </div>
        </div>

        <div class="callout callout-info">
            <h5><i class="fas fa-info-circle mr-1"></i>Lưu ý</h5>
            <p class="mb-0 small">Mọi giao dịch điều chỉnh ví đều được ghi nhận lịch sử. Hãy nhập ghi chú rõ ràng để tiện tra cứu sau này.</p>
        </div>
    </div>
</div>
@endsection
