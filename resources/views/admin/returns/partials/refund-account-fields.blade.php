{{-- Expects $prefix (unique id prefix) and optional $workflow (ReturnRequestWorkflow|null). --}}
<div class="form-group">
    <label for="{{ $prefix }}-bank-name">Ngân hàng</label>
    <input id="{{ $prefix }}-bank-name" name="refund_bank_name" maxlength="100" class="form-control @error('refund_bank_name') is-invalid @enderror"
        value="{{ old('refund_bank_name', $workflow?->refund_bank_name) }}" placeholder="Ví dụ: Vietcombank" list="{{ $prefix }}-bank-options">
    <datalist id="{{ $prefix }}-bank-options">
        @foreach (['Vietcombank', 'VietinBank', 'BIDV', 'Agribank', 'Techcombank', 'MB Bank', 'ACB', 'VPBank', 'Sacombank', 'TPBank', 'VIB', 'HDBank', 'SHB', 'OCB', 'MSB'] as $bankName)
            <option value="{{ $bankName }}">
        @endforeach
    </datalist>
    @error('refund_bank_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="form-row">
    <div class="form-group col-sm-6">
        <label for="{{ $prefix }}-account-number">Số tài khoản</label>
        <input id="{{ $prefix }}-account-number" name="refund_account_number" maxlength="30" inputmode="numeric"
            class="form-control @error('refund_account_number') is-invalid @enderror"
            value="{{ old('refund_account_number', $workflow?->refund_account_number) }}" placeholder="Chỉ gồm chữ số">
        @error('refund_account_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="form-group col-sm-6">
        <label for="{{ $prefix }}-account-holder">Chủ tài khoản</label>
        <input id="{{ $prefix }}-account-holder" name="refund_account_holder" maxlength="100"
            class="form-control text-uppercase @error('refund_account_holder') is-invalid @enderror"
            value="{{ old('refund_account_holder', $workflow?->refund_account_holder) }}" placeholder="NGUYEN VAN A">
        @error('refund_account_holder')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>
