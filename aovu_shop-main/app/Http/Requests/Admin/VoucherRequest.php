<?php

namespace App\Http\Requests\Admin;

use App\Models\Voucher;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VoucherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Voucher|null $voucher */
        $voucher = $this->route('voucher');

        $uniqueRule = Rule::unique('vouchers', 'ma_voucher');
        if ($voucher) {
            $uniqueRule->ignore($voucher->getKey());
        }

        $isPercent = (int) $this->input('loai_giam') === Voucher::TYPE_PERCENT;

        return [
            'ma_voucher'    => ['required', 'string', 'max:50', 'alpha_dash:ascii', $uniqueRule],
            'mo_ta'         => ['nullable', 'string', 'max:255'],
            'loai_giam'     => ['required', 'integer', Rule::in([Voucher::TYPE_PERCENT, Voucher::TYPE_FIXED])],
            'gia_tri_giam'  => array_filter([
                'required',
                'numeric',
                'min:1',
                $isPercent ? 'max:100' : null,
            ]),
            'giam_toi_da'   => ['nullable', 'numeric', 'min:0'],
            'don_toi_thieu' => ['nullable', 'numeric', 'min:0'],
            'so_luong'      => array_filter([
                'nullable',
                'integer',
                'min:1',
                // Khi sửa: số lượng mới không được nhỏ hơn số đã sử dụng
                $voucher ? 'min:' . ($voucher->da_su_dung ?? 0) : null,
            ]),
            'ngay_bat_dau'  => ['nullable', 'date'],
            'ngay_ket_thuc' => ['nullable', 'date', 'after_or_equal:ngay_bat_dau'],
            'trang_thai'    => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        /** @var Voucher|null $voucher */
        $voucher = $this->route('voucher');

        $messages = [
            'ma_voucher.required'         => 'Vui lòng nhập mã voucher.',
            'ma_voucher.max'              => 'Mã voucher không được vượt quá 50 ký tự.',
            'ma_voucher.alpha_dash'       => 'Mã voucher chỉ chứa chữ cái, số, dấu gạch ngang và gạch dưới.',
            'ma_voucher.unique'           => 'Mã voucher này đã tồn tại.',
            'loai_giam.required'          => 'Vui lòng chọn loại giảm giá.',
            'loai_giam.in'                => 'Loại giảm giá không hợp lệ.',
            'gia_tri_giam.required'       => 'Vui lòng nhập giá trị giảm.',
            'gia_tri_giam.min'            => 'Giá trị giảm phải lớn hơn 0.',
            'gia_tri_giam.max'            => 'Giá trị giảm theo phần trăm không được vượt quá 100%.',
            'giam_toi_da.min'             => 'Giảm tối đa không được âm.',
            'don_toi_thieu.min'           => 'Đơn tối thiểu không được âm.',
            'so_luong.min'                => $voucher
                ? 'Số lượng không được nhỏ hơn số đã sử dụng (' . ($voucher->da_su_dung ?? 0) . ').'
                : 'Số lượng phải lớn hơn 0.',
            'ngay_ket_thuc.after_or_equal' => 'Ngày kết thúc phải sau hoặc bằng ngày bắt đầu.',
            'trang_thai.required'         => 'Vui lòng chọn trạng thái.',
            'trang_thai.boolean'          => 'Trạng thái không hợp lệ.',
        ];

        return $messages;
    }

    /**
     * Chuẩn hoá dữ liệu trước khi validate.
     */
    protected function prepareForValidation(): void
    {
        if ($this->filled('ma_voucher')) {
            $this->merge([
                'ma_voucher' => strtoupper(trim($this->input('ma_voucher'))),
            ]);
        }
    }
}
