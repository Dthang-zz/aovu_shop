<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Discount code customers enter at checkout (legacy `vouchers` table, managed from the admin "Khuyến mãi" page).
 */
class Voucher extends Model
{
    public const TYPE_PERCENT = 1;

    public const TYPE_AMOUNT = 2;

    protected $table = 'vouchers';

    public $timestamps = false;

    protected $fillable = [
        'ma_voucher',
        'mo_ta',
        'loai_giam',
        'gia_tri_giam',
        'giam_toi_da',
        'don_toi_thieu',
        'so_luong',
        'da_su_dung',
        'ngay_bat_dau',
        'ngay_ket_thuc',
        'trang_thai',
    ];

    protected function casts(): array
    {
        return [
            'loai_giam' => 'integer',
            'gia_tri_giam' => 'float',
            'giam_toi_da' => 'integer',
            'don_toi_thieu' => 'integer',
            'so_luong' => 'integer',
            'da_su_dung' => 'integer',
            'ngay_bat_dau' => 'datetime',
            'ngay_ket_thuc' => 'datetime',
            'trang_thai' => 'boolean',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function types(): array
    {
        return [
            self::TYPE_PERCENT => 'Giảm theo %',
            self::TYPE_AMOUNT => 'Giảm theo số tiền',
        ];
    }

    /**
     * Short label of the discount, e.g. "10%" or "50.000 ₫".
     */
    public function discountLabel(): string
    {
        if ($this->loai_giam === self::TYPE_PERCENT) {
            return rtrim(rtrim(number_format((float) $this->gia_tri_giam, 2, ',', '.'), '0'), ',').'%';
        }

        return number_format((float) $this->gia_tri_giam, 0, ',', '.').' ₫';
    }

    /**
     * Conditions of the code, e.g. "tối đa 50.000 ₫ · đơn từ 200.000 ₫".
     */
    public function conditionsLabel(): ?string
    {
        $conditions = array_filter([
            $this->loai_giam === self::TYPE_PERCENT && $this->giam_toi_da
                ? 'tối đa '.number_format($this->giam_toi_da, 0, ',', '.').' ₫'
                : null,
            $this->don_toi_thieu
                ? 'đơn từ '.number_format($this->don_toi_thieu, 0, ',', '.').' ₫'
                : null,
        ]);

        return $conditions === [] ? null : implode(' · ', $conditions);
    }

    /**
     * @return 'disabled'|'upcoming'|'expired'|'used_up'|'running'
     */
    public function state(): string
    {
        return match (true) {
            ! $this->trang_thai => 'disabled',
            $this->ngay_bat_dau !== null && $this->ngay_bat_dau->isFuture() => 'upcoming',
            $this->ngay_ket_thuc !== null && $this->ngay_ket_thuc->isPast() => 'expired',
            $this->so_luong !== null && $this->da_su_dung >= $this->so_luong => 'used_up',
            default => 'running',
        };
    }

    /**
     * @return array{0: string, 1: string} Label and badge colour of {@see state()}.
     */
    public function stateBadge(): array
    {
        return match ($this->state()) {
            'disabled' => ['Đã tắt', 'secondary'],
            'upcoming' => ['Sắp diễn ra', 'info'],
            'expired' => ['Đã kết thúc', 'secondary'],
            'used_up' => ['Hết lượt', 'warning'],
            default => ['Đang chạy', 'success'],
        };
    }
}
