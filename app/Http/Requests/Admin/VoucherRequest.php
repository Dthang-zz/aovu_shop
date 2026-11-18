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

    public function rules(): array
    {
        /** @var Voucher|null $voucher */
        $voucher = $this->route('voucher');

        $uniqueCode = Rule::unique('vouchers', 'ma_voucher');

        if ($voucher instanceof Voucher) {
            $uniqueCode->ignore($voucher->getKey());
        }

        $isPercent = (int) $this->input('loai_giam')
            === Voucher::TYPE_PERCENT;

        $minimumQuantity = $voucher
            ? max(1, (int) $voucher->da_su_dung)
            : 1;

        return [
            'ma_voucher' => [
                'required',
                'string',
                'max:50',
                'alpha_dash:ascii',
                $uniqueCode,
            ],

            'mo_ta' => [
                'nullable',
                'string',
                'max:255',
            ],

            'loai_giam' => [
                'required',
                'integer',
                Rule::in([
                    Voucher::TYPE_PERCENT,
                    Voucher::TYPE_FIXED,
                ]),
            ],

            'gia_tri_giam' => [
                'required',
                'numeric',
                'min:1',
                ...($isPercent ? ['max:100'] : []),
            ],

            'giam_toi_da' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'don_toi_thieu' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'so_luong' => [
                'nullable',
                'integer',
                'min:' . $minimumQuantity,
            ],

            'ngay_bat_dau' => [
                'nullable',
                'date',
            ],

            'ngay_ket_thuc' => [
                'nullable',
                'date',
                'after_or_equal:ngay_bat_dau',
            ],

            'trang_thai' => [
                'required',
                'boolean',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'ma_voucher.required' => 'Vui lòng nhập mã giảm giá.',
            'ma_voucher.string' => 'Mã giảm giá phải là chuỗi ký tự.',
            'ma_voucher.max' => 'Mã giảm giá không được vượt quá 50 ký tự.',
            'ma_voucher.alpha_dash' => 'Mã chỉ được chứa chữ cái, số, dấu gạch ngang và gạch dưới.',
            'ma_voucher.unique' => 'Mã giảm giá này đã tồn tại.',

            'mo_ta.string' => 'Mô tả phải là chuỗi ký tự.',
            'mo_ta.max' => 'Mô tả không được vượt quá 255 ký tự.',

            'loai_giam.required' => 'Vui lòng chọn loại giảm giá.',
            'loai_giam.integer' => 'Loại giảm giá không hợp lệ.',
            'loai_giam.in' => 'Loại giảm giá không hợp lệ.',

            'gia_tri_giam.required' => 'Vui lòng nhập giá trị giảm.',
            'gia_tri_giam.numeric' => 'Giá trị giảm phải là số.',
            'gia_tri_giam.min' => 'Giá trị giảm phải lớn hơn 0.',
            'gia_tri_giam.max' => 'Mức giảm theo phần trăm không được vượt quá 100%.',

            'giam_toi_da.numeric' => 'Mức giảm tối đa phải là số.',
            'giam_toi_da.min' => 'Mức giảm tối đa không được nhỏ hơn 0.',

            'don_toi_thieu.numeric' => 'Giá trị đơn hàng tối thiểu phải là số.',
            'don_toi_thieu.min' => 'Giá trị đơn hàng tối thiểu không được nhỏ hơn 0.',

            'so_luong.integer' => 'Số lượng voucher phải là số nguyên.',
            'so_luong.min' => 'Số lượng voucher không được nhỏ hơn số lượt đã sử dụng.',

            'ngay_bat_dau.date' => 'Ngày bắt đầu không hợp lệ.',
            'ngay_ket_thuc.date' => 'Ngày kết thúc không hợp lệ.',
            'ngay_ket_thuc.after_or_equal' => 'Ngày kết thúc phải sau hoặc bằng ngày bắt đầu.',

            'trang_thai.required' => 'Vui lòng chọn trạng thái.',
            'trang_thai.boolean' => 'Trạng thái không hợp lệ.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'ma_voucher' => strtoupper(
                trim((string) $this->input('ma_voucher'))
            ),
        ]);
    }
}
