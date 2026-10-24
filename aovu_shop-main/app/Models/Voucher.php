<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    public const TYPE_PERCENT = 1;
    public const TYPE_FIXED = 2;

    public const STATUS_ACTIVE = 1;
    public const STATUS_INACTIVE = 0;

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
            'loai_giam'     => 'integer',
            'gia_tri_giam'  => 'integer',
            'giam_toi_da'   => 'integer',
            'don_toi_thieu' => 'integer',
            'so_luong'      => 'integer',
            'da_su_dung'    => 'integer',
            'ngay_bat_dau'  => 'datetime',
            'ngay_ket_thuc' => 'datetime',
            'trang_thai'    => 'boolean',
        ];
    }

    // ───────────────────────────────────────────────
    //  Query Scopes
    // ───────────────────────────────────────────────

    /**
     * Tìm kiếm theo mã voucher hoặc mô tả.
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! $term || trim($term) === '') {
            return $query;
        }

        $term = trim($term);

        return $query->where(function (Builder $q) use ($term) {
            $q->where('ma_voucher', 'like', "%{$term}%")
              ->orWhere('mo_ta', 'like', "%{$term}%");
        });
    }

    /**
     * Lọc theo trạng thái: 'active', 'inactive', hoặc null (tất cả).
     */
    public function scopeFilterStatus(Builder $query, ?string $status): Builder
    {
        return match ($status) {
            'active'   => $query->where('trang_thai', true),
            'inactive' => $query->where('trang_thai', false),
            default    => $query,
        };
    }

    /**
     * Lọc theo loại giảm giá.
     */
    public function scopeFilterType(Builder $query, mixed $type): Builder
    {
        if ($type !== null && is_numeric($type)) {
            return $query->where('loai_giam', (int) $type);
        }

        return $query;
    }

    /**
     * Chỉ lấy voucher đang hoạt động & chưa hết hạn (dùng cho client).
     */
    public function scopeAvailable(Builder $query): Builder
    {
        $now = now();

        return $query
            ->where('trang_thai', true)
            ->where(fn (Builder $q) => $q->whereNull('ngay_bat_dau')->orWhere('ngay_bat_dau', '<=', $now))
            ->where(fn (Builder $q) => $q->whereNull('ngay_ket_thuc')->orWhere('ngay_ket_thuc', '>=', $now))
            ->where(function (Builder $q) {
                $q->whereNull('so_luong')
                  ->orWhereColumn('da_su_dung', '<', 'so_luong');
            });
    }

    // ───────────────────────────────────────────────
    //  Thống kê (single-query)
    // ───────────────────────────────────────────────

    /**
     * Lấy các thống kê bằng 1 query duy nhất thay vì 3 query riêng.
     *
     * @return array{total: int, active: int, total_used: int}
     */
    public static function stats(): array
    {
        $row = static::query()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN trang_thai = 1 THEN 1 ELSE 0 END) as active')
            ->selectRaw('COALESCE(SUM(da_su_dung), 0) as total_used')
            ->first();

        return [
            'total'      => (int) ($row->total ?? 0),
            'active'     => (int) ($row->active ?? 0),
            'total_used' => (int) ($row->total_used ?? 0),
        ];
    }

    // ───────────────────────────────────────────────
    //  Helper labels / display
    // ───────────────────────────────────────────────

    /**
     * @return array<int, string>
     */
    public static function discountTypes(): array
    {
        return [
            self::TYPE_PERCENT => 'Giảm theo %',
            self::TYPE_FIXED   => 'Giảm số tiền cố định',
        ];
    }

    public function discountTypeLabel(): string
    {
        return self::discountTypes()[$this->loai_giam] ?? 'Không xác định';
    }

    public function formattedDiscount(): string
    {
        if ($this->loai_giam === self::TYPE_PERCENT) {
            return number_format($this->gia_tri_giam) . '%';
        }

        return '₫ ' . number_format($this->gia_tri_giam);
    }

    public function isExpired(): bool
    {
        return $this->ngay_ket_thuc && $this->ngay_ket_thuc->isPast();
    }

    public function isUpcoming(): bool
    {
        return $this->ngay_bat_dau && $this->ngay_bat_dau->isFuture();
    }

    public function isRunning(): bool
    {
        if (!$this->trang_thai) {
            return false;
        }

        $now = now();

        return (!$this->ngay_bat_dau || $this->ngay_bat_dau->lte($now))
            && (!$this->ngay_ket_thuc || $this->ngay_ket_thuc->gte($now));
    }

    public function remainingQuantity(): ?int
    {
        if ($this->so_luong === null) {
            return null;
        }

        return max(0, $this->so_luong - ($this->da_su_dung ?? 0));
    }

    public function usagePercent(): ?float
    {
        if ($this->so_luong === null || $this->so_luong === 0) {
            return null;
        }

        return min(100, round(($this->da_su_dung ?? 0) / $this->so_luong * 100, 1));
    }

    public function statusLabel(): string
    {
        if (!$this->trang_thai) {
            return 'Tạm ngưng';
        }

        if ($this->isExpired()) {
            return 'Đã kết thúc';
        }

        if ($this->isUpcoming()) {
            return 'Sắp diễn ra';
        }

        if ($this->remainingQuantity() === 0) {
            return 'Đã hết lượt';
        }

        return 'Đang diễn ra';
    }

    public function statusBadge(): string
    {
        if (!$this->trang_thai) {
            return 'secondary';
        }

        if ($this->isExpired()) {
            return 'secondary';
        }

        if ($this->isUpcoming()) {
            return 'info';
        }

        if ($this->remainingQuantity() === 0) {
            return 'warning';
        }

        return 'success';
    }

    /**
     * Kiểm tra voucher đã được dùng trong đơn hàng chưa.
     */
    public function hasOrders(): bool
    {
        return \Illuminate\Support\Facades\DB::table('don_hangs')
            ->where('voucher_id', $this->getKey())
            ->exists();
    }
}
